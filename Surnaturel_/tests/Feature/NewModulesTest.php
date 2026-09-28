<?php

namespace Tests\Feature;

use App\Enums\AffiliationStatus;
use App\Enums\StockOperationReason;
use App\Exceptions\BusinessRuleException;
use App\Livewire\Finance\Accounting;
use App\Livewire\Hr\Cards;
use App\Livewire\Inventory\LiveReport;
use App\Livewire\Inventory\Reports;
use App\Livewire\Marketing\FollowUps;
use App\Livewire\Settings\Documents;
use App\Models\Affiliation;
use App\Models\Client;
use App\Models\DesignJob;
use App\Models\Employee;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Hr\AffiliationService;
use App\Services\Hr\EmployeeCards;
use App\Services\Inventory\InventoryReports;
use App\Services\Inventory\StockOperationService;
use App\Services\Marketing\FollowUpService;
use App\Support\DocumentSignatures;
use App\Support\RouteKey;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NewModulesTest extends TestCase
{
    use RefreshDatabase;

    private User $director;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->detectEnvironment(fn () => 'local');
        $this->seed(DatabaseSeeder::class);
        $this->director = User::query()->where('username', 'directeur.general')->firstOrFail();
    }

    public function test_urls_carry_encrypted_identifiers_that_cannot_be_tampered_with(): void
    {
        $this->actingAs($this->director);
        $order = Order::query()->firstOrFail();
        $key = $order->getRouteKey();

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{22}$/', $key);
        $this->assertStringNotContainsString('/'.$order->id, route('orders.show', $order));
        $this->assertSame($order->id, RouteKey::decode(Order::class, $key));
        $this->assertNull(RouteKey::decode(Client::class, $key), 'Une clé ne doit pas être réutilisable pour un autre modèle.');

        $this->get(route('orders.show', $order))->assertOk();
        $this->get('/commandes/'.$order->id)->assertNotFound();
        $this->get('/commandes/'.substr($key, 0, -1).($key[-1] === 'A' ? 'B' : 'A'))->assertNotFound();
        $this->get('/imprimer/commande/'.$order->id.'/ticket')->assertNotFound();
    }

    public function test_marketing_agent_logs_calls_and_whatsapp_messages_and_exports_a_report(): void
    {
        $agent = User::query()->where('username', 'agent.marketing')->firstOrFail();
        $client = Client::query()->whereNotNull('phone')->firstOrFail();
        $client->update(['phone' => '0812345678', 'whatsapp' => null]);

        $this->assertSame('243812345678', $client->fresh()->whatsappNumber());
        $this->assertStringStartsWith('https://wa.me/243812345678?text=Bonjour', app(FollowUpService::class)->whatsappUrl($client->fresh(), 'Bonjour'));

        $before = $client->interactions()->where('user_id', $agent->id)->count();
        $this->actingAs($agent)->get(route('followups.index'))->assertOk();
        Livewire::actingAs($agent)->test(FollowUps::class)
            ->call('startCall', $client->id)
            ->set('log.outcome', 'callback')->set('log.notes', 'Rappeler pour le devis')->set('log.next_follow_up_at', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('record')->assertHasNoErrors()
            ->call('startWhatsApp', $client->id)->assertSet('log.channel', 'whatsapp')
            ->call('sendWhatsApp')
            ->set('tab', 'report')->call('exportPdf')->assertFileDownloaded();

        $this->assertSame($before + 2, $client->interactions()->where('user_id', $agent->id)->count());
        $this->assertTrue($client->fresh()->next_follow_up_at->isTomorrow());

        $this->expectException(BusinessRuleException::class);
        app(FollowUpService::class)->record($client, $agent, ['channel' => 'call', 'outcome' => 'callback']);
    }

    public function test_hr_affiliates_agents_and_families_and_issues_care_vouchers(): void
    {
        $hr = User::query()->where('username', 'responsable.rh')->firstOrFail();
        $this->actingAs($hr);
        $service = app(AffiliationService::class);
        $affiliation = Affiliation::query()->whereHas('policy', fn ($q) => $q->where('type', 'medical'))->whereNotNull('employee_dependent_id')->firstOrFail();

        $voucher = $service->issueCareVoucher($affiliation, $hr, ['care_provider' => 'Clinique Ngaliema', 'care_type' => 'pharmacy', 'estimated_amount' => 20]);
        $this->assertStringStartsWith('PEC-', $voucher->number);

        foreach ([route('print.affiliation', $affiliation), route('print.member-card', $affiliation), route('print.care-voucher', $voucher)] as $url) {
            $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
        }
        $this->get(route('hr.affiliations'))->assertOk();

        $service->setStatus($affiliation, AffiliationStatus::Suspended);
        $this->expectException(BusinessRuleException::class);
        $service->issueCareVoucher($affiliation->fresh('policy'), $hr, ['care_provider' => 'X', 'care_type' => 'consultation']);
    }

    public function test_hr_prints_cards_with_a_signed_verification_qr(): void
    {
        $hr = User::query()->where('username', 'responsable.rh')->firstOrFail();
        $employee = Employee::query()->firstOrFail();

        foreach (['service', 'badge', 'business'] as $kind) {
            foreach (['single', 'sheet'] as $layout) {
                $pdf = app(EmployeeCards::class)->render(collect([$employee]), $kind, $layout)->output();
                $this->assertStringStartsWith('%PDF', $pdf, "{$kind} / {$layout}");
            }
        }
        $this->assertTrue($employee->fresh()->card_expires_at->isFuture());

        Livewire::actingAs($hr)->test(Cards::class)->set('selected', [$employee->id])->call('generate')->assertFileDownloaded();

        $url = app(EmployeeCards::class)->verificationUrl($employee->fresh());
        $this->get($url)->assertOk()->assertSee($employee->fullName())->assertSee('Carte valide');
        $this->get(route('employees.verify', $employee))->assertForbidden();
    }

    public function test_warehouse_live_report_tracks_who_recorded_and_performed_each_movement(): void
    {
        $storekeeper = User::query()->where('username', 'magasinier')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'ENT-CEN')->firstOrFail();
        app(StockOperationService::class)->record($warehouse, StockOperationReason::InternalUse, ['performed_by_name' => 'Chauffeur Kalala'], [['product_id' => Product::query()->where('sku', 'PAP-A4-80')->value('id'), 'quantity' => 2]], $storekeeper);

        Livewire::actingAs($storekeeper)->test(LiveReport::class)->set('warehouseId', $warehouse->id)
            ->assertSee('Chauffeur Kalala')->assertSee($storekeeper->name)
            ->call('exportPdf')->assertFileDownloaded()
            ->call('exportExcel')->assertFileDownloaded();
    }

    public function test_document_signatories_are_configurable_and_new_documents_render(): void
    {
        $this->actingAs($this->director);
        Livewire::test(Documents::class)->set('signatures.voucher', "Caissier principal\nDAF\nDG")->call('save')->assertHasNoErrors();
        $this->assertSame(['Caissier principal', 'DAF', 'DG'], DocumentSignatures::for('voucher'));

        $job = DesignJob::query()->firstOrFail();
        $this->get(route('design.notebook', $job))->assertOk();
        $this->get(route('print.design-brief', $job))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('direction.payroll'))->assertOk();
        $this->get(route('hr.cards'))->assertOk();
    }

    public function test_accounting_dashboard_and_every_tab_export_to_pdf_and_excel(): void
    {
        $accountant = User::query()->where('username', 'comptable')->firstOrFail();
        $this->actingAs($accountant)->get(route('accounting.index'))->assertOk()->assertSee('Matrice comptes de gestion');

        $component = Livewire::actingAs($accountant)->test(Accounting::class)->call('applyPreset', 'year');
        $this->assertGreaterThan(0, $component->instance()->analytics['kpis']['revenue']['value']);

        foreach (['dashboard', 'journal', 'balance', 'income', 'sheet', 'chart'] as $tab) {
            $component->set('tab', $tab)->call('exportPdf')->assertFileDownloaded()->call('exportExcel')->assertFileDownloaded();
        }
    }

    public function test_warehouse_reports_render_and_export(): void
    {
        $manager = User::query()->where('username', 'responsable.entrepot')->firstOrFail();
        $this->actingAs($manager)->get(route('inventory.reports'))->assertOk();

        $component = Livewire::actingAs($manager)->test(Reports::class)->call('applyPreset', 'year');
        foreach (array_keys(InventoryReports::REPORTS) as $report) {
            $component->set('report', $report)->assertSee(InventoryReports::REPORTS[$report]['label'])
                ->call('exportPdf')->assertFileDownloaded()
                ->call('exportExcel')->assertFileDownloaded();
        }

        $movements = app(InventoryReports::class)->build('movements', $manager, null, today()->startOfYear()->toDateString(), today()->toDateString());
        foreach (array_slice($movements['rows'], 0, -1) as $row) {
            $this->assertEqualsWithDelta($row[3] + $row[4] - $row[5], $row[6], 0.001, "Stock incohérent pour {$row[1]}");
        }
    }
}
