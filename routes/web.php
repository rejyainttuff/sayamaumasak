<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\RecipeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/explore', [PageController::class, 'explore'])->name('explore');
Route::get('/food-detail/{slug?}', [PageController::class, 'foodDetail'])->name('food.detail');
Route::get('/recipe', [PageController::class, 'recipe'])->name('recipe');
Route::get('/recipe/create', [PageController::class, 'createRecipe'])->name('recipe.create');
Route::post('/recipe', [PageController::class, 'storeRecipe'])->name('recipe.store');
Route::get('/favorites', [PageController::class, 'favorites'])->name('favorites');
Route::get('/stories', [PageController::class, 'stories'])->name('stories');
Route::get('/about', [PageController::class, 'about'])->name('about');

Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');

Route::middleware(['auth', 'role:admin,helper'])->group(function () {
    Route::get('/recipes/create', [RecipeController::class, 'create'])->name('recipes.create');
    Route::post('/recipes', [RecipeController::class, 'store'])->name('recipes.store');
    Route::get('/recipes/{recipe}/edit', [RecipeController::class, 'edit'])->name('recipes.edit');
    Route::put('/recipes/{recipe}', [RecipeController::class, 'update'])->name('recipes.update');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::patch('/recipes/{recipe}/approve', [RecipeController::class, 'approve'])->name('recipes.approve');
    Route::patch('/recipes/{recipe}/reject', [RecipeController::class, 'reject'])->name('recipes.reject');
});
