<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackComment extends Model
{
    protected $fillable = ['feedback_id','user_guid','body'];

    public function feedback(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_guid', 'guid');
    }

    public function reactions()
    {
        return $this->hasMany(FeedbackCommentReaction::class, 'comment_id');
    }

}
