<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\ReceiptPrinter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_personalized_ticket_renders_as_html_and_pdf(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::role('admin')->firstOrFail();
        $order = Order::firstOrFail();

        foreach ([
            'ticket_template' => 'bold',
            'ticket_code_type' => 'qrcode',
            'ticket_header_text' => 'FACTURE DE VENTE',
            'ticket_footer_text' => 'Merci pour votre confiance',
            'ticket_show_fiscal_info' => '1',
            'ticket_show_tax_details' => '1',
            'ticket_show_payment_details' => '1',
            'auto_print_ticket' => '0',
        ] as $key => $value) {
            Setting::updateOrCreate([
                'establishment_id' => $admin->establishment_id,
                'branch_id' => null,
                'group' => 'ticket',
                'key' => $key,
            ], ['value' => $value, 'type' => str_starts_with($key, 'ticket_show_') || $key === 'auto_print_ticket' ? 'boolean' : 'string']);
        }

        $this->actingAs($admin)
            ->get(route('receipt.print', $order))
            ->assertOk()
            ->assertSee('FACTURE DE VENTE')
            ->assertSee('TVA (16% incluse)')
            ->assertSee('CHANGE')
            ->assertSee($order->order_number);

        $pdf = app(ReceiptPrinter::class)->generatePDF($order)->output();
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }
}
