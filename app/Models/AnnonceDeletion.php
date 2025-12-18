<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnonceDeletion extends Model
{
    protected $fillable = [
        'annonce_id',
        'user_id',
        'titre',
        'prix',
        'was_sold',
    ];

    protected $casts = [
        'was_sold' => 'boolean',
    ];
}
