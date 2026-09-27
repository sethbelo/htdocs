<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Employee;
use App\Models\JournalLine;
use App\Models\Payslip;
use App\Models\Product;
use App\Models\Site;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Rapports de gestion (écran « Rapports » et impressions PDF).
 * Chaque rapport renvoie : title, subtitle, kpis[], sections[{title, columns, numeric, rows}].
 */
class ReportService
{
    public const REPORTS = [
        'ventes' => ['Synthèse des ventes', 'presentation-chart-line', 'Chiffre d’affaires, top clients et top articles.'],
        'resultat' => ['Compte de résultat', 'scale', 'Produits et charges par compte SYSCOHADA.'],
        'balance' => ['Balance générale', 'calculator', 'Soldes débiteurs et créditeurs par compte.'],
        'creances' => ['Balance âgée clients', 'clock', 'Factures impayées par tranche d’ancienneté.'],
        'stock' => ['Valorisation du stock', 'cube', 'Quantités et valeur au coût d’achat.'],
        'paie' => ['Masse salariale', 'wallet', 'Brut, retenues et charges par département.'],
        'chantiers' => ['Synthèse des chantiers', 'home-modern', 'Budget, main-d’œuvre, bons et avancement.'],
    ];

    public function build(string $key, CarbonInterface $from, CarbonInterface $to): array
    {
        abort_unless(array_key_exists($key, self::REPORTS), 404);
        $data = $this->{$key}(company_id(), $from->copy()->startOfDay(), $to->copy()->endOfDay());

        return $data + ['title' => self::REPORTS[$key][0], 'key' => $key];
    }

    private function ventes(int $cid, $from, $to): array
    {
        $invoices = Document::where('company_id', $cid)->where('type', 'invoice')->whereNotIn('status', ['cancelled', 'draft'])->whereBetween('issue_date', [$from, $to]);
        $total = (float) (clone $invoices)->sum('base_total');
        $clients = (clone $invoices)->with('contact')->get()->groupBy('contact_id')
            ->map(fn ($docs) => (object) ['name' => $docs->first()->contact?->display_name, 'segment' => $docs->first()->contact?->segment, 'n' => $docs->count(), 'total' => $docs->sum('base_total'), 'due' => $docs->sum(fn ($d) => $d->balance_due)])
            ->sortByDesc('total')->take(15);
        $items = DB::table('document_items')->join('documents', 'documents.id', '=', 'document_items.document_id')
            ->where('documents.company_id', $cid)->where('documents.type', 'invoice')->whereNull('documents.deleted_at')->whereNotIn('documents.status', ['cancelled', 'draft'])->whereBetween('documents.issue_date', [$from, $to])
            ->selectRaw('document_items.description, SUM(document_items.quantity) as qty, SUM(document_items.subtotal) as total')->groupBy('document_items.description')->orderByDesc('total')->limit(15)->get();

        return [
            'subtitle' => 'Factures émises (hors brouillons et annulations)',
            'kpis' => [
                ['label' => 'Chiffre d’affaires TTC', 'value' => money($total)],
                ['label' => 'Factures', 'value' => (clone $invoices)->count()],
                ['label' => 'Encaissé', 'value' => money((clone $invoices)->sum('amount_paid'))],
                ['label' => 'Reste à encaisser', 'value' => money((clone $invoices)->sum(DB::raw('total - amount_paid')))],
            ],
            'sections' => [
                ['title' => 'Top clients', 'columns' => ['Client', 'Segment', 'Factures', 'CA TTC', 'Impayé'], 'numeric' => [false, false, true, true, true],
                    'rows' => $clients->map(fn ($r) => [$r->name, strtoupper($r->segment), $r->n, money($r->total), money($r->due)])->all()],
                ['title' => 'Top articles et prestations', 'columns' => ['Désignation', 'Quantité', 'Total HT'], 'numeric' => [false, true, true],
                    'rows' => $items->map(fn ($r) => [$r->description, qty($r->qty), money($r->total)])->all()],
            ],
        ];
    }

    private function balances(int $cid, $from, $to)
    {
        return JournalLine::query()->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_entries.company_id', $cid)->whereBetween('journal_entries.entry_date', [$from, $to])
            ->selectRaw('chart_of_accounts.code, chart_of_accounts.name, chart_of_accounts.class, SUM(journal_lines.debit) as debit, SUM(journal_lines.credit) as credit')
            ->groupBy('chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_accounts.class')->orderBy('chart_of_accounts.code')->get();
    }

    private function resultat(int $cid, $from, $to): array
    {
        $rows = $this->balances($cid, $from, $to);
        $revenue = $rows->where('class', '7')->map(fn ($r) => [$r->code, $r->name, money($r->credit - $r->debit)]);
        $charges = $rows->where('class', '6')->map(fn ($r) => [$r->code, $r->name, money($r->debit - $r->credit)]);
        $totalRevenue = $rows->where('class', '7')->sum(fn ($r) => $r->credit - $r->debit);
        $totalCharges = $rows->where('class', '6')->sum(fn ($r) => $r->debit - $r->credit);

        return [
            'subtitle' => 'Selon les écritures comptabilisées (SYSCOHADA)',
            'kpis' => [['label' => 'Produits', 'value' => money($totalRevenue)], ['label' => 'Charges', 'value' => money($totalCharges)], ['label' => 'Résultat', 'value' => money($totalRevenue - $totalCharges)]],
            'sections' => [
                ['title' => 'Produits (classe 7)', 'columns' => ['Compte', 'Libellé', 'Montant'], 'numeric' => [false, false, true], 'rows' => $revenue->push(['_total' => true, '', 'Total des produits', money($totalRevenue)])->values()->all()],
                ['title' => 'Charges (classe 6)', 'columns' => ['Compte', 'Libellé', 'Montant'], 'numeric' => [false, false, true], 'rows' => $charges->push(['_total' => true, '', 'Total des charges', money($totalCharges)])->values()->all()],
            ],
        ];
    }

    private function balance(int $cid, $from, $to): array
    {
        $rows = $this->balances($cid, $from, $to);

        return [
            'subtitle' => 'Toutes classes',
            'orientation' => 'portrait',
            'kpis' => [['label' => 'Total débit', 'value' => money($rows->sum('debit'))], ['label' => 'Total crédit', 'value' => money($rows->sum('credit'))], ['label' => 'Écart', 'value' => money($rows->sum('debit') - $rows->sum('credit'))]],
            'sections' => [[
                'title' => 'Balance générale', 'columns' => ['Compte', 'Libellé', 'Débit', 'Crédit', 'Solde débiteur', 'Solde créditeur'], 'numeric' => [false, false, true, true, true, true],
                'rows' => $rows->map(fn ($r) => [$r->code, $r->name, money($r->debit), money($r->credit), $r->debit > $r->credit ? money($r->debit - $r->credit) : '', $r->credit > $r->debit ? money($r->credit - $r->debit) : ''])->all(),
            ]],
        ];
    }

    private function creances(int $cid, $from, $to): array
    {
        $open = Document::with('contact')->where('company_id', $cid)->where('type', 'invoice')->whereNotIn('status', ['paid', 'cancelled', 'draft'])->get();
        $bucket = fn (Document $d) => match (true) {
            ! $d->due_date || $d->due_date->isFuture() => 'Non échu',
            $d->due_date->diffInDays(now()) <= 30 => '1–30 j',
            $d->due_date->diffInDays(now()) <= 60 => '31–60 j',
            default => '+ 60 j',
        };

        return [
            'subtitle' => 'Situation au '.now()->format('d/m/Y'),
            'kpis' => collect(['Non échu', '1–30 j', '31–60 j', '+ 60 j'])->map(fn ($b) => ['label' => $b, 'value' => money($open->filter(fn ($d) => $bucket($d) === $b)->sum('balance_due'))])->all(),
            'sections' => [[
                'title' => 'Factures ouvertes', 'columns' => ['Facture', 'Client', 'Émise le', 'Échéance', 'Tranche', 'Reste dû'], 'numeric' => [false, false, false, false, false, true],
                'rows' => $open->sortBy('due_date')->map(fn ($d) => [$d->number, $d->contact?->display_name, $d->issue_date->format('d/m/Y'), $d->due_date?->format('d/m/Y') ?? '—', $bucket($d), money($d->balance_due, $d->currency_code)])->values()->all(),
            ]],
        ];
    }

    private function stock(int $cid, $from, $to): array
    {
        $products = Product::with('category')->where('company_id', $cid)->where('track_stock', true)->orderBy('category_id')->orderBy('name')->get();
        $value = $products->sum(fn ($p) => (float) $p->stock_quantity * (float) $p->purchase_price);

        return [
            'subtitle' => 'Valorisation au dernier coût d’achat',
            'kpis' => [['label' => 'Articles suivis', 'value' => $products->count()], ['label' => 'Valeur totale', 'value' => money($value)], ['label' => 'Sous le seuil', 'value' => $products->filter->isLow()->count()]],
            'sections' => [[
                'title' => 'Inventaire valorisé', 'columns' => ['Code', 'Article', 'Catégorie', 'Stock', 'Unité', 'Coût unitaire', 'Valeur'], 'numeric' => [false, false, false, true, false, true, true],
                'rows' => $products->map(fn ($p) => [$p->sku, $p->name, $p->category?->name, qty($p->stock_quantity), $p->unit, money($p->purchase_price), money((float) $p->stock_quantity * (float) $p->purchase_price)])->all(),
            ]],
        ];
    }

    private function paie(int $cid, $from, $to): array
    {
        $slips = Payslip::with('employee.department')->where('company_id', $cid)->whereHas('period', fn ($q) => $q->whereBetween('end_date', [$from, $to])->whereIn('status', ['validated', 'paid']))->get();
        $byDept = $slips->groupBy(fn ($s) => $s->employee->department?->name ?? 'Sans département');

        return [
            'subtitle' => 'Périodes validées ou payées',
            'kpis' => [['label' => 'Masse brute', 'value' => money($slips->sum('gross'))], ['label' => 'Net versé', 'value' => money($slips->sum('net'))], ['label' => 'IPR retenu', 'value' => money($slips->sum('ipr'))], ['label' => 'Charges patronales', 'value' => money($slips->sum('employer_charges'))]],
            'sections' => [[
                'title' => 'Par département', 'columns' => ['Département', 'Bulletins', 'Brut', 'CNSS', 'IPR', 'Net', 'Coût employeur'], 'numeric' => [false, true, true, true, true, true, true],
                'rows' => $byDept->map(fn ($g, $name) => [$name, $g->count(), money($g->sum('gross')), money($g->sum('cnss_employee')), money($g->sum('ipr')), money($g->sum('net')), money($g->sum('gross') + $g->sum('employer_charges'))])->values()->all(),
            ]],
        ];
    }

    private function chantiers(int $cid, $from, $to): array
    {
        $sites = Site::with('manager', 'vouchers')->where('company_id', $cid)->get();

        return [
            'subtitle' => 'Tous chantiers',
            'orientation' => 'landscape',
            'kpis' => [['label' => 'Chantiers', 'value' => $sites->count()], ['label' => 'En cours', 'value' => $sites->where('status', 'in_progress')->count()], ['label' => 'Budget total', 'value' => money($sites->sum('budget'))], ['label' => 'Journaliers affectés', 'value' => Employee::where('company_id', $cid)->where('pay_type', 'daily')->where('status', 'active')->count()]],
            'sections' => [[
                'title' => 'Tableau de bord des chantiers', 'columns' => ['Code', 'Chantier', 'Responsable', 'Statut', 'Budget', 'Main-d’œuvre', 'Bons exécutés', 'Avancement'], 'numeric' => [false, false, false, false, true, true, true, true],
                'rows' => $sites->map(function ($s) use ($from, $to) {
                    $labour = DB::table('attendance_entries')->join('attendance_sheets', 'attendance_sheets.id', '=', 'attendance_entries.attendance_sheet_id')
                        ->where('attendance_sheets.site_id', $s->id)->where('attendance_sheets.status', 'validated')->whereBetween('attendance_sheets.work_date', [$from, $to])
                        ->selectRaw("SUM(CASE WHEN attendance_entries.status IN ('present','late') THEN attendance_entries.daily_rate WHEN attendance_entries.status = 'half' THEN attendance_entries.daily_rate * 0.5 ELSE 0 END) as total")->value('total');

                    return [$s->code, $s->name, $s->manager?->full_name, erp_status('site', $s->status)[0], money($s->budget), money($labour), money($s->vouchers->where('status', 'executed')->sum('amount')), $s->progress.' %'];
                })->all(),
            ]],
        ];
    }
}
