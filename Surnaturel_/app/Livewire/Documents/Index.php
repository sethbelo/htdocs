<?php

namespace App\Livewire\Documents;

use App\Enums\DocumentCategory;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Document;
use App\Services\Documents\DocumentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Title('Documents')]
class Index extends Component
{
    use InteractsWithToasts, WithFileUploads, WithPagination;

    #[Url(as: 'categorie')]
    public string $category = '';

    #[Url(as: 'q')]
    public string $search = '';

    public bool $archived = false;

    /** @var array<string, mixed> */
    public array $form = ['title' => '', 'category' => 'administrative', 'is_sensitive' => false];

    public $file = null;

    public ?int $versionFor = null;

    public $newVersion = null;

    public ?int $historyFor = null;

    public function upload(DocumentService $documents): void
    {
        $this->authorize('documents.create');
        $data = $this->validate([
            'form.title' => ['nullable', 'string', 'max:255'],
            'form.category' => ['required', Rule::enum(DocumentCategory::class)],
            'form.is_sensitive' => ['boolean'],
            'file' => ['required', 'file', 'max:102400'],
        ], [], ['file' => 'fichier'])['form'];

        $documents->store($this->file, DocumentCategory::from($data['category']), auth()->user(), $data['title'] ?: null, sensitive: (bool) $data['is_sensitive']);
        $this->reset('file', 'form');
        $this->modal('upload')->close();
        $this->toast('Document archivé dans la GED.');
    }

    public function openVersion(int $id): void
    {
        $this->versionFor = $id;
        $this->modal('version')->show();
    }

    public function addVersion(DocumentService $documents): void
    {
        $document = Document::query()->findOrFail($this->versionFor);
        $this->authorize('update', $document);
        $this->validate(['newVersion' => ['required', 'file', 'max:102400']]);
        $documents->addVersion($document, $this->newVersion, auth()->user());
        $this->reset('newVersion', 'versionFor');
        $this->modal('version')->close();
        $this->toast('Nouvelle version enregistrée.');
    }

    public function toggleArchive(int $id): void
    {
        $document = Document::query()->findOrFail($id);
        $this->authorize('update', $document);
        $document->update(['archived_at' => $document->archived_at ? null : now()]);
    }

    public function download(int $id, DocumentService $documents): void
    {
        $document = Document::query()->with('latestVersion')->findOrFail($id);
        $this->authorize('view', $document);
        $this->redirect($documents->temporaryUrl($document->latestVersion));
    }

    public function showHistory(int $id): void
    {
        $this->historyFor = $id;
        $this->modal('history')->show();
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.documents.index', [
            'documents' => Document::query()->with('latestVersion', 'uploader', 'documentable')
                ->where(fn (Builder $q) => $q->whereNull('site_id')->orWhereIn('site_id', $user->accessibleSiteIds()))
                ->when(! $user->can('employees.view_sensitive'), fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('is_sensitive', false)->where('category', '!=', DocumentCategory::Hr)->orWhere('uploaded_by', $user->id)))
                ->when($this->category, fn (Builder $q) => $q->where('category', $this->category))
                ->when($this->search, fn (Builder $q) => $q->where('title', 'like', "%{$this->search}%"))
                ->when($this->archived, fn (Builder $q) => $q->whereNotNull('archived_at'), fn (Builder $q) => $q->whereNull('archived_at'))
                ->latest()->paginate(25),
            'categories' => DocumentCategory::options(),
            'history' => $this->historyFor ? Document::query()->with('versions.uploader')->find($this->historyFor) : null,
            'activity' => $this->historyFor ? Activity::query()->where('subject_type', 'document')->where('subject_id', $this->historyFor)->latest()->limit(20)->get() : collect(),
        ]);
    }
}
