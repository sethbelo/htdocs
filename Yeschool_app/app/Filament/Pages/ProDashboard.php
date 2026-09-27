<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\WelcomeProWidget;
use App\Filament\Widgets\StatsOverviewProWidget;
use App\Filament\Widgets\RecentActivityProWidget;

class ProDashboard extends BaseDashboard
{
    protected static ?string $title = 'Tableau de bord';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    // Override layout to use our Pro Max SIGS Layout
    protected static string $layout = 'layouts.sigs-layout';

    // Use a custom view instead of widgets
    protected static string $view = 'filament.pages.pro-dashboard';

    public function getColumns(): int | string | array
    {
        return 1;
    }

    public function getWidgets(): array
    {
        return [];
    }
}
