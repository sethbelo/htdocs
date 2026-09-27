<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Support\DocumentPreferences;
use App\Support\DisplayCurrency;
use App\Support\ReportWordGenerator;
use Carbon\Carbon;
use Livewire\Component;

class Reports extends Component
{
    public $reportType = 'sales'; // 'sales', 'stock', 'sessions'

    // Filters
    public $startDate;

    public $endDate;

    public $departmentFilter = '';

    public $selectedSessionId = '';

    public $companyPhone;

    public $companyAddress;

    public $exchangeRate;

    public string $lastRefreshedAt = '';

    public function mount()
    {
        // Le pilotage s'ouvre sur la journée courante, le besoin le plus
        // fréquent en caisse. Les raccourcis permettent d'élargir la période.
        $this->startDate = Carbon::today()->format('Y-m-d');
        $this->endDate = Carbon::today()->format('Y-m-d');

        $this->companyPhone = Setting::get('company_phone', '+243 81 000 0000');
        $this->companyAddress = Setting::get('company_address', 'Kinshasa');
        $this->exchangeRate = (float) Setting::get('exchange_rate', 2850);
        $this->lastRefreshedAt = now()->format('H:i:s');
    }

    public function refreshReport(): void
    {
        $this->lastRefreshedAt = now()->format('H:i:s');
    }

    public function setPeriod(string $period): void
    {
        [$start, $end] = match ($period) {
            'yesterday' => [Carbon::yesterday(), Carbon::yesterday()],
            'week' => [Carbon::today()->startOfWeek(), Carbon::today()],
            'month' => [Carbon::today()->startOfMonth(), Carbon::today()],
            default => [Carbon::today(), Carbon::today()],
        };

        $this->startDate = $start->format('Y-m-d');
        $this->endDate = $end->format('Y-m-d');
        $this->refreshReport();
    }

    private function getReportData()
    {
        $period = [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()];
        $data = [
            'sales' => collect(),
            'movements' => collect(),
            'sessions' => collect(),
            'selectedSession' => null,
            'lowStockProducts' => collect(),
            'salesTrend' => collect(),
            'topProducts' => collect(),
            'categorySales' => collect(),
        ];

        // 1. Sales Report Data
        if ($this->reportType === 'sales') {
            $salesQuery = Sale::with(['customer', 'user', 'items.product'])
                ->whereBetween('created_at', $period);

            if ($this->departmentFilter) {
                $salesQuery->where('department', $this->departmentFilter);
            }

            $data['sales'] = $salesQuery->latest()->get();

            $data['salesTrend'] = $data['sales']
                ->groupBy(fn (Sale $sale) => $sale->created_at->format('Y-m-d'))
                ->sortKeys()
                ->map(fn ($dailySales, $date) => [
                    'date' => $date,
                    'label' => Carbon::parse($date)->translatedFormat('d M'),
                    'revenue' => (float) $dailySales->sum('total_amount'),
                    'orders' => $dailySales->count(),
                ])
                ->values();

            $items = $data['sales']->flatMap(fn (Sale $sale) => $sale->items->map(function ($item) use ($sale) {
                return [
                    'sale_id' => $sale->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product?->name ?? 'Article supprimé',
                    'category' => trim((string) ($item->product?->category ?? '')) ?: 'Sans catégorie',
                    'quantity' => (float) $item->quantity,
                    'revenue' => (float) $item->total_price,
                ];
            }));

            $data['topProducts'] = $items
                ->groupBy(fn (array $item) => $item['product_id'].'|'.$item['name'])
                ->map(fn ($productItems) => [
                    'name' => $productItems->first()['name'],
                    'category' => $productItems->first()['category'],
                    'quantity' => (float) $productItems->sum('quantity'),
                    'revenue' => (float) $productItems->sum('revenue'),
                    'orders' => $productItems->pluck('sale_id')->unique()->count(),
                ])
                ->sortByDesc('quantity')
                ->take(8)
                ->values();

            $categoryTotal = max(1, (float) $items->sum('revenue'));
            $data['categorySales'] = $items
                ->groupBy('category')
                ->map(fn ($categoryItems, $category) => [
                    'name' => $category,
                    'quantity' => (float) $categoryItems->sum('quantity'),
                    'revenue' => (float) $categoryItems->sum('revenue'),
                    'orders' => $categoryItems->pluck('sale_id')->unique()->count(),
                    'share' => round(((float) $categoryItems->sum('revenue') / $categoryTotal) * 100, 1),
                ])
                ->sortByDesc('revenue')
                ->values();
        }

        // 2. Stock Report Data
        if ($this->reportType === 'stock') {
            $data['movements'] = StockMovement::with(['product', 'user'])
                ->whereBetween('created_at', $period)
                ->latest()
                ->get();

            $data['lowStockProducts'] = Product::where('type', 'product')
                ->whereRaw('stock_qty <= alert_qty')
                ->get();
        }

        // 3. Sessions Report Data
        if ($this->reportType === 'sessions') {
            $data['sessions'] = RegisterSession::with(['user', 'sales'])
                ->whereBetween('opened_at', $period)
                ->latest()
                ->get();

            if ($this->selectedSessionId) {
                $data['selectedSession'] = RegisterSession::with(['user', 'sales.customer'])->find($this->selectedSessionId);
            }
        }

        return $data;
    }

    public function exportExcel()
    {
        abort_unless(auth()->user()?->can('export reports'), 403, 'Vous ne pouvez pas exporter les rapports.');
        $data = $this->getReportData();
        $csvData = [];
        $filename = "Export_{$this->reportType}_".date('Y-m-d_H-i').'.csv';

        if ($this->reportType === 'sales') {
            $csvData[] = ['Date', 'Client', 'Secteur', 'Règlement', 'Net payé ('.DisplayCurrency::baseCode().')'];
            foreach ($data['sales'] as $sale) {
                $customer = $sale->customer ? $sale->customer->name : 'Client Passant';
                $csvData[] = [
                    $sale->created_at->format('Y-m-d H:i:s'),
                    $customer,
                    $sale->department,
                    $sale->payment_method,
                    $sale->total_amount,
                ];
            }
        } elseif ($this->reportType === 'stock') {
            $csvData[] = ['Date', 'Produit', 'Mouvement', 'Quantite', 'Motif', 'Par'];
            foreach ($data['movements'] as $mvt) {
                $type = $mvt->type === 'in' ? 'Entree' : 'Sortie';
                $csvData[] = [
                    $mvt->created_at->format('Y-m-d H:i:s'),
                    $mvt->product->name,
                    $type,
                    $mvt->quantity,
                    $mvt->description,
                    $mvt->user->name,
                ];
            }
        } elseif ($this->reportType === 'sessions') {
            $csvData[] = ['Ouverture', 'Fermeture', 'Caissier', 'Statut', 'Total ('.DisplayCurrency::baseCode().')'];
            foreach ($data['sessions'] as $sess) {
                $closedAt = $sess->closed_at ? $sess->closed_at->format('Y-m-d H:i:s') : 'En cours';
                $csvData[] = [
                    $sess->opened_at->format('Y-m-d H:i:s'),
                    $closedAt,
                    $sess->user->name,
                    $sess->status,
                    $sess->sales->sum('total_amount'),
                ];
            }
        }

        $output = fopen('php://temp', 'w');
        // UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        foreach ($csvData as $row) {
            fputcsv($output, $row, ';');
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportWord()
    {
        abort_unless(auth()->user()?->can('export reports'), 403, 'Vous ne pouvez pas exporter les rapports.');

        return app(ReportWordGenerator::class)->download(
            $this->reportType,
            $this->getReportData(),
            $this->startDate,
            $this->endDate,
            auth()->user()?->name ?? 'Système',
        );
    }

    /** @deprecated Kept temporarily for backward compatibility with old .doc integrations. */
    private function exportWordHtmlLegacy()
    {
        $data = $this->getReportData();
        $filename = "Export_{$this->reportType}_".date('Y-m-d_H-i').'.doc';

        $companyName = Setting::get('company_name', 'Yeboox App');
        $generationDate = date('d/m/Y à H:i');
        $userName = auth()->user()->name ?? 'Système';

        $html = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>";
        $html .= "<head><meta charset='utf-8'><title>Rapport {$this->reportType}</title>";
        $html .= "<style>
                    body { font-family: 'Segoe UI', Arial, sans-serif; }
                    table { border-collapse: collapse; width: 100%; margin-top: 20px; } 
                    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; } 
                    th { background-color: #f8f9fa; font-weight: bold; }
                    .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #2d3748; padding-bottom: 15px; }
                    .header h1 { margin: 0; font-size: 24px; color: #1a202c; text-transform: uppercase; }
                    .header p { margin: 5px 0 0 0; font-size: 14px; color: #4a5568; }
                    .report-title { font-size: 18px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; }
                    .report-period { font-size: 14px; color: #718096; margin-bottom: 20px; }
                    .footer { margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 15px; font-size: 12px; text-align: center; color: #a0aec0; }
                  </style>";
        $html .= '</head><body>';

        // Header
        $html .= "<div class='header'>";
        $html .= '<h1>'.htmlspecialchars($companyName).'</h1>';
        $html .= '<p>'.htmlspecialchars($this->companyAddress.' | '.$this->companyPhone).'</p>';
        $html .= '</div>';

        // Report Title
        $reportTypesTr = ['sales' => 'Ventes', 'stock' => 'Mouvements de Stock', 'sessions' => 'Sessions de Caisse'];
        $title = $reportTypesTr[$this->reportType] ?? strtoupper($this->reportType);
        $html .= "<div class='report-title'>RAPPORT : ".$title.'</div>';
        $html .= "<div class='report-period'>Période : du ".date('d/m/Y', strtotime($this->startDate)).' au '.date('d/m/Y', strtotime($this->endDate)).'</div>';

        if ($this->reportType === 'sales') {
            $html .= '<table><thead><tr><th>Date</th><th>Client</th><th>Secteur</th><th>Règlement</th><th>Net Payé</th></tr></thead><tbody>';
            $total = 0;
            foreach ($data['sales'] as $sale) {
                $customer = $sale->customer ? htmlspecialchars($sale->customer->name) : 'Client Passant';
                $html .= "<tr><td>{$sale->created_at->format('d/m/Y H:i')}</td><td>{$customer}</td><td>{$sale->department}</td><td>{$sale->payment_method}</td><td>\${$sale->total_amount}</td></tr>";
                $total += $sale->total_amount;
            }
            $html .= "</tbody><tfoot><tr><th colspan='4' style='text-align: right;'>Total Net</th><th>\${$total}</th></tr></tfoot></table>";
        } elseif ($this->reportType === 'stock') {
            $html .= '<table><thead><tr><th>Date</th><th>Produit</th><th>Mouvement</th><th>Quantité</th><th>Motif</th></tr></thead><tbody>';
            foreach ($data['movements'] as $mvt) {
                $type = $mvt->type === 'in' ? 'Entrée' : 'Sortie';
                $html .= "<tr><td>{$mvt->created_at->format('d/m/Y H:i')}</td><td>".htmlspecialchars($mvt->product->name)."</td><td>{$type}</td><td>{$mvt->quantity}</td><td>".htmlspecialchars($mvt->description).'</td></tr>';
            }
            $html .= '</tbody></table>';
        } elseif ($this->reportType === 'sessions') {
            $html .= '<table><thead><tr><th>Ouverture</th><th>Fermeture</th><th>Caissier</th><th>Status</th><th>Total</th></tr></thead><tbody>';
            foreach ($data['sessions'] as $sess) {
                $closedAt = $sess->closed_at ? $sess->closed_at->format('d/m/Y H:i') : 'En cours';
                $sessionSales = $sess->sales->sum('total_amount');
                $html .= "<tr><td>{$sess->opened_at->format('d/m/Y H:i')}</td><td>{$closedAt}</td><td>".htmlspecialchars($sess->user->name)."</td><td>{$sess->status}</td><td>\${$sessionSales}</td></tr>";
            }
            $html .= '</tbody></table>';
        }

        // Footer
        $html .= "<div class='footer'>";
        $html .= "<p>Document généré le {$generationDate} par {$userName}.</p>";
        $html .= '<p>&copy; '.date('Y').' '.htmlspecialchars($companyName).'. Tous droits réservés.</p>';
        $html .= '</div>';

        $html .= '</body></html>';

        return response()->streamDownload(function () use ($html) {
            echo $html;
        }, $filename, ['Content-Type' => 'application/vnd.ms-word; charset=UTF-8']);
    }

    public function exportMarkdown()
    {
        abort_unless(auth()->user()?->can('export reports'), 403, 'Vous ne pouvez pas exporter les rapports.');
        $data = $this->getReportData();
        $filename = "Export_{$this->reportType}_".date('Y-m-d_H-i').'.md';

        $md = '# Rapport - '.strtoupper($this->reportType)."\n";
        $md .= "**Période :** {$this->startDate} au {$this->endDate}\n\n";

        if ($this->reportType === 'sales') {
            $md .= "| Date & Heure | Client | Secteur | Règlement | Net Payé ($) |\n";
            $md .= "|---|---|---|---|---|\n";
            $total = 0;
            foreach ($data['sales'] as $sale) {
                $customer = $sale->customer ? str_replace('|', '', $sale->customer->name) : 'Client Passant';
                $md .= "| {$sale->created_at->format('Y-m-d H:i')} | {$customer} | {$sale->department} | {$sale->payment_method} | {$sale->total_amount} |\n";
                $total += $sale->total_amount;
            }
            $md .= "\n**Total Net : \${$total}**\n";
        } elseif ($this->reportType === 'stock') {
            $md .= "| Date | Produit | Mouvement | Quantité | Motif |\n";
            $md .= "|---|---|---|---|---|\n";
            foreach ($data['movements'] as $mvt) {
                $type = $mvt->type === 'in' ? 'Entrée' : 'Sortie';
                $productName = str_replace('|', '', $mvt->product->name);
                $desc = str_replace('|', '', $mvt->description);
                $md .= "| {$mvt->created_at->format('Y-m-d H:i')} | {$productName} | {$type} | {$mvt->quantity} | {$desc} |\n";
            }
        } elseif ($this->reportType === 'sessions') {
            $md .= "| Ouverture | Fermeture | Caissier | Status | Total ($) |\n";
            $md .= "|---|---|---|---|---|\n";
            foreach ($data['sessions'] as $sess) {
                $closedAt = $sess->closed_at ? $sess->closed_at->format('Y-m-d H:i') : 'En cours';
                $userName = str_replace('|', '', $sess->user->name);
                $sessionSales = $sess->sales->sum('total_amount');
                $md .= "| {$sess->opened_at->format('Y-m-d H:i')} | {$closedAt} | {$userName} | {$sess->status} | {$sessionSales} |\n";
            }
        }

        return response()->streamDownload(function () use ($md) {
            echo $md;
        }, $filename, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }

    public function render()
    {
        $data = $this->getReportData();
        $data['reportPreferences'] = DocumentPreferences::report();
        $data['reportTemplateMeta'] = DocumentPreferences::reportTemplates()[$data['reportPreferences']['template']]
            ?? DocumentPreferences::reportTemplates()['executive'];

        return view('livewire.reports', $data)->layout('layouts.app');
    }
}
