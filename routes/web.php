<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CulinaryStoryController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RecipeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/explore', [PageController::class, 'explore'])->name('explore');
Route::get('/food-detail/{slug?}', [PageController::class, 'foodDetail'])->name('food.detail');
Route::get('/recipe', [PageController::class, 'explore'])->name('recipe');
Route::get('/stories', [PageController::class, 'stories'])->name('stories');
Route::get('/photo-of-the-day', [PageController::class, 'photoOfTheDay'])->name('photo.of.the.day');
Route::get('/about', [PageController::class, 'about'])->name('about');

// Password Reset (direct form during local development — SMTP/OTP disabled until server hosting)
Route::get('/change-password', [AuthController::class, 'showChangePasswordForm'])->name('password.change');
Route::post('/change-password', [AuthController::class, 'resetPassword'])->name('password.change.submit');

// Public Recipes listing
Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');

// Authenticated recipe creation, owner revision & owner/admin deletion (strictly validated in RecipeController backend)
Route::middleware('auth')->group(function () {
    Route::get('/recipes/create', [RecipeController::class, 'create'])->name('recipes.create');
    Route::post('/recipes', [RecipeController::class, 'store'])->name('recipes.store');
    Route::get('/recipes/{recipe}/edit', [RecipeController::class, 'edit'])->name('recipes.edit');
    Route::put('/recipes/{recipe}', [RecipeController::class, 'update'])->name('recipes.update');
    Route::delete('/recipes/{recipe}', [RecipeController::class, 'destroy'])->name('recipes.destroy');
});

// Public Recipe Detail (placed after /recipes/create to avoid route collision)
Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])->name('recipes.show');

// Helper & Admin recipe & story review/approval routes (3-role system: user, helper, admin)
Route::middleware(['auth', 'role:admin,helper'])->group(function () {
    Route::get('/admin/recipe-approvals', [RecipeController::class, 'approvals'])->name('admin.recipes.approvals');
    Route::patch('/recipes/{recipe}/approve', [RecipeController::class, 'approve'])->name('recipes.approve');
    Route::patch('/recipes/{recipe}/reject', [RecipeController::class, 'reject'])->name('recipes.reject');

    Route::get('/admin/stories', [CulinaryStoryController::class, 'adminIndex'])->name('admin.stories.index');
    Route::patch('/admin/stories/{story}/approve', [CulinaryStoryController::class, 'approve'])->name('admin.stories.approve');
    Route::patch('/admin/stories/{story}/reject', [CulinaryStoryController::class, 'reject'])->name('admin.stories.reject');
});

// Admin-only routes (full management: dashboard, foods, users, roles, contributor requests)
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin', [PageController::class, 'adminDashboard'])->name('admin.dashboard');
    Route::get('/admin/contributors', [PageController::class, 'adminContributorRequests'])->name('admin.contributors.index');
    Route::get('/admin/users', [PageController::class, 'adminUsers'])->name('admin.users.index');
    Route::patch('/admin/users/{user}/role', [PageController::class, 'updateUserRole'])->name('admin.users.role');
    Route::post('/admin/submissions/{slug}/status', [PageController::class, 'updateSubmissionStatus'])->name('admin.submission.status');
    Route::post('/admin/contributor-requests/{contributorRequest}/status', [PageController::class, 'updateContributorRequestStatus'])->name('admin.contributor.status');

    Route::get('/foods', [FoodController::class, 'index'])->name('foods.index');
    Route::get('/foods/create', [FoodController::class, 'create'])->name('foods.create');
    Route::post('/foods', [FoodController::class, 'store'])->name('foods.store');
    Route::get('/foods/{food}/edit', [FoodController::class, 'edit'])->name('foods.edit');
    Route::put('/foods/{food}', [FoodController::class, 'update'])->name('foods.update');
    Route::delete('/foods/{food}', [FoodController::class, 'destroy'])->name('foods.destroy');
});

// Authentication routes
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
    Route::get('/account', [PageController::class, 'account'])->name('account');
    Route::get('/account/edit', [PageController::class, 'editProfile'])->name('account.edit');
    Route::put('/account/profile', [PageController::class, 'updateProfile'])->name('account.profile.update');
    Route::get('/favorites', [PageController::class, 'favorites'])->name('favorites');
    Route::post('/favorites/toggle/{slug}', [PageController::class, 'toggleFavorite'])
        ->name('favorites.toggle');
    Route::post('/foods/{slug}/reviews', [PageController::class, 'storeReview'])
        ->name('reviews.store');

    Route::post('/reading/heartbeat', [PageController::class, 'recordReadingHeartbeat'])
        ->name('reading.heartbeat');

    Route::get('/contributor/apply', [PageController::class, 'showContributorForm'])->name('contributor.apply');
    Route::post('/contributor/apply', [PageController::class, 'submitContributorApplication'])->name('contributor.submit');

    Route::get('/recipe/create', [PageController::class, 'createRecipe'])->name('recipe.create');
    Route::post('/recipe', [PageController::class, 'storeRecipe'])->name('recipe.store');

    Route::get('/stories/create', [CulinaryStoryController::class, 'create'])->name('stories.create');
    Route::post('/stories', [CulinaryStoryController::class, 'store'])->name('stories.store');
    Route::delete('/stories/{story}', [CulinaryStoryController::class, 'destroy'])->name('stories.destroy');

    Route::get('/my-submissions', [PageController::class, 'mySubmissions'])->name('submissions.index');
});

// Public Culinary Story Detail (placed after /stories/create to avoid route collision)
Route::get('/stories/{slug}', [CulinaryStoryController::class, 'show'])->name('stories.show');
