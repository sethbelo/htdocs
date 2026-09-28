<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Services\Import\DataImporter;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Bouton « Importer » + assistant : modèle Excel, envoi du fichier, aperçu et contrôle ligne par ligne, import.
 */
class ImportData extends Component
{
    use InteractsWithToasts, WithFileUploads;

    #[Locked]
    public string $type = 'clients';

    public $file = null;

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    /** @var array<int, list<string>> */
    public array $rowErrors = [];

    public bool $done = false;

    /** @var array{created: int, updated: int, skipped: int}|null */
    public ?array $result = null;

    public function mount(string $type): void
    {
        abort_unless(isset(DataImporter::TYPES[$type]), 404);
        $this->type = $type;
    }

    private function definition(): array
    {
        return DataImporter::TYPES[$this->type];
    }

    public function downloadTemplate(DataImporter $importer): BinaryFileResponse
    {
        $this->authorize($this->definition()['permission']);

        return Excel::download($importer->template($this->type), "modele-import-{$this->type}.xlsx");
    }

    public function updatedFile(DataImporter $importer): void
    {
        $this->authorize($this->definition()['permission']);
        $this->validate(['file' => ['required', 'file', 'max:10240', 'mimes:xlsx,xls,csv,txt']], [], ['file' => 'fichier']);
        $this->reset('result', 'done');

        $this->rows = $importer->read($this->type, $this->file->getRealPath());
        if (count($this->rows) > 5000) {
            $this->rows = [];
            $this->addError('file', 'Fichier trop volumineux : 5 000 lignes au maximum par import.');

            return;
        }
        $this->rowErrors = $importer->validate($this->type, $this->rows);

        if ($this->rows === []) {
            $this->addError('file', 'Aucune ligne exploitable : vérifiez que la première ligne contient les en-têtes du modèle.');
        }
    }

    public function import(DataImporter $importer): void
    {
        $this->authorize($this->definition()['permission']);

        if ($this->rows === []) {
            return;
        }

        $this->result = $importer->import($this->type, $this->rows, auth()->user());
        $this->done = true;
        $this->reset('file', 'rows', 'rowErrors');
        $this->toast("Import terminé : {$this->result['created']} créé(s), {$this->result['updated']} mis à jour, {$this->result['skipped']} ignoré(s).");
        $this->dispatch('data-imported', type: $this->type);
    }

    public function restart(): void
    {
        $this->reset('file', 'rows', 'rowErrors', 'result', 'done');
    }

    public function render()
    {
        return view('livewire.import-data', ['definition' => $this->definition()]);
    }
}
