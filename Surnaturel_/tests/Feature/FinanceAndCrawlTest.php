<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Enums\VoucherStatus;
use App\Exceptions\BusinessRuleException;
use App\Livewire\Finance\Invoices;
use App\Livewire\Finance\Vouchers;
use App\Livewire\ImportData;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\TreasuryAccount;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Warehouse;
use App\Services\Finance\ReceivablesService;
use App\Services\Import\DataImporter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class FinanceAndCrawlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->detectEnvironment(fn () => 'local');
        $this->seed(DatabaseSeeder::class);
    }

    private function creditInvoice(float $total = 116): Invoice
    {
        $client = Client::query()->firstOrFail();
        $invoice = Invoice::query()->create([
            'number' => 'FAC-TEST-'.uniqid(), 'type' => InvoiceType::Invoice, 'client_id' => $client->id, 'site_id' => $client->site_id,
            'status' => InvoiceStatus::Issued, 'issued_at' => now(), 'due_at' => now()->addDays(30),
            'subtotal' => $total / 1.16, 'discount_total' => 0, 'tax_total' => round($total - $total / 1.16, 2), 'total' => $total, 'amount_paid' => 0,
        ]);

        return $invoice;
    }

    public function test_receivables_collect_credit_note_and_cancel_keep_the_books_balanced(): void
    {
        $finance = User::query()->where('username', 'responsable.financier')->firstOrFail();
        $this->actingAs($finance);
        $service = app(ReceivablesService::class);
        $bank = TreasuryAccount::query()->where('type', 'bank')->firstOrFail();
        $balanceBefore = (float) $bank->balance;

        $invoice = $this->creditInvoice();
        $service->collect($invoice, $bank, PaymentMethod::BankTransfer, 50, $finance, 'VIR-001');
        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->fresh()->status);
        $this->assertEqualsWithDelta($balanceBefore + 50, (float) $bank->fresh()->balance, 0.001);

        $credit = $service->creditNote($invoice->fresh(), 66, 'Remise commerciale', $finance);
        $this->assertSame(InvoiceType::CreditNote, $credit->type);
        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);

        try {
            $service->collect($invoice->fresh(), $bank, PaymentMethod::Cash, 1, $finance);
            $this->fail('Une facture soldée ne peut plus être encaissée.');
        } catch (BusinessRuleException) {
            $this->addToAssertionCount(1);
        }

        $second = $this->creditInvoice(58);
        $service->cancel($second, 'Doublon', $finance);
        $this->assertSame(InvoiceStatus::Cancelled, $second->fresh()->status);

        $this->assertEqualsWithDelta((float) JournalEntryLine::query()->sum('debit'), (float) JournalEntryLine::query()->sum('credit'), 0.01, 'Le grand livre doit rester équilibré.');

        Livewire::test(Invoices::class)->assertOk()
            ->call('openAction', $this->creditInvoice()->id, 'collect')
            ->set('action.treasury_account_id', $bank->id)->set('action.amount', '10')
            ->call('runAction')->assertHasNoErrors();
    }

    public function test_approved_disbursement_voucher_is_paid_from_treasury(): void
    {
        $finance = User::query()->where('username', 'responsable.financier')->firstOrFail();
        $this->actingAs($finance);
        $cash = TreasuryAccount::query()->where('type', 'cash')->where('balance', '>', 20)->firstOrFail();
        $voucher = Voucher::query()->create(['number' => 'BDC-TEST-1', 'type' => 'disbursement_voucher', 'status' => VoucherStatus::Approved, 'amount' => 20, 'beneficiary' => 'Transport', 'description' => 'Course livraison', 'created_by' => $finance->id, 'site_id' => $cash->site_id ?? $finance->default_site_id]);
        $before = (float) $cash->balance;

        Livewire::test(Vouchers::class)
            ->call('openExecute', $voucher->id)
            ->set('execution.treasury_account_id', $cash->id)->set('execution.expense_account', '618')
            ->call('execute')->assertHasNoErrors();

        $this->assertSame(VoucherStatus::Executed, $voucher->fresh()->status);
        $this->assertEqualsWithDelta($before - 20, (float) $cash->fresh()->balance, 0.001);
    }

    public function test_data_import_creates_and_updates_records(): void
    {
        $admin = User::query()->where('username', 'directeur.general')->firstOrFail();
        $path = tempnam(sys_get_temp_dir(), 'imp').'.csv';
        file_put_contents($path, "reference,nom,type,categorie,unite,prix_achat,prix_vente,stock_min,vente_caisse\nIMP-001,Papier kraft 90 g,papier,Papiers & supports,rame,4,6.5,5,oui\nPAP-A4-80,Rame papier A4 80 g (maj),papier,,,3.6,5.2,10,non\n,Sans référence,papier,,,,,,\n");

        $importer = app(DataImporter::class);
        $rows = $importer->read('products', $path);
        $this->assertCount(3, $rows);
        $this->assertCount(1, $importer->validate('products', $rows));

        $result = $importer->import('products', $rows, $admin);
        $this->assertSame(['created' => 1, 'updated' => 1, 'skipped' => 1], $result);
        $this->assertTrue(Product::query()->where('sku', 'IMP-001')->where('is_sellable_pos', true)->exists());
        $this->assertSame('Rame papier A4 80 g (maj)', Product::query()->where('sku', 'PAP-A4-80')->value('name'));

        $stock = tempnam(sys_get_temp_dir(), 'inv').'.csv';
        file_put_contents($stock, "reference,entrepot,quantite\nIMP-001,ENT-CEN,40\n");
        $importer->import('stock', $importer->read('stock', $stock), $admin);
        $this->assertEquals(40, Product::query()->where('sku', 'IMP-001')->first()->stockIn(Warehouse::query()->where('code', 'ENT-CEN')->first()));

        Livewire::actingAs($admin)->test(ImportData::class, ['type' => 'clients'])
            ->call('downloadTemplate')->assertFileDownloaded('modele-import-clients.xlsx')
            ->set('file', UploadedFile::fake()->createWithContent('clients.csv', "nom,telephone,type\nClient importé,+243 899 000 111,entreprise\n"))
            ->assertSet('rowErrors', [])
            ->call('import')->assertSet('done', true);
        $this->assertTrue(Client::query()->where('phone', '+243 899 000 111')->exists());
    }

    public function test_every_role_can_open_every_screen_it_is_allowed_to_see(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => in_array('GET', $route->methods(), true) && in_array('password.changed', $route->gatherMiddleware(), true))
            ->reject(fn ($route) => str_contains($route->uri(), '{') || $route->getName() === 'home');

        foreach (array_keys(config('erp.roles')) as $role) {
            $user = User::query()->where('username', str_replace('-', '.', $role))->firstOrFail();
            $this->actingAs($user);
            $this->get(route('home'))->assertRedirect();

            foreach ($routes as $route) {
                $can = collect($route->gatherMiddleware())->filter(fn ($m) => str_starts_with($m, 'can:'))->map(fn ($m) => substr($m, 4));
                $allowed = $can->every(fn ($permission) => $user->can($permission));
                $response = $this->get('/'.ltrim($route->uri(), '/'));

                if ($allowed) {
                    $this->assertSame(200, $response->status(), "Rôle {$role} : l’écran {$route->getName()} renvoie {$response->status()} ".($response->exception?->getMessage() ?? ''));
                } else {
                    $this->assertSame(403, $response->status(), "Rôle {$role} : l’écran {$route->getName()} devrait être interdit.");
                }
            }
        }
    }
}
