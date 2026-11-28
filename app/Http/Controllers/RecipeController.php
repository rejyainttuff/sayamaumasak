<?php

namespace App\Http\Controllers;

use App\Models\Food;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeStep;
use App\Models\User;
use App\Services\ContributorLevelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RecipeController extends Controller
{
    public function __construct(
        private readonly ContributorLevelService $levelService
    ) {}

    public function show(Recipe $recipe)
    {
        $recipe->load([
            'food.category',
            'user',
            'recipeIngredients.ingredient',
            'recipeSteps' => fn ($q) => $q->orderBy('step_number'),
        ]);

        $user = auth()->user();
        if (! $recipe->isApproved() && (! $user || (! $user->isAdmin() && $user->id !== $recipe->user_id))) {
            abort(404);
        }

        return view('recipes.show', compact('recipe'));
    }

    public function approvals(Request $request)
    {
        $statusFilter = trim((string) $request->query('status', 'pending'));

        $query = Recipe::with([
            'food.category',
            'user',
            'recipeIngredients.ingredient',
            'recipeSteps' => fn ($q) => $q->orderBy('step_number'),
        ])->latest();

        if (in_array($statusFilter, ['pending', 'approved', 'rejected'], true)) {
            $query->where('approval_status', $statusFilter);
        }

        $recipes = $query->paginate(10)->withQueryString();
        $counts = [
            'all' => Recipe::count(),
            'pending' => Recipe::where('approval_status', 'pending')->count(),
            'approved' => Recipe::where('approval_status', 'approved')->count(),
            'rejected' => Recipe::where('approval_status', 'rejected')->count(),
        ];

        return view('admin.recipe-approvals', compact('recipes', 'statusFilter', 'counts'));
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Recipe::with([
            'food',
            'recipeIngredients.ingredient',
            'recipeSteps' => fn ($q) => $q->orderBy('step_number'),
        ])->latest();

        if ($user && $user->isAdmin()) {
            $recipes = $query->get();
        } elseif ($user && $user->isHelper()) {
            $recipes = $query->where(function ($q) {
                $q->where('approval_status', 'approved')
                    ->orWhere('approval_status', 'pending');
            })->get();
        } else {
            $recipes = $query->approved()->get();
        }

        return view('recipes.index', compact('recipes'));
    }

    public function create()
    {
        $foods = Food::orderBy('name')->get();
        $ingredients = Ingredient::orderBy('name')->get();

        return view('recipes.create', compact('foods', 'ingredients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'food_id' => ['required', 'exists:foods,id'],
            'title' => ['required', 'string', 'max:255'],
            'prep_time_minutes' => ['required', 'integer', 'min:0'],
            'cook_time_minutes' => ['required', 'integer', 'min:0'],
            'servings' => ['required', 'integer', 'min:1'],
            'difficulty' => ['required', 'string', 'max:100'],

            'ingredients' => ['nullable', 'array'],
            'ingredients.*.ingredient_id' => ['nullable', 'exists:ingredients,id', 'required_without:ingredients.*.ingredient_name'],
            'ingredients.*.ingredient_name' => ['nullable', 'string', 'max:255', 'required_without:ingredients.*.ingredient_id'],
            'ingredients.*.amount' => ['nullable', 'numeric', 'min:0'],
            'ingredients.*.unit' => ['nullable', 'string', 'max:100'],
            'ingredients.*.notes' => ['nullable', 'string', 'max:255'],

            'steps' => ['nullable', 'array'],
            'steps.*.step_number' => ['nullable', 'integer', 'min:1'],
            'steps.*.instruction' => ['required_with:steps', 'string'],
            'steps.*.image' => ['nullable', 'image', 'max:2048'],
        ]);

        $user = $request->user();
        $approvalStatus = $user && $user->isAdmin() ? 'approved' : 'pending';

        DB::transaction(function () use ($request, $validated, $approvalStatus) {
            $recipe = Recipe::create([
                'user_id' => $request->user()?->id,
                'food_id' => $validated['food_id'],
                'title' => $validated['title'],
                'prep_time_minutes' => $validated['prep_time_minutes'],
                'cook_time_minutes' => $validated['cook_time_minutes'],
                'servings' => $validated['servings'],
                'difficulty' => $validated['difficulty'],
                'approval_status' => $approvalStatus,
            ]);

            $this->syncIngredients($recipe, $validated['ingredients'] ?? []);
            $this->syncSteps($request, $recipe, $validated['steps'] ?? []);
        });

        $message = $approvalStatus === 'approved'
            ? 'Resep berhasil ditambahkan dan disetujui.'
            : 'Resep berhasil dikirim dan menunggu persetujuan admin.';

        return redirect()->route('recipes.index')->with('success', $message);
    }

    public function edit(Request $request, Recipe $recipe)
    {
        $user = $request->user();
        $this->authorizeRecipeEdit($user, $recipe);

        $recipe->load([
            'food',
            'recipeIngredients.ingredient',
            'recipeSteps' => fn ($q) => $q->orderBy('step_number'),
        ]);

        $foods = Food::orderBy('name')->get();
        $ingredients = Ingredient::orderBy('name')->get();

        return view('recipes.edit', compact('recipe', 'foods', 'ingredients'));
    }

    public function update(Request $request, Recipe $recipe)
    {
        $user = $request->user();
        $this->authorizeRecipeEdit($user, $recipe);

        $rules = [
            'food_id' => ['required', 'exists:foods,id'],
            'title' => ['required', 'string', 'max:255'],
            'prep_time_minutes' => ['required', 'integer', 'min:0'],
            'cook_time_minutes' => ['required', 'integer', 'min:0'],
            'servings' => ['required', 'integer', 'min:1'],
            'difficulty' => ['required', 'string', 'max:100'],

            'ingredients' => ['nullable', 'array'],
            'ingredients.*.ingredient_id' => ['nullable', 'exists:ingredients,id', 'required_without:ingredients.*.ingredient_name'],
            'ingredients.*.ingredient_name' => ['nullable', 'string', 'max:255', 'required_without:ingredients.*.ingredient_id'],
            'ingredients.*.amount' => ['nullable', 'numeric', 'min:0'],
            'ingredients.*.unit' => ['nullable', 'string', 'max:100'],
            'ingredients.*.notes' => ['nullable', 'string', 'max:255'],

            'steps' => ['nullable', 'array'],
            'steps.*.id' => ['nullable', 'integer', 'exists:recipe_steps,id'],
            'steps.*.step_number' => ['nullable', 'integer', 'min:1'],
            'steps.*.instruction' => ['required_with:steps', 'string'],
            'steps.*.image' => ['nullable', 'image', 'max:2048'],
            'submission_action' => ['nullable', 'in:save_draft,submit,resubmit'],
        ];

        if ($user && $user->isAdmin()) {
            $rules['approval_status'] = ['nullable', 'in:draft,pending,approved,rejected'];
        }
        $rules['video_url'] = ['nullable', 'string', 'max:500'];
        $rules['food_thumbnail'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];

        $validated = $request->validate($rules);

        $recipeData = [
            'food_id' => $validated['food_id'],
            'title' => $validated['title'],
            'prep_time_minutes' => $validated['prep_time_minutes'],
            'cook_time_minutes' => $validated['cook_time_minutes'],
            'servings' => $validated['servings'],
            'difficulty' => $validated['difficulty'],
            'video_url' => array_key_exists('video_url', $validated) ? $validated['video_url'] : $recipe->video_url,
            'revision_count' => (int) ($recipe->revision_count ?? 0) + 1,
            'last_revised_at' => now(),
        ];

        $action = $validated['submission_action'] ?? null;

        if ($user && $user->isAdmin() && ! empty($validated['approval_status'])) {
            $recipeData['approval_status'] = $validated['approval_status'];
            if ($validated['approval_status'] !== 'rejected') {
                $recipeData['rejection_reason'] = null;
            }
        } elseif ($action === 'save_draft' && in_array($recipe->approval_status, ['draft', 'rejected'], true)) {
            $recipeData['approval_status'] = 'draft';
        } elseif (in_array($action, ['submit', 'resubmit'], true)) {
            $recipeData['approval_status'] = ($user && $user->isAdmin()) ? 'approved' : 'pending';
            $recipeData['rejection_reason'] = null;
        } elseif ($recipe->isApproved() && $user && ! $user->isAdmin()) {
            // Published recipes revised by non-admin require re-review
            $recipeData['approval_status'] = 'pending';
        }

        DB::transaction(function () use ($request, $recipe, $recipeData, $validated): void {
            $recipe->update($recipeData);

            if ($request->hasFile('food_thumbnail') && $recipe->food) {
                $path = $request->file('food_thumbnail')->store('foods', 'public');
                $recipe->food->update(['thumbnail' => '/storage/'.$path]);
            }

            if ($request->has('ingredients')) {
                $this->syncIngredients($recipe, $validated['ingredients'] ?? []);
            }

            if ($request->has('steps')) {
                $this->syncSteps($request, $recipe, $validated['steps'] ?? []);
            }
        });

        $recipe->refresh();
        if ($recipe->isApproved()) {
            $this->levelService->grantRecipeApprovalBonus($recipe);
        }

        if ($user && ($user->isAdmin() || $user->isHelper())) {
            return redirect()->route('admin.recipes.approvals')->with('success', 'Resep berhasil diperbarui.');
        }

        $msg = $recipe->isDraft()
            ? 'Draft resep berhasil disimpan.'
            : 'Revisi resep berhasil disimpan dan dikirim untuk peninjauan.';

        return redirect()->route('submissions.index')->with('success', $msg);
    }

    public function approve(Recipe $recipe)
    {
        $recipe->update([
            'approval_status' => 'approved',
            'rejection_reason' => null,
        ]);

        if ($recipe->food && ($recipe->food->approval_status ?? 'pending') !== 'approved') {
            $recipe->food->update(['approval_status' => 'approved']);
        }

        $bonusGranted = $this->levelService->grantRecipeApprovalBonus($recipe);

        $message = $bonusGranted
            ? 'Resep berhasil disetujui dan Contributor mendapatkan bonus +5 level.'
            : 'Resep berhasil disetujui.';

        return back()->with('success', $message);
    }

    public function reject(Request $request, Recipe $recipe)
    {
        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $recipe->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'] ?? 'Perlu perbaikan informasi, bahan, atau langkah memasak.',
        ]);

        return back()->with('success', 'Resep ditolak beserta alasan penolakan.');
    }

    public function destroy(Request $request, Recipe $recipe): RedirectResponse
    {
        $user = $request->user();
        if (! $user || (! $user->isAdmin() && (int) $recipe->user_id !== (int) $user->id)) {
            abort(403, 'Anda hanya dapat menghapus pengajuan resep milik Anda sendiri.');
        }

        $title = $recipe->title;

        DB::transaction(function () use ($recipe) {
            $recipe->recipeIngredients()->delete();
            $recipe->recipeSteps()->delete();
            $recipe->delete();
        });

        return back()->with('success', 'Pengajuan resep "'.$title.'" berhasil dihapus.');
    }

    private function authorizeRecipeEdit(?User $user, Recipe $recipe): void
    {
        if (! $user) {
            abort(403, 'Anda harus login untuk mengedit resep.');
        }

        if ($user->isAdmin() || $user->isHelper()) {
            return;
        }

        if ($recipe->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit resep milik pengguna lain.');
        }

        if (! $user->canSubmitRecipes()) {
            abort(403, 'Akun Anda belum memiliki izin untuk mengelola resep.');
        }
    }

    private function syncIngredients(Recipe $recipe, array $ingredientsInput): void
    {
        $recipe->recipeIngredients()->delete();

        foreach ($ingredientsInput as $item) {
            $ingredientId = $item['ingredient_id'] ?? null;
            $ingredientName = isset($item['ingredient_name']) ? trim($item['ingredient_name']) : null;

            if (! $ingredientId && ! $ingredientName) {
                continue;
            }

            if (! $ingredientId && $ingredientName) {
                $ingredient = Ingredient::firstOrCreate(['name' => $ingredientName]);
                $ingredientId = $ingredient->id;
            }

            $recipe->recipeIngredients()->create([
                'ingredient_id' => $ingredientId,
                'amount' => $item['amount'] ?? null,
                'unit' => $item['unit'] ?? null,
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    private function syncSteps(Request $request, Recipe $recipe, array $stepsInput): void
    {
        $existingSteps = $recipe->recipeSteps()->get()->keyBy('id');
        $keptStepIds = [];

        foreach (array_values($stepsInput) as $index => $stepData) {
            $instruction = isset($stepData['instruction']) ? trim($stepData['instruction']) : '';
            if ($instruction === '') {
                continue;
            }

            $stepNumber = ! empty($stepData['step_number'])
                ? (int) $stepData['step_number']
                : ($index + 1);

            $stepId = $stepData['id'] ?? null;
            $existingStep = ($stepId && $existingSteps->has($stepId))
                ? $existingSteps->get($stepId)
                : null;

            $imagePath = $existingStep?->image;

            if ($request->hasFile("steps.{$index}.image")) {
                if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
                $imagePath = $request->file("steps.{$index}.image")->store('recipe-steps', 'public');
            }

            if ($existingStep) {
                $existingStep->update([
                    'step_number' => $stepNumber,
                    'instruction' => $instruction,
                    'image' => $imagePath,
                ]);
                $keptStepIds[] = $existingStep->id;
            } else {
                $createdStep = RecipeStep::create([
                    'recipe_id' => $recipe->id,
                    'step_number' => $stepNumber,
                    'instruction' => $instruction,
                    'image' => $imagePath,
                ]);
                $keptStepIds[] = $createdStep->id;
            }
        }

        foreach ($existingSteps as $id => $step) {
            if (! in_array($id, $keptStepIds, true)) {
                if ($step->image && Storage::disk('public')->exists($step->image)) {
                    Storage::disk('public')->delete($step->image);
                }
                $step->delete();
            }
        }
    }
}
