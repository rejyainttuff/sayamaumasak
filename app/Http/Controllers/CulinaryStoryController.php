<?php

namespace App\Http\Controllers;

use App\Models\CulinaryStory;
use App\Models\Food;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CulinaryStoryController extends Controller
{
    public function create(): View
    {
        $foods = Food::orderBy('name')->get();

        return view('stories-create', compact('foods'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'food_id' => ['nullable', 'exists:foods,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $slug = $this->generateUniqueSlug($validated['title']);
        $thumbnailPath = null;

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('stories', 'public');
            $thumbnailPath = '/storage/' . $path;
        }

        $user = $request->user();
        $approvalStatus = $user?->isAdmin() ? 'approved' : 'pending';

        CulinaryStory::create([
            'food_id' => $validated['food_id'] ?? null,
            'user_id' => $user?->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'content' => $validated['content'],
            'thumbnail' => $thumbnailPath,
            'approval_status' => $approvalStatus,
        ]);

        $message = $approvalStatus === 'approved'
            ? 'Cerita kuliner berhasil ditambahkan dan disetujui.'
            : 'Cerita kuliner berhasil dikirim dan menunggu persetujuan admin.';

        return redirect()
            ->route('stories')
            ->with('success', $message);
    }

    private function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug !== '' ? $baseSlug : 'cerita-kuliner';
        $counter = 1;

        while (CulinaryStory::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}