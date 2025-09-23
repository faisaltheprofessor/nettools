<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirewallTemplate extends Model
{
    protected $fillable = ['name', 'sources', 'destinations', 'ports'];

    protected $casts = [
        'sources' => 'array',
        'destinations' => 'array',
        'ports' => 'array',
    ];
}
