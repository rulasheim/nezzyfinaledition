<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SubscriptionStatusWidget extends BaseWidget

{

    protected int | string | array $columnSpan = 'full';
    protected function getStats(): array
    {
        
        $user = Auth::user();
        $expiresAt = $user->subscription_expires_at;
        $planName = $user->subscription?->name ?? 'Sin Plan Activo';

        // Lógica de días restantes
        $isExpired = $expiresAt ? Carbon::parse($expiresAt)->isPast() : true;
        $daysRemaining = $expiresAt && !$isExpired 
            ? now()->diffInDays(Carbon::parse($expiresAt)) 
            : 0;

        return [
            Stat::make('Plan Actual', $planName)
                ->description($isExpired ? 'Tu acceso ha expirado' : 'Suscripción activa')
                ->descriptionIcon($isExpired ? 'heroicon-m-x-circle' : 'heroicon-m-check-badge')
                ->color($isExpired ? 'danger' : 'success'),

            Stat::make('Días Restantes', $isExpired ? '0' : $daysRemaining)
                ->description($isExpired ? 'Renueva para continuar' : 'Días de acceso premium')
                ->descriptionIcon('heroicon-m-clock')
                ->color($daysRemaining <= 3 ? 'warning' : 'info'),

            Stat::make('Fecha de Vencimiento', $expiresAt ? Carbon::parse($expiresAt)->format('d/m/Y') : 'N/A')
                ->description('Fin del periodo contratado')
                ->descriptionIcon('heroicon-m-calendar'),
        ];
    }
}