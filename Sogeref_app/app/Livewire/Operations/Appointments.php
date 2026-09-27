<?php

namespace App\Livewire\Operations;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Agenda des rendez-vous : visites showroom, visites de chantier, prises
 * de mesures, livraisons et réunions.
 */
#[Title('Rendez-vous')]
class Appointments extends Component
{
    use InteractsWithUi;

    #[Url(as: 'semaine')]
    public string $week = '';

    public bool $mine = false;

    public bool $showForm = false;

    public ?int $editing = null;

    public array $form = [];

    public function mount(): void
    {
        $this->week = $this->week ?: now()->startOfWeek()->toDateString();
    }

    public function shift(int $weeks): void
    {
        $this->week = Carbon::parse($this->week)->addWeeks($weeks)->startOfWeek()->toDateString();
    }

    public function edit(?int $id = null, ?string $date = null, ?string $time = null): void
    {
        $this->authorizeModule('appointments', 'manage');
        $a = $id ? Appointment::where('company_id', company_id())->findOrFail($id) : null;
        $this->editing = $a?->id;
        $this->form = [
            'title' => $a?->title ?? '', 'type' => $a?->type ?? 'reunion', 'contact_id' => $a?->contact_id, 'visitor_name' => $a?->visitor_name ?? '', 'visitor_phone' => $a?->visitor_phone ?? '',
            'user_id' => $a?->user_id ?? auth()->id(), 'date' => $a?->starts_at->toDateString() ?? ($date ?? now()->toDateString()), 'start' => $a?->starts_at->format('H:i') ?? ($time ?? '09:00'),
            'duration' => $a ? $a->ends_at->diffInMinutes($a->starts_at, true) : 60, 'location' => $a?->location ?? 'Siège SOGEREF', 'status' => $a?->status ?? 'scheduled', 'notes' => $a?->notes ?? '',
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorizeModule('appointments', 'manage');
        $data = $this->validate([
            'form.title' => ['required', 'string', 'max:200'], 'form.type' => ['required', 'in:'.implode(',', array_keys(config('erp.appointment_types')))], 'form.contact_id' => ['nullable', 'integer'],
            'form.visitor_name' => ['nullable', 'string', 'max:120'], 'form.visitor_phone' => ['nullable', 'string', 'max:40'], 'form.user_id' => ['required', 'integer'],
            'form.date' => ['required', 'date'], 'form.start' => ['required', 'date_format:H:i'], 'form.duration' => ['required', 'integer', 'between:15,600'],
            'form.location' => ['nullable', 'string', 'max:160'], 'form.status' => ['required', 'in:scheduled,confirmed,done,cancelled,no_show'], 'form.notes' => ['nullable', 'string', 'max:2000'],
        ], [], ['form.title' => 'objet'])['form'];
        $start = Carbon::parse($data['date'].' '.$data['start']);
        $conflict = Appointment::where('company_id', company_id())->where('user_id', $data['user_id'])->whereNotIn('status', ['cancelled'])->whereKeyNot($this->editing ?? 0)
            ->where('starts_at', '<', $start->copy()->addMinutes((int) $data['duration']))->where('ends_at', '>', $start)->exists();
        Appointment::updateOrCreate(['id' => $this->editing], [
            'company_id' => company_id(), 'title' => $data['title'], 'type' => $data['type'], 'contact_id' => $data['contact_id'] ?: null, 'visitor_name' => $data['visitor_name'] ?: null,
            'visitor_phone' => $data['visitor_phone'] ?: null, 'user_id' => $data['user_id'], 'starts_at' => $start, 'ends_at' => $start->copy()->addMinutes((int) $data['duration']),
            'location' => $data['location'] ?: null, 'status' => $data['status'], 'notes' => $data['notes'] ?: null,
        ]);
        $this->showForm = false;
        $this->toast($conflict ? 'Rendez-vous enregistré — attention : chevauchement avec un autre rendez-vous.' : 'Rendez-vous enregistré.', $conflict ? 'warning' : 'success');
    }

    public function delete(): void
    {
        $this->authorizeModule('appointments', 'manage');
        Appointment::where('company_id', company_id())->whereKey($this->editing)->delete();
        $this->showForm = false;
    }

    public function render()
    {
        $cid = company_id();
        $start = Carbon::parse($this->week)->startOfWeek();
        $days = collect(range(0, 5))->map(fn ($d) => $start->copy()->addDays($d));
        $appointments = Appointment::with('contact', 'user')->where('company_id', $cid)->whereBetween('starts_at', [$start, $start->copy()->addDays(6)->endOfDay()])
            ->when($this->mine, fn ($q) => $q->where('user_id', auth()->id()))->orderBy('starts_at')->get();

        return view('livewire.operations.appointments', [
            'days' => $days,
            'byDay' => $appointments->groupBy(fn ($a) => $a->starts_at->toDateString()),
            'hours' => range(7, 18),
            'upcoming' => Appointment::with('contact', 'user')->where('company_id', $cid)->where('starts_at', '>=', now())->whereIn('status', ['scheduled', 'confirmed'])->orderBy('starts_at')->limit(6)->get(),
            'contacts' => Contact::where('company_id', $cid)->whereIn('type', ['client', 'prospect'])->orderByRaw('COALESCE(company_name, last_name)')->get(),
            'users' => User::whereHas('companies', fn ($q) => $q->where('companies.id', $cid))->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
