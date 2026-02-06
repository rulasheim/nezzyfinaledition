<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\RevenueStatsWidget;
use App\Filament\Widgets\RevenueChart;

class AdminDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Panel Admin';
    protected static ?string $title = 'Análisis de Ingresos';
    protected static ?string $navigationGroup = 'Administración';

    protected static string $view = 'filament.pages.admin-dashboard';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role === \App\Models\User::ROLE_ADMIN || 
               auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RevenueStatsWidget::class,
            RevenueChart::class,
        ];
    }
}