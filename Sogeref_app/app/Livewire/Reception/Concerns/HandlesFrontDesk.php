<?php

namespace App\Livewire\Reception\Concerns;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\MailRecord;
use App\Models\User;
use App\Models\Visitor;
use App\Services\SequenceService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

/**
 * Actions de la réception partagées par l'Accueil du jour, le registre
 * des visites et le registre du courrier : arrivée / départ des visiteurs
 * (y compris depuis un rendez-vous) et enregistrement du courrier avec scan.
 *
 * Requiert InteractsWithUi. Vues : livewire.reception.partials.*
 */
trait HandlesFrontDesk
{
    use WithFileUploads;

    // Visiteur
    public bool $showVisitor = false;

    public array $visitor = [];

    // Courrier
    public bool $showMail = false;

    public ?int $editingMail = null;

    public array $mail = [];

    /** Scan du courrier (PDF ou image) */
    public $mailFile = null;

    public const MAIL_FLOW = ['registered' => 'forwarded', 'forwarded' => 'processed', 'processed' => 'archived'];

    // ── Visiteurs ───────────────────────────────────────────────────────────
    public function newVisitor(?int $appointmentId = null): void
    {
        $this->authorizeModule('reception', 'manage');
        $appointment = $appointmentId ? Appointment::with('contact')->where('company_id', company_id())->find($appointmentId) : null;
        $this->resetValidation();
        $this->visitor = [
            'full_name' => $appointment?->contact?->display_name ?? $appointment?->visitor_name ?? '', 'organization' => $appointment?->contact?->company_name ?? '',
            'phone' => $appointment?->contact?->phone ?? $appointment?->visitor_phone ?? '', 'id_document' => '', 'purpose' => $appointment?->title ?? '', 'host_id' => $appointment?->user_id,
            'appointment_id' => $appointment?->id, 'contact_id' => $appointment?->contact_id, 'badge_number' => $this->nextBadge(),
        ];
        $this->showVisitor = true;
    }

    public function checkIn(): void
    {
        $this->authorizeModule('reception', 'manage');
        $data = $this->validate([
            'visitor.full_name' => ['required', 'string', 'max:120'], 'visitor.organization' => ['nullable', 'string', 'max:120'], 'visitor.phone' => ['nullable', 'string', 'max:40'],
            'visitor.id_document' => ['nullable', 'string', 'max:80'], 'visitor.purpose' => ['required', 'string', 'max:255'], 'visitor.host_id' => ['nullable', 'integer'],
            'visitor.appointment_id' => ['nullable', 'integer'], 'visitor.contact_id' => ['nullable', 'integer'], 'visitor.badge_number' => ['nullable', 'string', 'max:20'],
        ], [], ['visitor.full_name' => 'nom', 'visitor.purpose' => 'objet de la visite'])['visitor'];
        $cid = company_id();
        // Ne conserver que des références appartenant à la société courante.
        $data['host_id'] = $data['host_id'] && $this->hosts()->contains('id', (int) $data['host_id']) ? (int) $data['host_id'] : null;
        $appointment = $data['appointment_id'] ? Appointment::where('company_id', $cid)->find($data['appointment_id']) : null;
        $data['appointment_id'] = $appointment?->id;
        $data['contact_id'] = $appointment?->contact_id;
        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

        DB::transaction(function () use ($data, $appointment, $cid) {
            Visitor::create($data + ['company_id' => $cid, 'checked_in_at' => now()]);
            $appointment?->update(['status' => 'done']);
        });
        $this->showVisitor = false;
        $this->toast("Arrivée de {$data['full_name']} enregistrée".($data['badge_number'] ? " — badge {$data['badge_number']}" : '').'.');
    }

    public function checkOut(int $id): void
    {
        $this->authorizeModule('reception', 'manage');
        $done = Visitor::where('company_id', company_id())->whereKey($id)->whereNull('checked_out_at')->update(['checked_out_at' => now()]);
        $this->toast($done ? 'Départ enregistré.' : 'Ce visiteur est déjà sorti.', $done ? 'success' : 'warning');
    }

    protected function nextBadge(): string
    {
        return 'V-'.str_pad((string) (Visitor::where('company_id', company_id())->whereDate('checked_in_at', today())->count() + 1), 2, '0', STR_PAD_LEFT);
    }

    // ── Courrier ────────────────────────────────────────────────────────────
    public function newMail(string $direction = 'in'): void
    {
        $this->authorizeModule('reception', 'manage');
        $this->resetValidation();
        $this->editingMail = null;
        $this->mailFile = null;
        $this->mail = [
            'direction' => $direction === 'out' ? 'out' : 'in', 'record_date' => now()->toDateString(), 'correspondent' => '', 'subject' => '', 'channel' => 'physique',
            'department_id' => null, 'handled_by' => null, 'status' => 'registered',
        ];
        $this->showMail = true;
    }

    public function editMail(int $id): void
    {
        $this->authorizeModule('reception', 'manage');
        $m = MailRecord::where('company_id', company_id())->findOrFail($id);
        $this->resetValidation();
        $this->editingMail = $m->id;
        $this->mailFile = null;
        $this->mail = [
            'direction' => $m->direction, 'record_date' => $m->record_date->toDateString(), 'correspondent' => $m->correspondent, 'subject' => $m->subject, 'channel' => $m->channel,
            'department_id' => $m->department_id, 'handled_by' => $m->handled_by, 'status' => $m->status, 'reference' => $m->reference, 'has_file' => (bool) $m->file_path,
        ];
        $this->showMail = true;
    }

    public function saveMail(SequenceService $sequences): void
    {
        $this->authorizeModule('reception', 'manage');
        $data = $this->validate([
            'mail.direction' => ['required', 'in:in,out'], 'mail.record_date' => ['required', 'date'], 'mail.correspondent' => ['required', 'string', 'max:160'],
            'mail.subject' => ['required', 'string', 'max:255'], 'mail.channel' => ['required', 'in:physique,email,coursier'], 'mail.department_id' => ['nullable', 'integer'],
            'mail.handled_by' => ['nullable', 'integer'], 'mail.status' => ['required', 'in:'.implode(',', array_keys(config('erp.statuses.mail')))],
            'mailFile' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ], [], ['mail.correspondent' => 'correspondant', 'mail.subject' => 'objet', 'mailFile' => 'pièce jointe']);
        $file = $data['mailFile'] ?? null;
        $data = $data['mail'];
        $cid = company_id();
        $data['department_id'] = $data['department_id'] ? Department::where('company_id', $cid)->whereKey($data['department_id'])->value('id') : null;
        $data['handled_by'] = $data['handled_by'] && $this->hosts()->contains('id', (int) $data['handled_by']) ? (int) $data['handled_by'] : null;
        if ($data['status'] === 'registered' && $data['handled_by'] && ! $this->editingMail) {
            $data['status'] = 'forwarded';
        }

        $record = $this->editingMail ? MailRecord::where('company_id', $cid)->findOrFail($this->editingMail) : null;
        if ($record) {
            $record->update($data);
        } else {
            $record = MailRecord::create($data + ['company_id' => $cid, 'reference' => $sequences->next($data['direction'] === 'in' ? 'mail_in' : 'mail_out')]);
        }
        if ($file) {
            $old = $record->file_path;
            $record->update(['file_path' => $file->storeAs("courrier/{$cid}", str($record->reference)->slug().'-'.now()->format('YmdHis').'.'.strtolower($file->getClientOriginalExtension() ?: $file->extension()), 'local')]);
            if ($old && $old !== $record->file_path) {
                Storage::disk('local')->delete($old);
            }
        }
        $this->showMail = false;
        $this->mailFile = null;
        $this->toast("Courrier {$record->reference} enregistré.");
    }

    public function mailStatus(int $id, string $status): void
    {
        $this->authorizeModule('reception', 'manage');
        abort_unless(array_key_exists($status, config('erp.statuses.mail')), 422);
        MailRecord::where('company_id', company_id())->whereKey($id)->update(['status' => $status]);
    }

    /** Fait avancer le courrier d'une étape : enregistré → transmis → traité → archivé. */
    public function advanceMail(int $id): void
    {
        $this->authorizeModule('reception', 'manage');
        $record = MailRecord::where('company_id', company_id())->findOrFail($id);
        if ($next = self::MAIL_FLOW[$record->status] ?? null) {
            $record->update(['status' => $next]);
            $this->toast("Courrier {$record->reference} : ".mb_strtolower(config("erp.statuses.mail.{$next}.0")).'.');
        }
    }

    // ── Listes ──────────────────────────────────────────────────────────────
    protected function hosts(): Collection
    {
        return once(fn () => User::whereHas('companies', fn ($q) => $q->where('companies.id', company_id()))->where('is_active', true)->orderBy('name')->get());
    }

    protected function departments(): Collection
    {
        return once(fn () => Department::where('company_id', company_id())->orderBy('name')->get());
    }
}
