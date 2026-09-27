<?php

namespace App\Livewire\Inventory;

use App\Exports\TableExport;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\Inventory\WarehouseReport;
use App\Services\Printing\PdfRenderer;
use App\Services\Support\Money;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rapport d'entrepôt en temps réel : entrées et sorties de la journée, qui les a enregistrées
 * et qui les a effectuées, avec export du rapport journalier (PDF signé, Excel).
 */
#[Title('Rapport d’entrepôt en temps réel')]
class LiveReport extends Component
{
    public ?int $warehouseId = null;

    #[Url(as: 'jour')]
    public string $date = '';

    #[Url(as: 'sens')]
    public string $direction = '';

    public bool $live = true;

    public function mount(): void
    {
        $this->date = $this->date ?: today()->toDateString();
        $this->warehouseId = Warehouse::query()->visibleTo(auth()->user())->count() === 1 ? Warehouse::query()->visibleTo(auth()->user())->value('id') : null;
    }

    public function updatedWarehouseId(?int $value): void
    {
        if ($value && ! Warehouse::query()->visibleTo(auth()->user())->whereKey($value)->exists()) {
            $this->warehouseId = null;
        }
    }

    public function updatedDate(): void
    {
        $this->live = Carbon::parse($this->date)->isToday();
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function report(): array
    {
        return app(WarehouseReport::class)->build(auth()->user(), $this->warehouseId, $this->date, $this->date, $this->direction);
    }

    public function shiftDay(int $days): void
    {
        $this->date = Carbon::parse($this->date)->addDays($days)->toDateString();
        $this->updatedDate();
    }

    public function exportPdf(PdfRenderer $renderer, WarehouseReport $builder): StreamedResponse
    {
        $this->authorize('inventory.export');
        $r = $this->report;
        $money = fn ($v) => Money::format($v);
        $day = Carbon::parse($this->date);

        $pdf = $renderer->make('pdf.report', [
            'title' => 'Rapport journalier d’entrepôt',
            'reference' => $builder->warehouseLabel($this->warehouseId),
            'from' => $this->date, 'to' => $this->date,
            'site' => $day->translatedFormat('l j F Y'),
            'kpis' => [
                ['label' => 'Mouvements', 'value' => $r['kpis']['count']],
                ['label' => 'Entrées', 'value' => $r['kpis']['entries'].' · '.$money($r['kpis']['value_in'])],
                ['label' => 'Sorties', 'value' => $r['kpis']['exits'].' · '.$money($r['kpis']['value_out'])],
                ['label' => 'Articles', 'value' => $r['kpis']['products']],
            ],
            'signatures' => 'warehouse_report',
            'sections' => [
                ['title' => 'Synthèse par article', 'headings' => ['Article', 'Entrepôt', 'Stock ouverture', 'Entrées', 'Sorties', 'Stock clôture'], 'rows' => $r['products']->map(fn ($p) => [
                    $p['product']->name, $p['warehouse']->name, (float) $p['opening'], (float) $p['in'], (float) $p['out'], (float) $p['closing'],
                ])->all()],
                ['title' => 'Détail des mouvements', 'headings' => ['Heure', 'N°', 'Type', 'Article', 'Qté', 'Enregistré par', 'Effectué par', 'Référence'], 'rows' => $r['movements']->sortBy('moved_at')->map(fn (StockMovement $m) => [
                    $m->moved_at->format('H:i'), $m->number, $m->type->label(), $m->product->name, (float) $m->signed_quantity,
                    $m->user?->name ?? '-', $m->performerName() ?? '-', $builder->sourceReference($m) ?? ($m->reason ?? '-'),
                ])->values()->all()],
                ['title' => 'Activité du personnel', 'headings' => ['Personne', 'Mouvements', 'Entrées', 'Sorties', 'Dernière action'], 'rows' => $r['byUser']->map(fn ($u) => [
                    $u['name'], $u['count'], $u['in'], $u['out'], $u['last']->format('H:i'),
                ])->all()],
            ],
        ], 'landscape');

        return response()->streamDownload(fn () => print ($pdf->output()), "rapport-entrepot-{$this->date}.pdf");
    }

    public function exportExcel(WarehouseReport $builder): BinaryFileResponse
    {
        $this->authorize('inventory.export');
        $rows = $this->report['movements']->sortBy('moved_at')->map(fn (StockMovement $m) => [
            $m->moved_at->format('d/m/Y H:i'), $m->number, $m->warehouse->name, $m->type->label(), $m->product->sku, $m->product->name,
            (float) $m->signed_quantity, (float) $m->balance_after, (float) $m->unit_cost, $m->user?->name, $m->performerName(), $builder->sourceReference($m), $m->reason,
        ])->values();

        return Excel::download(new TableExport(['Date', 'N°', 'Entrepôt', 'Type', 'Réf.', 'Article', 'Quantité', 'Stock après', 'Coût unitaire', 'Enregistré par', 'Effectué par', 'Document', 'Motif'], $rows, 'Mouvements '.$this->date), "mouvements-entrepot-{$this->date}.xlsx");
    }

    public function render()
    {
        return view('livewire.inventory.live-report', [
            'warehouses' => Warehouse::query()->visibleTo(auth()->user())->orderBy('name')->get(['id', 'name']),
            'money' => fn ($v) => Money::format($v),
            'builder' => app(WarehouseReport::class),
        ]);
    }
}
