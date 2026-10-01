<?php

namespace Tests\Feature;

use App\Livewire\Crm\Contacts;
use App\Livewire\Crm\Directory;
use App\Livewire\Reception\Desk;
use App\Livewire\Reception\Mail;
use App\Livewire\Reception\Visits;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Document;
use App\Models\MailRecord;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Applications « Réception » (accueil du jour, courrier, visites, rendez-vous)
 * et « Relations clients » (pipeline, contacts, annuaire, devis & pro forma).
 */
class ReceptionRelationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $login): User
    {
        return User::where('email', "{$login}@sogeref.cd")->firstOrFail();
    }

    public function test_screens_render_for_master(): void
    {
        $this->actingAs($this->user('master'));
        foreach (['reception', 'reception.mail', 'reception.visits', 'appointments', 'crm', 'contacts', 'directory', 'quotes', 'loyalty'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('directory', ['vue' => 'interne', 'lettre' => 'K']))->assertOk();
        $this->get(route('contacts', ['nouveau' => 1]))->assertOk()->assertSee('Créer le contact');
        $this->get(route('quotes'))->assertSee('Devis &amp; pro forma', false);
    }

    public function test_receptionist_sees_reception_but_not_pipeline(): void
    {
        $this->actingAs($this->user('accueil'));
        foreach (['reception', 'reception.mail', 'reception.visits', 'appointments', 'directory', 'contacts'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('crm'))->assertForbidden();
        $this->get(route('quotes'))->assertForbidden();
        $this->get(route('reception.mail.print'))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_commercial_sees_relations_clients_but_not_reception_registers(): void
    {
        $this->actingAs($this->user('commercial'));
        foreach (['crm', 'contacts', 'directory', 'quotes', 'loyalty', 'appointments'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('reception.mail'))->assertForbidden();
        $this->get(route('reception.visits'))->assertForbidden();
    }

    public function test_mail_registration_with_attachment(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user('accueil'));

        Livewire::test(Mail::class)
            ->call('newMail', 'in')
            ->set('mail.correspondent', 'Ministère des Finances')
            ->set('mail.subject', 'Invitation à soumissionner')
            ->set('mailFile', UploadedFile::fake()->create('scan.pdf', 120, 'application/pdf'))
            ->call('saveMail')
            ->assertHasNoErrors()
            ->assertSet('showMail', false);

        $record = MailRecord::where('subject', 'Invitation à soumissionner')->firstOrFail();
        $this->assertStringStartsWith('CE', $record->reference);
        $this->assertSame('registered', $record->status);
        $this->assertNotNull($record->file_path);
        Storage::disk('local')->assertExists($record->file_path);

        Livewire::test(Mail::class)->call('advanceMail', $record->id);
        $this->assertSame('forwarded', $record->fresh()->status);

        $this->get(route('reception.mail.file', $record))->assertOk();
        $this->assertStringNotContainsString((string) $record->id.'/scan', route('reception.mail.file', $record));

        // Une pièce non autorisée est refusée.
        Livewire::test(Mail::class)->call('newMail', 'out')->set('mail.correspondent', 'X')->set('mail.subject', 'Y')
            ->set('mailFile', UploadedFile::fake()->create('virus.exe', 10))->call('saveMail')->assertHasErrors('mailFile');

        // Le commercial n'accède pas au scan (module Réception).
        $this->actingAs($this->user('commercial'))->get(route('reception.mail.file', $record))->assertForbidden();
    }

    public function test_visitor_check_in_and_out_and_badge(): void
    {
        $this->actingAs($this->user('accueil'));
        $host = $this->user('commercial');

        Livewire::test(Visits::class)
            ->call('newVisitor')
            ->set('visitor.full_name', 'Jean Mukendi')
            ->set('visitor.organization', 'Bralima')
            ->set('visitor.id_document', 'Passeport OP123456')
            ->set('visitor.purpose', 'Livraison de documents')
            ->set('visitor.host_id', $host->id)
            ->call('checkIn')
            ->assertHasNoErrors();

        $visitor = Visitor::where('full_name', 'Jean Mukendi')->firstOrFail();
        $this->assertSame($host->id, $visitor->host_id);
        $this->assertNull($visitor->checked_out_at);

        $this->get(route('reception.visits.badge', $visitor))->assertOk()->assertHeader('content-type', 'application/pdf');

        Livewire::test(Visits::class)->call('checkOut', $visitor->id);
        $this->assertNotNull($visitor->fresh()->checked_out_at);
    }

    public function test_check_in_from_appointment_creates_visitor(): void
    {
        $this->actingAs($this->user('accueil'));
        $appointment = Appointment::create([
            'title' => 'Visite showroom', 'type' => 'showroom', 'visitor_name' => 'Aline Tshala', 'visitor_phone' => '0812345678',
            'user_id' => $this->user('commercial')->id, 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2), 'status' => 'confirmed',
        ]);

        Livewire::test(Desk::class)->call('newVisitor', $appointment->id)
            ->assertSet('visitor.full_name', 'Aline Tshala')
            ->call('checkIn')->assertHasNoErrors();

        $this->assertDatabaseHas('visitors', ['full_name' => 'Aline Tshala', 'appointment_id' => $appointment->id]);
        $this->assertSame('done', $appointment->fresh()->status);
    }

    public function test_directory_vcard_and_exports(): void
    {
        $this->actingAs($this->user('accueil'));
        $contact = Contact::whereNotNull('phone')->firstOrFail();

        $response = $this->get(route('directory.vcard', $contact))->assertOk();
        $this->assertStringContainsString('text/vcard', $response->headers->get('content-type'));
        $this->assertStringContainsString('BEGIN:VCARD', $response->getContent());
        $this->assertStringContainsString('END:VCARD', $response->getContent());

        $this->get(route('directory.export'))->assertOk()->assertDownload();
        $this->get(route('directory.print', ['type' => 'client']))->assertOk()->assertHeader('content-type', 'application/pdf');

        Livewire::test(Directory::class)->set('letter', 'A')->assertOk()->set('view', 'interne')->assertOk();
    }

    public function test_contact_creation_detects_duplicates(): void
    {
        $this->actingAs($this->user('commercial'));
        $existing = Contact::whereNotNull('phone')->firstOrFail();

        Livewire::test(Contacts::class)
            ->call('edit', null, 'partner')
            ->set('form.segment', 'b2b')
            ->set('form.company_name', 'Partenaire Logistique Sarl')
            ->set('form.phone', $existing->phone)
            ->call('save')
            ->assertSet('showForm', true)
            ->assertNotSet('duplicates', [])
            ->call('saveAnyway')
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('contacts', ['company_name' => 'Partenaire Logistique Sarl', 'type' => 'partner']);

        Livewire::test(Contacts::class)->call('edit')
            ->set('form.segment', 'b2c')->set('form.first_name', 'Grâce')->set('form.last_name', 'Ilunga')->set('form.phone', '+243 990 000 111')
            ->call('save')->assertSet('showForm', false)->assertSet('duplicates', []);
        $this->assertDatabaseHas('contacts', ['last_name' => 'Ilunga', 'type' => 'client']);
    }

    public function test_quote_prints_as_proforma(): void
    {
        $this->actingAs($this->user('commercial'));
        $quote = Document::where('type', 'quote')->firstOrFail();

        $this->get(route('pdf.document', ['document' => $quote, 'format' => 'proforma']))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('pdf.document', $quote))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('documents.show', $quote))->assertOk()->assertSee('Imprimer en pro forma');

        $invoice = Document::where('type', 'invoice')->first();
        if ($invoice) {
            $this->actingAs($this->user('master'))->get(route('pdf.document', $invoice))->assertOk()->assertHeader('content-type', 'application/pdf');
        }
    }
}
