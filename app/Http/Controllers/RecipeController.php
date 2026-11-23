<?php

namespace App\Http\Controllers;

use App\Models\Food;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RecipeController extends Controller
{
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

    public function edit(Recipe $recipe)
    {
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
        ];

        $user = $request->user();

        if ($user && $user->isAdmin()) {
            $rules['approval_status'] = ['nullable', 'in:pending,approved,rejected'];
        }

        $validated = $request->validate($rules);

        $recipeData = [
            'food_id' => $validated['food_id'],
            'title' => $validated['title'],
            'prep_time_minutes' => $validated['prep_time_minutes'],
            'cook_time_minutes' => $validated['cook_time_minutes'],
            'servings' => $validated['servings'],
            'difficulty' => $validated['difficulty'],
        ];

        if ($user && $user->isHelper()) {
            $recipeData['approval_status'] = 'pending';
        } elseif ($user && $user->isAdmin() && !empty($validated['approval_status'])) {
            $recipeData['approval_status'] = $validated['approval_status'];
        }

        DB::transaction(function () use ($request, $recipe, $recipeData, $validated) {
            $recipe->update($recipeData);

            $this->syncIngredients($recipe, $validated['ingredients'] ?? []);
            $this->syncSteps($request, $recipe, $validated['steps'] ?? []);
        });

        return redirect()->route('recipes.index')->with('success', 'Resep berhasil diperbarui.');
    }

    public function approve(Recipe $recipe)
    {
        $recipe->update(['approval_status' => 'approved']);

        return back()->with('success', 'Resep berhasil disetujui.');
    }

    public function reject(Recipe $recipe)
    {
        $recipe->update(['approval_status' => 'rejected']);

        return back()->with('success', 'Resep ditolak.');
    }

    private function syncIngredients(Recipe $recipe, array $ingredientsInput): void
    {
        $recipe->recipeIngredients()->delete();

        foreach ($ingredientsInput as $item) {
            $ingredientId = $item['ingredient_id'] ?? null;
            $ingredientName = isset($item['ingredient_name']) ? trim($item['ingredient_name']) : null;

            if (!$ingredientId && !$ingredientName) {
                continue;
            }

            if (!$ingredientId && $ingredientName) {
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

            $stepNumber = !empty($stepData['step_number'])
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
            if (!in_array($id, $keptStepIds, true)) {
                if ($step->image && Storage::disk('public')->exists($step->image)) {
                    Storage::disk('public')->delete($step->image);
                }
                $step->delete();
            }
        }
    }
}