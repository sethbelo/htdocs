<?php

namespace App\Exports;

use App\Models\PersonalSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Tableur personnel → .xlsx (valeurs calculées : nombres conservés en numérique). */
class PersonalSheetExport implements FromArray, ShouldAutoSize, WithTitle
{
    public function __construct(private readonly PersonalSheet $sheet) {}

    public function array(): array
    {
        return array_map(fn (array $row) => array_map(
            fn ($value) => is_string($value) && str_starts_with($value, '=') ? "'".$value : $value,
            $row,
        ), $this->sheet->matrix());
    }

    public function title(): string
    {
        $title = preg_replace('#[\\\\/?*\[\]:]#', ' ', $this->sheet->title);

        return mb_substr(trim($title) ?: 'Feuille 1', 0, 31);
    }
}
