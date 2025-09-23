<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ServiceAction extends Model
{
    protected $fillable = [
        'service', 'action', 'target', 'user_id', 'user_name', 'user_username', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public static function log(string $service, string $action, ?string $target = null, array $meta = []): self
    {
        $u = Auth::user();

        return self::create([
            'service' => $service,
            'action' => $action,
            'target' => $target,
            'user_id' => $u?->id,
            'user_name' => $u?->name,
            'user_username' => $u?->username ?? $u?->email,
            'meta' => $meta ?: null,
        ]);
    }
}
