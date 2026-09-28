<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Export Excel générique : en-tête à la charte (encre + filet magenta), ligne figée,
 * filtres automatiques et format numérique « 1 234,56 » sur les colonnes chiffrées.
 */
class TableExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithTitle
{
    /**
     * @param  list<string>  $headings
     * @param  Collection<int, array<int, mixed>>  $rows
     * @param  list<int>  $totalRows  index (0-based) des lignes de total à mettre en gras
     */
    public function __construct(private array $headings, private Collection $rows, private string $title = 'Rapport', private array $totalRows = []) {}

    public function collection(): Collection
    {
        return $this->rows->map(fn ($row) => array_values((array) $row));
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return mb_substr(str_replace(['[', ']', ':', '*', '?', '/', '\\'], ' ', $this->title), 0, 31);
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = Coordinate::stringFromColumnIndex(max(1, count($this->headings)));
                $lastRow = $this->rows->count() + 1;

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16161D']],
                    'borders' => ['bottom' => ['borderStyle' => 'medium', 'color' => ['rgb' => 'E6007E']]],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(22);
                $sheet->freezePane('A2');

                if ($lastRow > 1) {
                    $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");
                }

                foreach (array_keys($this->headings) as $index) {
                    $numeric = $this->rows->isNotEmpty() && $this->rows->every(fn ($row) => ($value = array_values((array) $row)[$index] ?? null) === null || $value === '' || is_int($value) || is_float($value));
                    if ($numeric && $this->rows->contains(fn ($row) => is_float(array_values((array) $row)[$index] ?? null))) {
                        $column = Coordinate::stringFromColumnIndex($index + 1);
                        $sheet->getStyle("{$column}2:{$column}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                    }
                }

                foreach ($this->totalRows as $rowIndex) {
                    $row = $rowIndex + 2;
                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F1F6']],
                    ]);
                }
            },
        ];
    }
}
