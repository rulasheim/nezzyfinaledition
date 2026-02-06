<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Payment;


class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // ===== ROLES DISPONIBLES =====
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // 👈 IMPORTANTE
        'subscription_id',
    'subscription_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ===== HELPERS DE ROLES =====

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN]);    }

    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    /**
     * Admin o Super Admin
     */
    public function isStaff(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
        ], true);
    }

    public function subscription()
{
    return $this->belongsTo(Subscription::class);
}

// Dentro de app/Models/User.php

public function payments()
{
    return $this->hasMany(Payment::class);
}


// Dentro de la clase User
// app/Models/User.php

protected static function booted()
{
    // Función interna para registrar el pago
    $registrarPago = function ($user) {
        if ($user->subscription_id) {
            $plan = \App\Models\Subscription::find($user->subscription_id);
            if ($plan) {
                \App\Models\Payment::create([
                    'user_id' => $user->id,
                    'subscription_id' => $plan->id,
                    'amount' => $plan->price,
                    'payment_method' => 'admin_manual',
                ]);
            }
        }
    };

    // 1. Al CREAR un usuario nuevo con suscripción
    static::created(function ($user) use ($registrarPago) {
        $registrarPago($user);
    });

    // 2. Al ACTUALIZAR un usuario existente
    static::updated(function ($user) use ($registrarPago) {
        if ($user->isDirty('subscription_id')) {
            $registrarPago($user);
        }
    });
}
}
