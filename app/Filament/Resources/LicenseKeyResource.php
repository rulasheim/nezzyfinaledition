<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LicenseKeyResource\Pages;
use App\Filament\Resources\LicenseKeyResource\RelationManagers;
use App\Models\LicenseKey;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LicenseKeyResource extends Resource
{
    protected static ?string $model = LicenseKey::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'License Keys';
    protected static ?string $navigationGroup = 'Administración';

    /**
     * Restringe el acceso al recurso únicamente a Super Admins
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->role === User::ROLE_SUPER_ADMIN;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('subscription_id')
                ->label('Plan a Otorgar')
                ->options(\App\Models\Subscription::all()->pluck('name', 'id'))
                ->native(false) // Lista desplegable moderna
                ->searchable() 
                ->required(),
            Forms\Components\TextInput::make('key')
                ->label('Código Generado')
                ->default(fn () => \App\Models\LicenseKey::generateCode())
                ->readonly()
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Código')
                    ->fontFamily('mono')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subscription.name')
                    ->label('Plan Asociado')
                    ->sortable(),

                IconColumn::make('is_used')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),

                TextColumn::make('user.name')
                ->label('Canjeado por')
                ->placeholder('Disponible') // Solo se muestra si user_id es null
                ->searchable()
                ->sortable(),
        ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_used')
                    ->label('Estado de Uso')
                    ->native(false), // Filtro moderno
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLicenseKeys::route('/'),
            'create' => Pages\CreateLicenseKey::route('/create'),
            'edit' => Pages\EditLicenseKey::route('/{record}/edit'),
        ];
    }
}