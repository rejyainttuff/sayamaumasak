<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CulinaryStory extends Model
{
    protected $fillable = [
        'food_id',
        'user_id',
        'title',
        'slug',
        'content',
        'thumbnail',
        'approval_status',
    ];

    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
