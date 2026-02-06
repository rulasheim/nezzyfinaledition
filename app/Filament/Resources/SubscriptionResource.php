<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use App\Models\User; // <--- IMPORTANTE: Importamos el modelo User para los roles
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Planes de Suscripción';
    protected static ?string $navigationGroup = 'Administración';

    /**
     * Solo Admin y Super Admin pueden ver y gestionar los planes
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->role === User::ROLE_ADMIN || 
               auth()->user()?->role === User::ROLE_SUPER_ADMIN;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Detalles del Plan')
                ->description('Configura los costos y duración de este plan.')
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre del Plan')
                        ->required()
                        ->placeholder('Ej: Plan Mensual Pro'),
                    
                    TextInput::make('price')
                        ->label('Precio')
                        ->numeric()
                        ->prefix('$')
                        ->required(),
                    
                    TextInput::make('duration_days')
                        ->label('Duración (Días)')
                        ->numeric()
                        ->required()
                        ->suffix('días'),

                    Toggle::make('is_active')
                        ->label('Plan Activo')
                        ->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('price')
                    ->label('Precio')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('duration_days')
                    ->label('Duración')
                    ->suffix(' días')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('users_count')
                    ->label('Usuarios')
                    ->counts('users'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}