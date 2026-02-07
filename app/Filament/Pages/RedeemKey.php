<?php

namespace App\Filament\Pages;

use App\Models\LicenseKey;
use App\Models\Payment;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

class RedeemKey extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'Canjear Código';
    protected static ?string $title = 'Activar Suscripción';

    // Esta es la propiedad que causaba el error si faltaba o estaba mal declarada
    protected static string $view = 'filament.pages.redeem-key';

    public $key = '';

    public function redeem()
    {
        // Validar que el código no esté vacío
        if (empty($this->key)) {
            Notification::make()->danger()->title('Por favor ingresa un código.')->send();
            return;
        }

        $license = LicenseKey::where('key', $this->key)
            ->where('is_used', false)
            ->first();

        if (!$license) {
            Notification::make()->danger()->title('Código inválido o ya usado.')->send();
            return;
        }

        $user = auth()->user();
        $plan = $license->subscription;

        // 1. Actualizar Usuario con la suscripción del plan
        $user->update([
            'subscription_id' => $plan->id,
            'subscription_expires_at' => Carbon::now()->addDays($plan->duration_days),
        ]);

        // 2. Marcar Key como usada
        $license->update([
            'is_used' => true,
            'used_at' => now(),
            'user_id' => $user->id,
        ]);

        // 3. Registrar el pago para los totales del Admin Dashboard
        Payment::create([
            'user_id' => $user->id,
            'subscription_id' => $plan->id,
            'amount' => $plan->price,
            'payment_method' => 'license_key',
        ]);

        Notification::make()
            ->success()
            ->title('¡Suscripción Activada!')
            ->body("Has activado con éxito el plan: {$plan->name}")
            ->send();

        $this->key = '';
    }
}