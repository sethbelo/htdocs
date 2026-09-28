<?php

namespace App\Livewire\Inventory;

use App\Exports\TableExport;
use App\Models\ProductCategory;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryReports;
use App\Services\Printing\PdfRenderer;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rapports d'entrepôt : état valorisé, fiche des mouvements, ruptures, natures de mouvements,
 * articles dormants, activité du personnel et transferts — à l'écran, en PDF signé et en Excel.
 */
#[Title('Rapports d’entrepôt')]
class Reports extends Component
{
    #[Url(as: 'rapport')]
    public string $report = 'valuation';

    public ?int $warehouseId = null;

    public ?int $categoryId = null;

    public string $from = '';

    public string $to = '';

    public string $preset = 'month';

    public function mount(): void
    {
        if (! array_key_exists($this->report, InventoryReports::REPORTS)) {
            $this->report = 'valuation';
        }
        $this->applyPreset('month');
    }

    public function applyPreset(string $preset): void
    {
        [$from, $to] = match ($preset) {
            'today' => [today(), today()],
            'week' => [today()->startOfWeek(), today()],
            'quarter' => [today()->firstOfQuarter(), today()],
            'year' => [today()->startOfYear(), today()],
            default => [today()->startOfMonth(), today()],
        };
        $this->preset = in_array($preset, ['today', 'week', 'month', 'quarter', 'year'], true) ? $preset : 'month';
        [$this->from, $this->to] = [$from->toDateString(), $to->toDateString()];
    }

    public function updatedFrom(): void
    {
        $this->preset = 'custom';
    }

    public function updatedTo(): void
    {
        $this->preset = 'custom';
    }

    public function updatedWarehouseId(?int $value): void
    {
        if ($value && ! Warehouse::query()->visibleTo(auth()->user())->whereKey($value)->exists()) {
            $this->warehouseId = null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function data(): array
    {
        return app(InventoryReports::class)->build($this->report, auth()->user(), $this->warehouseId, $this->from, $this->to, $this->categoryId);
    }

    private function scopeLabel(): string
    {
        return $this->warehouseId ? (string) Warehouse::query()->find($this->warehouseId)?->name : 'Tous les entrepôts';
    }

    public function exportPdf(PdfRenderer $renderer): StreamedResponse
    {
        $this->authorize('inventory.export');
        $data = $this->data;

        $pdf = $renderer->make('pdf.report', [
            'title' => $data['title'],
            'reference' => $this->scopeLabel(),
            'from' => $this->from, 'to' => $this->to,
            'site' => in_array($this->report, ['valuation', 'alerts', 'dormant'], true) ? 'Situation au '.Carbon::parse($this->to)->format('d/m/Y') : null,
            'kpis' => $data['kpis'],
            'sections' => [['title' => $data['description'], 'headings' => $data['headings'], 'rows' => array_map(fn ($row) => array_map(fn ($cell) => $cell ?? '', $row), $data['rows']), 'totals' => null]],
            'signatures' => 'warehouse_report',
        ], count($data['headings']) > 7 ? 'landscape' : 'a4');

        return response()->streamDownload(fn () => print ($pdf->output()), "entrepot-{$this->report}-{$this->from}-{$this->to}.pdf");
    }

    public function exportExcel(): BinaryFileResponse
    {
        $this->authorize('inventory.export');
        $data = $this->data;

        return Excel::download(new TableExport($data['headings'], collect($data['rows']), $data['title'], $data['totals']), "entrepot-{$this->report}-{$this->from}-{$this->to}.xlsx");
    }

    public function render()
    {
        return view('livewire.inventory.reports', [
            'reports' => InventoryReports::REPORTS,
            'warehouses' => Warehouse::query()->visibleTo(auth()->user())->orderBy('name')->get(['id', 'name']),
            'categories' => ProductCategory::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
