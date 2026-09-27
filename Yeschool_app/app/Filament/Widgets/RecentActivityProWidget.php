<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class RecentActivityProWidget extends Widget
{
    protected static string $view = 'filament.widgets.recent-activity-pro-widget';
    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'activities' => [
                [
                    'title' => 'Nouvelle inscription',
                    'description' => 'Marie Dupont s\'est inscrite en 6ème A.',
                    'time' => 'Il y a 10 minutes',
                    'icon' => 'heroicon-o-user-plus',
                    'color' => 'text-blue-500 bg-blue-100 dark:bg-blue-900/30'
                ],
                [
                    'title' => 'Paiement reçu',
                    'description' => 'Frais de scolarité réglés pour le trimestre 1.',
                    'time' => 'Il y a 2 heures',
                    'icon' => 'heroicon-o-credit-card',
                    'color' => 'text-emerald-500 bg-emerald-100 dark:bg-emerald-900/30'
                ],
                [
                    'title' => 'Mise à jour du système',
                    'description' => 'Le module de présence a été mis à jour.',
                    'time' => 'Hier à 14:30',
                    'icon' => 'heroicon-o-cpu-chip',
                    'color' => 'text-purple-500 bg-purple-100 dark:bg-purple-900/30'
                ],
            ]
        ];
    }
}
