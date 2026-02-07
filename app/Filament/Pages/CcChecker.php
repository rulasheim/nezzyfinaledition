<?php

namespace App\Filament\Pages;

use App\Services\EpicSwordsGate;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class CcChecker extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Epic (Auth)';
    protected static ?string $navigationGroup = 'AUTH';
    protected static ?string $title = 'Epic Gate';

    protected static string $view = 'filament.pages.cc-checker';

    /**
     * Restricción de acceso: Solo Admins o Usuarios con suscripción activa.
     */
    public static function canAccess(): bool
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($user->isAdmin()) {
            return true;
        }

        return $user->subscription_id && 
               $user->subscription_expires_at && 
               Carbon::parse($user->subscription_expires_at)->isFuture();
    }

    public ?array $data = [];
    public array $logs = [];
    public array $queue = [];
    public bool $isProcessing = false;

    public function mount(): void
    {
        if (!static::canAccess()) {
            abort(403, 'Tu suscripción ha expirado o no tienes acceso a este Gate.');
        }

        $this->form->fill([
            'gen_bin' => '456789xxxxxxxxx',
            'gen_mes' => 'rnd',
            'gen_anio' => 'rnd',
            'cvv_mode' => 'rnd', 
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)->schema([
                    Section::make('Motor de Generación')
                        ->description('Configura el BIN y el CVV para la lista fija de 10.')
                        ->columnSpan(1)
                        ->icon('heroicon-m-cpu-chip') // Icono moderno para la sección
                        ->schema([
                            TextInput::make('gen_bin')
                                ->label('BIN / Patrón')
                                ->placeholder('456789xxxxxxxxx')
                                ->required()
                                ->extraInputAttributes(['class' => 'font-mono tracking-widest']), // Estilo mono para el BIN
                            
                            Grid::make(2)->schema([
                                Select::make('gen_mes')
                                    ->label('Mes')
                                    ->native(false) // Selector moderno estilo Filament
                                    ->prefix('📅')
                                    ->options(['rnd' => '🎲 Random', '01'=>'01','02'=>'02','03'=>'03','04'=>'04','05'=>'05','06'=>'06','07'=>'07','08'=>'08','09'=>'09','10'=>'10','11'=>'11','12'=>'12']),
                                Select::make('gen_anio')
                                    ->label('Año')
                                    ->native(false) // Selector moderno estilo Filament
                                    ->prefix('🗓️')
                                    ->options(function(){
                                        $y = date('Y');
                                        $opts = ['rnd' => '🎲 Random'];
                                        for($i=0; $i<8; $i++) $opts[$y+$i] = $y+$i;
                                        return $opts;
                                    }),
                            ]),

                            // Configuración de CVV Modernizada
                            Grid::make(2)->schema([
                                Select::make('cvv_mode')
                                    ->label('Modo CVV')
                                    ->native(false)
                                    ->prefix('🔒')
                                    ->options([
                                        'rnd' => '🎲 Random',
                                        'manual' => '✍️ Manual',
                                    ])
                                    ->live(), 
                                
                                TextInput::make('gen_cvv')
                                    ->label('CVV Fijo')
                                    ->placeholder('000')
                                    ->numeric()
                                    ->prefix('🔑')
                                    ->maxLength(4)
                                    ->visible(fn ($get) => $get('cvv_mode') === 'manual')
                                    ->required(fn ($get) => $get('cvv_mode') === 'manual'),
                            ]),

                            \Filament\Forms\Components\Actions::make([
                                \Filament\Forms\Components\Actions\Action::make('generate_now')
                                    ->label('Generar y Cargar (10)')
                                    ->icon('heroicon-m-sparkles')
                                    ->color('success')
                                    ->size('lg') // Botón más prominente
                                    ->action(fn () => $this->runInternalGeneration()),
                            ]),
                        ]),

                    Section::make('Cola de Procesamiento')
                        ->description('Tarjetas listas para el Service.')
                        ->columnSpan(1)
                        ->icon('heroicon-m-list-bullet')
                        ->schema([
                            Textarea::make('ccs')
                                ->label('Lista CCs')
                                ->rows(10)
                                ->placeholder("cc|mm|yyyy|cvv")
                                ->required()
                                ->extraInputAttributes(['class' => 'font-mono text-sm leading-relaxed']),
                        ]),
                ])
            ])
            ->statePath('data');
    }

    public function runInternalGeneration()
    {
        $settings = $this->data;
        $generated = [];
        $qty = 10; 
        
        for ($i = 0; $i < $qty; $i++) {
            $cc = $this->generateLuhn($settings['gen_bin']);
            $mes = ($settings['gen_mes'] === 'rnd') ? str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) : $settings['gen_mes'];
            $anio = ($settings['gen_anio'] === 'rnd') ? rand((int)date('Y'), (int)date('Y')+5) : $settings['gen_anio'];
            
            $cvv = ($settings['cvv_mode'] === 'manual' && !empty($settings['gen_cvv'])) 
                ? str_pad($settings['gen_cvv'], 3, '0', STR_PAD_LEFT) 
                : rand(100, 999);

            $generated[] = "$cc|$mes|$anio|$cvv";
        }

        $this->data['ccs'] = implode("\n", $generated);
        $this->form->fill($this->data);
        Notification::make()
            ->success()
            ->icon('heroicon-o-check-badge')
            ->title("Generadas 10 tarjetas con éxito.")
            ->send();
    }

    protected function generateLuhn($pattern)
    {
        $clean = preg_replace('/[^0-9x]/', '', strtolower($pattern));
        $clean = str_pad(substr($clean, 0, 16), 16, 'x');
        $temp = '';
        for ($i = 0; $i < 15; $i++) { $temp .= ($clean[$i] === 'x') ? rand(0, 9) : $clean[$i]; }
        $sum = 0;
        for ($i = 0; $i < 15; $i++) {
            $d = (int)$temp[$i];
            if ($i % 2 == 0) { $d *= 2; if ($d > 9) $d -= 9; }
            $sum += $d;
        }
        return $temp . ((10 - ($sum % 10)) % 10);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('start')
                ->label('Iniciar Epic Gate')
                ->icon('heroicon-o-play-circle')
                ->color('primary')
                ->disabled(fn() => $this->isProcessing)
                ->action(fn() => $this->startChecking()),

            Action::make('stop')
                ->label('Detener')
                ->icon('heroicon-o-stop-circle')
                ->color('danger')
                ->visible(fn() => $this->isProcessing)
                ->action(fn() => $this->isProcessing = false),
        ];
    }

    public function startChecking()
    {
        $lines = array_filter(explode("\n", $this->data['ccs'] ?? ''), fn($l) => trim($l) !== '');
        if (empty($lines)) return;

        $this->queue = array_values($lines);
        $this->isProcessing = true;
        $this->dispatch('process-card');
    }

    #[On('process-card')]
    public function processCard()
    {
        if (!static::canAccess()) {
            $this->isProcessing = false;
            Notification::make()
                ->danger()
                ->title('Suscripción Expirada')
                ->body('Tu acceso ha terminado. Por favor, renueva tu plan.')
                ->persistent()
                ->send();
            return;
        }

        if (empty($this->queue) || !$this->isProcessing) {
            $this->isProcessing = false;
            return;
        }

        $cc = array_shift($this->queue);
        $checker = new EpicSwordsGate();
        $res = $checker->check($cc);

        $rawArray = is_array($res['raw']) ? $res['raw'] : json_decode($res['raw'], true);
        $serverRef = 'N/A';

        if (isset($rawArray['error']['doc_url'])) {
            $serverRef = Str::afterLast($rawArray['error']['doc_url'], '/');
        } elseif (isset($rawArray['id'])) {
            $serverRef = substr($rawArray['id'], 0, 12) . '...';
        }

        array_unshift($this->logs, [
            'cc' => $cc,
            'result' => $res['result'],
            'message' => $res['message'],
            'server_ref' => $serverRef,
            'raw' => is_array($res['raw']) ? json_encode($res['raw'], JSON_PRETTY_PRINT) : $res['raw'],
            'time' => now()->format('H:i:s'),
            'color' => match($res['result']) { 'LIVE' => 'success', 'DEAD' => 'danger', default => 'warning' }
        ]);

        $this->dispatch('process-card');
    }
}