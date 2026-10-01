<?php

namespace App\Livewire\Personal;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\PersonalNote;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Notes personnelles façon Notion : liste, recherche, épingles et éditeur riche
 * (titres, listes, cases à cocher, citations, séparateurs) avec sauvegarde automatique.
 */
#[Title('Mes notes')]
class Notes extends Component
{
    use InteractsWithUi;

    #[Url(as: 'note')]
    public ?int $noteId = null;

    public string $search = '';

    public function mount(): void
    {
        if (request()->boolean('nouvelle')) {
            $this->create();

            return;
        }
        if (! $this->noteId || ! PersonalNote::mine()->whereKey($this->noteId)->exists()) {
            $this->noteId = PersonalNote::mine()->orderByDesc('is_pinned')->latest('updated_at')->value('id');
        }
    }

    public function create(): void
    {
        $note = PersonalNote::create(['company_id' => company_id(), 'user_id' => auth()->id(), 'title' => 'Sans titre', 'content' => '']);
        $this->noteId = $note->id;
        $this->search = '';
    }

    public function select(int $id): void
    {
        $this->noteId = $this->find($id)->id;
    }

    /** Appelé par l'éditeur (Alpine) : sauvegarde automatique ou manuelle. */
    public function saveNote(int $id, string $title, string $content): void
    {
        $note = $this->find($id);
        $this->authorize('update', $note);
        validator(['title' => $title, 'content' => $content], ['title' => ['nullable', 'string', 'max:255'], 'content' => ['nullable', 'string', 'max:1000000']])->validate();
        $note->update(['title' => trim($title) ?: 'Sans titre', 'content' => $content]);
    }

    public function togglePin(int $id): void
    {
        $note = $this->find($id);
        $this->authorize('update', $note);
        $note->update(['is_pinned' => ! $note->is_pinned]);
        $this->toast($note->is_pinned ? 'Note épinglée.' : 'Note désépinglée.');
    }

    public function duplicate(int $id): void
    {
        $note = $this->find($id);
        $this->authorize('view', $note);
        $copy = $note->replicate(['is_pinned']);
        $copy->title = str($note->title)->limit(240)->append(' (copie)')->toString();
        $copy->save();
        $this->noteId = $copy->id;
        $this->toast('Note dupliquée.');
    }

    public function delete(int $id): void
    {
        $note = $this->find($id);
        $this->authorize('delete', $note);
        $note->delete();
        if ($this->noteId === $id) {
            $this->noteId = PersonalNote::mine()->orderByDesc('is_pinned')->latest('updated_at')->value('id');
        }
        $this->toast('Note supprimée.');
    }

    private function find(int $id): PersonalNote
    {
        return PersonalNote::mine()->findOrFail($id);
    }

    public function render()
    {
        $term = trim($this->search);

        return view('livewire.personal.notes', [
            'notes' => PersonalNote::mine()
                ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', "%{$term}%")->orWhere('content', 'like', "%{$term}%")))
                ->orderByDesc('is_pinned')->latest('updated_at')->get(),
            'note' => $this->noteId ? PersonalNote::mine()->find($this->noteId) : null,
        ]);
    }
}
