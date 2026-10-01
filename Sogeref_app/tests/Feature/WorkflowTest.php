<?php

namespace Tests\Feature;

use App\Livewire as Screens;
use App\Models\AttendanceSheet;
use App\Models\CashSession;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Employee;
use App\Models\JournalLine;
use App\Models\PayrollPeriod;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Site;
use App\Models\StockLevel;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Warehouse;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Parcours métier de bout en bout, joués à travers les écrans Livewire.
 */
class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function as(string $login): User
    {
        $user = User::where('email', "{$login}@sogeref.cd")->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    public function test_quote_to_delivery_full_order_journey(): void
    {
        $this->as('master');
        $client = Contact::where('company_name', 'Hôtel Fleuve Congo')->first();
        $chair = Product::where('sku', 'CA-MLBN')->first();

        // 1. Devis + ouverture du dossier depuis l'éditeur
        Livewire::test(Screens\Sales\DocumentEditor::class, ['type' => 'quote'])
            ->set('contact_id', $client->id)
            ->set('items.0.product_id', $chair->id)
            ->set('items.0.quantity', 10)
            ->set('orderKind', 'fabrication')
            ->set('orderTitle', 'Chaises du restaurant')
            ->call('save')
            ->assertHasNoErrors();
        $order = SalesOrder::where('title', 'Chaises du restaurant')->firstOrFail();
        $this->assertSame('devis', $order->stage);
        $expected = round(1000 * (1 - (float) $client->discount_rate / 100) * 1.16, 2); // 10 × 100 $ HT, remise négociée, TVA 16 %
        $this->assertEquals($expected, (float) $order->total);

        // 2. Validation, facture, encaissement de l'acompte, atelier, livraison, clôture
        $page = Livewire::test(Screens\Orders\Show::class, ['order' => $order])->call('validateQuote')->call('issueInvoice');
        $order->refresh();
        $this->assertSame('facture', $order->stage);

        $page->call('openPayment')->assertSet('paymentAmount', number_format($expected / 2, 2, '.', ''))->call('recordPayment')->assertHasNoErrors();
        $this->assertSame('paye', $order->fresh()->stage);

        $page->call('openLaunch')->call('launch');
        $order->refresh();
        $this->assertSame('fabrication', $order->stage);
        $mo = $order->manufacturingOrder;
        $this->assertGreaterThan(0, $mo->materials()->count(), 'La liste de matériel est générée depuis la nomenclature.');
        $this->assertEquals(12.0, (float) $mo->materials()->whereHas('product', fn ($q) => $q->where('sku', 'MAT-VEL-RH'))->value('quantity_planned')); // 1,2 m × 10

        Livewire::test(Screens\Operations\ManufacturingShow::class, ['mo' => $mo])->call('start')->call('finish');
        $this->assertSame('done', $mo->fresh()->status);

        $page->call('deliver');
        $this->assertSame('livre', $order->fresh()->stage);
        $page->call('close');
        $this->assertSame('livre', $order->fresh()->stage, 'Clôture refusée tant que le solde n’est pas payé.');
        $page->call('openPayment')->call('recordPayment')->call('close');
        $this->assertSame('cloture', $order->fresh()->stage);
        $this->assertEquals(0.0, $order->fresh()->balanceDue());
    }

    public function test_voucher_approval_circuit_updates_stock_and_cash(): void
    {
        $this->as('chantier');
        $cement = Product::where('sku', 'MAT-CIM-50')->first();
        $site = Site::where('status', 'in_progress')->first();
        $central = Warehouse::where('type', 'central')->first();
        $siteWarehouse = Warehouse::where('site_id', $site->id)->first();
        $level = fn ($w) => (float) StockLevel::where('product_id', $cement->id)->where('warehouse_id', $w->id)->value('quantity');
        $before = $level($central);
        $beforeSite = $level($siteWarehouse);

        Livewire::test(Screens\Stock\Vouchers::class)
            ->call('create', 'sortie')
            ->set('purpose', 'Ciment pour le chaînage')
            ->set('siteId', $site->id)
            ->set('warehouseId', Warehouse::where('type', 'central')->value('id'))
            ->set('lines.0.product_id', $cement->id)
            ->set('lines.0.quantity', 10)
            ->call('saveAndSubmit')
            ->assertHasNoErrors();
        $voucher = Voucher::where('purpose', 'Ciment pour le chaînage')->firstOrFail();
        $this->assertSame('submitted', $voucher->status);

        // Le chef de chantier ne peut pas approuver lui-même.
        Livewire::test(Screens\Stock\Vouchers::class)->call('show', $voucher->id)->call('approve')->assertForbidden();

        $this->as('direction');
        Livewire::test(Screens\Stock\Vouchers::class)->call('show', $voucher->id)->call('approve');
        $this->assertSame('approved', $voucher->fresh()->status);

        $this->as('magasin');
        Livewire::test(Screens\Stock\Vouchers::class)->call('show', $voucher->id)->call('execute');
        $this->assertSame('executed', $voucher->fresh()->status);
        $this->assertEquals($before - 10, $level($central), 'Sortie du dépôt central…');
        $this->assertEquals($beforeSite + 10, $level($siteWarehouse), '…et entrée dans le magasin du chantier.');
    }

    public function test_attendance_feeds_daily_payroll(): void
    {
        $this->as('chantier');
        $site = Site::where('status', 'in_progress')->first();
        $date = now()->subDays(40)->startOfWeek()->addDay()->toDateString();

        $page = Livewire::test(Screens\Operations\Attendance::class)->set('siteId', $site->id)->set('date', $date);
        $sheet = AttendanceSheet::where('site_id', $site->id)->whereDate('work_date', $date)->firstOrFail();
        $entry = $sheet->entries()->whereHas('employee', fn ($q) => $q->where('pay_type', 'daily'))->first();
        $page->call('setStatus', $entry->id, 'half')->call('validateSheet');
        $this->assertSame('validated', $sheet->fresh()->status);

        $this->as('rh');
        $period = PayrollPeriod::create(['company_id' => $site->company_id, 'reference' => 'TEST-1', 'name' => 'Test', 'pay_type' => 'daily', 'site_id' => $site->id, 'start_date' => $date, 'end_date' => $date, 'exchange_rate' => 2847]);
        app(PayrollService::class)->compute($period);
        $slip = $period->payslips()->where('employee_id', $entry->employee_id)->firstOrFail();
        $this->assertEquals(0.5, (float) $slip->days_worked);
        $this->assertEquals(round((float) $entry->daily_rate * 0.5, 2), (float) $slip->gross);
    }

    public function test_monthly_payroll_matches_official_payslip(): void
    {
        $this->as('rh');
        $slip = Employee::where('matricule', 'EMP-006')->first()->payslips()->first();
        $this->assertEquals(1000.0, (float) $slip->gross);
        $this->assertEquals(50.0, (float) $slip->cnss_employee);
        $this->assertEquals(181.63, (float) $slip->ipr);
        $this->assertEquals(768.37, (float) $slip->net);
    }

    public function test_pos_sale_issues_stock_and_posts_accounting(): void
    {
        $this->as('caisse');
        $door = Product::where('sku', 'PO-ISO-83')->first();
        $before = (float) $door->stock_quantity;

        Livewire::test(Screens\Sales\Pos::class)
            ->set('openingAmount', '100')->call('openSession')
            ->call('add', $door->id)->call('add', $door->id)
            ->call('checkout')->set('tendered', '500')->call('pay')
            ->assertSet('cart', []);

        $this->assertEquals($before - 2, (float) $door->fresh()->stock_quantity);
        $this->assertEquals(round(JournalLine::sum('debit'), 2), round(JournalLine::sum('credit'), 2), 'La comptabilité reste équilibrée.');
    }

    public function test_replenishment_generates_purchase_orders(): void
    {
        $this->as('achats');
        Product::where('sku', 'MAT-TOL-BAC')->update(['stock_quantity' => 5]);
        $count = Document::where('type', 'purchase_order')->count();
        Livewire::test(Screens\Stock\Purchases::class)->call('generate');
        $this->assertGreaterThan($count, Document::where('type', 'purchase_order')->count());
    }

    public function test_disabled_module_is_hidden_and_blocked(): void
    {
        $this->as('master');
        Livewire::test(Screens\System\Master::class)->call('toggleModule', 'rentals');
        $this->get(route('rentals'))->assertForbidden();
        $this->get(route('launcher'))->assertOk()->assertDontSee('Location</span>', false);
        Livewire::test(Screens\System\Master::class)->call('toggleModule', 'rentals');
        $this->get(route('rentals'))->assertOk();
        $this->get(route('launcher'))->assertOk()->assertSee('Location</span>', false);
    }

    public function test_cash_session_close_computes_gap(): void
    {
        $this->as('caisse');
        $invoice = Document::where('type', 'invoice')->where('status', 'sent')->first();
        Livewire::test(Screens\Finance\Cash::class)
            ->set('openingAmount', '200')->call('open')
            ->call('startCollect', $invoice->id)->set('amount', '100')->call('collect')
            ->call('prepareClose')->assertSet('countedAmount', number_format(300, 2, '.', ''))
            ->set('countedAmount', '295')->call('close');
        $this->assertEquals(-5.0, round((float) CashSession::latest('id')->first()->closing_amount - (float) CashSession::latest('id')->first()->expected_amount, 2));
        $this->assertEquals(100.0, (float) $invoice->fresh()->amount_paid);
    }
}
