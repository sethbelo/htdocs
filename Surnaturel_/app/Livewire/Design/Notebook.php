<?php

namespace App\Livewire\Design;

use App\Enums\DesignNoteType;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\DesignBrief;
use App\Models\DesignJob;
use App\Services\Design\DesignNotebookService;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Cahier de conception d'un projet client : brief créatif, carnet de notes, fiche technique d'impression
 * et validation « conception terminée » qui donne le feu vert à la salle d'impression.
 */
class Notebook extends Component
{
    use InteractsWithToasts;

    public DesignJob $designJob;

    /** @var array<string, mixed> */
    public array $brief = [];

    /** @var array<string, bool> */
    public array $checklist = [];

    public string $noteType = 'note';

    public string $noteBody = '';

    public function mount(DesignJob $designJob, DesignNotebookService $notebook): void
    {
        abort_unless($designJob->order()->visibleTo(auth()->user())->exists(), 404);
        $this->designJob = $designJob;
        $this->fillFrom($notebook->briefFor($designJob));
    }

    private function fillFrom(DesignBrief $brief): void
    {
        $this->brief = $brief->only(['title', 'objective', 'target_audience', 'content', 'style_notes', 'final_format', 'width_mm', 'height_mm', 'bleed_mm', 'orientation', 'colors', 'paper', 'grammage', 'finishing', 'quantity', 'pages', 'final_file_name', 'print_notes']);
        $this->brief = array_map(fn ($value) => $value === null ? '' : (is_numeric($value) ? (string) (float) $value : $value), $this->brief);
        $this->checklist = collect(DesignBrief::CHECKLIST)->mapWithKeys(fn ($label, $key) => [$key => (bool) ($brief->checklist[$key] ?? false)])->all();
    }

    #[Computed]
    public function job(): DesignJob
    {
        return $this->designJob->load('order.client', 'order.items', 'order.salesRep', 'designer', 'versions.uploader', 'proofs', 'brief.validator', 'notes.user');
    }

    public function applyFormat(string $format): void
    {
        [$w, $h] = self::FORMATS[$format] ?? [null, null];
        if ($w) {
            $this->brief['final_format'] = $format;
            $this->brief['width_mm'] = (string) $w;
            $this->brief['height_mm'] = (string) $h;
            $this->brief['orientation'] = $w > $h ? 'paysage' : 'portrait';
        }
    }

    /** @var array<string, array{0: float, 1: float}> Formats d'impression courants (mm). */
    public const FORMATS = [
        'Carte de visite' => [85, 55], 'A6' => [105, 148], 'A5' => [148, 210], 'A4' => [210, 297], 'A3' => [297, 420],
        'A2' => [420, 594], 'A1' => [594, 841], 'DL' => [99, 210], 'Kakemono' => [850, 2000], 'Bâche 3×1 m' => [3000, 1000],
    ];

    public function saveBrief(DesignNotebookService $notebook): void
    {
        $this->authorize('design.update');
        $data = $this->validate([
            'brief.title' => ['nullable', 'string', 'max:255'],
            'brief.objective' => ['nullable', 'string', 'max:5000'],
            'brief.target_audience' => ['nullable', 'string', 'max:255'],
            'brief.content' => ['nullable', 'string', 'max:10000'],
            'brief.style_notes' => ['nullable', 'string', 'max:5000'],
            'brief.final_format' => ['nullable', 'string', 'max:40'],
            'brief.width_mm' => ['nullable', 'numeric', 'gt:0', 'max:100000'],
            'brief.height_mm' => ['nullable', 'numeric', 'gt:0', 'max:100000'],
            'brief.bleed_mm' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'brief.orientation' => ['required', 'in:portrait,paysage'],
            'brief.colors' => ['nullable', 'string', 'max:40'],
            'brief.paper' => ['nullable', 'string', 'max:255'],
            'brief.grammage' => ['nullable', 'integer', 'min:40', 'max:1000'],
            'brief.finishing' => ['nullable', 'string', 'max:255'],
            'brief.quantity' => ['nullable', 'integer', 'min:1'],
            'brief.pages' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'brief.final_file_name' => ['nullable', 'string', 'max:255'],
            'brief.print_notes' => ['nullable', 'string', 'max:5000'],
        ], [], ['brief.width_mm' => 'largeur', 'brief.height_mm' => 'hauteur', 'brief.quantity' => 'quantité'])['brief'];

        $data = array_map(fn ($value) => $value === '' ? null : $value, $data) + ['checklist' => $this->checklist];
        $data['bleed_mm'] ??= 0;

        $this->attempt(fn () => $notebook->saveBrief($this->designJob, $data), 'Cahier enregistré.');
        unset($this->job);
    }

    public function addNote(DesignNotebookService $notebook): void
    {
        $this->authorize('design.update');
        $this->validate(['noteBody' => ['required', 'string', 'max:5000'], 'noteType' => ['required', 'in:'.implode(',', array_column(DesignNoteType::cases(), 'value'))]], [], ['noteBody' => 'note']);

        $this->attempt(fn () => $notebook->addNote($this->designJob, auth()->user(), DesignNoteType::from($this->noteType), $this->noteBody));
        $this->reset('noteBody');
        unset($this->job);
    }

    public function toggleTodo(int $noteId, DesignNotebookService $notebook): void
    {
        $this->authorize('design.update');
        $notebook->toggleTodo($this->designJob->notes()->findOrFail($noteId));
        unset($this->job);
    }

    public function deleteNote(int $noteId): void
    {
        $note = $this->designJob->notes()->findOrFail($noteId);
        abort_unless($note->user_id === auth()->id() || auth()->user()->can('design.delete'), 403);
        $note->delete();
        unset($this->job);
    }

    public function validateForPrint(DesignNotebookService $notebook): void
    {
        $this->authorize('design.work');
        $this->saveBrief($notebook);

        $this->attempt(fn () => $notebook->validateForPrint($this->designJob->fresh(), auth()->user()), 'Conception validée : la salle d’impression est prévenue.');
        unset($this->job);
    }

    public function render()
    {
        return view('livewire.design.notebook', [
            'noteTypes' => DesignNoteType::cases(),
            'formats' => self::FORMATS,
            'checks' => DesignBrief::CHECKLIST,
        ])->title('Cahier de conception — '.$this->designJob->order->number);
    }
}
