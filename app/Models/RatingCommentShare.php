<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RatingCommentShare extends Model
{
    protected $table = 'rating_comments_shares';

    protected $fillable = [
        'user_id',
        'feed_id',
        'rating',
        'comment',
        'shares',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function feed()
    {
        return $this->belongsTo(Feed::class);
    }

    public function comments()
    {
        return $this->belongsToMany(Comment::class, 'rating_comments_shares', 'id', 'rating_comment_share_id');
    }
}
