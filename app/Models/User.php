<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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

// app/Models/User.php

protected static function booted()
{
    static::updating(function ($user) {
        // Si el subscription_id cambió...
        if ($user->isDirty('subscription_id') && $user->subscription_id) {
            $plan = $user->subscription; // Usamos la relación
            if ($plan) {
                $user->subscription_expires_at = now()->addDays($plan->duration_days);
            }
        }
    });
}
}
