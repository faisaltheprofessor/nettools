<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $fillable = [
        'user_guid',
        'type',
        'title',
        'body',
        'url',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function scopeUnread($q)
    {
        return $q->whereNull('read_at');
    }

    /**
     * Create a new in‑app notification.
     *
     * @param  string      $userGuid  recipient user's GUID
     * @param  string      $title     short title shown in the bell dropdown
     * @param  string|null $url       where to navigate (can include #anchors)
     * @param  string|null $body      optional body/preview text
     * @param  string      $type      info|success|warning|error|anliegen|...
     */
    public static function notify(string $userGuid, string $title, ?string $url = null, ?string $body = null, string $type = 'info'): self
    {
        return static::create([
            'user_guid' => $userGuid,
            'type'      => $type,
            'title'     => $title,
            'body'      => $body,
            'url'       => $url,
        ]);
    }
}
