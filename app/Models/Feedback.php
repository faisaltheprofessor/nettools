<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = [
        'user_guid',
        'type',
        'title',
        'description',
        'attachments',
        'status',
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
}
