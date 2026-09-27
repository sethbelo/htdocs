<?php

namespace App\Services\Reports;

use App\Enums\OrderStatus;
use App\Models\Attendance;
use App\Models\DesignJob;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Payslip;
use App\Models\PrintJob;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Rapports transverses filtrables (date, siège, PDV, entrepôt, utilisateur, commercial, caissier, client, produit, statut).
 */
class ReportBuilder
{
    /**
     * @var array<string, array{label: string, permission: string}>
     */
    public const REPORTS = [
        'revenue' => ['label' => 'Chiffre d’affaires encaissé', 'permission' => 'reports.view'],
        'sales' => ['label' => 'Ventes boutique', 'permission' => 'reports.view'],
        'orders' => ['label' => 'Commandes d’impression', 'permission' => 'reports.view'],
        'margins' => ['label' => 'Marges par article (boutique)', 'permission' => 'reports.view'],
        'production' => ['label' => 'Production & opérateurs', 'permission' => 'reports.view'],
        'designers' => ['label' => 'Activité des graphistes', 'permission' => 'reports.view'],
        'sales_reps' => ['label' => 'Performances commerciales', 'permission' => 'reports.view'],
        'stock' => ['label' => 'État des stocks', 'permission' => 'reports.view'],
        'movements' => ['label' => 'Mouvements & pertes', 'permission' => 'reports.view'],
        'expenses' => ['label' => 'Dépenses & décaissements', 'permission' => 'reports.view'],
        'unpaid' => ['label' => 'Factures impayées', 'permission' => 'reports.view'],
        'attendance' => ['label' => 'Présence', 'permission' => 'attendance.view'],
        'payroll' => ['label' => 'Paie', 'permission' => 'payroll.view'],
    ];

    /**
     * @param  array{from: string, to: string, site_id?: int|null, cash_register_id?: int|null, warehouse_id?: int|null, user_id?: int|null, client_id?: int|null, product_id?: int|null, status?: string|null}  $filters
     * @return array{headings: list<string>, rows: Collection<int, list<mixed>>, totals: array<int, float>}
     */
    public function build(string $report, array $filters, User $user): array
    {
        $sites = ! empty($filters['site_id']) && $user->canAccessSite((int) $filters['site_id']) ? [(int) $filters['site_id']] : $user->accessibleSiteIds();
        $period = [$filters['from'].' 00:00:00', $filters['to'].' 23:59:59'];
        $f = fn (string $key) => $filters[$key] ?? null;

        [$headings, $rows] = match ($report) {
            'revenue' => [
                ['Date', 'N° paiement', 'Référence', 'Client', 'Moyen', 'Caissier', 'Montant'],
                Payment::query()->with('client', 'receiver', 'payable')->whereIn('site_id', $sites)->where('direction', 'in')->whereBetween('paid_at', $period)
                    ->when($f('user_id'), fn (Builder $q, $v) => $q->where('received_by', $v))
                    ->when($f('client_id'), fn (Builder $q, $v) => $q->where('client_id', $v))
                    ->when($f('cash_register_id'), fn (Builder $q, $v) => $q->whereHas('cashSession', fn (Builder $s) => $s->where('cash_register_id', $v)))
                    ->orderBy('paid_at')->get()
                    ->map(fn (Payment $p): array => [$p->paid_at->format('d/m/Y H:i'), $p->number, $p->payable?->number, $p->client?->displayName(), $p->method->label(), $p->receiver?->name, (float) $p->amount]),
            ],
            'sales' => [
                ['Date', 'N° vente', 'Caisse', 'Caissier', 'Client', 'Remises', 'TVA', 'Total'],
                Sale::query()->with('register', 'cashier', 'client')->whereIn('site_id', $sites)->whereBetween('sold_at', $period)
                    ->when($f('cash_register_id'), fn (Builder $q, $v) => $q->where('cash_register_id', $v))
                    ->when($f('user_id'), fn (Builder $q, $v) => $q->where('user_id', $v))
                    ->when($f('client_id'), fn (Builder $q, $v) => $q->where('client_id', $v))
                    ->when($f('product_id'), fn (Builder $q, $v) => $q->whereHas('items', fn (Builder $i) => $i->where('product_id', $v)))
                    ->orderBy('sold_at')->get()
                    ->map(fn (Sale $s): array => [$s->sold_at->format('d/m/Y H:i'), $s->number, $s->register->code, $s->cashier->name, $s->client?->displayName(), (float) $s->discount_total, (float) $s->tax_total, (float) $s->total]),
            ],
            'orders' => [
                ['Date', 'N° commande', 'Client', 'Commercial', 'Statut', 'Délai', 'Total', 'Payé', 'Reste'],
                Order::query()->with('client', 'salesRep')->whereIn('site_id', $sites)->whereBetween('ordered_at', $period)
                    ->when($f('status'), fn (Builder $q, $v) => $q->where('status', $v))
                    ->when($f('user_id'), fn (Builder $q, $v) => $q->where('sales_rep_id', $v))
                    ->when($f('client_id'), fn (Builder $q, $v) => $q->where('client_id', $v))
                    ->when($f('product_id'), fn (Builder $q, $v) => $q->whereHas('items', fn (Builder $i) => $i->where('product_id', $v)))
                    ->orderBy('ordered_at')->get()
                    ->map(fn (Order $o): array => [$o->ordered_at->format('d/m/Y'), $o->number, $o->client->displayName(), $o->salesRep?->name, $o->status->label(), $o->due_at?->format('d/m/Y'), (float) $o->total, (float) $o->amount_paid, $o->balanceDue()]),
            ],
            'margins' => [
                ['Article', 'Quantité', 'CA HT', 'Coût', 'Marge', 'Taux de marge %'],
                SaleItem::query()->with('product')->whereHas('sale', fn (Builder $q) => $q->whereIn('site_id', $sites)->whereBetween('sold_at', $period)->where('status', 'completed'))
                    ->when($f('product_id'), fn (Builder $q, $v) => $q->where('product_id', $v))
                    ->get()->groupBy('product_id')
                    ->map(function (Collection $items): array {
                        $revenue = $items->sum(fn (SaleItem $i): float => (float) $i->total / (1 + (float) $i->tax_rate / 100));
                        $cost = $items->sum(fn (SaleItem $i): float => (float) $i->unit_cost * (float) $i->quantity);

                        return [$items->first()->description, (float) $items->sum('quantity'), round($revenue, 2), round($cost, 2), round($revenue - $cost, 2), $revenue > 0 ? round(($revenue - $cost) / $revenue * 100, 1) : 0];
                    })->sortByDesc(fn (array $row) => $row[4])->values(),
            ],
            'production' => [
                ['Bon', 'Commande', 'Machine', 'Opérateur', 'Début', 'Fin', 'Durée (min)', 'Prévu', 'Produit', 'Pertes', 'Statut'],
                PrintJob::query()->with('order', 'machine', 'operator')->whereHas('order', fn (Builder $q) => $q->whereIn('site_id', $sites))
                    ->whereBetween('created_at', $period)
                    ->when($f('user_id'), fn (Builder $q, $v) => $q->where('operator_id', $v))
                    ->get()
                    ->map(fn (PrintJob $j): array => [$j->number, $j->order->number, $j->machine?->name, $j->operator?->name, $j->started_at?->format('d/m H:i'), $j->finished_at?->format('d/m H:i'), $j->durationMinutes(), $j->planned_quantity, $j->produced_quantity, $j->waste_quantity, $j->status->label()]),
            ],
            'designers' => [
                ['Graphiste', 'Travaux', 'Terminés', 'En cours', 'Bloqués', 'Versions déposées', 'Délai moyen (h)'],
                DesignJob::query()->with('designer', 'versions')->whereNotNull('designer_id')->whereHas('order', fn (Builder $q) => $q->whereIn('site_id', $sites))->whereBetween('created_at', $period)
                    ->when($f('user_id'), fn (Builder $q, $v) => $q->where('designer_id', $v))
                    ->get()->groupBy('designer_id')
                    ->map(fn (Collection $jobs): array => [
                        $jobs->first()->designer->name, $jobs->count(),
                        $jobs->whereIn('status.value', ['validated', 'sent_to_print'])->count(),
                        $jobs->whereIn('status.value', ['in_progress', 'proposal', 'correction', 'proof'])->count(),
                        $jobs->where('status.value', 'blocked')->count(),
                        $jobs->sum(fn (DesignJob $j) => $j->versions->count()),
                        round($jobs->filter(fn (DesignJob $j) => $j->started_at && $j->completed_at)->avg(fn (DesignJob $j) => $j->started_at->diffInMinutes($j->completed_at) / 60) ?? 0, 1),
                    ])->values(),
            ],
            'sales_reps' => [
                ['Commercial', 'Commandes', 'CA commandé', 'Encaissé', 'Panier moyen', 'Commandes annulées'],
                Order::query()->with('salesRep')->whereIn('site_id', $sites)->whereBetween('ordered_at', $period)->whereNotNull('sales_rep_id')
                    ->when($f('user_id'), fn (Builder $q, $v) => $q->where('sales_rep_id', $v))
                    ->get()->groupBy('sales_rep_id')
                    ->map(fn (Collection $orders): array => [
                        $orders->first()->salesRep->name, $orders->count(), round((float) $orders->sum('total'), 2), round((float) $orders->sum('amount_paid'), 2),
                        round((float) $orders->avg('total'), 2), $orders->where('status', OrderStatus::Cancelled)->count(),
                    ])->sortByDesc(fn (array $r) => $r[2])->values(),
            ],
            'stock' => [
                ['SKU', 'Article', 'Entrepôt', 'Quantité', 'Minimum', 'Coût unitaire', 'Valeur', 'Alerte'],
                StockLevel::query()->with('product', 'warehouse')->whereIn('warehouse_id', Warehouse::query()->whereIn('site_id', $sites)->pluck('id'))
                    ->when($f('warehouse_id'), fn (Builder $q, $v) => $q->where('warehouse_id', $v))
                    ->when($f('product_id'), fn (Builder $q, $v) => $q->where('product_id', $v))
                    ->get()
                    ->map(fn (StockLevel $l): array => [$l->product->sku, $l->product->name, $l->warehouse->name, (float) $l->quantity, (float) $l->product->min_stock, (float) $l->product->cost_price, round((float) $l->quantity * (float) $l->product->cost_price, 2), (float) $l->quantity <= (float) $l->product->min_stock ? 'Stock faible' : '']),
            ],
            'movements' => [
                ['Date', 'N°', 'Article', 'Entrepôt', 'Type', 'Quantité', 'Valeur', 'Motif', 'Utilisateur'],
                StockMovement::query()->with('product', 'warehouse', 'user')->whereIn('warehouse_id', Warehouse::query()->whereIn('site_id', $sites)->pluck('id'))->whereBetween('moved_at', $period)
                    ->when($f('warehouse_id'), fn (Builder $q, $v) => $q->where('warehouse_id', $v))
                    ->when($f('product_id'), fn (Builder $q, $v) => $q->where('product_id', $v))
                    ->when($f('status'), fn (Builder $q, $v) => $q->where('type', $v))
                    ->orderBy('moved_at')->get()
                    ->map(fn (StockMovement $m): array => [$m->moved_at->format('d/m/Y H:i'), $m->number, $m->product->name, $m->warehouse->name, $m->type->label(), (float) $m->quantity, round(abs((float) $m->quantity) * (float) $m->unit_cost, 2), $m->reason, $m->user?->name]),
            ],
            'expenses' => [
                ['Date', 'N°', 'Objet', 'Moyen', 'Compte', 'Montant'],
                Payment::query()->with('payable', 'treasuryAccount')->whereIn('site_id', $sites)->where('direction', 'out')->whereBetween('paid_at', $period)->orderBy('paid_at')->get()
                    ->map(fn (Payment $p): array => [$p->paid_at->format('d/m/Y'), $p->number, $p->payable?->number ?? $p->payable?->title, $p->method->label(), $p->treasuryAccount?->name, (float) $p->amount]),
            ],
            'unpaid' => [
                ['Facture', 'Commande', 'Client', 'Émise le', 'Échéance', 'Retard (j)', 'Total', 'Reste dû'],
                Invoice::query()->with('client', 'order')->whereIn('site_id', $sites)->whereIn('status', ['issued', 'partially_paid'])
                    ->when($f('client_id'), fn (Builder $q, $v) => $q->where('client_id', $v))
                    ->orderBy('due_at')->get()
                    ->map(fn (Invoice $i): array => [$i->number, $i->order?->number, $i->client?->displayName(), $i->issued_at->format('d/m/Y'), $i->due_at?->format('d/m/Y'), $i->due_at?->isPast() ? (int) $i->due_at->diffInDays(today()) : 0, (float) $i->total, $i->balanceDue()]),
            ],
            'attendance' => [
                ['Employé', 'Présences', 'Retards', 'Absences', 'Missions', 'Congés', 'Heures travaillées', 'Heures sup.'],
                Attendance::query()->with('employee')->whereIn('site_id', $sites)->whereDate('date', '>=', $filters['from'])->whereDate('date', '<=', $filters['to'])->get()->groupBy('employee_id')
                    ->map(fn (Collection $rows): array => [
                        $rows->first()->employee->fullName(), $rows->where('status.value', 'present')->count(), $rows->where('status.value', 'late')->count(),
                        $rows->where('status.value', 'absent')->count(), $rows->where('status.value', 'mission')->count(), $rows->where('status.value', 'leave')->count(),
                        round($rows->sum('worked_minutes') / 60, 1), round($rows->sum('overtime_minutes') / 60, 1),
                    ])->values(),
            ],
            'payroll' => [
                ['Période', 'Matricule', 'Employé', 'Brut', 'Retenues', 'Net'],
                Payslip::query()->with('period', 'employee')->whereHas('employee', fn (Builder $q) => $q->whereIn('site_id', $sites))
                    ->whereHas('period', fn (Builder $q) => $q->where('starts_on', '<=', $filters['to'])->where('ends_on', '>=', $filters['from']))->get()
                    ->map(fn (Payslip $p): array => [$p->period->code, $p->employee->matricule, $p->employee->fullName(), (float) $p->gross_earnings, (float) $p->total_deductions, (float) $p->net_pay]),
            ],
            default => [[], collect()],
        };

        $rows = collect($rows)->map(fn ($row) => array_values((array) $row))->values();
        $totals = [];

        foreach ($headings as $index => $heading) {
            if (preg_match('/Montant|Total|Payé|Reste|Valeur|CA|Coût|Marge|Brut|Net|Retenues|Encaissé|Remises|TVA/u', $heading) && ! str_contains($heading, '%')) {
                $totals[$index] = round($rows->sum(fn (array $row) => is_numeric($row[$index] ?? null) ? (float) $row[$index] : 0), 2);
            }
        }

        return ['headings' => $headings, 'rows' => $rows, 'totals' => $totals];
    }
}
