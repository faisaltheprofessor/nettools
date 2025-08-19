<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackCommentReaction extends Model
{
    protected $fillable = ['comment_id', 'user_guid', 'emoji'];

    public function comment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FeedbackComment::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_guid', 'guid');
    }
}
