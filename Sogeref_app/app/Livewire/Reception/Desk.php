<?php

namespace App\Livewire\Reception;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Department;
use App\Models\MailRecord;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\User;
use App\Models\Visitor;
use App\Services\SequenceService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Module Réception : accueil des visiteurs (badges, entrées / sorties),
 * rendez-vous du jour, registre du courrier et prise de demandes clients.
 */
#[Title('Réception')]
class Desk extends Component
{
    use InteractsWithUi;

    #[Url(as: 'onglet')]
    public string $tab = 'accueil';

    // Visiteur
    public bool $showVisitor = false;

    public array $visitor = [];

    // Courrier
    public bool $showMail = false;

    public array $mail = [];

    // Demande client (lead)
    public bool $showLead = false;

    public array $lead = [];

    public function newVisitor(?int $appointmentId = null): void
    {
        $this->authorizeModule('reception', 'manage');
        $appointment = $appointmentId ? Appointment::with('contact')->where('company_id', company_id())->find($appointmentId) : null;
        $this->visitor = [
            'full_name' => $appointment?->contact?->display_name ?? $appointment?->visitor_name ?? '', 'organization' => $appointment?->contact?->company_name ?? '',
            'phone' => $appointment?->contact?->phone ?? '', 'id_document' => '', 'purpose' => $appointment?->title ?? '', 'host_id' => $appointment?->user_id,
            'appointment_id' => $appointment?->id, 'badge_number' => 'V-'.str_pad((string) (Visitor::where('company_id', company_id())->whereDate('checked_in_at', today())->count() + 1), 2, '0', STR_PAD_LEFT),
        ];
        $this->showVisitor = true;
    }

    public function checkIn(): void
    {
        $this->authorizeModule('reception', 'manage');
        $data = $this->validate([
            'visitor.full_name' => ['required', 'string', 'max:120'], 'visitor.organization' => ['nullable', 'string', 'max:120'], 'visitor.phone' => ['nullable', 'string', 'max:40'],
            'visitor.id_document' => ['nullable', 'string', 'max:80'], 'visitor.purpose' => ['required', 'string', 'max:255'], 'visitor.host_id' => ['nullable', 'integer'],
            'visitor.appointment_id' => ['nullable', 'integer'], 'visitor.badge_number' => ['nullable', 'string', 'max:20'],
        ], [], ['visitor.full_name' => 'nom', 'visitor.purpose' => 'objet de la visite'])['visitor'];
        DB::transaction(function () use ($data) {
            Visitor::create($data + ['company_id' => company_id(), 'checked_in_at' => now()]);
            if ($data['appointment_id']) {
                Appointment::whereKey($data['appointment_id'])->update(['status' => 'done']);
            }
        });
        $this->showVisitor = false;
        $this->toast("Arrivée de {$data['full_name']} enregistrée — badge {$data['badge_number']}.");
    }

    public function checkOut(int $id): void
    {
        $this->authorizeModule('reception', 'manage');
        Visitor::where('company_id', company_id())->whereKey($id)->whereNull('checked_out_at')->update(['checked_out_at' => now()]);
        $this->toast('Départ enregistré.');
    }

    public function newMail(string $direction = 'in'): void
    {
        $this->authorizeModule('reception', 'manage');
        $this->mail = ['direction' => $direction, 'record_date' => now()->toDateString(), 'correspondent' => '', 'subject' => '', 'channel' => 'physique', 'department_id' => null, 'handled_by' => null];
        $this->showMail = true;
    }

    public function saveMail(SequenceService $sequences): void
    {
        $this->authorizeModule('reception', 'manage');
        $data = $this->validate([
            'mail.direction' => ['required', 'in:in,out'], 'mail.record_date' => ['required', 'date'], 'mail.correspondent' => ['required', 'string', 'max:160'],
            'mail.subject' => ['required', 'string', 'max:255'], 'mail.channel' => ['required', 'in:physique,email,coursier'], 'mail.department_id' => ['nullable', 'integer'], 'mail.handled_by' => ['nullable', 'integer'],
        ], [], ['mail.correspondent' => 'correspondant', 'mail.subject' => 'objet'])['mail'];
        $record = MailRecord::create($data + ['company_id' => company_id(), 'reference' => $sequences->next($data['direction'] === 'in' ? 'mail_in' : 'mail_out'), 'status' => $data['handled_by'] ? 'forwarded' : 'registered']);
        $this->showMail = false;
        $this->toast("Courrier {$record->reference} enregistré.");
    }

    public function mailStatus(int $id, string $status): void
    {
        $this->authorizeModule('reception', 'manage');
        abort_unless(array_key_exists($status, config('erp.statuses.mail')), 422);
        MailRecord::where('company_id', company_id())->whereKey($id)->update(['status' => $status]);
    }

    public function newLead(): void
    {
        $this->authorizeModule('reception', 'manage');
        $this->lead = ['name' => '', 'company' => '', 'phone' => '', 'email' => '', 'need' => '', 'amount' => '', 'segment' => 'b2c'];
        $this->showLead = true;
    }

    public function saveLead(): void
    {
        $this->authorizeModule('reception', 'manage');
        $data = $this->validate(['lead.name' => ['required', 'string', 'max:120'], 'lead.company' => ['nullable', 'string', 'max:160'], 'lead.phone' => ['required', 'string', 'max:40'], 'lead.email' => ['nullable', 'email'], 'lead.need' => ['required', 'string', 'max:255'], 'lead.amount' => ['nullable', 'numeric', 'min:0'], 'lead.segment' => ['in:b2b,b2c']], [], ['lead.name' => 'nom', 'lead.phone' => 'téléphone', 'lead.need' => 'besoin'])['lead'];
        DB::transaction(function () use ($data) {
            [$first, $last] = array_pad(explode(' ', $data['name'], 2), 2, null);
            $contact = Contact::create([
                'company_id' => company_id(), 'type' => 'prospect', 'segment' => $data['segment'], 'reference' => 'PRS-'.now()->format('ymd').'-'.random_int(100, 999),
                'company_name' => $data['company'] ?: null, 'first_name' => $first, 'last_name' => $last, 'phone' => $data['phone'], 'email' => $data['email'] ?: null, 'currency_code' => 'USD',
            ]);
            $pipeline = Pipeline::where('company_id', company_id())->where('is_default', true)->first();
            if ($pipeline) {
                Opportunity::create(['company_id' => company_id(), 'contact_id' => $contact->id, 'pipeline_id' => $pipeline->id, 'stage_id' => $pipeline->stages()->orderBy('position')->value('id'), 'title' => $data['need'], 'amount' => $data['amount'] ?: 0, 'currency_code' => 'USD', 'notes' => 'Demande reçue à l’accueil par '.auth()->user()->name]);
            }
        });
        $this->showLead = false;
        $this->toast('Demande transmise au service commercial (pipeline CRM).');
    }

    public function render()
    {
        $cid = company_id();

        return view('livewire.reception.desk', [
            'present' => Visitor::with('host')->where('company_id', $cid)->whereDate('checked_in_at', today())->whereNull('checked_out_at')->latest('checked_in_at')->get(),
            'todayVisitors' => Visitor::with('host')->where('company_id', $cid)->whereDate('checked_in_at', today())->latest('checked_in_at')->get(),
            'appointments' => Appointment::with('contact', 'user')->where('company_id', $cid)->whereDate('starts_at', today())->orderBy('starts_at')->get(),
            'mails' => MailRecord::with('department', 'handler')->where('company_id', $cid)->latest('record_date')->latest('id')->limit(40)->get(),
            'hosts' => User::whereHas('companies', fn ($q) => $q->where('companies.id', $cid))->where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('company_id', $cid)->orderBy('name')->get(),
        ]);
    }
}
