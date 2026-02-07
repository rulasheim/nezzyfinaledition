<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class SubscribedUsersChart extends BaseWidget
{
    /**
     * Determina si el widget debe ser visible.
     * Solo permite el acceso a Admin y Super Admin.
     */
    public static function canView(): bool
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return $user && (
            $user->role === User::ROLE_ADMIN || 
            $user->role === User::ROLE_SUPER_ADMIN
        );
    }

    protected function getStats(): array
    {
        // Contamos usuarios con suscripción no vencida
        $activeSubscribers = User::whereNotNull('subscription_id')
            ->where('subscription_expires_at', '>', Carbon::now())
            ->count();

        // Contamos usuarios totales para comparar
        $totalUsers = User::count();

        return [
            Stat::make('Usuarios Suscritos', $activeSubscribers)
                ->description('Usuarios con acceso premium activo')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 9]),

            Stat::make('Usuarios Totales', $totalUsers)
                ->description('Registros en la plataforma')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
}