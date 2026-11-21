<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CulinaryStory extends Model
{
    protected $fillable = [
        'food_id',
        'title',
        'slug',
        'content',
        'thumbnail',
    ];

    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }
}
