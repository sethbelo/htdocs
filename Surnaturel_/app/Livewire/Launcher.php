<?php

namespace App\Livewire;

use App\Support\Navigation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Lanceur d'applications : chaque département affiche ses rubriques sous forme d'icônes ;
 * un clic ouvre l'interface correspondante. Seules les rubriques autorisées au profil apparaissent.
 */
#[Layout('layouts.launcher')]
#[Title('Applications')]
class Launcher extends Component
{
    public function render()
    {
        $user = auth()->user();

        return view('livewire.launcher', [
            'departments' => Navigation::departments($user),
            'badges' => Navigation::badges($user),
            'firstName' => explode(' ', trim($user->name))[0],
        ]);
    }
}
