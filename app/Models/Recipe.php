<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $fillable = [
        'food_id',
        'title',
        'prep_time_minutes',
        'cook_time_minutes',
        'servings',
        'difficulty',
        'approval_status',
    ];

    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function recipeSteps(): HasMany
    {
        return $this->hasMany(RecipeStep::class);
    }
}
