<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    protected $fillable = [
        'user_guid',
        'is_global',
        'name',
        'type',
        'url',
        'icon',
        'icon_name',
        'favicon',
        'parent_id',
        'sort_order',
    ];

    protected $casts = [
        'is_global' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        // Browser-like: manual order then name
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function user()
    {
        // users.guid is the PK/unique column
        return $this->belongsTo(User::class, 'user_guid', 'guid');
    }

    /** Scope: items a given user can see (their own + global) */
    public function scopeAccessible($q, ?string $userGuid)
    {
        return $q->where(function ($qq) use ($userGuid) {
            $qq->where('user_guid', $userGuid)
                ->orWhere('is_global', true);
        });
    }

    /** Helper: extract host/domain safely (null if not URL) */
    public function getHostAttribute(): ?string
    {
        if (!$this->url) return null;
        $host = parse_url($this->url, PHP_URL_HOST);
        return $host ?: null;
    }
}
