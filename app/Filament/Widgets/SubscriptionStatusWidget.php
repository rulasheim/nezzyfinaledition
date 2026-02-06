<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SubscriptionStatusWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $expiresAt = $user->subscription_expires_at;
        $planName = $user->subscription?->name ?? 'Sin Plan Activo';

        $isExpired = $expiresAt ? Carbon::parse($expiresAt)->isPast() : true;
        $daysRemaining = $expiresAt && !$isExpired 
            ? now()->diffInDays(Carbon::parse($expiresAt)) 
            : 0;

        return [
            Stat::make('Mi Plan', $planName)
                ->description($isExpired ? 'Acceso expirado' : 'Suscripción activa')
                ->color($isExpired ? 'danger' : 'success'),

            Stat::make('Días Restantes', $isExpired ? '0' : $daysRemaining)
                ->description('Días de acceso premium')
                ->color($daysRemaining <= 3 ? 'warning' : 'info'),

            Stat::make('Vencimiento', $expiresAt ? Carbon::parse($expiresAt)->format('d/m/Y') : 'N/A'),
        ];
    }
}