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

        // Verificamos si la fecha ya pasó
        $isExpired = $expiresAt ? Carbon::parse($expiresAt)->isPast() : true;

        // Calculamos la diferencia asegurando que sea un número entero
        // diffInDays devuelve la diferencia absoluta en días naturales
        $daysRemaining = $expiresAt && !$isExpired 
            ? (int) now()->diffInDays(Carbon::parse($expiresAt)) 
            : 0;

        return [
            Stat::make('Mi Plan', $planName)
                ->description($isExpired ? 'Acceso expirado' : 'Suscripción activa')
                ->color($isExpired ? 'danger' : 'success')
                ->icon('heroicon-m-credit-card'),

            Stat::make('Días Restantes', (string) $daysRemaining)
                ->description($isExpired ? 'Renueva para continuar' : 'Días de acceso Premium')
                ->color($isExpired ? 'danger' : ($daysRemaining <= 3 ? 'warning' : 'primary'))
                ->icon('heroicon-m-clock'),

            Stat::make('Vencimiento', $expiresAt ? Carbon::parse($expiresAt)->format('d/m/Y') : 'N/A')
                ->description('Fecha de término del plan')
                ->icon('heroicon-m-calendar'),
        ];
    }
}