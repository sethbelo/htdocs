<?php

namespace App\Livewire;

use App\Support\Approvals;
use App\Support\Modules;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Lanceur d'applications (inspiré d'Odoo) : chaque service présente ses rubriques sous forme d'icônes.
 * Seules les rubriques autorisées au profil apparaissent ; un clic ouvre l'application,
 * dont la barre latérale ne montre ensuite que ses propres options.
 */
#[Title('Applications')]
class Launcher extends Component
{
    public function render()
    {
        $user = auth()->user();
        $badges = collect(Approvals::for($user))->groupBy('module')->map->sum('count');

        return view('livewire.launcher', [
            'apps' => Modules::apps($user),
            'badges' => $badges,
            'firstName' => explode(' ', trim($user->name))[0],
        ]);
    }
}
