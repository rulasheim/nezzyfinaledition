<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class RevenueStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';

    public static function canView(): bool
{
    return auth()->user()?->role === \App\Models\User::ROLE_ADMIN || 
           auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN;
}

    protected function getStats(): array
    {
        $totalRevenue = Payment::query()->sum('amount');
        
        $monthlyRevenue = Payment::query()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        return [
            Stat::make('Ventas Totales (Sistema)', Number::currency($totalRevenue, 'USD'))
                ->description('Ingresos globales acumulados')
                ->color('success'),

            Stat::make('Ingresos del Mes', Number::currency($monthlyRevenue, 'USD'))
                ->color('info'),
        ];
    }
}