<?php

namespace App\Livewire\Settings;

use App\Services\Import\DataImporter;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Point central d'import de données (reprise de l'existant, mises à jour en masse).
 */
#[Title('Paramètres — Import de données')]
class Imports extends Component
{
    public function render()
    {
        return view('livewire.settings.imports', ['types' => DataImporter::allowedFor(auth()->user())]);
    }
}
