<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContributorLevelLog extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'user_id',
        'source',
        'recipe_id',
        'levels_gained',
        'level_before',
        'level_after',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
