<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class UserStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();

        return [
            Stat::make('Usuario Logueado', $user->name)
                ->description($user->email)
                ->descriptionIcon('heroicon-m-user')
                ->color('primary'),

            Stat::make('Tu Rol en el Sistema', $this->getRoleLabel($user->role))
                ->description('Nivel de acceso actual')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color($user->role === 'super_admin' ? 'danger' : 'success'),

            Stat::make('Miembro desde', $user->created_at->format('d/m/Y'))
                ->description('Fecha de registro')
                ->descriptionIcon('heroicon-m-calendar-days'),
        ];
    }

    private function getRoleLabel(string $role): string
    {
        return match ($role) {
            'super_admin' => 'Super Admin',
            'admin' => 'Administrador',
            default => 'Usuario Estándar',
        };
    }
}