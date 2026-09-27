<?php

namespace Tests\Feature;

use App\Livewire\Admin\Customers\Index;
use App\Models\Customer;
use App\Models\LoyaltyCard;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerLoyaltyCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_issue_read_and_export_loyalty_cards(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::role('admin')->firstOrFail();
        $this->actingAs($admin);
        [$simpleCustomer, $nfcCustomer] = Customer::take(2)->get();

        Livewire::test(Index::class)
            ->call('setViewMode', 'grid')
            ->assertSet('viewMode', 'grid')
            ->call('openCardPanel', $simpleCustomer->id)
            ->set('card_type', 'simple')
            ->call('saveLoyaltyCard')
            ->assertHasNoErrors();

        $simpleCard = LoyaltyCard::where('customer_id', $simpleCustomer->id)->firstOrFail();
        $this->assertSame('simple', $simpleCard->type);
        $this->assertNull($simpleCard->nfc_uid);

        Livewire::test(Index::class)
            ->call('openCardPanel', $nfcCustomer->id)
            ->set('card_type', 'nfc')
            ->call('nfcCardWritten', '04A1B2C3D4', 'ACS ACR122U')
            ->call('saveLoyaltyCard')
            ->assertHasNoErrors();

        $nfcCard = LoyaltyCard::where('customer_id', $nfcCustomer->id)->firstOrFail();
        $this->assertSame('nfc', $nfcCard->type);
        $this->assertSame('04A1B2C3D4', $nfcCard->nfc_uid);

        Livewire::test(Index::class)
            ->call('openCardPanel')
            ->assertSet('cardPanelMode', 'read')
            ->call('nfcCardRead', $nfcCard->nfc_uid, '')
            ->assertSet('detected_customer_name', $nfcCustomer->full_name)
            ->assertHasNoErrors();

        $pdf = $this->get(route('admin.customers.export.pdf'));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }
}
