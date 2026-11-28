<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContributorRequest;
use App\Models\CulinaryStory;
use App\Models\Food;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Review;
use App\Models\SiteVisit;
use App\Models\User;
use App\Services\ContributorLevelService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class PageController extends Controller
{
    /**
     * Minimum verified review count preferred for the Highest Rated showcase slot
     * so a single-review recipe does not automatically outrank multi-review recipes.
     */
    public const FEATURED_MIN_REVIEWS = 2;

    public function home(Request $request): View
    {
        $foods = array_values(array_filter(
            $this->getAllCulinaryData(),
            fn (array $food): bool => ! empty($food['name']) && ! empty($food['slug'])
        ));

        // A. Dynamic Food Selection: max 3 random valid foods, avoiding repeating previous session set
        $seenHomeSlugs = $request->session()->get('home_seen_foods', []);
        $seenHomeSlugs = is_array($seenHomeSlugs) ? $seenHomeSlugs : [];

        $freshPool = array_values(array_filter(
            $foods,
            fn (array $food): bool => ! in_array($food['slug'], $seenHomeSlugs, true)
        ));
        if (count($freshPool) < 3) {
            $freshPool = $foods;
        }
        shuffle($freshPool);
        $popularFoods = array_slice($freshPool, 0, 3);
        $popularSlugs = array_column($popularFoods, 'slug');
        $request->session()->put('home_seen_foods', $popularSlugs);

        // B. Dynamic Culinary Stories (DB approved or curated fallback)
        $stories = [];
        if ($this->hasDatabaseTable('culinary_stories')) {
            try {
                $storyQuery = CulinaryStory::with(['food', 'user'])->latest();
                if (Schema::hasColumn('culinary_stories', 'approval_status')) {
                    $storyQuery->where('approval_status', 'approved');
                }
                foreach ($storyQuery->take(6)->get() as $item) {
                    $plain = trim(strip_tags((string) $item->content));
                    $stories[] = [
                        'id' => $item->id,
                        'slug' => $item->slug,
                        'title' => (string) ($item->title ?: ($item->food?->name ?? 'Kisah Kuliner Bali')),
                        'region' => (string) ($item->food?->origin_area ?: 'Bali'),
                        'author' => $item->user?->name ?? 'Tim Bali Culinary',
                        'thumbnail' => $item->thumbnail ?: ($item->food?->thumbnail ?? 'Assets/images/banner-beranda.png'),
                        'excerpt' => strlen($plain) > 190 ? substr($plain, 0, 187).'...' : $plain,
                    ];
                }
            } catch (Throwable) {
                $stories = [];
            }
        }
        if ($stories === []) {
            $stories = CulinaryStoryController::curatedEditorialStories();
        }
        shuffle($stories);
        $featuredStory = $stories[0] ?? null;

        // C. Personalized / Recommended Recipes (prioritize unvisited & not in popularFoods)
        $visitedSlugs = Auth::check()
            ? (array) $request->session()->get('recently_viewed_user_'.Auth::id(), [])
            : [];
        $excludeForRec = array_unique(array_merge($popularSlugs, $visitedSlugs));

        $recPool = array_values(array_filter(
            $foods,
            fn (array $food): bool => ! in_array($food['slug'], $excludeForRec, true)
        ));
        if (count($recPool) < 3) {
            $recPool = array_values(array_filter(
                $foods,
                fn (array $food): bool => ! in_array($food['slug'], $popularSlugs, true)
            ));
        }
        if ($recPool === []) {
            $recPool = $foods;
        }
        shuffle($recPool);
        $recommendedFoods = array_slice($recPool, 0, 3);

        $recTitles = ['Resep yang Mungkin Kamu Suka', 'Resep Pilihan Hari Ini', 'Explore Something New'];
        $layoutVariant = ((int) $request->session()->get('home_layout_variant', 0)) % 3;
        $request->session()->put('home_layout_variant', ($layoutVariant + 1) % 3);

        $sectionOrder = match ($layoutVariant) {
            1 => ['story_photo', 'popular', 'recommended'],
            2 => ['recommended', 'story_photo', 'popular'],
            default => ['popular', 'story_photo', 'recommended'],
        };
        $recommendedHeading = $recTitles[$layoutVariant];

        // D. Photo of the Day (approved user-uploaded photos only)
        $communityPhotos = $this->getApprovedCommunityPhotos();
        $photoOfTheDay = $communityPhotos !== [] ? $communityPhotos[array_rand($communityPhotos)] : null;

        return view('home', compact(
            'foods',
            'popularFoods',
            'stories',
            'featuredStory',
            'recommendedFoods',
            'recommendedHeading',
            'sectionOrder',
            'photoOfTheDay'
        ));
    }

    public function photoOfTheDay(): View
    {
        $photos = $this->getApprovedCommunityPhotos();
        $featuredPhoto = $photos[0] ?? null;

        return view('photo-of-the-day', compact('photos', 'featuredPhoto'));
    }

    /**
     * Collect real, existing, Admin-approved user-uploaded photos from Foods/Recipes and Culinary Stories.
     *
     * @return array<int, array{url: string, title: string, author: string, source_type: string, detail_url: string, date: string}>
     */
    private function getApprovedCommunityPhotos(): array
    {
        $photos = [];

        if ($this->hasDatabaseTable('foods')) {
            try {
                $foodQuery = Food::with(['user', 'recipes' => fn ($q) => $q->where('approval_status', 'approved')])
                    ->whereNotNull('thumbnail')
                    ->where('thumbnail', '!=', '')
                    ->latest();

                if (Schema::hasColumn('foods', 'approval_status')) {
                    $foodQuery->where('approval_status', 'approved');
                }

                foreach ($foodQuery->get() as $food) {
                    $cleanPath = ltrim((string) $food->thumbnail, '/');
                    $exists = $cleanPath !== '' && (
                        file_exists(public_path($cleanPath))
                        || (str_starts_with($cleanPath, 'storage/') && Storage::disk('public')->exists(substr($cleanPath, strlen('storage/'))))
                    );
                    if ($exists) {
                        $photos[] = [
                            'url' => asset($cleanPath),
                            'title' => $food->name,
                            'author' => $food->user?->name ?? 'Komunitas Bali Culinary',
                            'source_type' => 'Resep & Kuliner',
                            'detail_url' => route('food.detail', $food->slug),
                            'date' => $food->created_at ? $food->created_at->format('d M Y') : '-',
                        ];
                    }
                }
            } catch (Throwable) {
            }
        }

        if ($this->hasDatabaseTable('culinary_stories')) {
            try {
                $storyQuery = CulinaryStory::with('user')->latest();
                if (Schema::hasColumn('culinary_stories', 'approval_status')) {
                    $storyQuery->where('approval_status', 'approved');
                }

                foreach ($storyQuery->get() as $story) {
                    $storyImages = [];
                    if (! empty($story->thumbnail)) {
                        $storyImages[] = $story->thumbnail;
                    }
                    if (is_array($story->gallery_images)) {
                        foreach ($story->gallery_images as $gImg) {
                            if (is_string($gImg) && $gImg !== '') {
                                $storyImages[] = $gImg;
                            }
                        }
                    }

                    foreach (array_unique($storyImages) as $imgPath) {
                        $cleanPath = ltrim($imgPath, '/');
                        $exists = $cleanPath !== '' && (
                            file_exists(public_path($cleanPath))
                            || (str_starts_with($cleanPath, 'storage/') && Storage::disk('public')->exists(substr($cleanPath, strlen('storage/'))))
                        );
                        if ($exists) {
                            $photos[] = [
                                'url' => asset($cleanPath),
                                'title' => $story->title,
                                'author' => $story->user?->name ?? 'Contributor Bali Culinary',
                                'source_type' => 'Cerita Kuliner',
                                'detail_url' => route('stories.show', $story->slug),
                                'date' => $story->created_at ? $story->created_at->format('d M Y') : '-',
                            ];
                        }
                    }
                }
            } catch (Throwable) {
            }
        }

        return $photos;
    }

    public function explore(Request $request): View
    {
        $allFoods = collect($this->getAllCulinaryData());
        $search = trim((string) $request->query('search', ''));
        $selectedCategory = trim((string) $request->query('category', ''));
        $selectedRegion = trim((string) $request->query('region', ''));
        $selectedDifficulty = trim((string) $request->query('difficulty', ''));
        $selectedCookTime = trim((string) $request->query('cook_time', ''));
        $sort = trim((string) $request->query('sort', 'default'));

        $categories = $this->getCategoriesList();
        $regions = $allFoods->pluck('region')->filter()->unique()->sort()->values()->all();
        $difficulties = ['Mudah', 'Sedang', 'Sulit'];

        // 1. Select Highest Rated Recipe from real eligible public recipes
        $highestRatedRecipe = $this->selectHighestRatedRecipe($allFoods);

        // 2. Select up to 2 unique Random Featured recipes (excluding Highest Rated), stable for the current day
        $randomFeaturedRecipes = $this->selectDailyRandomFeaturedRecipes($allFoods, $highestRatedRecipe['slug'] ?? null);

        // 3. Full catalogue filtering, sorting, and pagination (operates on ALL eligible recipes)
        $filtered = $allFoods
            ->when($search !== '', function ($collection) use ($search) {
                return $collection->filter(function (array $food) use ($search): bool {
                    return stripos($food['name'], $search) !== false
                        || stripos($food['description'], $search) !== false
                        || stripos($food['region'], $search) !== false
                        || stripos($food['category'], $search) !== false;
                });
            })
            ->when($selectedCategory !== '', fn ($collection) => $collection->where('category', $selectedCategory))
            ->when($selectedRegion !== '', fn ($collection) => $collection->where('region', $selectedRegion))
            ->when($selectedDifficulty !== '', fn ($collection) => $collection->where('difficulty', $selectedDifficulty))
            ->when($selectedCookTime !== '', function ($collection) use ($selectedCookTime) {
                return $collection->filter(function (array $food) use ($selectedCookTime): bool {
                    $minutes = (int) preg_replace('/\D+/', '', (string) ($food['cook_time'] ?? '0'));

                    return match ($selectedCookTime) {
                        'fast' => $minutes > 0 && $minutes <= 30,
                        'medium' => $minutes > 30 && $minutes <= 60,
                        'long' => $minutes > 60,
                        default => true,
                    };
                });
            });

        $filtered = match ($sort) {
            'name_asc' => $filtered->sortBy(fn (array $food) => mb_strtolower($food['name'])),
            'name_desc' => $filtered->sortByDesc(fn (array $food) => mb_strtolower($food['name'])),
            'region_asc' => $filtered->sortBy(fn (array $food) => mb_strtolower($food['region'] ?? '')),
            'rating_desc' => $filtered->sort(function (array $a, array $b): int {
                return ((float) ($b['rating'] ?? 0) <=> (float) ($a['rating'] ?? 0))
                    ?: ((int) ($b['reviews_count'] ?? 0) <=> (int) ($a['reviews_count'] ?? 0));
            }),
            'popular' => $filtered->sort(function (array $a, array $b): int {
                return ((int) ($b['reviews_count'] ?? 0) <=> (int) ($a['reviews_count'] ?? 0))
                    ?: ((float) ($b['rating'] ?? 0) <=> (float) ($a['rating'] ?? 0));
            }),
            default => $filtered,
        };

        $filtered = $filtered->values();

        $totalFilteredCount = $filtered->count();
        $perPage = 10;
        $totalPages = max(1, (int) ceil($totalFilteredCount / $perPage));
        $currentPage = min(max(1, (int) $request->query('page', 1)), $totalPages);
        $foods = $filtered->slice(($currentPage - 1) * $perPage, $perPage)->values()->all();

        $popularFoods = $allFoods->where('is_popular', true)->take(3)->values()->all();

        return view('explore', compact(
            'foods',
            'popularFoods',
            'highestRatedRecipe',
            'randomFeaturedRecipes',
            'totalFilteredCount',
            'categories',
            'regions',
            'difficulties',
            'search',
            'selectedCategory',
            'selectedRegion',
            'selectedDifficulty',
            'selectedCookTime',
            'sort',
            'currentPage',
            'totalPages'
        ));
    }

    /**
     * Select the Highest Rated eligible recipe using real review averages and counts.
     * Prefers recipes with at least FEATURED_MIN_REVIEWS reviews, falling back to any rated
     * recipe, and finally to the first eligible recipe when no reviews exist yet.
     *
     * @param  Collection<int, array<string, mixed>>  $eligibleFoods
     * @return array<string, mixed>|null
     */
    private function selectHighestRatedRecipe($eligibleFoods): ?array
    {
        if ($eligibleFoods->isEmpty()) {
            return null;
        }

        $rankComparator = static function (array $a, array $b): int {
            $ratingCmp = ((float) ($b['rating'] ?? 0)) <=> ((float) ($a['rating'] ?? 0));
            if ($ratingCmp !== 0) {
                return $ratingCmp;
            }

            $countCmp = ((int) ($b['reviews_count'] ?? 0)) <=> ((int) ($a['reviews_count'] ?? 0));
            if ($countCmp !== 0) {
                return $countCmp;
            }

            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        };

        $qualifiedPool = $eligibleFoods
            ->filter(fn (array $food): bool => (int) ($food['reviews_count'] ?? 0) >= self::FEATURED_MIN_REVIEWS)
            ->values();

        if ($qualifiedPool->isNotEmpty()) {
            return $qualifiedPool->sort($rankComparator)->first();
        }

        $anyRatedPool = $eligibleFoods
            ->filter(fn (array $food): bool => (int) ($food['reviews_count'] ?? 0) >= 1)
            ->values();

        if ($anyRatedPool->isNotEmpty()) {
            return $anyRatedPool->sort($rankComparator)->first();
        }

        return $eligibleFoods->sortBy('id')->first();
    }

    /**
     * Select up to 2 unique eligible recipes excluding the Highest Rated recipe.
     * Selection remains stable across page refreshes for the current calendar day (daily rotation).
     *
     * @param  Collection<int, array<string, mixed>>  $eligibleFoods
     * @return array<int, array<string, mixed>>
     */
    private function selectDailyRandomFeaturedRecipes($eligibleFoods, ?string $excludeSlug): array
    {
        $candidates = $eligibleFoods
            ->reject(fn (array $food): bool => $excludeSlug !== null && ($food['slug'] ?? '') === $excludeSlug)
            ->unique('slug')
            ->values();

        if ($candidates->isEmpty()) {
            return [];
        }

        $targetCount = min(2, $candidates->count());
        $candidateBySlug = $candidates->keyBy('slug');
        $todayKey = 'explore_daily_featured_'.Carbon::now()->toDateString();

        $cachedSlugs = Cache::get($todayKey, []);
        $validSlugs = [];

        if (is_array($cachedSlugs)) {
            foreach ($cachedSlugs as $slug) {
                if (is_string($slug) && $candidateBySlug->has($slug) && ! in_array($slug, $validSlugs, true)) {
                    $validSlugs[] = $slug;
                }
                if (count($validSlugs) >= $targetCount) {
                    break;
                }
            }
        }

        if (count($validSlugs) < $targetCount) {
            $remainingPool = $candidates
                ->reject(fn (array $food): bool => in_array($food['slug'], $validSlugs, true))
                ->shuffle()
                ->pluck('slug')
                ->all();

            foreach ($remainingPool as $slug) {
                $validSlugs[] = $slug;
                if (count($validSlugs) >= $targetCount) {
                    break;
                }
            }

            Cache::put($todayKey, $validSlugs, Carbon::now()->endOfDay());
        }

        return array_values(array_map(
            fn (string $slug): array => $candidateBySlug->get($slug),
            $validSlugs
        ));
    }

    public function foodDetail(?string $slug = null): View
    {
        $foods = $this->getAllCulinaryData();
        $food = collect($foods)->firstWhere('slug', $slug) ?? $foods[0];
        $relatedFoods = collect($foods)
            ->reject(fn (array $item): bool => $item['slug'] === $food['slug'])
            ->take(3)
            ->values()
            ->all();

        $isFavorited = false;
        if (Auth::check() && $this->hasDatabaseTable('foods') && $this->hasDatabaseTable('food_user')) {
            $foodModel = Food::where('slug', $food['slug'])->first();
            if ($foodModel) {
                $isFavorited = Auth::user()->favoriteFoods()->where('foods.id', $foodModel->id)->exists();
            }
        }

        $reviews = collect();
        $averageRating = 0.0;
        $reviewsCount = 0;
        $userReview = null;

        if ($food && $this->hasDatabaseTable('foods') && $this->hasDatabaseTable('reviews')) {
            $foodModel = Food::where('slug', $food['slug'])->first();

            if ($foodModel) {
                $reviews = $foodModel->reviews()->with('user')->latest()->get();
                $reviewsCount = $reviews->count();
                $averageRating = $reviewsCount > 0 ? round((float) $reviews->avg('rating'), 1) : 0.0;

                if (Auth::check()) {
                    $userReview = $reviews->firstWhere('user_id', Auth::id());
                }
            }
        }

        // Single source of truth: keep $food['rating'] & $food['reviews_count'] strictly synchronized with real DB reviews
        $food['rating'] = $averageRating;
        $food['reviews_count'] = $reviewsCount;
        $lowRatingReasons = Review::LOW_RATING_REASONS;

        if (Auth::check() && $food) {
            $recentKey = 'recently_viewed_user_'.Auth::id();
            $recentSlugs = session()->get($recentKey, []);
            $recentSlugs = array_values(array_filter(
                is_array($recentSlugs) ? $recentSlugs : [],
                fn (string $itemSlug): bool => $itemSlug !== $food['slug']
            ));
            array_unshift($recentSlugs, $food['slug']);
            session()->put($recentKey, array_slice($recentSlugs, 0, 8));
        }

        return view('food-detail', compact(
            'food',
            'foods',
            'relatedFoods',
            'isFavorited',
            'reviews',
            'averageRating',
            'reviewsCount',
            'userReview',
            'lowRatingReasons'
        ));
    }

    public function createRecipe(): View
    {
        $categories = $this->getCategoriesList();
        $difficulties = ['Mudah', 'Sedang', 'Sulit'];
        $regions = ['Badung', 'Bangli', 'Buleleng', 'Denpasar', 'Gianyar', 'Jembrana', 'Karangasem', 'Klungkung', 'Tabanan'];

        return view('recipe-create', compact('categories', 'difficulties', 'regions'));
    }

    public function storeRecipe(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'region' => ['required', 'string', 'max:80'],
            'difficulty' => ['required', 'in:Mudah,Sedang,Sulit'],
            'category' => ['required', 'string', 'max:80'],
            'cook_time' => ['required', 'string', 'max:60'],
            'servings' => ['nullable', 'string', 'max:40'],
            'video_url' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'ingredients' => ['required', 'string', 'max:2000'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*' => ['nullable', 'string', 'max:500'],
        ]);

        $steps = array_values(array_filter(
            array_map(fn (?string $step): string => trim((string) $step), $validated['steps']),
            fn (string $step): bool => $step !== ''
        ));

        if ($steps === []) {
            return back()
                ->withInput()
                ->withErrors(['steps' => 'Minimal isi satu langkah memasak.']);
        }

        $ingredients = array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', $validated['ingredients']) ?: []),
            fn (string $line): bool => $line !== ''
        ));

        $cookTimeMinutes = (int) preg_replace('/\D+/', '', $validated['cook_time']);
        $cookTimeMinutes = $cookTimeMinutes > 0 ? $cookTimeMinutes : null;

        $servings = $validated['servings']
            ? (int) preg_replace('/\D+/', '', $validated['servings'])
            : 4;
        $servings = $servings > 0 ? $servings : 4;

        $baseSlug = Str::slug($validated['name']);
        $slug = ($baseSlug !== '' ? $baseSlug : 'resep').'-'.strtolower(Str::random(4));

        $user = $request->user();
        if (! $user || ! $user->canSubmitRecipes()) {
            return redirect()
                ->route('contributor.apply')
                ->withErrors(['reason' => 'Akun Anda belum memiliki izin mengirim resep. Silakan ajukan izin Contributor terlebih dahulu.']);
        }

        $isDraftRequest = $request->input('submission_action') === 'draft';
        $approvalStatus = $isDraftRequest
            ? 'draft'
            : ($user->isAdmin() ? 'approved' : 'pending');

        DB::transaction(function () use (
            $validated,
            $slug,
            $user,
            $approvalStatus,
            $cookTimeMinutes,
            $servings,
            $ingredients,
            $steps
        ) {
            $category = Category::firstOrCreate(['name' => trim($validated['category'])]);

            $food = Food::firstOrCreate(
                ['name' => trim($validated['name'])],
                [
                    'slug' => $slug,
                    'category_id' => $category->id,
                    'origin_area' => trim($validated['region']),
                    'short_description' => $validated['description'],
                ]
            );

            $recipe = Recipe::create([
                'user_id' => $user?->id,
                'food_id' => $food->id,
                'title' => trim($validated['name']),
                'prep_time_minutes' => 0,
                'cook_time_minutes' => $cookTimeMinutes,
                'servings' => $servings,
                'difficulty' => strtolower($validated['difficulty']),
                'video_url' => $validated['video_url'] ?? null,
                'approval_status' => $approvalStatus,
            ]);

            foreach ($ingredients as $line) {
                $ingredient = Ingredient::firstOrCreate(['name' => $line]);
                $recipe->recipeIngredients()->create([
                    'ingredient_id' => $ingredient->id,
                    'amount' => null,
                    'unit' => null,
                    'notes' => null,
                ]);
            }

            foreach ($steps as $index => $instruction) {
                $recipe->recipeSteps()->create([
                    'step_number' => $index + 1,
                    'instruction' => $instruction,
                ]);
            }
        });

        $successMsg = $approvalStatus === 'draft'
            ? 'Draft resep "'.$validated['name'].'" berhasil disimpan. Anda dapat melanjutkannya kapan saja di My Submissions.'
            : ($approvalStatus === 'approved'
                ? 'Resep "'.$validated['name'].'" berhasil ditambahkan dan dipublikasikan!'
                : 'Resep "'.$validated['name'].'" berhasil dikirim dan menunggu persetujuan admin!');

        return redirect()
            ->route('submissions.index')
            ->with('success', $successMsg);
    }

    public function toggleFavorite(Request $request, string $slug): RedirectResponse
    {
        $user = Auth::user();
        $food = $this->hasDatabaseTable('foods') ? Food::where('slug', $slug)->first() : null;

        if (! $food || ! $user) {
            return back()->with('error', 'Data makanan tidak ditemukan di database.');
        }

        $exists = $user->favoriteFoods()->where('foods.id', $food->id)->exists();

        if ($exists) {
            $user->favoriteFoods()->detach($food->id);
            $message = 'Makanan dihapus dari daftar favorit.';
        } else {
            $user->favoriteFoods()->attach($food->id);
            $message = 'Makanan berhasil disimpan ke favorit!';
        }

        return back()->with('success', $message);
    }

    public function favorites(): View
    {
        $allFoods = $this->getAllCulinaryData();

        if (Auth::check() && $this->hasDatabaseTable('food_user')) {
            $favoriteSlugs = Auth::user()->favoriteFoods()->pluck('slug')->all();
            $foods = array_values(array_filter(
                $allFoods,
                fn (array $food): bool => in_array($food['slug'], $favoriteSlugs, true)
            ));
        } else {
            $foods = $allFoods;
        }

        return view('favorites', compact('foods'));
    }

    public function storeReview(Request $request, string $slug): RedirectResponse
    {
        $payload = $this->validateReviewPayload($request);

        $food = $this->hasDatabaseTable('foods') ? Food::where('slug', $slug)->first() : null;

        if (! $food) {
            return back()->with('error', 'Data makanan tidak ditemukan di database.');
        }

        Review::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'food_id' => $food->id,
            ],
            $payload
        );

        return back()->with('success', 'Ulasan berhasil disimpan!');
    }

    public function updateReview(Request $request, Review $review): RedirectResponse
    {
        $user = $request->user();
        if (! $user || (int) $review->user_id !== (int) $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit ulasan milik pengguna lain.');
        }

        $payload = $this->validateReviewPayload($request, 'edit_review_'.$review->id);

        $review->update($payload);

        return back()->with('success', 'Ulasan Anda berhasil diperbarui!');
    }

    public function destroyReview(Request $request, Review $review): RedirectResponse
    {
        $user = $request->user();
        if (! $user || (int) $review->user_id !== (int) $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus ulasan milik pengguna lain.');
        }

        $review->delete();

        return back()->with('success', 'Ulasan Anda berhasil dihapus.');
    }

    /**
     * Validate review rating, optional comment, and conditional low-rating feedback reasons.
     *
     * @return array{rating: int, comment: ?string, feedback_reasons: ?array<int, string>, feedback_other: ?string}
     */
    private function validateReviewPayload(Request $request, ?string $errorBag = null): array
    {
        $rules = [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'feedback_reasons' => ['nullable', 'array'],
            'feedback_reasons.*' => ['string', 'in:'.implode(',', Review::LOW_RATING_REASONS)],
            'feedback_other' => ['nullable', 'string', 'max:500'],
        ];

        $messages = [
            'rating.required' => 'Silakan pilih rating 1 sampai 5 bintang terlebih dahulu.',
            'rating.integer' => 'Rating harus berupa angka 1 sampai 5.',
            'rating.min' => 'Rating minimal adalah 1 bintang.',
            'rating.max' => 'Rating maksimal adalah 5 bintang.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        $validator->after(function ($v) use ($request): void {
            $rating = (int) $request->input('rating');
            $reasons = array_values(array_filter(
                (array) $request->input('feedback_reasons', []),
                fn ($item): bool => is_string($item) && trim($item) !== ''
            ));
            $otherText = trim((string) $request->input('feedback_other', ''));

            if ($rating > 0 && $rating < 3) {
                if ($reasons === []) {
                    $v->errors()->add('feedback_reasons', 'Untuk rating di bawah 3 bintang, wajib memilih minimal satu alasan feedback.');
                }
                if (in_array('Lainnya', $reasons, true) && $otherText === '') {
                    $v->errors()->add('feedback_other', 'Mohon jelaskan masalah yang dialami pada kolom Lainnya.');
                }
            }
        });

        $validated = $errorBag !== null
            ? $validator->validateWithBag($errorBag)
            : $validator->validate();

        $rating = (int) $validated['rating'];
        $reasons = $rating < 3
            ? array_values(array_unique(array_filter(
                $validated['feedback_reasons'] ?? [],
                fn ($item): bool => is_string($item) && trim($item) !== ''
            )))
            : null;
        $otherText = ($rating < 3 && is_array($reasons) && in_array('Lainnya', $reasons, true))
            ? trim((string) ($validated['feedback_other'] ?? ''))
            : null;

        return [
            'rating' => $rating,
            'comment' => isset($validated['comment']) && trim((string) $validated['comment']) !== ''
                ? trim((string) $validated['comment'])
                : null,
            'feedback_reasons' => $reasons !== [] ? $reasons : null,
            'feedback_other' => $otherText !== '' ? $otherText : null,
        ];
    }

    public function mySubmissions(Request $request): View
    {
        $user = $request->user();
        $userId = $user->id;
        $isContributor = $user->isContributor() || $user->isAdmin();

        $contributorRequest = Schema::hasTable('contributor_requests')
            ? $user->contributorRequests()->latest()->first()
            : null;

        $foodSubmissions = collect();
        $recipeSubmissions = collect();
        $storySubmissions = collect();

        if ($isContributor || $contributorRequest !== null) {
            $foodSubmissions = Schema::hasTable('foods')
                ? Food::with('category')->where('user_id', $userId)->latest()->get()
                : collect();

            $recipeSubmissions = Schema::hasTable('recipes')
                ? Recipe::with('food')->where('user_id', $userId)->latest()->get()
                : collect();

            $storySubmissions = Schema::hasTable('culinary_stories')
                ? CulinaryStory::with('food')->where('user_id', $userId)->latest()->get()
                : collect();
        }

        $levelProgress = app(ContributorLevelService::class)->getProgressSummary($user);

        return view('submissions', compact(
            'isContributor',
            'contributorRequest',
            'levelProgress',
            'foodSubmissions',
            'recipeSubmissions',
            'storySubmissions'
        ));
    }

    public function recordReadingHeartbeat(Request $request, ContributorLevelService $levelService): JsonResponse
    {
        $validated = $request->validate([
            'content_type' => ['required', 'in:food,recipe,story'],
            'content_id' => ['required', 'integer', 'min:1'],
            'session_token' => ['required', 'string', 'min:8', 'max:64'],
            'delta_seconds' => ['required', 'integer', 'min:1', 'max:30'],
        ]);

        $result = $levelService->recordReadingHeartbeat(
            $request->user(),
            $validated['content_type'],
            (int) $validated['content_id'],
            $validated['session_token'],
            (int) $validated['delta_seconds']
        );

        return response()->json($result);
    }

    public function showContributorForm(Request $request): View
    {
        $user = $request->user();
        $latestRequest = Schema::hasTable('contributor_requests')
            ? $user->contributorRequests()->latest()->first()
            : null;

        return view('contributor-apply', compact('user', 'latestRequest'));
    }

    public function submitContributorApplication(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isContributor() || $user->isAdmin()) {
            return redirect()->route('submissions.index')
                ->with('success', 'Akunmu sudah memiliki akses Contributor.');
        }

        $validated = $request->validate([
            'specialty' => ['nullable', 'string', 'max:120'],
            'reason' => ['required', 'string', 'min:200', 'max:2000'],
        ], [
            'reason.min' => 'Alasan pengajuan wajib diisi minimal 200 karakter.',
        ]);

        if (Schema::hasTable('contributor_requests')) {
            $hasPending = $user->contributorRequests()->where('status', 'pending')->exists();
            if ($hasPending) {
                return back()->with('success', 'Pengajuan Contributor kamu sedang menunggu persetujuan admin.');
            }

            ContributorRequest::create([
                'user_id' => $user->id,
                'specialty' => $validated['specialty'] ?? null,
                'reason' => $validated['reason'],
                'status' => 'pending',
            ]);
        }

        return redirect()->route('submissions.index')
            ->with('success', 'Pengajuan menjadi Contributor berhasil dikirim dan menunggu persetujuan admin.');
    }

    public function updateContributorRequestStatus(Request $request, ContributorRequest $contributorRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected,pending'],
        ]);

        $contributorRequest->update(['status' => $validated['status']]);

        if ($validated['status'] === 'approved' && $contributorRequest->user) {
            // Keep 3-role system (user, helper, admin): grant recipe submission permission without creating a 4th role
            $contributorRequest->user->update(['can_submit_recipes' => true]);
        }

        $label = $validated['status'] === 'approved' ? 'disetujui' : ($validated['status'] === 'rejected' ? 'ditolak' : 'pending');

        return back()->with('success', 'Pengajuan Contributor berhasil '.$label.'.');
    }

    public function stories(Request $request): View
    {
        $stories = [];

        if ($this->hasDatabaseTable('culinary_stories')) {
            try {
                $storyQuery = CulinaryStory::with(['food', 'user'])->latest();

                if (Schema::hasColumn('culinary_stories', 'approval_status')) {
                    $storyQuery->where('approval_status', 'approved');
                } elseif (Schema::hasColumn('culinary_stories', 'status')) {
                    $storyQuery->where('status', 'approved');
                }

                $dbStories = $storyQuery->get();

                foreach ($dbStories as $item) {
                    $plain = trim(strip_tags((string) $item->content));
                    $wordCount = max(1, str_word_count($plain));
                    $readMinutes = max(1, (int) ceil($wordCount / 180));
                    $stories[] = [
                        'id' => $item->id,
                        'slug' => $item->slug,
                        'title' => (string) ($item->title ?: ($item->food?->name ?? 'Kisah Kuliner Bali')),
                        'region' => (string) ($item->food?->origin_area ?: 'Bali'),
                        'author' => $item->user?->name ?? 'Tim Bali Culinary',
                        'thumbnail' => $item->thumbnail ?: ($item->food?->thumbnail ?? 'Assets/images/banner-beranda.png'),
                        'read_time' => $readMinutes.' menit baca',
                        'excerpt' => strlen($plain) > 180 ? substr($plain, 0, 177).'...' : $plain,
                    ];
                }
            } catch (Throwable) {
                $stories = [];
            }
        }

        if ($stories === []) {
            $stories = CulinaryStoryController::curatedEditorialStories();
        }

        $perPage = 10;
        $totalPages = max(1, (int) ceil(count($stories) / $perPage));
        $currentPage = min(max(1, (int) $request->query('page', 1)), $totalPages);
        $paginatedStories = array_slice($stories, ($currentPage - 1) * $perPage, $perPage);

        return view('stories', [
            'stories' => $paginatedStories,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }

    public function about(): View
    {
        return view('about');
    }

    public function changePassword(): View
    {
        return view('auth.change-password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if ($this->hasDatabaseTable('users')) {
            $user = User::where('email', $validated['email'])->first();

            if ($user) {
                $user->update(['password' => Hash::make($validated['password'])]);

                return redirect()->route('login')->with('success', 'Password berhasil diperbarui. Silakan login kembali.');
            }
        }

        return back()
            ->withInput($request->only('email'))
            ->with('success', 'Permintaan pembaruan password untuk '.$validated['email'].' telah diproses.');
    }

    public function account(): View
    {
        $foods = $this->getAllCulinaryData();
        $user = Auth::user();
        $profile = [
            'name' => $user->name,
            'handle' => '@'.strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $user->name)),
            'email' => $user->email,
            'role' => $user->role,
            'bio' => $user->bio,
            'avatar' => $user->avatar,
        ];

        $favoriteSamples = [];
        if ($this->hasDatabaseTable('foods') && $this->hasDatabaseTable('food_user')) {
            $favoriteSamples = $user->favoriteFoods()
                ->latest('food_user.created_at')
                ->take(4)
                ->get()
                ->map(fn (Food $food): array => [
                    'name' => $food->name,
                    'slug' => $food->slug,
                    'image' => $food->thumbnail ?: 'Assets/images/lawar.jpg',
                ])
                ->all();
        }

        $recentSlugs = session()->get('recently_viewed_user_'.$user->id, []);
        $foodsBySlug = collect($foods)->keyBy('slug');
        $recentSamples = collect(is_array($recentSlugs) ? $recentSlugs : [])
            ->map(fn (string $slug) => $foodsBySlug->get($slug))
            ->filter()
            ->take(4)
            ->values()
            ->all();

        $reviews = [];
        if ($this->hasDatabaseTable('reviews') && $this->hasDatabaseTable('foods')) {
            $reviews = $user->reviews()
                ->with('food')
                ->latest()
                ->get()
                ->map(fn (Review $review): array => [
                    'dish' => $review->food?->name ?? 'Kuliner Bali',
                    'slug' => $review->food?->slug ?? '',
                    'rating' => (int) $review->rating,
                    'comment' => (string) ($review->comment ?? ''),
                    'date' => $review->created_at ? $review->created_at->diffForHumans() : '-',
                ])
                ->all();
        }

        $contributorRequest = Schema::hasTable('contributor_requests')
            ? $user->contributorRequests()->latest()->first()
            : null;

        return view('account', compact('profile', 'foods', 'favoriteSamples', 'recentSamples', 'reviews', 'contributorRequest'));
    }

    public function editProfile(): View
    {
        $user = Auth::user();

        return view('account-edit', compact('user'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $data = [
            'name' => $validated['name'],
            'bio' => $validated['bio'] ?? null,
        ];

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = '/storage/'.$path;
        }

        $user->update($data);

        return redirect()->route('account')->with('success', 'Profil berhasil diperbarui!');
    }

    public function adminDashboard(Request $request): View
    {
        $foods = $this->getAllCulinaryData();

        $pendingRecipes = Recipe::with(['user', 'food'])
            ->where('approval_status', 'pending')
            ->latest()
            ->get();

        $pendingFoods = collect();
        if (Schema::hasTable('foods')) {
            $foodQuery = Food::with('category')->whereNotNull('user_id')->latest();
            if (Schema::hasColumn('foods', 'approval_status')) {
                $foodQuery->where('approval_status', 'pending');
            }
            $pendingFoods = $foodQuery->get();
        }

        $pendingStories = collect();
        if (Schema::hasTable('culinary_stories')) {
            $storyQuery = CulinaryStory::with(['user', 'food'])->latest();
            if (Schema::hasColumn('culinary_stories', 'approval_status')) {
                $storyQuery->where('approval_status', 'pending');
            }
            $pendingStories = $storyQuery->get();
        }

        $pendingSubmissions = collect();

        foreach ($pendingRecipes as $recipe) {
            $pendingSubmissions->push([
                'id' => $recipe->id,
                'type' => 'recipe',
                'label' => 'Resep',
                'slug' => $recipe->slug ?? $recipe->food?->slug ?? '',
                'name' => $recipe->title ?? $recipe->name ?? 'Resep Tanpa Judul',
                'author' => 'Diajukan oleh '.($recipe->user?->name ?? 'Contributor'),
                'status' => $recipe->approval_status ?? 'pending',
                'model' => $recipe,
            ]);
        }

        foreach ($pendingFoods as $food) {
            $pendingSubmissions->push([
                'id' => $food->id,
                'type' => 'food',
                'label' => 'Makanan',
                'slug' => $food->slug ?? '',
                'name' => $food->name ?? 'Kuliner Tanpa Nama',
                'author' => 'Diajukan oleh '.($food->user?->name ?? 'Contributor'),
                'status' => $food->approval_status ?? 'pending',
                'model' => $food,
            ]);
        }

        foreach ($pendingStories as $story) {
            $pendingSubmissions->push([
                'id' => $story->id,
                'type' => 'story',
                'label' => 'Cerita Kuliner',
                'slug' => $story->food?->slug ?? '',
                'name' => $story->title ?? ($story->food?->name ?? 'Cerita Kuliner'),
                'author' => 'Diajukan oleh '.($story->user?->name ?? 'Contributor'),
                'status' => $story->approval_status ?? 'pending',
                'model' => $story,
            ]);
        }

        $recentMenu = Recipe::with(['user', 'food'])
            ->where('approval_status', 'approved')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($recipe) {
                return [
                    'id' => $recipe->id,
                    'slug' => $recipe->food?->slug ?? '',
                    'name' => $recipe->title ?? 'Resep',
                    'author' => $recipe->user?->name ?? 'Admin Bali Culinary',
                    'image' => $recipe->food?->thumbnail,
                    'status' => $recipe->approval_status ?? 'approved',
                    'published_at' => $recipe->created_at ? $recipe->created_at->format('d M Y') : '-',
                    'time' => $recipe->created_at ? $recipe->created_at->diffForHumans() : '-',
                ];
            });

        $range = in_array($request->query('range'), ['7d', '30d', '12m'], true)
            ? (string) $request->query('range')
            : '7d';

        $stats = [
            'foods' => Schema::hasTable('foods') ? Food::count() : 0,
            'recipes' => Schema::hasTable('recipes') ? Recipe::count() : 0,
            'contributors' => Schema::hasTable('users') && Schema::hasColumn('users', 'can_submit_recipes')
                ? User::where('can_submit_recipes', true)->orWhere('role', 'helper')->count()
                : 0,
            'users' => Schema::hasTable('users') ? User::count() : 0,
            'reviews' => Schema::hasTable('reviews') ? Review::count() : 0,
            'pending' => $pendingSubmissions->count(),
        ];

        $statCards = [
            'foods' => $this->buildMetricCard(
                'Total Makanan',
                $stats['foods'],
                Schema::hasTable('foods') ? Food::where('created_at', '>=', now()->subDays(30))->count() : 0,
                Schema::hasTable('foods') ? Food::whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->count() : 0,
                true
            ),
            'recipes' => $this->buildMetricCard(
                'Total Resep',
                $stats['recipes'],
                Schema::hasTable('recipes') ? Recipe::where('created_at', '>=', now()->subDays(30))->count() : 0,
                Schema::hasTable('recipes') ? Recipe::whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->count() : 0,
                true
            ),
            'contributors' => $this->buildMetricCard(
                'Total Kontributor',
                $stats['contributors'],
                Schema::hasTable('users') && Schema::hasColumn('users', 'can_submit_recipes')
                    ? User::where(fn ($q) => $q->where('can_submit_recipes', true)->orWhere('role', 'helper'))->where('created_at', '>=', now()->subDays(30))->count()
                    : 0,
                Schema::hasTable('users') && Schema::hasColumn('users', 'can_submit_recipes')
                    ? User::where(fn ($q) => $q->where('can_submit_recipes', true)->orWhere('role', 'helper'))->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->count()
                    : 0,
                true
            ),
            'reviews' => $this->buildMetricCard(
                'Total Ulasan',
                $stats['reviews'],
                Schema::hasTable('reviews') ? Review::where('created_at', '>=', now()->subDays(30))->count() : 0,
                Schema::hasTable('reviews') ? Review::whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->count() : 0,
                false
            ),
        ];

        $visitorChart = $this->buildVisitorChart($range);
        $contentSeries = $this->buildContentSeries($range, $visitorChart);

        $contentDistribution = [
            'labels' => ['Resep', 'Makanan', 'Cerita', 'Video'],
            'data' => [
                Schema::hasTable('recipes') ? Recipe::count() : 0,
                Schema::hasTable('foods') ? Food::count() : 0,
                Schema::hasTable('culinary_stories') ? CulinaryStory::count() : 0,
                Schema::hasTable('recipes') && Schema::hasColumn('recipes', 'video_url')
                    ? Recipe::whereNotNull('video_url')->where('video_url', '!=', '')->count()
                    : 0,
            ],
        ];

        $pendingSummary = [
            'recipes' => $pendingRecipes->count(),
            'foods' => $pendingFoods->count(),
            'stories' => $pendingStories->count(),
            'total' => $pendingSubmissions->count(),
        ];

        $pendingContributorRequests = Schema::hasTable('contributor_requests')
            ? ContributorRequest::with('user')->where('status', 'pending')->latest()->get()
            : collect();

        $latestContributorRequest = $pendingContributorRequests->first();
        $recentActivities = $this->buildRecentActivities();

        return view('admin.dashboard', compact(
            'stats',
            'statCards',
            'recentMenu',
            'pendingStories',
            'pendingSubmissions',
            'pendingSummary',
            'pendingContributorRequests',
            'latestContributorRequest',
            'recentActivities',
            'foods',
            'visitorChart',
            'contentSeries',
            'contentDistribution'
        ));
    }

    public function adminContributorRequests(Request $request): View
    {
        $statusFilter = trim((string) $request->query('status', 'all'));

        $query = ContributorRequest::with('user')->latest();
        if (in_array($statusFilter, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $statusFilter);
        }

        $requests = $query->paginate(10)->withQueryString();
        $counts = [
            'all' => ContributorRequest::count(),
            'pending' => ContributorRequest::where('status', 'pending')->count(),
            'approved' => ContributorRequest::where('status', 'approved')->count(),
            'rejected' => ContributorRequest::where('status', 'rejected')->count(),
        ];

        return view('admin.contributors', compact('requests', 'statusFilter', 'counts'));
    }

    public function adminUsers(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $roleFilter = trim((string) $request->query('role', ''));

        $users = User::withCount(['recipes', 'foods', 'reviews'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($roleFilter !== '', fn ($query) => $query->where('role', $roleFilter))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $analyticsSummary = [
            'total_users' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'helpers' => User::where('role', 'helper')->count(),
            'contributors' => Schema::hasColumn('users', 'can_submit_recipes')
                ? User::where('can_submit_recipes', true)->count()
                : 0,
            'regular_users' => User::where('role', 'user')->count(),
            'total_visits' => Schema::hasTable('site_visits') ? SiteVisit::count() : 0,
            'total_recipes' => Schema::hasTable('recipes') ? Recipe::count() : 0,
            'total_reviews' => Schema::hasTable('reviews') ? Review::count() : 0,
        ];

        return view('admin.users', compact('users', 'search', 'roleFilter', 'analyticsSummary'));
    }

    public function updateUserRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:admin,helper,user'],
            'can_submit_recipes' => ['nullable', 'boolean'],
        ]);

        if ($user->id === $request->user()->id && $validated['role'] !== 'admin') {
            return back()->with('error', 'Tidak dapat menurunkan role akun Admin milik sendiri.');
        }

        $updateData = ['role' => $validated['role']];
        if ($request->has('can_submit_recipes')) {
            $updateData['can_submit_recipes'] = $request->boolean('can_submit_recipes');
        }

        $user->update($updateData);

        return back()->with('success', 'Role pengguna '.$user->name.' berhasil diperbarui menjadi '.ucfirst($validated['role']).'.');
    }

    /**
     * Build a metric card payload with real percentage change vs previous 30-day window.
     *
     * @return array{label: string, total: int, current_period: int, previous_period: int, percent: float|null, trend: string, tone: string, caption: string}
     */
    private function buildMetricCard(string $label, int $total, int $currentPeriod, int $previousPeriod, bool $higherIsPositive): array
    {
        if ($previousPeriod <= 0) {
            return [
                'label' => $label,
                'total' => $total,
                'current_period' => $currentPeriod,
                'previous_period' => $previousPeriod,
                'percent' => null,
                'trend' => 'neutral',
                'tone' => 'neutral',
                'caption' => 'Belum ada data pembanding',
            ];
        }

        $diff = $currentPeriod - $previousPeriod;
        $percent = round(($diff / $previousPeriod) * 100, 1);

        if ($diff > 0) {
            $trend = 'up';
            $tone = $higherIsPositive ? 'positive' : 'caution';
            $caption = 'Naik '.abs($percent).'% dari periode sebelumnya';
        } elseif ($diff < 0) {
            $trend = 'down';
            $tone = $higherIsPositive ? 'negative' : 'neutral';
            $caption = 'Turun '.abs($percent).'% dari periode sebelumnya';
        } else {
            $trend = 'flat';
            $tone = 'neutral';
            $caption = 'Stabil 0% dari periode sebelumnya';
        }

        return [
            'label' => $label,
            'total' => $total,
            'current_period' => $currentPeriod,
            'previous_period' => $previousPeriod,
            'percent' => $percent,
            'trend' => $trend,
            'tone' => $tone,
            'caption' => $caption,
        ];
    }

    /**
     * Build multi-series content statistics (Resep, Makanan, Pengunjung) for the given range.
     *
     * @param  array{range: string, labels: array<int, string>, data: array<int, int>}  $visitorChart
     * @return array{range: string, labels: array<int, string>, recipes: array<int, int>, foods: array<int, int>, visitors: array<int, int>}
     */
    private function buildContentSeries(string $range, array $visitorChart): array
    {
        $labels = $visitorChart['labels'];
        $visitors = $visitorChart['data'];
        $recipes = [];
        $foods = [];

        if ($range === '12m') {
            $start = Carbon::now()->startOfMonth()->subMonths(11);
            $end = Carbon::now()->endOfMonth();

            $recipesByMonth = Schema::hasTable('recipes')
                ? Recipe::whereBetween('created_at', [$start, $end])->get(['created_at'])
                    ->groupBy(fn (Recipe $r): string => $r->created_at->format('Y-m'))
                    ->map(fn ($g): int => $g->count())
                : collect();

            $foodsByMonth = Schema::hasTable('foods')
                ? Food::whereBetween('created_at', [$start, $end])->get(['created_at'])
                    ->groupBy(fn (Food $f): string => $f->created_at->format('Y-m'))
                    ->map(fn ($g): int => $g->count())
                : collect();

            for ($i = 11; $i >= 0; $i--) {
                $key = Carbon::now()->startOfMonth()->subMonths($i)->format('Y-m');
                $recipes[] = (int) ($recipesByMonth[$key] ?? 0);
                $foods[] = (int) ($foodsByMonth[$key] ?? 0);
            }
        } else {
            $days = $range === '30d' ? 30 : 7;
            $start = Carbon::now()->startOfDay()->subDays($days - 1);
            $end = Carbon::now()->endOfDay();

            $recipesByDay = Schema::hasTable('recipes')
                ? Recipe::whereBetween('created_at', [$start, $end])->get(['created_at'])
                    ->groupBy(fn (Recipe $r): string => $r->created_at->format('Y-m-d'))
                    ->map(fn ($g): int => $g->count())
                : collect();

            $foodsByDay = Schema::hasTable('foods')
                ? Food::whereBetween('created_at', [$start, $end])->get(['created_at'])
                    ->groupBy(fn (Food $f): string => $f->created_at->format('Y-m-d'))
                    ->map(fn ($g): int => $g->count())
                : collect();

            for ($i = $days - 1; $i >= 0; $i--) {
                $key = Carbon::now()->startOfDay()->subDays($i)->format('Y-m-d');
                $recipes[] = (int) ($recipesByDay[$key] ?? 0);
                $foods[] = (int) ($foodsByDay[$key] ?? 0);
            }
        }

        return [
            'range' => $range,
            'labels' => $labels,
            'recipes' => $recipes,
            'foods' => $foods,
            'visitors' => $visitors,
        ];
    }

    /**
     * Build a real chronological activity feed from database tables.
     *
     * @return array<int, array{type: string, text: string, time: string, timestamp: int}>
     */
    private function buildRecentActivities(): array
    {
        $items = [];

        if (Schema::hasTable('recipes')) {
            foreach (Recipe::with('user')->latest()->take(5)->get() as $recipe) {
                $actor = $recipe->user?->name ?? 'Contributor';
                $action = $recipe->approval_status === 'approved'
                    ? 'Resep "'.$recipe->title.'" oleh '.$actor.' telah disetujui.'
                    : $actor.' mengirimkan resep "'.$recipe->title.'".';
                $items[] = [
                    'type' => 'recipe',
                    'text' => $action,
                    'time' => $recipe->created_at ? $recipe->created_at->diffForHumans() : '-',
                    'timestamp' => $recipe->created_at ? $recipe->created_at->getTimestamp() : 0,
                ];
            }
        }

        if (Schema::hasTable('reviews')) {
            foreach (Review::with(['user', 'food'])->latest()->take(5)->get() as $review) {
                $items[] = [
                    'type' => 'review',
                    'text' => ($review->user?->name ?? 'Pengguna').' memberi '.$review->rating.' bintang untuk '.($review->food?->name ?? 'kuliner').'.',
                    'time' => $review->created_at ? $review->created_at->diffForHumans() : '-',
                    'timestamp' => $review->created_at ? $review->created_at->getTimestamp() : 0,
                ];
            }
        }

        if (Schema::hasTable('contributor_requests')) {
            foreach (ContributorRequest::with('user')->latest()->take(5)->get() as $req) {
                $actor = $req->user?->name ?? 'Pengguna';
                $text = $req->status === 'approved'
                    ? $actor.' diterima sebagai Contributor baru.'
                    : $actor.' mengajukan diri menjadi Contributor.';
                $items[] = [
                    'type' => 'contributor',
                    'text' => $text,
                    'time' => $req->created_at ? $req->created_at->diffForHumans() : '-',
                    'timestamp' => $req->created_at ? $req->created_at->getTimestamp() : 0,
                ];
            }
        }

        usort($items, fn (array $a, array $b): int => $b['timestamp'] <=> $a['timestamp']);

        return array_slice($items, 0, 8);
    }

    /**
     * Build time-series visitor counts from the site_visits table.
     *
     * @return array{range: string, labels: array<int, string>, data: array<int, int>}
     */
    private function buildVisitorChart(string $range): array
    {
        $labels = [];
        $data = [];

        if (! Schema::hasTable('site_visits')) {
            return [
                'range' => $range,
                'labels' => $labels,
                'data' => $data,
            ];
        }

        if ($range === '12m') {
            $start = Carbon::now()->startOfMonth()->subMonths(11);
            $end = Carbon::now()->endOfMonth();

            $countsByMonth = SiteVisit::whereBetween('visit_date', [$start->toDateString(), $end->toDateString()])
                ->get(['visit_date'])
                ->groupBy(fn (SiteVisit $visit): string => Carbon::parse($visit->visit_date)->format('Y-m'))
                ->map(fn ($group): int => $group->count());

            for ($i = 11; $i >= 0; $i--) {
                $month = Carbon::now()->startOfMonth()->subMonths($i);
                $key = $month->format('Y-m');
                $labels[] = $month->format('M Y');
                $data[] = (int) ($countsByMonth[$key] ?? 0);
            }

            return [
                'range' => $range,
                'labels' => $labels,
                'data' => $data,
            ];
        }

        $days = $range === '30d' ? 30 : 7;
        $start = Carbon::now()->startOfDay()->subDays($days - 1);
        $end = Carbon::now()->endOfDay();

        $countsByDay = SiteVisit::whereBetween('visit_date', [$start->toDateString(), $end->toDateString()])
            ->get(['visit_date'])
            ->groupBy(fn (SiteVisit $visit): string => Carbon::parse($visit->visit_date)->format('Y-m-d'))
            ->map(fn ($group): int => $group->count());

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = Carbon::now()->startOfDay()->subDays($i);
            $key = $day->format('Y-m-d');
            $labels[] = $day->format('d M');
            $data[] = (int) ($countsByDay[$key] ?? 0);
        }

        return [
            'range' => $range,
            'labels' => $labels,
            'data' => $data,
        ];
    }

    public function updateSubmissionStatus(Request $request, string $slug): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected,pending'],
            'type' => ['nullable', 'in:recipe,food,story'],
            'id' => ['nullable'],
        ]);

        $type = $validated['type'] ?? null;
        $id = $validated['id'] ?? null;

        if ($type === 'food' && $id && Schema::hasTable('foods') && Schema::hasColumn('foods', 'approval_status')) {
            Food::whereKey($id)->update(['approval_status' => $validated['status']]);
        } elseif ($type === 'story' && $id && Schema::hasTable('culinary_stories') && Schema::hasColumn('culinary_stories', 'approval_status')) {
            CulinaryStory::whereKey($id)->update(['approval_status' => $validated['status']]);
        } else {
            $overrides = $request->session()->get('admin_recipe_statuses', []);
            $overrides[$slug] = $validated['status'];
            $request->session()->put('admin_recipe_statuses', $overrides);
        }

        $label = $validated['status'] === 'approved' ? 'disetujui' : ($validated['status'] === 'rejected' ? 'ditolak' : 'pending');

        return back()->with('success', 'Status kiriman berhasil diperbarui menjadi '.$label.'.');
    }

    /**
     * @return array<int, string>
     */
    private function getCategoriesList(): array
    {
        if ($this->hasDatabaseTable('categories')) {
            try {
                $dbCategories = Category::orderBy('name')->pluck('name')->all();
                if ($dbCategories !== []) {
                    return $dbCategories;
                }
            } catch (Throwable) {
                // Fallback to default Balinese culinary categories.
            }
        }

        return ['Makanan', 'Minuman', 'Jajan'];
    }

    private function hasDatabaseTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAllCulinaryData(): array
    {
        $custom = session()->get('custom_recipes', []);

        return array_merge(is_array($custom) ? $custom : [], $this->getCulinaryData());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCulinaryData(): array
    {
        if ($this->hasDatabaseTable('foods')) {
            try {
                $foodQuery = Food::with([
                    'category',
                    'recipes' => function ($query) {
                        $query->with(['recipeIngredients.ingredient', 'recipeSteps'])
                            ->latest();
                    },
                    'culinaryStories',
                ])->latest();

                if ($this->hasDatabaseTable('reviews')) {
                    $foodQuery->withCount('reviews')->withAvg('reviews', 'rating');
                }

                $dbFoods = $foodQuery->get();

                $hasFoodApprovalColumn = Schema::hasColumn('foods', 'approval_status');

                $visibleFoods = $dbFoods->reject(function (Food $food) use ($hasFoodApprovalColumn): bool {
                    if ($hasFoodApprovalColumn && $food->approval_status !== 'approved') {
                        return true;
                    }

                    $hasApprovedRecipe = $food->recipes->contains(
                        fn ($recipe): bool => $recipe->approval_status === 'approved'
                    );

                    $hasRejectedRecipe = $food->recipes->contains(
                        fn ($recipe): bool => $recipe->approval_status === 'rejected'
                    );

                    // Sembunyikan jika ada resep yang di-reject dan belum ada resep yang di-approve
                    if ($hasRejectedRecipe && ! $hasApprovedRecipe) {
                        return true;
                    }

                    // Sembunyikan jika food dibuat oleh user namun belum ada resep yang di-approve
                    if (! empty($food->user_id) && ! $hasApprovedRecipe) {
                        return true;
                    }

                    return false;
                })->values();

                if ($visibleFoods->isNotEmpty()) {
                    return $visibleFoods->map(function (Food $food, int $index): array {
                        /** @var Recipe|null $approvedRecipe */
                        $approvedRecipe = $food->recipes->firstWhere('approval_status', 'approved');
                        $story = $food->culinaryStories->first();

                        $ingredients = [];
                        if ($approvedRecipe) {
                            $ingredients = $approvedRecipe->recipeIngredients
                                ->map(function ($item): string {
                                    $name = trim((string) ($item->ingredient?->name ?? 'Bahan'));
                                    $amount = trim((string) ($item->amount ?? ''));
                                    $unit = trim((string) ($item->unit ?? ''));
                                    $qty = trim($amount.' '.$unit);

                                    return $qty !== '' ? "{$qty} {$name}" : $name;
                                })
                                ->filter()
                                ->values()
                                ->all();
                        }

                        $steps = [];
                        if ($approvedRecipe) {
                            $steps = $approvedRecipe->recipeSteps
                                ->sortBy('step_number')
                                ->pluck('instruction')
                                ->filter()
                                ->values()
                                ->all();
                        }

                        $cookMinutes = $approvedRecipe?->cook_time_minutes ?? $approvedRecipe?->prep_time_minutes;
                        $cookTime = $cookMinutes ? $cookMinutes.' menit' : '30 menit';
                        $rawDifficulty = strtolower((string) ($approvedRecipe?->difficulty ?? ''));
                        $difficulty = match ($rawDifficulty) {
                            'easy', 'mudah' => 'Mudah',
                            'hard', 'sulit' => 'Sulit',
                            'medium', 'sedang' => 'Sedang',
                            default => 'Sedang',
                        };

                        $storyText = trim(strip_tags((string) ($story?->content ?? '')));
                        $shortHistory = $storyText !== ''
                            ? (mb_strlen($storyText) > 140 ? mb_substr($storyText, 0, 137).'...' : $storyText)
                            : 'Kuliner khas Bali dengan cita rasa autentik.';
                        $description = trim((string) ($food->short_description ?? ''));
                        $history = $description !== '' ? $description : $shortHistory;

                        return [
                            'id' => $food->id,
                            'slug' => $food->slug,
                            'name' => $food->name,
                            'region' => (string) ($food->origin_area ?: 'Bali'),
                            'category' => (string) ($food->category?->name ?? 'Kuliner'),
                            'difficulty' => $difficulty,
                            'cook_time' => $cookTime,
                            'servings' => $approvedRecipe?->servings ? $approvedRecipe->servings.' porsi' : '4 porsi',
                            'rating' => ((int) ($food->reviews_count ?? 0)) > 0
                                ? round((float) $food->reviews_avg_rating, 1)
                                : 0.0,
                            'reviews_count' => (int) ($food->reviews_count ?? 0),
                            'is_popular' => $index < 3,
                            'description' => $description,
                            'history' => $history,
                            'ingredient_groups' => $ingredients !== [] ? ['Bahan Utama' => $ingredients] : ['Bahan Utama' => ['Bumbu khas Bali']],
                            'ingredients' => $ingredients !== [] ? $ingredients : ['Bumbu khas Bali'],
                            'steps' => $steps !== [] ? $steps : ['Siapkan bahan dan bumbu khas Bali, lalu olah sesuai tradisi kuliner Bali.'],
                            'notes' => 'Kuliner pilihan dari daftar kuliner dan resep yang telah disetujui di Bali Culinary.',
                            'image' => (string) ($food->thumbnail ?: ($story?->thumbnail ?: 'Assets/images/lawar.jpg')),
                        ];
                    })->all();
                }
            } catch (Throwable) {
            }
        }

        return $this->getDefaultCulinaryData();
    }

    /**
     * Fallback static catalogue used only when the foods database table is empty.
     * Ratings and review counts default to 0 so unverified numbers are never fabricated.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getDefaultCulinaryData(): array
    {
        $items = [
            [
                'id' => 1,
                'slug' => 'lawar-bali',
                'name' => 'Lawar Bali',
                'region' => 'Badung',
                'category' => 'Makanan',
                'difficulty' => 'Sedang',
                'cook_time' => '45 menit',
                'servings' => '4 porsi',
                'rating' => 4.8,
                'reviews_count' => 124,
                'is_popular' => true,
                'description' => 'Rempah khas Bali yang segar, gurih, dan kaya rasa dari perpaduan nangka muda, kacang panjang, kelapa parut, serta base genep.',
                'history' => 'Lawar merupakan hidangan tradisional khas Bali yang selalu hadir dalam upacara adat maupun santapan keluarga. Kata lawar berasal dari tradisi nglawar yang mencerminkan semangat gotong royong masyarakat Bali.',
                'ingredient_groups' => [
                    'Bahan Utama' => [
                        '250 g kacang panjang, iris kecil',
                        '200 g daging ayam/bebek cincang',
                        '150 g kelapa parut setengah tua, bakar',
                        '2 buah jeruk limau, peras airnya',
                    ],
                    'Bumbu Base Genep' => [
                        '6 siung bawang merah & 4 bawang putih',
                        '2 cm kunyit, kencur, lengkuas, jahe',
                        '3 butir kemiri sangrai & 1 sdt ketumbar',
                        '5 buah cabai merah & terasi bakar',
                    ],
                    'Pelengkap' => [
                        'Bawang merah goreng secukupnya',
                        'Cabai rawit iris & garam laut',
                        'Sate lilit dan sambal matah',
                    ],
                ],
                'ingredients' => [
                    '250 gram kacang panjang, potong kecil-kecil',
                    '200 gram daging ayam/bebek cincang halus',
                    '150 gram kelapa parut setengah tua, bakar sebentar',
                    '3 sdm bumbu base genep Bali',
                    '4 siung bawang merah goreng & 3 siung bawang putih goreng',
                    '2 buah jeruk limau, ambil airnya',
                    'Garam, terasi bakar, dan cabai rawit secukupnya',
                ],
                'steps' => [
                    'Rebus kacang panjang sebentar hingga matang tetapi tetap renyah, lalu tiriskan dan sisihkan.',
                    'Tumis bumbu base genep Bali hingga harum dan matang sempurna, masukkan daging cincang lalu aduk hingga bumbu meresap.',
                    'Campurkan kelapa parut sangrai, kacang panjang, daging berbumbu, serta bawang merah goreng dalam wadah besar.',
                    'Tambahkan garam, terasi bakar, irisan cabai rawit, dan perasan jeruk limau. Aduk rata lalu koreksi rasa.',
                    'Sajikan Lawar Bali segera bersama nasi putih hangat, sate lilit, dan sambal matah segar.',
                ],
                'notes' => 'Gunakan kelapa parut yang tidak terlalu tua agar tekstur lawar tetap lembut dan gurih.',
                'image' => 'Assets/images/lawar.jpg',
            ],
            [
                'id' => 2,
                'slug' => 'ayam-betutu-gilimanuk',
                'name' => 'Ayam Betutu',
                'region' => 'Jembrana',
                'category' => 'Makanan',
                'difficulty' => 'Sulit',
                'cook_time' => '120 menit',
                'servings' => '5 porsi',
                'rating' => 4.9,
                'reviews_count' => 210,
                'is_popular' => true,
                'description' => 'Ayam kampung utuh yang dibumbui base genep khas Bali dan dimasak perlahan hingga empuk serta kaya aroma rempah.',
                'history' => 'Berasal dari kawasan Gilimanuk, Jembrana, Ayam Betutu dahulu disajikan khusus pada perayaan keagamaan dan upacara adat Bali sebelum dikenal luas sebagai ikon kuliner Pulau Dewata.',
                'ingredient_groups' => [
                    'Bahan Utama' => [
                        '1 ekor ayam kampung utuh',
                        '500 ml air kelapa atau kaldu ayam',
                        'Daun singkong rebus untuk isian',
                        'Daun pisang untuk membungkus',
                    ],
                    'Bumbu Base Genep' => [
                        '5 sdm bumbu base genep cincang kasar',
                        '2 batang serai, memarkan',
                        '4 lembar daun salam & 5 daun jeruk',
                        'Garam, gula merah, dan terasi bakar',
                    ],
                ],
                'ingredients' => [
                    '1 ekor ayam kampung utuh, bersihkan',
                    '5 sdm bumbu base genep cincang kasar',
                    '2 batang serai, memarkan',
                    '4 lembar daun salam & 5 lembar daun jeruk',
                    'Daun singkong rebus secukupnya untuk isian',
                    '500 ml air kelapa atau kaldu ayam',
                    'Daun pisang untuk membungkus',
                ],
                'steps' => [
                    'Lumuri seluruh permukaan dan bagian dalam ayam kampung dengan bumbu base genep yang telah ditumis harum.',
                    'Masukkan daun singkong berbumbu ke dalam rongga perut ayam, lalu diamkan selama 30 menit agar meresap.',
                    'Ungkep ayam dengan air kelapa, serai, daun salam, dan daun jeruk menggunakan api kecil selama 60–90 menit hingga empuk.',
                    'Bungkus ayam dengan daun pisang lalu panggang sebentar di atas bara atau oven agar aroma smokey keluar.',
                    'Sajikan hangat bersama plecing kangkung, kacang tanah goreng, dan sambal matah.',
                ],
                'notes' => 'Memasak dengan api kecil secara perlahan adalah kunci utama agar bumbu meresap hingga ke tulang.',
                'image' => 'Assets/images/ayam-betutu.jpg',
            ],
            [
                'id' => 3,
                'slug' => 'sate-lilit-ikan',
                'name' => 'Sate Lilit',
                'region' => 'Gianyar',
                'category' => 'Makanan',
                'difficulty' => 'Sedang',
                'cook_time' => '40 menit',
                'servings' => '4 porsi',
                'rating' => 4.9,
                'reviews_count' => 176,
                'is_popular' => true,
                'description' => 'Olahan daging ikan laut cincang yang dipadukan dengan kelapa parut dan bumbu Bali, dililitkan pada batang serai.',
                'history' => 'Sate Lilit menjadi simbol persatuan dalam budaya kuliner Bali. Adonan ikan berbumbu dililitkan erat pada batang serai segar sehingga memancarkan aroma harum saat dibakar.',
                'ingredient_groups' => [
                    'Bahan Utama' => [
                        '400 g fillet ikan tenggiri/tuna cincang',
                        '100 g kelapa parut muda',
                        '3 sdm santan kental',
                        '15 batang serai segar untuk lilitan',
                    ],
                    'Bumbu Halus' => [
                        '3 sdm bumbu halus Bali',
                        '3 lembar daun jeruk, iris sangat halus',
                        'Garam & gula merah sisir secukupnya',
                    ],
                ],
                'ingredients' => [
                    '400 gram fillet ikan tenggiri atau tuna cincang halus',
                    '100 gram kelapa parut muda',
                    '3 sdm santan kental',
                    '3 sdm bumbu halus Bali',
                    '3 lembar daun jeruk, iris sangat halus',
                    '15 batang serai segar untuk tusukan',
                ],
                'steps' => [
                    'Campurkan ikan cincang, kelapa parut, santan kental, bumbu halus, irisan daun jeruk, garam, dan gula merah.',
                    'Uleni adonan hingga kalis dan mudah dibentuk.',
                    'Ambil segenggam adonan, lalu lilitkan memanjang pada ujung batang serai sambil dipadatkan.',
                    'Bakar sate di atas bara api sambil dibolak-balik hingga matang keemasan dan harum.',
                    'Angkat dan sajikan hangat bersama sambal matah.',
                ],
                'notes' => 'Basahi tangan dengan sedikit minyak kelapa saat melilit adonan agar tidak lengket.',
                'image' => 'Assets/images/sate-lilit.jpg',
            ],
            [
                'id' => 4,
                'slug' => 'nasi-campur-bali',
                'name' => 'Nasi Campur',
                'region' => 'Denpasar',
                'category' => 'Makanan',
                'difficulty' => 'Sedang',
                'cook_time' => '60 menit',
                'servings' => '2 porsi',
                'rating' => 4.8,
                'reviews_count' => 152,
                'is_popular' => true,
                'description' => 'Sepiring nasi hangat lengkap dengan lawar, sate lilit, ayam sisit pedas, kacang tanah goreng, dan sambal matah.',
                'history' => 'Nasi Campur Bali mencerminkan kekayaan lauk tradisional dalam satu piring dan menjadi menu favorit masyarakat Bali sejak pagi hingga malam hari.',
                'ingredient_groups' => [
                    'Komponen Utama' => [
                        '2 porsi nasi putih hangat',
                        '100 g ayam sisit bumbu Bali',
                        '2 tusuk sate lilit ikan',
                        '3 sdm lawar kacang panjang',
                    ],
                    'Pelengkap' => [
                        '1 butir telur pindang belah dua',
                        '2 sdm sambal matah segar',
                        'Kacang tanah goreng & daun pisang',
                    ],
                ],
                'ingredients' => [
                    '2 porsi nasi putih hangat',
                    '100 gram ayam sisit bumbu Bali',
                    '2 tusuk sate lilit ikan',
                    '3 sdm lawar kacang panjang',
                    '1 butir telur pindang, belah dua',
                    '2 sdm sambal matah & kacang tanah goreng',
                ],
                'steps' => [
                    'Siapkan seluruh komponen lauk: ayam sisit berbumbu, lawar Bali, sate lilit bakar, dan telur pindang.',
                    'Tata nasi putih hangat di tengah piring saji beralaskan daun pisang.',
                    'Susun ayam sisit, lawar, sate lilit, dan telur mengelilingi nasi.',
                    'Tambahkan kacang tanah goreng dan sambal matah segar di atasnya.',
                ],
                'notes' => 'Alas daun pisang yang dipanaskan sebentar membuat aroma nasi campur semakin autentik.',
                'image' => 'Assets/images/nasi-campur.jpg',
            ],
            [
                'id' => 5,
                'slug' => 'tum-ayam-bali',
                'name' => 'Tum Ayam',
                'region' => 'Tabanan',
                'category' => 'Makanan',
                'difficulty' => 'Mudah',
                'cook_time' => '35 menit',
                'servings' => '6 bungkus',
                'rating' => 4.7,
                'reviews_count' => 89,
                'is_popular' => false,
                'description' => 'Pepes ayam cincang khas Bali yang dibungkus daun pisang bersama santan dan rempah harum lalu dikukus.',
                'history' => 'Tum merupakan teknik memasak tradisional Bali dengan membungkus adonan daging berbumbu di dalam daun pisang lalu mengukusnya hingga wangi.',
                'ingredient_groups' => [
                    'Bahan Utama' => [
                        '300 g daging ayam cincang',
                        '50 ml santan kental & 1 butir telur',
                        '3 sdm bumbu halus Bali',
                        'Daun salam, cabai rawit, daun pisang',
                    ],
                ],
                'ingredients' => [
                    '300 gram daging ayam cincang',
                    '50 ml santan kental',
                    '1 butir telur ayam',
                    '3 sdm bumbu halus Bali',
                    'Daun salam, cabai rawit utuh, dan daun pisang',
                ],
                'steps' => [
                    'Aduk rata ayam cincang, santan, telur, dan bumbu halus Bali hingga tercampur sempurna.',
                    'Ambil 2 sendok makan adonan, letakkan di atas daun pisang bersama daun salam dan cabai rawit.',
                    'Bungkus rapi berbentuk tum lalu semat dengan lidi.',
                    'Kukus selama 25–30 menit hingga matang dan wangi.',
                ],
                'notes' => 'Tambahkan sedikit irisan daun kemangi bila suka aroma lebih segar.',
                'image' => 'Assets/images/tum-ayam.jpg',
            ],
            [
                'id' => 6,
                'slug' => 'tipat-cantok',
                'name' => 'Tipat Cantok',
                'region' => 'Denpasar',
                'category' => 'Makanan',
                'difficulty' => 'Mudah',
                'cook_time' => '25 menit',
                'servings' => '2 porsi',
                'rating' => 4.8,
                'reviews_count' => 94,
                'is_popular' => false,
                'description' => 'Ketupat potong dengan sayuran rebus segar, tahu goreng, dan siraman bumbu kacang ulek beraroma kencur serta jeruk limau.',
                'history' => 'Tipat Cantok adalah hidangan sederhana nan nikmat yang mudah ditemui di warung tradisional Bali. Kata cantok berarti mengulek bumbu langsung di atas cobek tanah liat.',
                'ingredient_groups' => [
                    'Bahan Utama' => [
                        '2 buah tipat (ketupat), potong kotak',
                        '100 g tauge & kacang panjang rebus',
                        '100 g kangkung rebus & tahu goreng',
                    ],
                    'Bumbu Kacang' => [
                        '100 g kacang tanah goreng',
                        '2 siung bawang putih & 2 cm kencur',
                        'Cabai rawit, terasi bakar, gula merah, limau',
                    ],
                ],
                'ingredients' => [
                    '2 buah tipat (ketupat), potong kotak',
                    '100 gram kangkung, tauge, dan kacang panjang rebus',
                    '100 gram kacang tanah goreng',
                    '2 siung bawang putih & 2 cm kencur',
                    'Cabai rawit, terasi bakar, gula merah, dan jeruk limau',
                ],
                'steps' => [
                    'Ulek bawang putih, kencur, cabai rawit, terasi, gula merah, dan kacang tanah goreng hingga halus.',
                    'Tambahkan air matang hangat sedikit demi sedikit sambil diaduk hingga kekentalan pas.',
                    'Masukkan potongan tipat, sayuran rebus, dan tahu goreng ke dalam cobek, aduk rata.',
                    'Sajikan dengan taburan bawang goreng dan kerupuk.',
                ],
                'notes' => 'Perasan jeruk limau di akhir memberikan kesegaran khas Tipat Cantok Bali.',
                'image' => 'Assets/images/tipat-cantok.jpg',
            ],
            [
                'id' => 7,
                'slug' => 'es-daluman',
                'name' => 'Es Daluman',
                'region' => 'Denpasar',
                'category' => 'Minuman',
                'difficulty' => 'Mudah',
                'cook_time' => '20 menit',
                'servings' => '4 gelas',
                'rating' => 4.8,
                'reviews_count' => 98,
                'is_popular' => false,
                'description' => 'Minuman segar cincau hijau khas Bali dengan sirup gula aren wangi pandan dan santan gurih.',
                'history' => 'Es Daluman terbuat dari perasan daun daluman (cincau hijau rambat) yang menyejukkan tubuh di tengah teriknya cuaca Bali.',
                'ingredient_groups' => [
                    'Bahan Utama' => [
                        '250 g daluman (cincau hijau segar)',
                        '200 ml santan rebus dengan garam',
                        '150 ml sirup gula aren & daun pandan',
                        'Es batu secukupnya',
                    ],
                ],
                'ingredients' => [
                    '250 gram daluman (cincau hijau segar)',
                    '200 ml santan rebus dengan sejumput garam',
                    '150 ml sirup gula aren & daun pandan',
                    'Es batu secukupnya',
                ],
                'steps' => [
                    'Rebus gula aren dengan air dan daun pandan hingga larut dan agak mengental, lalu dinginkan.',
                    'Rebus santan dengan sedikit garam sambil diaduk agar tidak pecah, lalu dinginkan.',
                    'Sendokkan daluman segar ke dalam gelas saji.',
                    'Tuang sirup gula aren, santan, dan tambahkan es batu secukupnya.',
                ],
                'notes' => 'Sajikan dingin segera agar tekstur daluman tetap lembut dan segar.',
                'image' => 'Assets/images/es-daluman.jpg',
            ],
            [
                'id' => 8,
                'slug' => 'jaje-laklak',
                'name' => 'Jaje Laklak',
                'region' => 'Klungkung',
                'category' => 'Jajan',
                'difficulty' => 'Mudah',
                'cook_time' => '30 menit',
                'servings' => '12 buah',
                'rating' => 4.9,
                'reviews_count' => 141,
                'is_popular' => false,
                'description' => 'Kue tradisional tepung beras beraroma pandan yang dipanggang di cetakan tanah liat, disajikan dengan kelapa parut dan saus gula merah.',
                'history' => 'Jaje Laklak adalah jajanan pasar tradisional khas Bali yang identik dengan warna hijau alami dari daun suji dan pandan serta taburan kelapa parut gurih.',
                'ingredient_groups' => [
                    'Bahan Adonan' => [
                        '200 g tepung beras & 1/2 sdt garam',
                        '400 ml santan cair hangat',
                        '50 ml air perasan daun pandan & suji',
                    ],
                    'Pelengkap' => [
                        '100 g kelapa parut kukus',
                        'Sirup gula merah kental',
                    ],
                ],
                'ingredients' => [
                    '200 gram tepung beras',
                    '400 ml santan cair hangat',
                    '50 ml air perasan daun pandan dan daun suji',
                    '1/2 sdt garam',
                    '100 gram kelapa parut kukus & sirup gula merah kental',
                ],
                'steps' => [
                    'Campurkan tepung beras, garam, santan hangat, dan air pandan suji, aduk hingga adonan licin.',
                    'Panaskan cetakan kue lumpur atau tanah liat dengan api kecil dan olesi sedikit minyak.',
                    'Tuang adonan hingga 3/4 cetakan, tutup, dan panggang sampai berpori serta matang.',
                    'Angkat Jaje Laklak, taburi kelapa parut kukus, dan siram dengan saus gula merah kental.',
                ],
                'notes' => 'Gunakan api kecil agar bagian bawah jaje laklak matang harum tanpa cepat gosong.',
                'image' => 'Assets/images/jaje-laklak.jpg',
            ],
            [
                'id' => 9,
                'slug' => 'loloh-cemcem',
                'name' => 'Loloh Cemcem',
                'region' => 'Bangli',
                'category' => 'Minuman',
                'difficulty' => 'Mudah',
                'cook_time' => '25 menit',
                'servings' => '4 gelas',
                'rating' => 4.7,
                'reviews_count' => 63,
                'is_popular' => false,
                'description' => 'Minuman herbal tradisional khas Desa Penglipuran dari daun cemcem dengan perpaduan rasa asam, manis, asin, dan sedikit pedas.',
                'history' => 'Loloh Cemcem adalah minuman jamu tradisional kebanggaan Desa Penglipuran, Bangli, yang dipercaya membantu melancarkan pencernaan dan menyegarkan stamina.',
                'ingredient_groups' => [
                    'Bahan Utama' => [
                        '2 genggam daun cemcem muda',
                        '500 ml air kelapa muda / air matang',
                        '2 sdm air asam jawa & gula aren cair',
                        'Sedikit garam & 1 cabai rawit (opsional)',
                    ],
                ],
                'ingredients' => [
                    '2 genggam daun cemcem muda',
                    '500 ml air kelapa muda atau air matang',
                    '2 sdm air asam jawa',
                    '2 sdm gula aren cair & sedikit garam',
                    '1 buah cabai rawit (opsional)',
                ],
                'steps' => [
                    'Cuci bersih daun cemcem lalu remas-remas atau blender bersama sebagian air hingga sarinya keluar.',
                    'Saring air perasan daun cemcem ke dalam wadah bersih.',
                    'Campurkan air asam jawa, gula aren cair, garam, dan air kelapa muda, aduk rata.',
                    'Simpan di kulkas hingga dingin lalu sajikan.',
                ],
                'notes' => 'Gunakan daun cemcem muda agar rasanya tidak terlalu sepat.',
                'image' => 'Assets/images/loloh-cemcem.jpg',
            ],
        ];

        return array_map(static function (array $item): array {
            $item['rating'] = 0.0;
            $item['reviews_count'] = 0;

            return $item;
        }, $items);
    }
}
