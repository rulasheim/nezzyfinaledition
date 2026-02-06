<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    // Autorizamos los campos para que Laravel permita guardarlos
    protected $fillable = [
        'user_id',
        'subscription_id',
        'amount',
        'payment_method',
    ];

    /**
     * Relación: Un pago pertenece a un usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación: Un pago pertenece a una suscripción (plan)
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}