<?php

namespace App\Livewire\Operations;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\Site;
use App\Models\Warehouse;
use App\Services\SequenceService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Liste des chantiers : avancement, équipe, budget et création.
 */
#[Title('Chantiers')]
class Sites extends Component
{
    use InteractsWithUi;

    #[Url(as: 'statut')]
    public string $status = '';

    public string $search = '';

    public bool $showForm = false;

    public string $name = '';

    public ?int $contactId = null;

    public ?int $managerId = null;

    public string $address = '';

    public string $city = '';

    public string $startDate = '';

    public string $plannedEndDate = '';

    public string $budget = '0';

    public string $description = '';

    public bool $createWarehouse = true;

    public function create(): void
    {
        $this->authorizeModule('sites', 'manage');
        $this->reset(['name', 'contactId', 'managerId', 'address', 'city', 'description']);
        $this->startDate = now()->toDateString();
        $this->plannedEndDate = now()->addMonths(2)->toDateString();
        $this->budget = '0';
        $this->showForm = true;
    }

    public function save(SequenceService $sequences)
    {
        $this->authorizeModule('sites', 'manage');
        $this->validate([
            'name' => ['required', 'string', 'max:200'], 'city' => ['nullable', 'string', 'max:120'], 'address' => ['nullable', 'string', 'max:255'],
            'startDate' => ['required', 'date'], 'plannedEndDate' => ['required', 'date', 'after_or_equal:startDate'], 'budget' => ['required', 'numeric', 'min:0'],
        ], [], ['name' => 'nom du chantier', 'startDate' => 'début', 'plannedEndDate' => 'fin prévue']);

        $site = DB::transaction(function () use ($sequences) {
            $site = Site::create([
                'company_id' => company_id(), 'code' => $sequences->next('site'), 'name' => $this->name, 'contact_id' => $this->contactId ?: null,
                'manager_id' => $this->managerId ?: null, 'address' => $this->address ?: null, 'city' => $this->city ?: null, 'description' => $this->description ?: null,
                'start_date' => $this->startDate, 'planned_end_date' => $this->plannedEndDate, 'budget' => $this->budget, 'status' => 'planned',
            ]);
            if ($this->createWarehouse) {
                Warehouse::create(['company_id' => company_id(), 'code' => $site->code, 'name' => 'Magasin '.$site->name, 'type' => 'chantier', 'site_id' => $site->id, 'address' => $site->address]);
            }
            if ($this->managerId) {
                $site->members()->attach($this->managerId, ['role' => 'Responsable de chantier', 'joined_at' => $this->startDate]);
            }

            return $site;
        });
        session()->flash('toast', ['type' => 'success', 'message' => "Chantier {$site->code} créé."]);

        return $this->redirectRoute('sites.show', $site, navigate: true);
    }

    public function render()
    {
        $cid = company_id();
        $sites = Site::with('manager', 'contact')->withCount('members')
            ->where('company_id', $cid)
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%")->orWhere('city', 'like', "%{$this->search}%")))
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'planned' THEN 1 WHEN 'suspended' THEN 2 ELSE 3 END")->latest('start_date')
            ->get();

        $labour = DB::table('attendance_entries')->join('attendance_sheets', 'attendance_sheets.id', '=', 'attendance_entries.attendance_sheet_id')
            ->where('attendance_sheets.company_id', $cid)->where('attendance_sheets.status', 'validated')
            ->selectRaw("attendance_sheets.site_id, SUM(CASE WHEN attendance_entries.status IN ('present','late') THEN attendance_entries.daily_rate WHEN attendance_entries.status = 'half' THEN attendance_entries.daily_rate * 0.5 ELSE 0 END) as total")
            ->groupBy('attendance_sheets.site_id')->pluck('total', 'site_id');
        $spent = DB::table('vouchers')->where('company_id', $cid)->where('status', 'executed')->whereNotNull('site_id')->whereNull('deleted_at')->groupBy('site_id')->selectRaw('site_id, SUM(amount) as total')->pluck('total', 'site_id');

        return view('livewire.operations.sites', [
            'sites' => $sites,
            'costs' => $sites->mapWithKeys(fn ($s) => [$s->id => (float) ($labour[$s->id] ?? 0) + (float) ($spent[$s->id] ?? 0)]),
            'counts' => Site::where('company_id', $cid)->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
            'clients' => Contact::where('company_id', $cid)->where('type', 'client')->orderByRaw('COALESCE(company_name, last_name)')->get(),
            'managers' => Employee::where('company_id', $cid)->where('status', 'active')->where('pay_type', 'monthly')->orderBy('last_name')->get(),
        ]);
    }
}
