<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user && $user->isAdmin()) {
            $recipes = Recipe::with('food')->latest()->get();
        } elseif ($user && $user->isHelper()) {
            $recipes = Recipe::with('food')
                ->where(function ($query) {
                    $query->where('approval_status', 'approved')
                        ->orWhere('approval_status', 'pending');
                })
                ->latest()
                ->get();
        } else {
            $recipes = Recipe::with('food')->approved()->latest()->get();
        }

        return view('recipes.index', compact('recipes'));
    }

    public function create()
    {
        $foods = Food::orderBy('name')->get();

        return view('recipes.create', compact('foods'));
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
        ]);

        $user = $request->user();
        $validated['approval_status'] = $user && $user->isAdmin() ? 'approved' : 'pending';

        Recipe::create($validated);

        $message = $validated['approval_status'] === 'approved'
            ? 'Resep berhasil ditambahkan dan disetujui.'
            : 'Resep berhasil dikirim dan menunggu persetujuan admin.';

        return redirect()->route('recipes.index')->with('success', $message);
    }

    public function edit(Recipe $recipe)
    {
        $foods = Food::orderBy('name')->get();

        return view('recipes.edit', compact('recipe', 'foods'));
    }

    public function update(Request $request, Recipe $recipe)
    {
        $validated = $request->validate([
            'food_id' => ['required', 'exists:foods,id'],
            'title' => ['required', 'string', 'max:255'],
            'prep_time_minutes' => ['required', 'integer', 'min:0'],
            'cook_time_minutes' => ['required', 'integer', 'min:0'],
            'servings' => ['required', 'integer', 'min:1'],
            'difficulty' => ['required', 'string', 'max:100'],
        ]);

        $user = $request->user();

        if ($user && $user->isHelper()) {
            $validated['approval_status'] = 'pending';
        } elseif ($user && $user->isAdmin() && $request->filled('approval_status')) {
            $request->validate([
                'approval_status' => ['in:pending,approved,rejected'],
            ]);
            $validated['approval_status'] = $request->input('approval_status');
        }

        $recipe->update($validated);

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
}
