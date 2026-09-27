<?php

namespace App\Livewire\Operations;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Contact;
use App\Models\Project;
use App\Models\User;
use App\Services\SequenceService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Projets : prestations, chantiers et suivis d'atelier structurés en tâches.
 */
#[Title('Projets')]
class Projects extends Component
{
    use InteractsWithUi;

    #[Url(as: 'statut')]
    public string $status = 'active';

    public bool $showForm = false;

    public array $form = [];

    public function create(): void
    {
        $this->authorizeModule('projects', 'manage');
        $this->form = ['name' => '', 'contact_id' => null, 'manager_id' => auth()->id(), 'start_date' => now()->toDateString(), 'end_date' => now()->addMonth()->toDateString(), 'budget' => 0, 'description' => '', 'color' => '#104A63'];
        $this->showForm = true;
    }

    public function save(SequenceService $sequences)
    {
        $this->authorizeModule('projects', 'manage');
        $data = $this->validate(['form.name' => ['required', 'string', 'max:200'], 'form.contact_id' => ['nullable', 'integer'], 'form.manager_id' => ['nullable', 'integer'], 'form.start_date' => ['nullable', 'date'], 'form.end_date' => ['nullable', 'date', 'after_or_equal:form.start_date'], 'form.budget' => ['numeric', 'min:0'], 'form.description' => ['nullable', 'string', 'max:3000'], 'form.color' => ['nullable', 'string', 'max:20']], [], ['form.name' => 'nom'])['form'];
        $project = Project::create(array_map(fn ($v) => $v === '' ? null : $v, $data) + ['company_id' => company_id(), 'code' => $sequences->next('project'), 'status' => 'active']);

        return $this->redirectRoute('projects.show', $project, navigate: true);
    }

    public function render()
    {
        $cid = company_id();

        return view('livewire.operations.projects', [
            'projects' => Project::with('contact', 'manager')->withCount(['tasks', 'tasks as done_count' => fn ($q) => $q->where('status', 'done')])->withSum('timesheets', 'hours')
                ->where('company_id', $cid)->when($this->status, fn ($q) => $q->where('status', $this->status))->latest('start_date')->get(),
            'counts' => Project::where('company_id', $cid)->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
            'clients' => Contact::where('company_id', $cid)->where('type', 'client')->orderByRaw('COALESCE(company_name, last_name)')->get(),
            'users' => User::whereHas('companies', fn ($q) => $q->where('companies.id', $cid))->orderBy('name')->get(),
        ]);
    }
}
