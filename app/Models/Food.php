<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'origin_area',
        'short_description',
        'thumbnail',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    public function culinaryStories(): HasMany
    {
        return $this->hasMany(CulinaryStory::class);
    }
}
