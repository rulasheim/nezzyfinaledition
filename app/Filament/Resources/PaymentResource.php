<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\User; // <--- ESTA IMPORTACIÓN ES LA QUE FALTABA
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    // Cambiado a un icono de moneda/pagos
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Historial de Pagos';
    protected static ?string $navigationGroup = 'Administración';

    /**
     * Solo Admin y Super Admin pueden ver este recurso
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->role === User::ROLE_ADMIN || 
               auth()->user()?->role === User::ROLE_SUPER_ADMIN;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Por lo general, los pagos son de solo lectura si se generan automáticamente
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subscription.name')
                    ->label('Plan Activado')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Pago')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            // No solemos permitir crear o editar pagos manualmente para evitar fraude
        ];
    }
}