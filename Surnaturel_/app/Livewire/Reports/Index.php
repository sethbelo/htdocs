<?php

namespace App\Livewire\Reports;

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Exports\TableExport;
use App\Models\CashRegister;
use App\Models\Client;
use App\Models\Product;
use App\Models\Site;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Printing\PdfRenderer;
use App\Services\Reports\ReportBuilder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Rapports')]
class Index extends Component
{
    #[Url(as: 'rapport')]
    public string $report = 'revenue';

    #[Url(as: 'du')]
    public string $from = '';

    #[Url(as: 'au')]
    public string $to = '';

    public ?int $siteId = null;

    public ?int $cashRegisterId = null;

    public ?int $warehouseId = null;

    public ?int $userId = null;

    public ?int $clientId = null;

    public ?int $productId = null;

    #[Url(as: 'statut')]
    public string $status = '';

    public function mount(): void
    {
        $this->from = $this->from ?: today()->startOfMonth()->toDateString();
        $this->to = $this->to ?: today()->toDateString();
    }

    /**
     * @return array{headings: list<string>, rows: Collection<int, list<mixed>>, totals: array<int, float>}
     */
    #[Computed]
    public function data(): array
    {
        abort_unless(isset(ReportBuilder::REPORTS[$this->report]) && auth()->user()->can(ReportBuilder::REPORTS[$this->report]['permission']), 403);

        return app(ReportBuilder::class)->build($this->report, [
            'from' => $this->from, 'to' => $this->to, 'site_id' => $this->siteId, 'cash_register_id' => $this->cashRegisterId,
            'warehouse_id' => $this->warehouseId, 'user_id' => $this->userId, 'client_id' => $this->clientId, 'product_id' => $this->productId, 'status' => $this->status ?: null,
        ], auth()->user());
    }

    public function exportExcel(): BinaryFileResponse
    {
        $this->authorize('reports.export');

        return Excel::download(new TableExport($this->data['headings'], $this->data['rows'], ReportBuilder::REPORTS[$this->report]['label']), "rapport-{$this->report}-{$this->from}-{$this->to}.xlsx");
    }

    public function exportPdf(PdfRenderer $renderer): StreamedResponse
    {
        $this->authorize('reports.export');
        $pdf = $renderer->make('pdf.report', [
            'title' => ReportBuilder::REPORTS[$this->report]['label'],
            'from' => $this->from, 'to' => $this->to, 'data' => $this->data,
            'site' => $this->siteId ? Site::query()->find($this->siteId)?->name : 'Tous les sièges',
        ], count($this->data['headings']) > 7 ? 'landscape' : 'a4');

        return response()->streamDownload(fn () => print ($pdf->output()), "rapport-{$this->report}-{$this->from}-{$this->to}.pdf");
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.reports.index', [
            'reports' => collect(ReportBuilder::REPORTS)->filter(fn (array $r): bool => $user->can($r['permission'])),
            'sites' => Site::query()->whereIn('id', $user->accessibleSiteIds())->get(),
            'registers' => CashRegister::query()->visibleTo($user)->get(),
            'warehouses' => Warehouse::query()->visibleTo($user)->get(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'clients' => Client::query()->orderBy('name')->limit(500)->get(['id', 'name', 'company_name']),
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => $this->report === 'movements' ? StockMovementType::options() : OrderStatus::options(),
        ]);
    }
}
