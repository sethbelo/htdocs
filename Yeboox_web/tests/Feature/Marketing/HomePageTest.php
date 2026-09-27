<?php

namespace Tests\Feature\Marketing;

use App\Livewire\Marketing\HomePage;
use App\Models\CommercialRequest;
use App\Models\Partner;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_presents_modules_and_database_pricing(): void
    {
        $this->seed();

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Tous les modules.')
            ->assertSee('Comptabilité SYSCOHADA')
            ->assertSee('Voir l’interface')
            ->assertSee('Essentiel')
            ->assertSee('$19')
            ->assertSee('Qui sommes-nous ?')
            ->assertSee('Vos questions,')
            ->assertSee('Discuter maintenant sur WhatsApp');
    }

    public function test_visitor_can_send_a_demo_request(): void
    {
        $this->seed();
        $plan = SubscriptionPlan::query()->where('slug', 'professional')->firstOrFail();

        Livewire::test(HomePage::class)
            ->set('requestType', 'order')
            ->set('planId', $plan->id)
            ->set('companyName', 'Maison Kivu')
            ->set('contactName', 'Amina Ilunga')
            ->set('email', 'AMINA@EXAMPLE.COM')
            ->set('phone', '+243 810 123 456')
            ->set('country', 'RDC')
            ->set('city', 'Goma')
            ->set('businessType', 'commerce')
            ->set('locationsCount', 2)
            ->set('preferredContact', 'whatsapp')
            ->set('message', 'Nous souhaitons équiper deux boutiques.')
            ->set('consent', true)
            ->call('submitRequest')
            ->assertHasNoErrors()
            ->assertSet('companyName', '')
            ->assertNotSet('confirmationNumber', null);

        $this->assertDatabaseHas('commercial_requests', [
            'request_type' => 'order',
            'subscription_plan_id' => $plan->id,
            'company_name' => 'Maison Kivu',
            'email' => 'amina@example.com',
            'city' => 'Goma',
            'status' => 'new',
            'consent' => true,
        ]);
    }

    public function test_appointment_requires_a_valid_date_and_time(): void
    {
        $this->travelTo(Carbon::parse('2026-09-07 12:00:00'));

        Livewire::test(HomePage::class)
            ->set('requestType', 'appointment')
            ->set('companyName', 'Bistro Congo')
            ->set('contactName', 'Jean Mbuyi')
            ->set('email', 'jean@example.com')
            ->set('phone', '+243810000001')
            ->set('city', 'Kinshasa')
            ->set('consent', true)
            ->set('appointmentDate', '2026-09-06')
            ->set('appointmentTime', '')
            ->call('submitRequest')
            ->assertHasErrors([
                'appointmentDate' => 'after_or_equal',
                'appointmentTime' => 'required_if',
            ]);

        $this->assertDatabaseCount('commercial_requests', 0);
    }

    public function test_visitor_can_request_an_appointment(): void
    {
        $this->travelTo(Carbon::parse('2026-09-07 12:00:00'));

        Livewire::test(HomePage::class)
            ->set('requestType', 'appointment')
            ->set('companyName', 'Bistro Congo')
            ->set('contactName', 'Jean Mbuyi')
            ->set('email', 'jean@example.com')
            ->set('phone', '+243810000001')
            ->set('city', 'Kinshasa')
            ->set('appointmentDate', '2026-09-10')
            ->set('appointmentTime', '14:30')
            ->set('consent', true)
            ->call('submitRequest')
            ->assertHasNoErrors();

        $commercialRequest = CommercialRequest::query()->firstOrFail();

        $this->assertSame('appointment', $commercialRequest->request_type);
        $this->assertSame('2026-09-10', $commercialRequest->appointment_date->toDateString());
        $this->assertSame('14:30', mb_substr((string) $commercialRequest->appointment_time, 0, 5));
        $this->assertSame('Africa/Kinshasa', $commercialRequest->timezone);
    }

    public function test_appointment_action_opens_and_closes_the_offcanvas_panel(): void
    {
        Livewire::test(HomePage::class)
            ->call('chooseRequest', 'appointment')
            ->assertSet('showBookingPanel', true)
            ->assertSet('requestType', 'appointment')
            ->assertSee('Réservez votre échange.')
            ->assertSee('Date souhaitée')
            ->call('closeBookingPanel')
            ->assertSet('showBookingPanel', false);
    }

    public function test_visitor_can_open_and_close_a_module_presentation(): void
    {
        Livewire::test(HomePage::class)
            ->call('showModule', 'pos')
            ->assertSet('selectedModule', 'pos')
            ->assertSee('Vendez sans ralentir')
            ->assertSee('Yeboox Desktop')
            ->assertSee('Commande #1048')
            ->assertSee('Voir ce module en démo')
            ->call('closeModule')
            ->assertSet('selectedModule', null);
    }

    public function test_module_demo_action_replaces_the_modal_with_the_booking_panel(): void
    {
        Livewire::test(HomePage::class)
            ->call('showModule', 'inventory')
            ->call('chooseRequest', 'demo')
            ->assertSet('selectedModule', null)
            ->assertSet('showBookingPanel', true)
            ->assertSet('requestType', 'demo');
    }

    public function test_partner_modal_limits_the_gallery_to_three_images(): void
    {
        $partner = Partner::query()->create([
            'name' => 'Maison Test',
            'slug' => 'maison-test',
            'category' => 'Commerce',
            'description' => 'Un établissement partenaire.',
            'gallery_images' => ['one.png', 'two.png', 'three.png', 'four.png'],
            'initials' => 'MT',
            'is_active' => true,
        ]);

        Livewire::test(HomePage::class)
            ->call('showPartner', $partner->id)
            ->assertSet('selectedPartnerId', $partner->id)
            ->assertSee('Maison Test')
            ->assertSee('three.png', false)
            ->assertDontSee('four.png', false)
            ->call('closePartner')
            ->assertSet('selectedPartnerId', null);
    }
}
