<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    // Todo el código debe estar DENTRO de estas llaves
    protected $fillable = [
        'name', 
        'price', 
        'duration_days', 
        'is_active'
    ];

    /**
     * Obtener los usuarios asociados a esta suscripción.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}