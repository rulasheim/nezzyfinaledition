<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\User; // Importación para validar roles
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Ingresos por Suscripciones';

    /**
     * Restringe la visibilidad del widget solo a administradores.
     */
    public static function canView(): bool
    {
        return auth()->user()?->role === User::ROLE_ADMIN || 
               auth()->user()?->role === User::ROLE_SUPER_ADMIN;
    }

    protected function getData(): array
    {
        // Esto suma los montos de la tabla pagos por mes
        $data = Payment::selectRaw('SUM(amount) as total, DATE_FORMAT(created_at, "%Y-%m") as month')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Ingresos ($)',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => '#fbbf24',
                    'borderColor' => '#fbbf24',
                ],
            ],
            'labels' => $data->pluck('month')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}