<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // Configuración de Navegación (Compatibilidad v3)
    protected static ?string $navigationGroup = 'Administración';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $navigationIcon = 'heroicon-o-users';

    /**
     * Políticas de acceso básicas
     */
    public static function canViewAny(): bool
    {
// Solo Admin y Super Admin ven la lista de usuarios
        return auth()->user()?->role === User::ROLE_ADMIN || 
               auth()->user()?->role === User::ROLE_SUPER_ADMIN;    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === User::ROLE_ADMIN || 
               auth()->user()?->role === User::ROLE_SUPER_ADMIN;
    }

    public static function canEdit(Model $record): bool
    {
        // Un usuario Admin/Super Admin puede editar a cualquiera
        return auth()->user()?->role === User::ROLE_ADMIN || 
               auth()->user()?->role === User::ROLE_SUPER_ADMIN;
    }

    public static function canDelete(Model $record): bool
    {
        // Verifica si el usuario es Super Admin (Requiere método isSuperAdmin en modelo User)
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    /**
     * Definición del Formulario
     */
    public static function form(Form $form): Form
    {
        $isSuperAdmin = auth()->user()?->isSuperAdmin() ?? false;

        return $form->schema([
            Section::make('Datos del usuario')
                ->description('Información básica y credenciales de acceso.')
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    TextInput::make('password')
                        ->label('Contraseña')
                        ->password()
                        ->revealable() // <--- Permite ver la contraseña
                        ->required(fn (string $operation) => $operation === 'create')
                        ->rule(Password::default())
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn ($state) => filled($state)),
                ])->columns(2),

            Section::make('Seguridad y Roles')
                ->description('Asignación de permisos dentro del sistema.')
                ->schema([
                    Select::make('role')
                        ->label('Rol')
                        ->required()
                        ->default(User::ROLE_USER)
                        ->options(fn () => $isSuperAdmin
                            ? [
                                User::ROLE_USER => 'Usuario',
                                User::ROLE_ADMIN => 'Admin',
                                User::ROLE_SUPER_ADMIN => 'Super Admin',
                            ]
                            : [
                                User::ROLE_USER => 'Usuario',
                            ]
                        )
                        ->disabled(fn () => ! $isSuperAdmin) // Solo Super Admin cambia roles
                        ->helperText(fn () => ! $isSuperAdmin ? 'Solo un Super Admin puede cambiar el rol.' : null),
                ]),
        ]);
    }

    /**
     * Definición de la Tabla
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->label('Rol')
                    ->badge() // Le da un estilo visual más profesional
                    ->color(fn (string $state): string => match ($state) {
                        User::ROLE_SUPER_ADMIN => 'danger',
                        User::ROLE_ADMIN => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        User::ROLE_SUPER_ADMIN => 'Super Admin',
                        User::ROLE_ADMIN => 'Admin',
                        default => 'Usuario',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Puedes añadir filtros por rol aquí más adelante
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                // Acciones masivas aquí
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}