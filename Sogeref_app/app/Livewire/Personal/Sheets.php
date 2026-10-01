<?php

namespace App\Livewire\Personal;

use App\Livewire\Concerns\EncryptsUrlIds;
use App\Livewire\Concerns\InteractsWithUi;
use App\Models\PersonalSheet;
use App\Support\SheetFormula;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Tableurs personnels façon Excel : grille éditable au clavier, formules
 * (SUM, AVERAGE, MIN, MAX, COUNT, + - * /), impression, export Excel et PDF.
 */
#[Title('Mes tableurs')]
class Sheets extends Component
{
    use EncryptsUrlIds, InteractsWithUi;

    public ?int $sheetId = null;

    /** Jeton chiffré de l'identifiant, seul visible dans l'URL (?classeur=…). */
    #[Url(as: 'classeur')]
    public ?string $sheetKey = null;

    protected function encryptedUrlIds(): array
    {
        return ['sheetId' => ['sheetKey', PersonalSheet::class]];
    }

    public function mount(): void
    {
        $this->decodeUrlIds();
        if (request()->boolean('nouveau')) {
            $this->create();

            return;
        }
        if (! $this->sheetId || ! PersonalSheet::mine()->whereKey($this->sheetId)->exists()) {
            $this->sheetId = PersonalSheet::mine()->latest('updated_at')->value('id');
        }
    }

    public function create(): void
    {
        $sheet = PersonalSheet::create(['company_id' => company_id(), 'user_id' => auth()->id(), 'title' => 'Classeur sans titre', 'rows' => 20, 'cols' => 8, 'cells' => []]);
        $this->sheetId = $sheet->id;
    }

    public function select(int $id): void
    {
        $this->sheetId = $this->find($id)->id;
    }

    /** Appelé par la grille (Alpine) : les formules sont recalculées côté serveur. */
    public function saveSheet(int $id, string $title, int $rows, int $cols, array $cells = []): array
    {
        $sheet = $this->find($id);
        $this->authorize('update', $sheet);
        validator(compact('title', 'rows', 'cols'), [
            'title' => ['nullable', 'string', 'max:255'],
            'rows' => ['required', 'integer', 'min:1', 'max:'.PersonalSheet::MAX_ROWS],
            'cols' => ['required', 'integer', 'min:1', 'max:'.PersonalSheet::MAX_COLS],
        ])->validate();

        $clean = [];
        foreach ($cells as $ref => $value) {
            $ref = strtoupper((string) $ref);
            if (! preg_match('/^[A-Z]{1,2}\d{1,3}$/', $ref) || (! is_scalar($value) && $value !== null)) {
                throw ValidationException::withMessages(['cells' => 'Cellule invalide : '.$ref]);
            }
            [$c, $r] = SheetFormula::parseRef($ref);
            $value = mb_substr(trim((string) $value), 0, 2000);
            if ($value !== '' && $c < $cols && $r < $rows) {
                $clean[$ref] = $value;
            }
        }

        $sheet->update(['title' => trim($title) ?: 'Classeur sans titre', 'rows' => $rows, 'cols' => $cols, 'cells' => $clean]);

        return $sheet->values ?? [];
    }

    public function duplicate(int $id): void
    {
        $sheet = $this->find($id);
        $this->authorize('view', $sheet);
        $copy = $sheet->replicate();
        $copy->title = str($sheet->title)->limit(240)->append(' (copie)')->toString();
        $copy->save();
        $this->sheetId = $copy->id;
        $this->toast('Classeur dupliqué.');
    }

    public function delete(int $id): void
    {
        $sheet = $this->find($id);
        $this->authorize('delete', $sheet);
        $sheet->delete();
        if ($this->sheetId === $id) {
            $this->sheetId = PersonalSheet::mine()->latest('updated_at')->value('id');
        }
        $this->toast('Classeur supprimé.');
    }

    private function find(int $id): PersonalSheet
    {
        return PersonalSheet::mine()->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.personal.sheets', [
            'sheets' => PersonalSheet::mine()->latest('updated_at')->get(['id', 'title', 'rows', 'cols', 'updated_at']),
            'sheet' => $this->sheetId ? PersonalSheet::mine()->find($this->sheetId) : null,
        ]);
    }
}
