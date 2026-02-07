<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnnouncementResource\Pages;
use App\Models\Announcement;
use App\Models\User; // Asegúrate de importar el modelo User
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'Anuncios';
    protected static ?string $navigationGroup = 'Administración';

    /**
     * Restringe el acceso al recurso.
     * Solo Admin y Super Admin podrán ver y gestionar anuncios.
     */
    public static function canViewAny(): bool
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return $user && (
            $user->role === User::ROLE_ADMIN || 
            $user->role === User::ROLE_SUPER_ADMIN
        );
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Nueva Publicación')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Título de la Promo')
                        ->required(),
                    
                    Forms\Components\FileUpload::make('image_path')
                        ->label('Archivo (Imagen o Video)')
                        ->disk('public')
                        ->directory('announcements')
                        ->visibility('public')
                        ->acceptedFileTypes(['image/*', 'video/mp4', 'video/quicktime', 'video/webm'])
                        ->maxSize(30720)
                        ->required(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Publicado')
                        ->default(true),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Prioridad / Orden')
                        ->numeric()
                        ->default(0),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Flyer')
                    ->disk('public')
                    ->square(),

                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Publicados'),
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
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }
}