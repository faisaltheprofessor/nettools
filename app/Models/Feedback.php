<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = [
        'user_id', 'assigned_to_id', 'title', 'type', 'description', 'url', 'user_agent',
        'attachments', 'status', 'tags', 'priority', 'assigned_to_id', 'user_guid'
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_guid', 'guid');
    }

    public function comments()
    {
        return $this->hasMany(FeedbackComment::class)->latest();
    }

    public static function statuses(): array
    {
        return ['open', 'in_progress', 'resolved', 'closed', 'wontfix'];
    }

    public const TAG_SUGGESTIONS = ['UI', 'Performance', 'Bug', 'Importer', 'Excel', 'Vorschlag', 'Tracking Tool'];

    public const STATUSES = ['open', 'in_progress', 'resolved', 'in_review', 'closed', 'wontfix'];

    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(FeedbackReaction::class);
    }

    public function userHasReacted(int $userId, ?int $commentId, string $emoji): bool
    {
        return $this->reactions()
            ->where('user_id', $userId)
            ->where('emoji', $emoji)
            ->where('comment_id', $commentId)
            ->exists();
    }
}
