<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Food;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FoodController extends Controller
{
    public function index(): View
    {
        $foods = Food::with('category')->latest()->paginate(10);

        return view('foods.index', compact('foods'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('foods.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'origin_area' => ['required', 'string', 'max:255'],
            'short_description' => ['required', 'string'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug($validated['name']);

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('foods', 'public');
            $validated['thumbnail'] = '/storage/' . $path;
        }

        Food::create($validated);

        return redirect()
            ->route('foods.index')
            ->with('success', 'Data makanan berhasil ditambahkan.');
    }

    public function edit(Food $food): View
    {
        $categories = Category::orderBy('name')->get();

        return view('foods.edit', compact('food', 'categories'));
    }

    public function update(Request $request, Food $food): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'origin_area' => ['required', 'string', 'max:255'],
            'short_description' => ['required', 'string'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($food->name !== $validated['name']) {
            $validated['slug'] = $this->generateUniqueSlug($validated['name'], $food->id);
        }

        if ($request->hasFile('thumbnail')) {
            $this->deleteLocalThumbnail($food->thumbnail);
            $path = $request->file('thumbnail')->store('foods', 'public');
            $validated['thumbnail'] = '/storage/' . $path;
        }

        $food->update($validated);

        return redirect()
            ->route('foods.index')
            ->with('success', 'Data makanan berhasil diperbarui.');
    }

    public function destroy(Food $food): RedirectResponse
    {
        $this->deleteLocalThumbnail($food->thumbnail);
        $food->delete();

        return redirect()
            ->route('foods.index')
            ->with('success', 'Data makanan berhasil dihapus.');
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug !== '' ? $baseSlug : 'kuliner';
        $counter = 1;

        while (
            Food::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function deleteLocalThumbnail(?string $thumbnail): void
    {
        if (!$thumbnail || !str_starts_with($thumbnail, '/storage/')) {
            return;
        }

        $relativePath = Str::after($thumbnail, '/storage/');
        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }
}