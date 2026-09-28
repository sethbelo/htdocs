<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Classeur Excel de plusieurs feuilles (une TableExport par onglet).
 */
class WorkbookExport implements Export, WithMultipleSheets
{
    /**
     * @param  list<TableExport>  $sheets
     */
    public function __construct(private array $sheets) {}

    /**
     * @return list<TableExport>
     */
    public function sheets(): array
    {
        return $this->sheets;
    }
}
