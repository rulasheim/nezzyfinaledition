<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LicenseKey extends Model
{
    protected $fillable = ['key', 'subscription_id', 'is_used', 'used_at', 'user_id'];

    /**
     * Casting de atributos.
     * Esto asegura que 'used_at' se maneje como objeto Carbon y no como string.
     */
    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    // Relación con el plan de suscripción
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Relación con el usuario que canjeó la llave.
     * Esta es la relación que falta para que aparezca el nombre en la tabla.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper para generar la nomenclatura solicitada
    public static function generateCode(): string
    {
        return 'nezzychk-' . strtoupper(Str::random(12));
    }
}