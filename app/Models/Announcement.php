<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'title',
        'image_path',
        'content',
        'is_active',
        'sort_order',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];
}