<?php

namespace Tests\Feature;

use App\Models\AttendanceSheet;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Employee;
use App\Models\ManufacturingOrder;
use App\Models\PayrollPeriod;
use App\Models\Project;
use App\Models\SalesOrder;
use App\Models\Site;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chaque écran et chaque impression PDF s'ouvre sans erreur (compte Master).
 */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function master(): User
    {
        return User::where('email', 'master@sogeref.cd')->firstOrFail();
    }

    public function test_every_screen_renders_for_master(): void
    {
        $this->actingAs($this->master());

        $urls = [
            route('dashboard'), route('orders.index'), route('orders.index', ['vue' => 'liste']), route('orders.show', SalesOrder::first()),
            route('reception'), route('crm'), route('contacts'), route('contacts.show', Contact::first()), route('loyalty'),
            route('quotes'), route('quotes.create'), route('invoices'), route('invoices.create'), route('documents.show', Document::first()), route('documents.edit', Document::where('type', 'quote')->where('status', 'draft')->first()),
            route('pos'), route('rentals'),
            route('manufacturing'), route('manufacturing.boms'), route('manufacturing.show', ManufacturingOrder::first()),
            route('sites'), route('sites.show', Site::first()), route('attendance'),
            route('projects'), route('projects.show', Project::first()), route('planning'), route('appointments'), route('timesheets'),
            route('inventory'), route('purchases'), route('purchases.create'), route('vouchers'),
            route('cash'), route('expense-claims'), route('accounting'), route('banking'), route('reports'),
            route('employees'), route('employees.show', Employee::first()), route('leaves'), route('payroll'), route('payroll.show', PayrollPeriod::first()),
            route('chat'), route('system'),
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_site_tabs_and_pdfs_render(): void
    {
        $this->actingAs($this->master());
        $site = Site::first();
        foreach (['apercu', 'equipe', 'presences', 'materiel', 'rapports', 'bons', 'paie', 'evaluation'] as $tab) {
            $this->get(route('sites.show', ['site' => $site, 'onglet' => $tab]))->assertOk();
        }

        foreach ([
            route('pdf.document', Document::first()), route('pdf.order', SalesOrder::first()), route('pdf.manufacturing', ManufacturingOrder::first()),
            route('pdf.site', $site), route('pdf.attendance', AttendanceSheet::first()), route('pdf.payroll', PayrollPeriod::first()),
            route('pdf.report', 'ventes'), route('pdf.letterhead'),
        ] as $url) {
            $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
        }
    }

    public function test_every_tab_renders(): void
    {
        $this->actingAs($this->master());
        $tabs = [
            'system' => ['societe', 'documents', 'modules', 'numerotation', 'regles', 'utilisateurs', 'roles', 'audit', 'systeme'],
            'inventory' => ['articles', 'niveaux', 'mouvements', 'entrepots', 'materiel', 'inventaires'],
            'accounting' => ['journal', 'grand-livre', 'balance', 'plan'],
            'cash' => ['encaisser', 'decaisser', 'mouvements'],
            'purchases' => ['reappro', 'commandes'],
            'reception' => ['accueil', 'visiteurs', 'courrier'],
        ];
        foreach ($tabs as $route => $list) {
            foreach ($list as $tab) {
                $this->get(route($route, ['onglet' => $tab]))->assertOk();
            }
        }
        foreach (array_keys(ReportService::REPORTS) as $report) {
            $this->get(route('reports', ['rapport' => $report]))->assertOk();
        }
        foreach (['infos', 'presences', 'conges', 'paie', 'avances', 'chantiers'] as $tab) {
            $this->get(route('employees.show', ['employee' => Employee::first(), 'onglet' => $tab]))->assertOk();
        }
        foreach (['materiel', 'operations', 'specifications', 'bons'] as $tab) {
            $this->get(route('manufacturing.show', ['mo' => ManufacturingOrder::first(), 'onglet' => $tab]))->assertOk();
        }
        foreach (SalesOrder::all() as $order) {
            $this->get(route('orders.show', $order))->assertOk();
        }
    }

    public function test_roles_only_see_their_modules(): void
    {
        $cashier = User::where('email', 'caisse@sogeref.cd')->first();
        $this->actingAs($cashier)->get(route('cash'))->assertOk();
        $this->actingAs($cashier)->get(route('payroll'))->assertForbidden();
        $this->actingAs($cashier)->get(route('system'))->assertForbidden();

        $hr = User::where('email', 'rh@sogeref.cd')->first();
        $this->actingAs($hr)->get(route('payroll'))->assertOk();
        $this->actingAs($hr)->get(route('cash'))->assertForbidden();
    }
}
