<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CulinaryStory;
use App\Models\Food;
use App\Models\Recipe;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;


class PageController extends Controller
{
    public function home(): View
    {
        $foods = $this->getAllCulinaryData();
        $popularFoods = array_values(array_filter($foods, fn (array $food): bool => $food['is_popular']));

        return view('home', compact('foods', 'popularFoods'));
    }

    public function explore(Request $request): View
    {
        $allFoods = collect($this->getAllCulinaryData());
        $search = trim((string) $request->query('search', ''));
        $selectedCategory = trim((string) $request->query('category', ''));
        $selectedRegion = trim((string) $request->query('region', ''));
        $selectedDifficulty = trim((string) $request->query('difficulty', ''));

        $categories = Category::orderBy('name')->pluck('name')->all();
        if ($categories === []) {
            $categories = ['Makanan', 'Minuman', 'Jajan'];
        }

        $regions = $allFoods->pluck('region')->filter()->unique()->sort()->values()->all();
        $difficulties = ['Mudah', 'Sedang', 'Sulit'];

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
            ->values();

        $perPage = 8;
        $totalPages = max(1, (int) ceil($filtered->count() / $perPage));
        $currentPage = min(max(1, (int) $request->query('page', 1)), $totalPages);
        $foods = $filtered->slice(($currentPage - 1) * $perPage, $perPage)->values()->all();

        $popularFoods = $allFoods->where('is_popular', true)->take(3)->values()->all();

        return view('explore', compact(
            'foods',
            'popularFoods',
            'categories',
            'regions',
            'difficulties',
            'search',
            'selectedCategory',
            'selectedRegion',
            'selectedDifficulty',
            'currentPage',
            'totalPages'
        ));
    }

    public function foodDetail(?string $slug = null): View
    {
        $foods = $this->getAllCulinaryData();
        $food = collect($foods)->firstWhere('slug', $slug) ?? ($foods[0] ?? null);
        $relatedFoods = $food
            ? collect($foods)
                ->reject(fn (array $item): bool => $item['slug'] === $food['slug'])
                ->take(3)
                ->values()
                ->all()
            : [];

        $isFavorited = false;
        if (Auth::check() && $food) {
            $foodModel = Food::where('slug', $food['slug'])->first();
            if ($foodModel) {
                $isFavorited = Auth::user()->favoriteFoods()->where('foods.id', $foodModel->id)->exists();
            }
        }

        $reviews = collect();
        $averageRating = $food['rating'] ?? 0;
        $reviewsCount = $food['reviews_count'] ?? 0;

        if ($food) {
            $foodModel = Food::where('slug', $food['slug'])->first();

            if ($foodModel) {
                $reviews = $foodModel->reviews()->with('user')->latest()->get();

                if ($reviews->isNotEmpty()) {
                    $reviewsCount = $reviews->count();
                    $averageRating = round((float) $reviews->avg('rating'), 1);
                }
            }
        }

        return view('food-detail', compact('food', 'relatedFoods', 'isFavorited', 'reviews', 'averageRating', 'reviewsCount'));
    }

    public function recipe(Request $request): View
    {
        $allFoods = collect($this->getAllCulinaryData());
        $selectedCategory = trim((string) $request->query('category', ''));
        $categories = Category::orderBy('name')->pluck('name')->all();
        if ($categories === []) {
            $categories = ['Makanan', 'Minuman', 'Jajan'];
        }

        $filtered = $allFoods
            ->when($selectedCategory !== '', fn ($collection) => $collection->where('category', $selectedCategory))
            ->values();

        $perPage = 9;
        $totalPages = max(1, (int) ceil($filtered->count() / $perPage));
        $currentPage = min(max(1, (int) $request->query('page', 1)), $totalPages);
        $foods = $filtered->slice(($currentPage - 1) * $perPage, $perPage)->values()->all();

        return view('recipe', compact('foods', 'categories', 'selectedCategory', 'currentPage', 'totalPages'));
    }

    public function createRecipe(): View
    {
        $categories = Category::orderBy('name')->pluck('name')->all();
        if ($categories === []) {
            $categories = ['Makanan', 'Minuman', 'Jajan'];
        }

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

        $baseSlug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $validated['name']), '-'));
        $slug = ($baseSlug !== '' ? $baseSlug : 'resep').'-'.strtolower(Str::random(4));

        $newRecipe = [
            'id' => time(),
            'slug' => $slug,
            'name' => $validated['name'],
            'region' => $validated['region'],
            'category' => $validated['category'],
            'difficulty' => $validated['difficulty'],
            'cook_time' => $validated['cook_time'],
            'rating' => 4.8,
            'reviews_count' => 1,
            'is_popular' => false,
            'description' => $validated['description'],
            'history' => $validated['description'],
            'ingredients' => $ingredients,
            'steps' => $steps,
            'notes' => 'Resep ini dibagikan oleh komunitas pecinta kuliner Bali di Bali Culinary Guide.',
            'image' => 'Assets/images/custom-recipe.jpg',
        ];

        $customRecipes = $request->session()->get('custom_recipes', []);
        array_unshift($customRecipes, $newRecipe);
        $request->session()->put('custom_recipes', $customRecipes);

        return redirect()
            ->route('food.detail', $slug)
            ->with('success', 'Resep "'.$validated['name'].'" berhasil dibagikan!');
    }

    public function stories(): View
    {
        $dbStories = CulinaryStory::with('food')->latest()->get();

        if ($dbStories->isNotEmpty()) {
            $stories = $dbStories->map(function (CulinaryStory $story): array {
                $plainText = trim(strip_tags((string) $story->content));
                $wordCount = str_word_count($plainText);
                $readMinutes = max(1, (int) ceil($wordCount / 180));

                return [
                    'title' => $story->title,
                    'region' => $story->food?->origin_area ?: 'Bali',
                    'read_time' => $readMinutes.' menit baca',
                    'excerpt' => Str::limit($plainText, 160),
                    'thumbnail' => $story->thumbnail ?: ($story->food?->thumbnail ?? 'Assets/images/lawar.jpg'),
                ];
            })->all();

            return view('stories', compact('stories'));
        }

        $stories = [];

        return view('stories', compact('stories'));
    }

    public function about(): View
    {
        return view('about');
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
        $foods = Food::with([
            'category',
            'recipes' => function ($query) {
                $query->where('approval_status', 'approved')
                    ->with(['recipeIngredients.ingredient', 'recipeSteps'])
                    ->latest();
            },
            'culinaryStories',
        ])->latest()->get();

        return $foods->map(function (Food $food, int $index): array {
            /** @var Recipe|null $approvedRecipe */
            $approvedRecipe = $food->recipes->first();
            $story = $food->culinaryStories->first();

            $ingredients = [];
            if ($approvedRecipe) {
                $ingredients = $approvedRecipe->recipeIngredients
                    ->map(function ($item): string {
                        $name = $item->ingredient?->name ?? 'Bahan';
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

            $rawDifficulty = strtolower(trim((string) ($approvedRecipe?->difficulty ?? '')));
            $difficulty = match ($rawDifficulty) {
                'easy', 'mudah' => 'Mudah',
                'hard', 'sulit' => 'Sulit',
                'medium', 'sedang' => 'Sedang',
                default => 'Sedang',
            };

            $description = (string) ($food->short_description ?: ($story ? Str::limit(strip_tags((string) $story->content), 160) : 'Kuliner khas Bali di Bali Culinary Guide.'));
            $history = $story ? strip_tags((string) $story->content) : $description;
            $image = (string) ($food->thumbnail ?: ($story?->thumbnail ?? 'Assets/images/lawar.jpg'));

            return [
                'id' => $food->id,
                'slug' => $food->slug,
                'name' => $food->name,
                'region' => (string) ($food->origin_area ?: 'Bali'),
                'category' => (string) ($food->category?->name ?? 'Makanan'),
                'difficulty' => $difficulty,
                'cook_time' => $cookTime,
                'rating' => 4.8,
                'reviews_count' => max(1, $food->recipes->count()),
                'is_popular' => $index < 4,
                'description' => $description,
                'history' => $history,
                'ingredients' => $ingredients,
                'steps' => $steps,
                'notes' => 'Disajikan melalui data kuliner dan resep yang telah disetujui di Bali Culinary Guide.',
                'image' => $image,
            ];
        })->all();
    }

    public function toggleFavorite(Request $request, string $slug): RedirectResponse
    {
        $user = Auth::user();
        $food = Food::where('slug', $slug)->first();

        if (! $food) {
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
        $favoriteSlugs = Auth::check()
            ? Auth::user()->favoriteFoods()->pluck('slug')->all()
            : [];

        $allFoods = $this->getAllCulinaryData();

        $foods = array_values(array_filter(
            $allFoods,
            fn (array $food): bool => in_array($food['slug'], $favoriteSlugs, true)
        ));

        return view('favorites', compact('foods'));
    }

    public function storeReview(Request $request, string $slug): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $food = Food::where('slug', $slug)->first();

        if (! $food) {
            return back()->with('error', 'Data makanan tidak ditemukan di database.');
        }

        Review::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'food_id' => $food->id,
            ],
            [
                'rating' => (int) $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        return back()->with('success', 'Ulasan berhasil disimpan!');
    }
}