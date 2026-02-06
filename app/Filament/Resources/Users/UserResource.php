<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker; // <--- IMPORTANTE: Faltaba esta línea
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

    protected static ?string $navigationGroup = 'Administración';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $navigationIcon = 'heroicon-o-users';

    /**
     * Políticas de acceso: Solo Admin y Super Admin entran al recurso
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->role === User::ROLE_ADMIN || 
               auth()->user()?->role === User::ROLE_SUPER_ADMIN;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === User::ROLE_ADMIN || 
               auth()->user()?->role === User::ROLE_SUPER_ADMIN;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->role === User::ROLE_ADMIN || 
               auth()->user()?->role === User::ROLE_SUPER_ADMIN;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    /**
     * Formulario de Usuario
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
                        ->revealable()
                        ->required(fn (string $operation) => $operation === 'create')
                        ->rule(Password::default())
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn ($state) => filled($state)),
                ])->columns(2),

            // app/Filament/Resources/Users/UserResource.php

Section::make('Suscripción y Pagos')
    ->description('El vencimiento se calcula automáticamente según el plan elegido.')
    ->schema([
        Select::make('subscription_id')
            ->label('Plan Asignado')
            ->relationship('subscription', 'name')
            ->searchable()
            ->preload()
            ->live() // Escucha cambios en tiempo real
            ->afterStateUpdated(function ($state, callable $set) {
                if (!$state) {
                    $set('subscription_expires_at', null);
                    return;
                }
                
                // Buscamos el plan para saber cuántos días sumar
                $plan = \App\Models\Subscription::find($state);
                if ($plan) {
                    $set('subscription_expires_at', now()->addDays($plan->duration_days)->toDateTimeString());
                }
            }),

        DateTimePicker::make('subscription_expires_at')
            ->label('Vencimiento Calculado')
            ->readonly() // Evita errores manuales, el sistema manda
            ->helperText('Se calcula sumando los días del plan a partir de hoy.'),
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
                        ->disabled(fn () => ! $isSuperAdmin)
                        ->helperText(fn () => ! $isSuperAdmin ? 'Solo un Super Admin puede cambiar el rol.' : null),
                ]),
        ]);
    }

    /**
     * Tabla de Usuarios
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
                    ->badge()
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

                    TextColumn::make('subscription.name')
                ->label('Plan')
                ->placeholder('Sin Plan')
                ->sortable(),

            TextColumn::make('subscription_expires_at')
                ->label('Vencimiento')
                ->dateTime('d/m/Y')
                ->sortable()
                ->badge() // Lo hace resaltar más
                ->color(fn ($state): string => 
                    $state && \Illuminate\Support\Carbon::parse($state)->isPast() 
                        ? 'danger'  // Rojo si ya pasó la fecha
                        : 'success' // Verde si está vigente
                )
                ->icon(fn ($state): string => 
                    $state && \Illuminate\Support\Carbon::parse($state)->isPast() 
                        ? 'heroicon-m-x-circle' 
                        : 'heroicon-m-check-badge'
                ),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
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