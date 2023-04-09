<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Feed extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'file_id',
        'resolved_by',
        'content',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'file_ids' => 'integer',
        'resolved_by' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getShortContent($words = 120, $end = '...')
    {
        return Str::limit($this->content, $words, $end);
    }


    public function file()
    {
        return $this->belongsTo(File::class);
    }
}
