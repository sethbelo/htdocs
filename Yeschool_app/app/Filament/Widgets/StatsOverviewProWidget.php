<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\School;
use App\Models\User;
// Assuming you have models for Students or similar, we mock some stats for the demo

class StatsOverviewProWidget extends Widget
{
    protected static string $view = 'filament.widgets.stats-overview-pro-widget';
    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        // Dummy data for "ultra pro" showcase
        return [
            'stats' => [
                [
                    'label' => 'Total Élèves',
                    'value' => '2,845',
                    'trend' => '+12.5%',
                    'trend_color' => 'text-emerald-500',
                    'icon' => 'heroicon-o-users',
                    'color' => 'from-blue-500 to-cyan-400',
                ],
                [
                    'label' => 'Revenus (Mensuel)',
                    'value' => '$45,231',
                    'trend' => '+8.2%',
                    'trend_color' => 'text-emerald-500',
                    'icon' => 'heroicon-o-currency-dollar',
                    'color' => 'from-purple-500 to-pink-500',
                ],
                [
                    'label' => 'Écoles Actives',
                    'value' => '12',
                    'trend' => 'Stable',
                    'trend_color' => 'text-gray-500',
                    'icon' => 'heroicon-o-building-office-2',
                    'color' => 'from-amber-500 to-orange-400',
                ],
                [
                    'label' => 'Taux de Présence',
                    'value' => '94.2%',
                    'trend' => '-1.1%',
                    'trend_color' => 'text-rose-500',
                    'icon' => 'heroicon-o-check-badge',
                    'color' => 'from-emerald-400 to-teal-500',
                ]
            ]
        ];
    }
}
