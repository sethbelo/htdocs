<?php

namespace Tests\Feature;

use App\Livewire\Invoices;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoicesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_invoice_workflow_is_available_from_the_page(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['name' => 'Client Facture', 'phone' => '+243810000100', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Prestation facturée', 'sku' => 'INV-TEST', 'category' => 'Service',
            'type' => 'service', 'price' => 1000, 'cost' => 100, 'stock_qty' => 0,
            'alert_qty' => 0, 'is_active' => true,
        ]);

        $component = Livewire::actingAs($user)->test(Invoices::class)
            ->call('openCreateModal')
            ->set('customer_id', $customer->id)
            ->set('items.0.product_id', $product->id)
            ->call('selectProductForItem', 0, $product->id)
            ->set('items.0.quantity', 1)
            ->set('tax_percent', 16)
            ->set('status', 'draft')
            ->call('saveInvoice')
            ->assertHasNoErrors();

        $invoice = Invoice::firstOrFail();
        $this->assertSame('draft', $invoice->status);

        $component->call('postInvoice', $invoice->id);
        $this->assertNotNull($invoice->fresh()->posted_at);

        $component->call('openPaymentModal', $invoice->id)
            ->set('paymentAmount', 500)
            ->call('recordPayment')
            ->assertHasNoErrors();
        $this->assertEqualsWithDelta(500, (float) $invoice->fresh()->paid_amount, 0.001);

        $component->call('openCreditModal', $invoice->id)
            ->set('creditSubtotal', 600)
            ->set('creditTaxAmount', 60)
            ->set('creditReason', 'Ajustement commercial')
            ->call('createCreditNote')
            ->assertHasNoErrors();
        $this->assertEqualsWithDelta(0, $invoice->fresh()->balance_due, 0.001);

        $component->call('duplicateInvoice', $invoice->id);
        $this->assertDatabaseCount('invoices', 2);
        $this->assertSame('draft', Invoice::latest('id')->first()->status);
    }

    public function test_selecting_a_tax_included_product_uses_its_net_price_on_the_invoice(): void
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Produit TTC', 'sku' => 'INV-TTC', 'category' => 'Service',
            'type' => 'service', 'price' => 116, 'sales_tax_rate' => 16,
            'tax_included' => true, 'cost' => 0, 'stock_qty' => 0, 'is_active' => true,
        ]);

        Livewire::actingAs($user)->test(Invoices::class)
            ->call('openCreateModal')
            ->call('selectProductForItem', 0, $product->id)
            ->assertSet('items.0.unit_price', 100.0)
            ->assertSet('tax_percent', 16.0);
    }
}
