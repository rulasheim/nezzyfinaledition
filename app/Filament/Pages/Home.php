<?php

namespace App\Filament\Pages;

use App\Models\Announcement;
use Filament\Pages\Page;

class Home extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Inicio';
    protected static ?string $title = 'Inicio';
    protected static ?int $navigationSort = -2;

    protected static string $view = 'filament.pages.home';

    protected function getViewData(): array
    {
        return [
            // Solo anuncios activos y ordenados por prioridad
            'announcements' => Announcement::where('is_active', true)
                ->orderBy('sort_order', 'asc')
                ->get(),
        ];
    }
}