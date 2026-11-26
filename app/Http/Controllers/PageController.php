<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CulinaryStory;
use App\Models\Food;
use App\Models\Recipe;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

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

        $categories = $this->getCategoriesList();
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
        $averageRating = $food['rating'] ?? 0;
        $reviewsCount = $food['reviews_count'] ?? 0;

        if ($food && $this->hasDatabaseTable('foods') && $this->hasDatabaseTable('reviews')) {
            $foodModel = Food::where('slug', $food['slug'])->first();

            if ($foodModel) {
                $reviews = $foodModel->reviews()->with('user')->latest()->get();

                if ($reviews->isNotEmpty()) {
                    $reviewsCount = $reviews->count();
                    $averageRating = round((float) $reviews->avg('rating'), 1);
                }
            }
        }

        return view('food-detail', compact('food', 'foods', 'relatedFoods', 'isFavorited', 'reviews', 'averageRating', 'reviewsCount'));
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
            'servings' => $validated['servings'] ?: '4 porsi',
            'rating' => 5.0,
            'reviews_count' => 1,
            'is_popular' => false,
            'status' => 'pending',
            'author' => Auth::user()?->name ?? 'Acakadut',
            'submitted_at' => 'Baru saja',
            'description' => $validated['description'],
            'history' => $validated['description'],
            'ingredient_groups' => [
                'Bahan Utama' => $ingredients,
            ],
            'ingredients' => $ingredients,
            'steps' => $steps,
            'notes' => 'Resep ini dibagikan oleh komunitas pecinta kuliner Bali di Bali Culinary.',
            'image' => 'Assets/images/custom-recipe.jpg',
        ];

        $customRecipes = $request->session()->get('custom_recipes', []);
        array_unshift($customRecipes, $newRecipe);
        $request->session()->put('custom_recipes', $customRecipes);

        return redirect()
            ->route('food.detail', $slug)
            ->with('success', 'Resep "'.$validated['name'].'" berhasil dikirim dan menunggu persetujuan admin!');
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
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $food = $this->hasDatabaseTable('foods') ? Food::where('slug', $slug)->first() : null;

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

    public function mySubmissions(Request $request): View
    {
        $userId = $request->user()->id;

        $foodSubmissions = Food::with('category')
            ->where('user_id', $userId)
            ->latest()
            ->get();

        $recipeSubmissions = Recipe::with('food')
            ->where('user_id', $userId)
            ->latest()
            ->get();

        $storySubmissions = CulinaryStory::with('food')
            ->where('user_id', $userId)
            ->latest()
            ->get();

        return view('submissions', compact(
            'foodSubmissions',
            'recipeSubmissions',
            'storySubmissions'
        ));
    }

    public function stories(): View
    {
        $stories = [];

        if ($this->hasDatabaseTable('culinary_stories')) {
            try {
                $storyQuery = CulinaryStory::with('food')->latest();

                if (Schema::hasColumn('culinary_stories', 'approval_status')) {
                    $storyQuery->where('approval_status', 'approved');
                } elseif (Schema::hasColumn('culinary_stories', 'status')) {
                    $storyQuery->where('status', 'approved');
                }

                $dbStories = $storyQuery->get();

                foreach ($dbStories as $item) {
                    $plain = trim(strip_tags((string) $item->content));
                    $stories[] = [
                        'title' => (string) ($item->title ?: ($item->food?->name ?? 'Kisah Kuliner Bali')),
                        'region' => (string) ($item->food?->origin_area ?: 'Bali'),
                        'read_time' => '4 menit baca',
                        'excerpt' => strlen($plain) > 180 ? substr($plain, 0, 177).'...' : $plain,
                    ];
                }
            } catch (Throwable) {
                $stories = [];
            }
        }

        if ($stories === []) {
            $stories = [
                [
                    'title' => 'Filosofi Base Genep dalam Dapur Tradisional Bali',
                    'region' => 'Denpasar',
                    'read_time' => '4 menit baca',
                    'excerpt' => 'Base genep bukan sekadar racikan bumbu, melainkan harmoni rasa yang diwariskan turun-temurun untuk upacara adat hingga santapan keluarga.',
                ],
                [
                    'title' => 'Tradisi Megibung di Karangasem: Makan Bersama Tanpa Sekat',
                    'region' => 'Karangasem',
                    'read_time' => '5 menit baca',
                    'excerpt' => 'Duduk melingkar menyantap nasi dan lauk khas Bali dalam satu dulang mengajarkan kebersamaan dan rasa syukur masyarakat Bali.',
                ],
                [
                    'title' => 'Rahasia Aroma Asap Ayam Betutu Gilimanuk',
                    'region' => 'Jembrana',
                    'read_time' => '3 menit baca',
                    'excerpt' => 'Proses memasak perlahan dalam balutan daun pisang dan bara sekam menghasilkan daging ayam empuk beraroma rempah yang memikat.',
                ],
                [
                    'title' => 'Jaje Laklak: Manisnya Jajanan Pasar Pagi di Bali',
                    'region' => 'Klungkung',
                    'read_time' => '3 menit baca',
                    'excerpt' => 'Dipanggang di atas cetakan tanah liat dengan aroma daun pandan, jaje laklak selalu menemani secangkir kopi Bali di pagi hari.',
                ],
            ];
        }

        return view('stories', compact('stories'));
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
            'name' => $user?->name ?? 'Acakadut',
            'handle' => $user ? '@'.strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $user->name)) : '@bali_culinary',
            'email' => $user?->email ?? 'acakadut@baliculinary.id',
            'role' => $user?->role ?? 'user',
        ];

        $favoriteSamples = array_slice($foods, 0, 2);
        $recentSamples = array_slice($foods, 2, 2);
        $reviews = [
            [
                'dish' => 'Lawar Bali',
                'slug' => 'lawar-bali',
                'rating' => 5,
                'comment' => 'Bumbu base genepnya terasa segar sekali, petunjuk langkahnya sangat mudah diikuti di rumah!',
                'date' => '2 hari lalu',
            ],
            [
                'dish' => 'Sate Lilit Ikan',
                'slug' => 'sate-lilit-ikan',
                'rating' => 5,
                'comment' => 'Aroma serai dan kelapa parutnya mantap, cocok disajikan dengan sambal matah.',
                'date' => '5 hari lalu',
            ],
            [
                'dish' => 'Ayam Betutu',
                'slug' => 'ayam-betutu-gilimanuk',
                'rating' => 4,
                'comment' => 'Dagingnya empuk sampai ke tulang, resep keluarga terbaik!',
                'date' => '1 minggu lalu',
            ],
        ];

        return view('account', compact('profile', 'foods', 'favoriteSamples', 'recentSamples', 'reviews'));
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
                'id'     => $recipe->id,
                'type'   => 'recipe',
                'label'  => 'Resep',
                'slug'   => $recipe->slug ?? $recipe->food?->slug ?? '',
                'name'   => $recipe->title ?? $recipe->name ?? 'Resep Tanpa Judul',
                'author' => 'Diajukan oleh ' . ($recipe->user?->name ?? 'Contributor'),
                'status' => $recipe->approval_status ?? 'pending',
                'model'  => $recipe,
            ]);
        }

        foreach ($pendingFoods as $food) {
            $pendingSubmissions->push([
                'id'     => $food->id,
                'type'   => 'food',
                'label'  => 'Makanan',
                'slug'   => $food->slug ?? '',
                'name'   => $food->name ?? 'Kuliner Tanpa Nama',
                'author' => 'Diajukan oleh ' . ($food->user?->name ?? 'Contributor'),
                'status' => $food->approval_status ?? 'pending',
                'model'  => $food,
            ]);
        }

        foreach ($pendingStories as $story) {
            $pendingSubmissions->push([
                'id'     => $story->id,
                'type'   => 'story',
                'label'  => 'Cerita Kuliner',
                'slug'   => $story->food?->slug ?? '',
                'name'   => $story->title ?? ($story->food?->name ?? 'Cerita Kuliner'),
                'author' => 'Diajukan oleh ' . ($story->user?->name ?? 'Contributor'),
                'status' => $story->approval_status ?? 'pending',
                'model'  => $story,
            ]);
        }

        $recentMenu = Recipe::with('user')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($recipe) {
                return [
                    'slug'   => $recipe->slug ?? '',
                    'name'   => $recipe->title ?? $recipe->name ?? 'Resep',
                    'author' => 'oleh ' . ($recipe->user?->name ?? 'Contributor'),
                    'status' => $recipe->approval_status ?? 'pending',
                    'time'   => $recipe->created_at ? $recipe->created_at->diffForHumans() : '-',
                ];
            });

        $stats = [
            'foods'    => Schema::hasTable('foods') ? Food::count() : count($foods),
            'recipes'  => Schema::hasTable('recipes') ? Recipe::count() : 0,
            'pending'  => $pendingSubmissions->count(),
            'reviews'  => Schema::hasTable('reviews') ? Review::count() : 0,
        ];

        return view('admin.dashboard', compact('stats', 'recentMenu', 'pendingSubmissions', 'foods'));
    }

    public function updateSubmissionStatus(Request $request, string $slug): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected,pending'],
            'type'   => ['nullable', 'in:recipe,food,story'],
            'id'     => ['nullable'],
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

        return back()->with('success', 'Status kiriman berhasil diperbarui menjadi ' . $label . '.');
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
                $dbFoods = Food::with([
                    'category',
                    'recipes' => function ($query) {
                        $query->where('approval_status', 'approved')
                            ->with(['recipeIngredients.ingredient', 'recipeSteps'])
                            ->latest();
                    },
                    'culinaryStories',
                ])->latest()->get();

                if ($dbFoods->isNotEmpty()) {
                    return $dbFoods->map(function (Food $food, int $index): array {
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

                        $storyText = $story ? trim(strip_tags((string) $story->content)) : '';
                        $fallbackDesc = $storyText !== ''
                            ? (strlen($storyText) > 160 ? substr($storyText, 0, 157).'...' : $storyText)
                            : 'Kuliner khas Bali di Bali Culinary.';
                        $description = (string) ($food->short_description ?: $fallbackDesc);
                        $history = $storyText !== '' ? $storyText : $description;
                        $image = (string) ($food->thumbnail ?: ($story?->thumbnail ?? 'Assets/images/lawar.jpg'));

                        return [
                            'id' => $food->id,
                            'slug' => $food->slug,
                            'name' => $food->name,
                            'region' => (string) ($food->origin_area ?: 'Bali'),
                            'category' => (string) ($food->category?->name ?? 'Makanan'),
                            'difficulty' => $difficulty,
                            'cook_time' => $cookTime,
                            'servings' => ($approvedRecipe?->servings ? $approvedRecipe->servings.' porsi' : '4 porsi'),
                            'rating' => 4.8,
                            'reviews_count' => max(1, $food->recipes->count()),
                            'is_popular' => $index < 4,
                            'description' => $description,
                            'history' => $history,
                            'ingredient_groups' => ['Bahan Utama' => $ingredients !== [] ? $ingredients : ['Bumbu base genep khas Bali']],
                            'ingredients' => $ingredients !== [] ? $ingredients : ['Bumbu base genep khas Bali'],
                            'steps' => $steps !== [] ? $steps : ['Siapkan bahan dan bumbu base genep, lalu olah sesuai tradisi kuliner Bali.'],
                            'notes' => 'Disajikan melalui data kuliner dan resep yang telah disetujui di Bali Culinary.',
                            'image' => $image,
                        ];
                    })->all();
                }
            } catch (Throwable) {
                // Fallback to curated Balinese culinary dataset below.
            }
        }

        return $this->getDefaultCulinaryData();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDefaultCulinaryData(): array
    {
        return [
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
    }
}
