<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use ZipArchive;

/** Builds a real offline DOCX report using the active A4 document model. */
final class ReportWordGenerator
{
    public function download(string $reportType, array $data, string $startDate, string $endDate, string $generatedBy = 'Système')
    {
        $template = DocumentPreferences::report()['template'];
        $filename = sprintf('Rapport-%s-%s-au-%s.docx', $reportType, $startDate, $endDate);
        $contents = $this->generate($reportType, $data, $startDate, $endDate, $generatedBy);

        return response()->streamDownload(
            static function () use ($contents): void {
                echo $contents;
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'X-Content-Type-Options' => 'nosniff',
                'X-Yeboox-Document-Template' => $template,
            ],
        );
    }

    /** Return the complete DOCX binary; kept public for deterministic tests and Electron exports. */
    public function generate(string $reportType, array $data, string $startDate, string $endDate, string $generatedBy = 'Système'): string
    {
        $context = DocumentPreferences::reportDocumentContext($generatedBy);
        $preferences = $context['preferences'];
        $theme = $context['documentTheme'];
        $company = $context['company'];
        $title = $this->reportTitle($reportType);
        $sections = $this->sections($reportType, $data);
        $storage = storage_path('app/word');
        File::ensureDirectoryExists($storage);
        $path = tempnam($storage, 'yeboox-docx-');

        if ($path === false) {
            throw new \RuntimeException('Impossible de préparer le document Word.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Impossible de construire le document Word.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->packageRelationshipsXml());
        $zip->addFromString('docProps/core.xml', $this->corePropertiesXml($title, $company['name']));
        $zip->addFromString('docProps/app.xml', $this->appPropertiesXml());
        $zip->addFromString('word/document.xml', $this->documentXml($title, $sections, $startDate, $endDate, $context));
        $zip->addFromString('word/styles.xml', $this->stylesXml($preferences, $theme));
        $zip->addFromString('word/settings.xml', $this->settingsXml());
        $zip->addFromString('word/_rels/document.xml.rels', $this->documentRelationshipsXml());
        $zip->addFromString('word/header1.xml', $this->headerXml($title, $context));
        $zip->addFromString('word/footer1.xml', $this->footerXml($context));
        $zip->addFromString('word/_rels/header1.xml.rels', $this->headerRelationshipsXml());

        $logoPath = $this->logoPath();
        if ($logoPath !== null) {
            $zip->addFile($logoPath, 'word/media/logo.png');
        } else {
            $zip->addFromString('word/media/logo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScLkWQAAAABJRU5ErkJggg=='));
        }

        $zip->close();
        $contents = (string) file_get_contents($path);
        unlink($path);

        return $contents;
    }

    private function documentXml(string $title, array $sections, string $startDate, string $endDate, array $context): string
    {
        $meta = $context['reportTemplateMeta'];
        $body = $this->paragraph('DOCUMENT DE GESTION', 'Kicker', 'center')
            .$this->paragraph($title, 'DocumentTitle', 'center')
            .$this->paragraph($context['company']['name'], 'DocumentSubtitle', 'center')
            .$this->table(
                ['Période', 'Modèle', 'Édité par'],
                [[date('d/m/Y', strtotime($startDate)).' - '.date('d/m/Y', strtotime($endDate)), $meta['label'].' - '.$meta['description'], $context['generatedBy']]],
                [5000, 5000, 5000],
                true,
            )
            .$this->paragraph('TABLE DES MATIÈRES', 'Heading1')
            .$this->tocTable($sections)
            .$this->paragraphBreak();

        foreach ($sections as $index => $section) {
            $body .= $this->paragraph(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).' · '.$section['title'], 'Heading1');
            if (! empty($section['description'])) {
                $body .= $this->paragraph($section['description'], 'Caption');
            }
            $body .= $this->table($section['headers'], $section['rows'], $section['widths']);
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<w:body>'.$body
            .'<w:sectPr><w:headerReference w:type="default" r:id="rIdHeader"/><w:footerReference w:type="default" r:id="rIdFooter"/>'
            .'<w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/><w:pgMar w:top="900" w:right="720" w:bottom="900" w:left="720" w:header="360" w:footer="360" w:gutter="0"/>'
            .'<w:cols w:space="720"/><w:docGrid w:linePitch="360"/></w:sectPr></w:body></w:document>';
    }

    private function headerXml(string $title, array $context): string
    {
        $company = $context['company'];
        $meta = $context['reportTemplateMeta'];
        $theme = $context['documentTheme'];
        $legal = 'RCCM '.($company['rccm'] ?: '—').' · NIF/Impôt '.($company['nif'] ?: '—').' · TVA '.($company['tva'] ?: '—');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:v="urn:schemas-microsoft-com:vml">'
            .'<w:tbl><w:tblPr><w:tblW w:w="15000" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblBorders><w:bottom w:val="single" w:sz="18" w:color="'.$this->color($theme['accent']).'"/></w:tblBorders></w:tblPr><w:tblGrid><w:gridCol w:w="1800"/><w:gridCol w:w="8500"/><w:gridCol w:w="4700"/></w:tblGrid><w:tr>'
            .$this->cell('<w:p><w:r><w:pict><v:shape type="#_x0000_t75" style="width:86pt;height:32pt" filled="f" stroked="f"><v:imagedata r:id="rIdLogo" o:title="Logo" xmlns:o="urn:schemas-microsoft-com:office:office"/></v:shape></w:pict></w:r></w:p>', 1800)
            .$this->cell($this->paragraph($company['name'], 'HeaderCompany').$this->paragraph(trim($company['address'].' · '.$company['phone'].' · '.$company['email'], ' ·'), 'HeaderMeta').$this->paragraph($legal, 'HeaderMeta'), 8500)
            .$this->cell($this->paragraph($title, 'HeaderTitle', 'right').$this->paragraph('Modèle '.$meta['label'], 'HeaderMeta', 'right'), 4700)
            .'</w:tr></w:tbl></w:hdr>';
    }

    private function footerXml(array $context): string
    {
        $company = $context['company'];
        $meta = $context['reportTemplateMeta'];
        $left = $this->xml($company['name'].' · Document confidentiel · '.$meta['label']);
        $right = $this->xml('Édité par '.$context['generatedBy'].' · Page ');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:tbl><w:tblPr><w:tblW w:w="15000" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblBorders><w:top w:val="single" w:sz="6" w:color="D4D4D8"/></w:tblBorders></w:tblPr><w:tblGrid><w:gridCol w:w="7500"/><w:gridCol w:w="7500"/></w:tblGrid><w:tr>'
            .$this->cell('<w:p><w:pPr><w:pStyle w:val="FooterText"/></w:pPr><w:r><w:t>'.$left.'</w:t></w:r></w:p>', 7500)
            .$this->cell('<w:p><w:pPr><w:pStyle w:val="FooterText"/><w:jc w:val="right"/></w:pPr><w:r><w:t xml:space="preserve">'.$right.'</w:t></w:r><w:fldSimple w:instr=" PAGE "><w:r><w:t>1</w:t></w:r></w:fldSimple><w:r><w:t xml:space="preserve"> / </w:t></w:r><w:fldSimple w:instr=" NUMPAGES "><w:r><w:t>1</w:t></w:r></w:fldSimple></w:p>', 7500)
            .'</w:tr></w:tbl></w:ftr>';
    }

    private function stylesXml(array $preferences, array $theme): string
    {
        $font = match ($preferences['font_family']) {
            'serif', 'georgia' => 'Georgia',
            'mono' => 'Consolas',
            default => 'Arial',
        };
        $size = $preferences['font_size'] * 2;
        $accent = $this->color($theme['accent']);
        $dark = $this->color($theme['dark']);
        $muted = $this->color($theme['muted']);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="'.$font.'" w:hAnsi="'.$font.'"/><w:sz w:val="'.$size.'"/><w:szCs w:val="'.$size.'"/><w:color w:val="18181B"/></w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:after="100" w:line="276" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>'
            .$this->style('Normal', 'Normal', $font, $size, '18181B', false, 0, 100)
            .$this->style('DocumentTitle', 'Document Title', $font, 42, $dark, true, 80, 120)
            .$this->style('DocumentSubtitle', 'Document Subtitle', $font, 22, $muted, false, 0, 220)
            .$this->style('Kicker', 'Kicker', $font, 16, $accent, true, 0, 70)
            .$this->style('Heading1', 'Heading 1', $font, 25, $dark, true, 220, 100, 1)
            .$this->style('Caption', 'Caption', $font, 17, '71717A', false, 0, 90)
            .$this->style('HeaderCompany', 'Header Company', $font, 22, $dark, true, 0, 20)
            .$this->style('HeaderTitle', 'Header Title', $font, 18, $dark, true, 0, 20)
            .$this->style('HeaderMeta', 'Header Meta', $font, 14, '71717A', false, 0, 10)
            .$this->style('FooterText', 'Footer Text', $font, 14, '71717A', false, 0, 0)
            .'</w:styles>';
    }

    private function style(string $id, string $name, string $font, int $size, string $color, bool $bold, int $before, int $after, ?int $outline = null): string
    {
        return '<w:style w:type="paragraph" w:styleId="'.$id.'"><w:name w:val="'.$name.'"/>'.($id === 'Normal' ? '<w:qFormat/><w:default w:val="1"/>' : '<w:basedOn w:val="Normal"/>').'<w:pPr><w:spacing w:before="'.$before.'" w:after="'.$after.'"/>'.($outline !== null ? '<w:outlineLvl w:val="'.$outline.'"/>' : '').'</w:pPr><w:rPr><w:rFonts w:ascii="'.$font.'" w:hAnsi="'.$font.'"/><w:sz w:val="'.$size.'"/><w:szCs w:val="'.$size.'"/><w:color w:val="'.$color.'"/>'.($bold ? '<w:b/>' : '').'</w:rPr></w:style>';
    }

    private function tocTable(array $sections): string
    {
        $rows = [];
        foreach ($sections as $index => $section) {
            $rows[] = [str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT), $section['title'], 'Section '.($index + 1)];
        }

        return $this->table(['N°', 'Contenu', 'Repère'], $rows, [1200, 10800, 3000]);
    }

    private function table(array $headers, array $rows, array $widths, bool $compact = false): string
    {
        $width = array_sum($widths);
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="'.$width.'" w:type="dxa"/><w:tblInd w:w="120" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblCellMar><w:top w:w="'.($compact ? 60 : 90).'" w:type="dxa"/><w:left w:w="120" w:type="dxa"/><w:bottom w:w="'.($compact ? 60 : 90).'" w:type="dxa"/><w:right w:w="120" w:type="dxa"/></w:tblCellMar><w:tblBorders><w:top w:val="single" w:sz="5" w:color="D4D4D8"/><w:left w:val="single" w:sz="5" w:color="D4D4D8"/><w:bottom w:val="single" w:sz="5" w:color="D4D4D8"/><w:right w:val="single" w:sz="5" w:color="D4D4D8"/><w:insideH w:val="single" w:sz="4" w:color="E4E4E7"/><w:insideV w:val="single" w:sz="4" w:color="E4E4E7"/></w:tblBorders></w:tblPr><w:tblGrid>';
        foreach ($widths as $columnWidth) {
            $xml .= '<w:gridCol w:w="'.$columnWidth.'"/>';
        }
        $xml .= '</w:tblGrid><w:tr><w:trPr><w:tblHeader/></w:trPr>';
        foreach ($headers as $index => $header) {
            $xml .= $this->cell($this->tableParagraph((string) $header, true, $index === 0 ? 'left' : 'center'), $widths[$index]);
        }
        $xml .= '</w:tr>';

        if ($rows === []) {
            $rows = [['Aucune donnée disponible.']];
        }
        foreach ($rows as $rowIndex => $row) {
            $xml .= '<w:tr>';
            foreach ($headers as $index => $_header) {
                $value = (string) ($row[$index] ?? '');
                $align = $index === 0 ? 'left' : (is_numeric(str_replace([' ', ',', '%'], '', $value)) ? 'right' : 'left');
                $xml .= $this->cell($this->tableParagraph($value, false, $align), $widths[$index], $rowIndex % 2 === 1 ? 'FAFAFA' : null);
            }
            $xml .= '</w:tr>';
        }

        return $xml.'</w:tbl><w:p><w:pPr><w:spacing w:after="80"/></w:pPr></w:p>';
    }

    private function tableParagraph(string $text, bool $header, string $align): string
    {
        $theme = DocumentPreferences::reportTheme();
        $run = '<w:r><w:rPr>'.($header ? '<w:b/><w:color w:val="FFFFFF"/>' : '').'</w:rPr><w:t xml:space="preserve">'.$this->xml($text).'</w:t></w:r>';

        return '<w:p><w:pPr><w:jc w:val="'.$align.'"/><w:spacing w:before="0" w:after="0"/></w:pPr>'.$run.'</w:p>'.($header ? '<w:tcPr><w:shd w:fill="'.$this->color($theme['dark']).'"/></w:tcPr>' : '');
    }

    private function cell(string $content, int $width, ?string $fill = null): string
    {
        // Move accidental cell properties emitted by tableParagraph into the real tcPr.
        $cellProperties = '<w:tcPr><w:tcW w:w="'.$width.'" w:type="dxa"/><w:vAlign w:val="center"/>'.($fill ? '<w:shd w:fill="'.$fill.'"/>' : '');
        if (str_contains($content, '<w:tcPr>')) {
            preg_match('/<w:tcPr>(.*?)<\/w:tcPr>/s', $content, $match);
            $cellProperties .= $match[1] ?? '';
            $content = preg_replace('/<w:tcPr>.*?<\/w:tcPr>/s', '', $content) ?? $content;
        }

        return '<w:tc>'.$cellProperties.'</w:tcPr>'.$content.'</w:tc>';
    }

    private function paragraph(string $text, string $style = 'Normal', ?string $align = null): string
    {
        return '<w:p><w:pPr><w:pStyle w:val="'.$style.'"/>'.($align ? '<w:jc w:val="'.$align.'"/>' : '').'</w:pPr><w:r><w:t xml:space="preserve">'.$this->xml($text).'</w:t></w:r></w:p>';
    }

    private function paragraphBreak(): string
    {
        return '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
    }

    private function sections(string $type, array $data): array
    {
        if ($type === 'sales') {
            $sales = $data['sales'] ?? collect();
            $total = (float) $sales->sum('total_amount');

            return [
                ['title' => 'Synthèse des encaissements', 'description' => 'Indicateurs consolidés sur la période sélectionnée.', 'headers' => ['Indicateur', 'Valeur'], 'rows' => [['Nombre de ventes', (string) $sales->count()], ['Chiffre d’affaires', DisplayCurrency::format($total)], ['Panier moyen', DisplayCurrency::format($sales->count() ? $total / $sales->count() : 0)]], 'widths' => [9000, 6000]],
                ['title' => 'Registre détaillé des ventes', 'description' => 'Liste chronologique des encaissements.', 'headers' => ['Date', 'Client', 'Secteur', 'Paiement', 'Montant'], 'rows' => $sales->map(fn ($sale) => [$sale->created_at->format('d/m/Y H:i'), $sale->customer?->name ?? 'Client passant', ucfirst((string) $sale->department), ucfirst((string) $sale->payment_method), DisplayCurrency::format($sale->total_amount)])->all(), 'widths' => [2500, 4000, 2500, 2800, 3200]],
            ];
        }

        if ($type === 'stock') {
            $movements = $data['movements'] ?? collect();
            $alerts = $data['lowStockProducts'] ?? collect();

            return [
                ['title' => 'Mouvements de stock', 'description' => 'Traçabilité des entrées, sorties et ajustements.', 'headers' => ['Date', 'Produit', 'Type', 'Quantité', 'Motif', 'Utilisateur'], 'rows' => $movements->map(fn ($movement) => [$movement->created_at->format('d/m/Y H:i'), $movement->product?->name ?? 'Article supprimé', $movement->type === 'in' ? 'Entrée' : 'Sortie', number_format($movement->quantity, 3, ',', ' '), $movement->description ?: '—', $movement->user?->name ?? 'Système'])->all(), 'widths' => [2200, 3300, 1800, 1800, 3500, 2400]],
                ['title' => 'Alertes de rupture', 'description' => 'Articles au seuil d’alerte ou en rupture.', 'headers' => ['SKU', 'Article', 'Catégorie', 'Stock', 'Seuil', 'État'], 'rows' => $alerts->map(fn ($product) => [$product->sku ?: '—', $product->name, $product->category ?: 'Sans catégorie', number_format($product->stock_qty, 3, ',', ' '), number_format($product->alert_qty, 3, ',', ' '), $product->stock_qty <= 0 ? 'Rupture' : 'Alerte'])->all(), 'widths' => [2200, 3800, 3000, 2000, 2000, 2000]],
            ];
        }

        $sessions = $data['sessions'] ?? collect();

        return [
            ['title' => 'Synthèse des sessions', 'description' => 'Vue consolidée des ouvertures et clôtures de caisse.', 'headers' => ['Indicateur', 'Valeur'], 'rows' => [['Sessions', (string) $sessions->count()], ['Ouvertes', (string) $sessions->where('status', 'open')->count()], ['Clôturées', (string) $sessions->where('status', 'closed')->count()], ['Encaissements', DisplayCurrency::format($sessions->sum(fn ($session) => $session->sales->sum('total_amount')))]], 'widths' => [9000, 6000]],
            ['title' => 'Registre des ouvertures et clôtures', 'description' => 'Contrôle des sessions par agent.', 'headers' => ['Ouverture', 'Fermeture', 'Caissier', 'Statut', 'Ventes', 'Montant'], 'rows' => $sessions->map(fn ($session) => [$session->opened_at?->format('d/m/Y H:i') ?? '—', $session->closed_at?->format('d/m/Y H:i') ?? 'En cours', $session->user?->name ?? 'Système', ucfirst((string) $session->status), (string) $session->sales->count(), DisplayCurrency::format($session->sales->sum('total_amount'))])->all(), 'widths' => [2600, 2600, 3000, 2200, 1800, 2800]],
        ];
    }

    private function reportTitle(string $type): string
    {
        return ['sales' => 'Rapport des ventes', 'stock' => 'Rapport des mouvements de stock', 'sessions' => 'Rapport des sessions de caisse'][$type] ?? 'Rapport de gestion';
    }

    private function logoPath(): ?string
    {
        $path = public_path('images/yeboox-logo.png');

        return is_file($path) ? $path : null;
    }

    private function color(string $hex): string
    {
        return strtoupper(ltrim($hex, '#'));
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/><Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/><Override PartName="/word/header1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"/><Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';
    }

    private function packageRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
    }

    private function documentRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/><Relationship Id="rIdSettings" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/><Relationship Id="rIdHeader" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/header" Target="header1.xml"/><Relationship Id="rIdFooter" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/></Relationships>';
    }

    private function headerRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rIdLogo" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/logo.png"/></Relationships>';
    }

    private function settingsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:updateFields w:val="true"/><w:compat><w:compatSetting w:name="compatibilityMode" w:uri="http://schemas.microsoft.com/office/word" w:val="15"/></w:compat></w:settings>';
    }

    private function corePropertiesXml(string $title, string $company): string
    {
        $date = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>'.$this->xml($title).'</dc:title><dc:creator>'.$this->xml($company).'</dc:creator><cp:lastModifiedBy>Yeboox</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">'.$date.'</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">'.$date.'</dcterms:modified></cp:coreProperties>';
    }

    private function appPropertiesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Yeboox Desktop</Application><AppVersion>1.4</AppVersion></Properties>';
    }
}
