<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class BinGenerator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'BIN Generador';
    protected static ?string $title = 'Generador Pro (Luhn)';
    protected static ?string $navigationGroup = 'Herramientas';

    protected static string $view = 'filament.pages.bin-generator';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'gen_bin' => '456789xxxxxxxxx',
            'quantity' => 10,
            'gen_mes' => 'rnd',
            'gen_anio' => 'rnd',
            'gen_cvv_rnd' => true,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuración del Generador')
                    ->schema([
                        TextInput::make('gen_bin')
                            ->label('BIN / Patrón')
                            ->placeholder('Ej: 421316xxxxxxxxxx')
                            ->required(),

                        Grid::make(2)->schema([
                            Select::make('gen_mes')
                                ->label('Mes')
                                ->options([
                                    'rnd' => 'Aleatorio',
                                    '01'=>'01','02'=>'02','03'=>'03','04'=>'04','05'=>'05','06'=>'06',
                                    '07'=>'07','08'=>'08','09'=>'09','10'=>'10','11'=>'11','12'=>'12',
                                ])->default('rnd'),

                            Select::make('gen_anio')
                                ->label('Año')
                                ->options(function(){
                                    $y = date('Y');
                                    $opts = ['rnd' => 'Aleatorio'];
                                    for($i=0; $i<8; $i++) $opts[$y+$i] = $y+$i;
                                    return $opts;
                                })->default('rnd'),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('quantity')
                                ->label('Cantidad')
                                ->numeric()
                                ->default(10)
                                ->minValue(1)
                                ->maxValue(100),

                            Checkbox::make('gen_cvv_rnd')
                                ->label('CVV Aleatorio')
                                ->default(true),
                        ]),

                        Textarea::make('results')
                            ->label('Tarjetas Generadas')
                            ->rows(10)
                            ->readonly()
                            ->placeholder('Los resultados aparecerán aquí...')
                            ->extraAttributes(['class' => 'font-mono text-success-600']),
                    ])
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generar Tarjetas')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->action(fn () => $this->runGeneration()),
            
            Action::make('clear')
                ->label('Limpiar')
                ->icon('heroicon-o-trash')
                ->color('gray')
                ->action(function() {
                    $this->data['results'] = '';
                    $this->form->fill($this->data);
                }),
        ];
    }

    public function runGeneration()
    {
        $settings = $this->data;
        $generated = [];
        $qty = $settings['quantity'] ?? 10;
        
        for ($i = 0; $i < $qty; $i++) {
            $cc = $this->generateLuhnCard($settings['gen_bin']);
            $mes = ($settings['gen_mes'] === 'rnd') ? str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) : $settings['gen_mes'];
            $anio = ($settings['gen_anio'] === 'rnd') ? rand((int)date('Y'), (int)date('Y')+5) : $settings['gen_anio'];
            $cvv = $settings['gen_cvv_rnd'] ? rand(100, 999) : '000';
            
            $generated[] = "$cc|$mes|$anio|$cvv";
        }

        $this->data['results'] = implode("\n", $generated);
        $this->form->fill($this->data);
        
        Notification::make()->success()->title("Se han generado $qty tarjetas")->send();
    }

    protected function generateLuhnCard($pattern)
    {
        $cleanPattern = preg_replace('/[^0-9x]/', '', strtolower($pattern));
        $cleanPattern = str_pad(substr($cleanPattern, 0, 16), 16, 'x');

        $tempCC = '';
        for ($i = 0; $i < 15; $i++) {
            $tempCC .= ($cleanPattern[$i] === 'x') ? rand(0, 9) : $cleanPattern[$i];
        }

        $sum = 0;
        for ($i = 0; $i < 15; $i++) {
            $digit = (int)$tempCC[$i];
            if ($i % 2 == 0) {
                $digit *= 2;
                if ($digit > 9) $digit -= 9;
            }
            $sum += $digit;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;
        return $tempCC . $checkDigit;
    }
}