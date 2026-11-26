<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RecipeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/explore', [PageController::class, 'explore'])->name('explore');
Route::get('/food-detail/{slug?}', [PageController::class, 'foodDetail'])->name('food.detail');
Route::get('/recipe', [PageController::class, 'explore'])->name('recipe');
Route::get('/recipe/create', [PageController::class, 'createRecipe'])->name('recipe.create');
Route::post('/recipe', [PageController::class, 'storeRecipe'])->name('recipe.store');
Route::get('/favorites', [PageController::class, 'favorites'])->name('favorites');
Route::get('/stories', [PageController::class, 'stories'])->name('stories');
Route::get('/about', [PageController::class, 'about'])->name('about');

// Account, Change Password & Admin Dashboard screens matching Figma design
Route::get('/change-password', [PageController::class, 'changePassword'])->name('password.change');
Route::post('/change-password', [PageController::class, 'updatePassword'])->name('password.change.submit');
Route::get('/account', [PageController::class, 'account'])->name('account');
Route::get('/admin', [PageController::class, 'adminDashboard'])->name('admin.dashboard');
Route::post('/admin/submissions/{slug}/status', [PageController::class, 'updateSubmissionStatus'])->name('admin.submission.status');

// Role-based Recipe & Food Management routes
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

    Route::get('/foods', [FoodController::class, 'index'])->name('foods.index');
    Route::get('/foods/create', [FoodController::class, 'create'])->name('foods.create');
    Route::post('/foods', [FoodController::class, 'store'])->name('foods.store');
    Route::get('/foods/{food}/edit', [FoodController::class, 'edit'])->name('foods.edit');
    Route::put('/foods/{food}', [FoodController::class, 'update'])->name('foods.update');
    Route::delete('/foods/{food}', [FoodController::class, 'destroy'])->name('foods.destroy');
});

// Authentication routes (AuthController)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::post('/favorites/toggle/{slug}', [PageController::class, 'toggleFavorite'])
        ->name('favorites.toggle');
});
