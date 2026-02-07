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
use Illuminate\Support\Carbon; // Importante para manejar fechas

class CcChecker extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Epic (Auth)';
    protected static ?string $navigationGroup = 'AUTH';
    protected static ?string $title = 'Epic Gate';

    protected static string $view = 'filament.pages.cc-checker';

    /**
     * Restricción de acceso a la página y al menú
     */
    public static function canAccess(): bool
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Los administradores siempre tienen acceso
        if ($user->isAdmin()) {
            return true;
        }

        // Suscriptores: deben tener plan activo y no estar vencidos
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
        // Doble verificación de seguridad al cargar
        if (!static::canAccess()) {
            abort(403, 'Tu suscripción ha expirado o no tienes acceso a este Gate.');
        }

        $this->form->fill([
            'gen_bin' => '456789xxxxxxxxx',
            'quantity' => 10,
            'gen_mes' => 'rnd',
            'gen_anio' => 'rnd'
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)->schema([
                    Section::make('Motor de Generación')
                        ->description('Configura el BIN para generar la lista.')
                        ->columnSpan(1)
                        ->schema([
                            TextInput::make('gen_bin')
                                ->label('BIN / Patrón')
                                ->required(),
                            Grid::make(2)->schema([
                                Select::make('gen_mes')
                                    ->label('Mes')
                                    ->options(['rnd' => 'Random', '01'=>'01','02'=>'02','03'=>'03','04'=>'04','05'=>'05','06'=>'06','07'=>'07','08'=>'08','09'=>'09','10'=>'10','11'=>'11','12'=>'12']),
                                Select::make('gen_anio')
                                    ->label('Año')
                                    ->options(function(){
                                        $y = date('Y');
                                        $opts = ['rnd' => 'Random'];
                                        for($i=0; $i<8; $i++) $opts[$y+$i] = $y+$i;
                                        return $opts;
                                    }),
                            ]),
                            TextInput::make('quantity')
                                ->label('Cantidad a Generar')
                                ->numeric()
                                ->default(10),
                            \Filament\Forms\Components\Actions::make([
                                \Filament\Forms\Components\Actions\Action::make('generate_now')
                                    ->label('Generar y Cargar')
                                    ->icon('heroicon-m-sparkles')
                                    ->color('success')
                                    ->action(fn () => $this->runInternalGeneration()),
                            ]),
                        ]),

                    Section::make('Cola de Procesamiento')
                        ->description('Tarjetas listas para el Service.')
                        ->columnSpan(1)
                        ->schema([
                            Textarea::make('ccs')
                                ->label('Lista CCs')
                                ->rows(10)
                                ->placeholder("cc|mm|yyyy|cvv")
                                ->required(),
                        ]),
                ])
            ])
            ->statePath('data');
    }

    public function runInternalGeneration()
    {
        $settings = $this->data;
        $generated = [];
        $qty = $settings['quantity'] ?? 10;
        
        for ($i = 0; $i < $qty; $i++) {
            $cc = $this->generateLuhn($settings['gen_bin']);
            $mes = ($settings['gen_mes'] === 'rnd') ? str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) : $settings['gen_mes'];
            $anio = ($settings['gen_anio'] === 'rnd') ? rand((int)date('Y'), (int)date('Y')+5) : $settings['gen_anio'];
            $cvv = rand(100, 999);
            $generated[] = "$cc|$mes|$anio|$cvv";
        }

        $this->data['ccs'] = implode("\n", $generated);
        $this->form->fill($this->data);
        Notification::make()->success()->title("Generadas $qty tarjetas con éxito.")->send();
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
                ->icon('heroicon-o-play')
                ->color('primary')
                ->disabled(fn() => $this->isProcessing)
                ->action(fn() => $this->startChecking()),

            Action::make('stop')
                ->label('Detener')
                ->icon('heroicon-o-stop')
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
        // Verificación de seguridad en cada paso para evitar bypass si expira durante el proceso
        if (!static::canAccess()) {
            $this->isProcessing = false;
            Notification::make()
                ->danger()
                ->title('Acceso Denegado')
                ->body('Tu suscripción ha vencido mientras procesabas. Por favor renueva.')
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