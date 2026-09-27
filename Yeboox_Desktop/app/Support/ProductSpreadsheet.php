<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Lit et génère le format Excel contractuel du catalogue produits. */
final class ProductSpreadsheet
{
    public const HEADERS = [
        'Référence SKU', 'Code-barres / QR', 'Nom du produit', 'Type', 'Catégorie', 'Unité',
        'Prix de vente', 'Coût unitaire', 'TVA (%)', 'Stock initial',
        'Seuil d’alerte', 'Actif', 'Notes internes',
    ];

    private const HEADER_KEYS = [
        'reference sku' => 'sku', 'reference' => 'sku', 'sku' => 'sku',
        'code barres qr' => 'barcode', 'code barres' => 'barcode', 'barcode' => 'barcode', 'qr' => 'barcode',
        'nom du produit' => 'name', 'nom produit' => 'name', 'nom' => 'name',
        'type' => 'type', 'type de produit' => 'type',
        'categorie' => 'category', 'unite' => 'unit', 'unite de mesure' => 'unit',
        'prix de vente' => 'price', 'prix' => 'price',
        'cout unitaire' => 'cost', 'cout' => 'cost',
        'tva %' => 'tax', 'tva' => 'tax', 'taxe de vente' => 'tax',
        'stock initial' => 'stock', 'stock' => 'stock',
        'seuil d alerte' => 'alert', 'seuil alerte' => 'alert',
        'actif' => 'active', 'active' => 'active',
        'notes internes' => 'notes', 'notes' => 'notes',
    ];

    public function read(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $worksheetNames = $reader->listWorksheetNames($path);
        if ($worksheetNames !== []) {
            $reader->setLoadSheetsOnly($worksheetNames[0]);
        }
        // Empêche un classeur surdimensionné de saturer la mémoire du PHP embarqué.
        $reader->setReadFilter(new class implements IReadFilter
        {
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                static $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];

                return $row <= 2002 && in_array($columnAddress, $columns, true);
            }
        });
        $spreadsheet = $reader->load($path);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        $spreadsheet->disconnectWorksheets();

        if ($rows === []) {
            throw new InvalidArgumentException('Le fichier Excel est vide.');
        }

        $headerMap = [];
        foreach (array_shift($rows) as $column => $label) {
            $normalized = $this->normalizeHeader((string) $label);
            if (isset(self::HEADER_KEYS[$normalized])) {
                $headerMap[$column] = self::HEADER_KEYS[$normalized];
            }
        }

        $missing = array_diff(['sku', 'name', 'type', 'category', 'price', 'cost'], $headerMap);
        if ($missing !== []) {
            throw new InvalidArgumentException('Colonnes obligatoires absentes. Téléchargez et utilisez le modèle Yeboox.');
        }

        if (count($rows) > 2000) {
            throw new InvalidArgumentException('Le fichier dépasse la limite de 2 000 lignes par import.');
        }

        return collect($rows)->map(function (array $cells, int $index) use ($headerMap): array {
            $row = ['line' => $index + 2];
            foreach ($headerMap as $column => $key) {
                $row[$key] = is_string($cells[$column] ?? null) ? trim($cells[$column]) : ($cells[$column] ?? null);
            }

            $row['sku'] = strtoupper(trim((string) ($row['sku'] ?? '')));
            $row['barcode'] = strtoupper(trim((string) ($row['barcode'] ?? '')));
            $row['name'] = trim((string) ($row['name'] ?? ''));
            $row['category'] = trim((string) ($row['category'] ?? ''));
            $row['unit'] = trim((string) ($row['unit'] ?? ''));
            // Les cellules facultatives reçoivent les mêmes valeurs que le formulaire manuel.
            $row['type'] = $this->normalizeType((string) ($row['type'] ?? ''));
            $row['price'] = $this->number($row['price'] ?? null) ?? 0.0;
            $row['cost'] = $this->number($row['cost'] ?? null) ?? 0.0;
            $row['tax'] = $this->number($row['tax'] ?? null) ?? 0.0;
            $row['stock'] = $this->number($row['stock'] ?? null) ?? 0.0;
            $row['alert'] = $this->number($row['alert'] ?? null) ?? 5.0;
            $row['active'] = $this->boolean($row['active'] ?? true);
            $row['notes'] = trim((string) ($row['notes'] ?? ''));

            return $row;
        })->reject(fn (array $row) => $row['sku'] === '' && $row['name'] === '')->values()->all();
    }

    public function template(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Produits');
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $sheet->fromArray([
            ['', '', 'Huile de massage', '', 'Soins', 'fl', '', '', '', '', '', '', ''],
            ['SRV-SPA-001', '2609220000015', 'Massage relaxant', 'Service', 'Spa', '', 35, 10, 16, 0, 0, 'Oui', 'Durée : 60 minutes'],
        ], null, 'A2');
        $this->styleProductsSheet($sheet, 3);

        $instructions = $spreadsheet->createSheet()->setTitle('Instructions');
        $instructions->fromArray([
            ['MODE D’EMPLOI — IMPORT PRODUITS YEBOOX'],
            ['1.', 'Ne renommez pas les colonnes de la feuille Produits.'],
            ['2.', 'SKU : laissez vide pour générer une référence unique. S’il est renseigné, il permet aussi de mettre à jour un article existant.'],
            ['3.', 'Code-barres / QR : facultatif, unique, 4 à 80 caractères. Il peut être scanné directement au POS.'],
            ['4.', 'Type : Bien par défaut. Vous pouvez aussi saisir Service. Les combos se construisent manuellement dans Yeboox.'],
            ['5.', 'Unité : symbole ou nom existant (u, kg, L…). Obligatoire pour un bien.'],
            ['6.', 'Prix, coût, TVA et stock : 0 par défaut. Seuil d’alerte : 5 par défaut.'],
            ['7.', 'Stock initial : utilisé uniquement lors de la création d’un nouvel article.'],
            ['8.', 'Actif : Oui par défaut. Valeurs acceptées : Oui/Non, Vrai/Faux ou 1/0.'],
            ['9.', 'Maximum : 2 000 lignes par fichier.'],
        ], null, 'A1');
        $instructions->getStyle('A1:B1')->getFont()->setBold(true)->setSize(15)->getColor()->setARGB('FFFFFFFF');
        $instructions->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4F46E5');
        $instructions->getColumnDimension('A')->setWidth(7);
        $instructions->getColumnDimension('B')->setWidth(95);
        $instructions->getStyle('A1:B10')->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        $instructions->freezePane('A2');
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    public function export(Collection $products): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Produits');
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $rows = $products->map(fn (Product $product) => [
            $product->sku,
            $product->barcode,
            $product->name,
            $product->type === 'product' ? 'Bien' : ($product->type === 'service' ? 'Service' : 'Combo'),
            $product->category,
            $product->unitOfMeasure?->symbol,
            (float) $product->price,
            (float) $product->cost,
            (float) $product->sales_tax_rate,
            (float) $product->stock_qty,
            (float) $product->alert_qty,
            $product->is_active ? 'Oui' : 'Non',
            $product->internal_notes,
        ])->all();
        if ($rows !== []) {
            $sheet->fromArray($rows, null, 'A2');
        }
        $this->styleProductsSheet($sheet, max(1, count($rows) + 1));
        $sheet->setAutoFilter('A1:M'.max(1, count($rows) + 1));

        return $spreadsheet;
    }

    public function save(Spreadsheet $spreadsheet, string $path): void
    {
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);
        $spreadsheet->disconnectWorksheets();
    }

    private function styleProductsSheet(Worksheet $sheet, int $lastRow): void
    {
        $sheet->freezePane('A2');
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getStyle('A1:M1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:M1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4F46E5');
        $sheet->getStyle('A1:M1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        foreach (['A' => 22, 'B' => 24, 'C' => 34, 'D' => 14, 'E' => 22, 'F' => 14, 'G' => 17, 'H' => 17, 'I' => 12, 'J' => 16, 'K' => 17, 'L' => 12, 'M' => 40] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->getStyle("A1:M{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        if ($lastRow >= 2) {
            $sheet->getStyle("G2:K{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        }
    }

    private function normalizeHeader(string $value): string
    {
        return (string) Str::of(Str::ascii($value))->lower()->replace(['_', '-'], ' ')->replaceMatches('/[^a-z0-9%]+/', ' ')->squish();
    }

    private function normalizeType(string $value): string
    {
        return match ((string) Str::of(Str::ascii($value))->lower()->trim()) {
            'bien', 'biens', 'produit', 'product' => 'product',
            'service', 'services' => 'service',
            'combo', 'pack' => 'combo',
            // Une cellule vide correspond à un bien ; une valeur inconnue reste invalide.
            default => trim($value) === '' ? 'product' : '',
        };
    }

    private function number(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        $normalized = str_replace(["\u{00A0}", ' '], '', trim((string) $value));
        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = strrpos($normalized, ',') > strrpos($normalized, '.')
                ? str_replace(',', '.', str_replace('.', '', $normalized))
                : str_replace(',', '', $normalized);
        } else {
            $normalized = str_replace(',', '.', $normalized);
        }

        return $normalized !== '' && is_numeric($normalized) ? (float) $normalized : null;
    }

    private function boolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return match ((string) Str::of(Str::ascii((string) $value))->lower()->trim()) {
            '', '1', 'oui', 'yes', 'vrai', 'true', 'actif', 'active' => true,
            '0', 'non', 'no', 'faux', 'false', 'inactif', 'inactive' => false,
            default => null,
        };
    }
}
