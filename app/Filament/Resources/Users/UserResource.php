<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
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

    public static function form(Form $form): Form
    {
        $isSuperAdmin = auth()->user()?->isSuperAdmin() ?? false;

        return $form->schema([
            Section::make('Datos del usuario')
                ->description('Información básica y credenciales de acceso.')
                ->icon('heroicon-m-user-circle')
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre Completo')
                        ->prefixIcon('heroicon-m-user')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label('Correo Electrónico')
                        ->prefixIcon('heroicon-m-envelope')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    TextInput::make('password')
                        ->label('Contraseña')
                        ->password()
                        ->revealable()
                        ->prefixIcon('heroicon-m-lock-closed')
                        ->required(fn (string $operation) => $operation === 'create')
                        ->rule(Password::default())
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn ($state) => filled($state)),
                ])->columns(2),

            Section::make('Suscripción y Pagos')
                ->description('Control de planes y expiración automática.')
                ->icon('heroicon-m-credit-card')
                ->schema([
                    Select::make('subscription_id')
                        ->label('Plan Asignado')
                        ->relationship('subscription', 'name')
                        ->native(false) // Desplegable moderno
                        ->searchable()
                        ->preload()
                        ->live()
                        ->prefixIcon('heroicon-m-sparkles')
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (!$state) {
                                $set('subscription_expires_at', null);
                                return;
                            }
                            $plan = \App\Models\Subscription::find($state);
                            if ($plan) {
                                $set('subscription_expires_at', now()->addDays($plan->duration_days)->toDateTimeString());
                            }
                        }),

                    DateTimePicker::make('subscription_expires_at')
                        ->label('Vencimiento del Acceso')
                        ->prefixIcon('heroicon-m-calendar-days')
                        ->readonly()
                        ->helperText('Calculado automáticamente según la duración del plan.'),
                ])->columns(2),

            Section::make('Seguridad y Roles')
                ->description('Permisos de nivel administrativo.')
                ->icon('heroicon-m-shield-check')
                ->schema([
                    Select::make('role')
                        ->label('Nivel de Acceso')
                        ->native(false) // Desplegable moderno
                        ->required()
                        ->prefixIcon('heroicon-m-identification')
                        ->default(User::ROLE_USER)
                        ->options(fn () => $isSuperAdmin
                            ? [
                                User::ROLE_USER => 'Usuario Estándar',
                                User::ROLE_ADMIN => 'Administrador',
                                User::ROLE_SUPER_ADMIN => 'Super Admin (Acceso Total)',
                            ]
                            : [
                                User::ROLE_USER => 'Usuario Estándar',
                            ]
                        )
                        ->disabled(fn () => ! $isSuperAdmin)
                        ->helperText(fn () => ! $isSuperAdmin ? 'Solo el Super Admin puede modificar roles jerárquicos.' : null),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label('Email')
                    ->icon('heroicon-m-envelope')
                    ->copyable()
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
                    ->label('Plan Actual')
                    ->placeholder('Sin Plan')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('subscription_expires_at')
                    ->label('Vencimiento')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => 
                        $state && \Illuminate\Support\Carbon::parse($state)->isPast() 
                            ? 'danger' 
                            : 'success'
                    )
                    ->icon(fn ($state): string => 
                        $state && \Illuminate\Support\Carbon::parse($state)->isPast() 
                            ? 'heroicon-m-x-circle' 
                            : 'heroicon-m-check-badge'
                    ),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y')
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