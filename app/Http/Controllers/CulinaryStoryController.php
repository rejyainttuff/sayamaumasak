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
    public function create(Request $request): View
    {
        $user = $request->user();
        if (! $user || ! $user->canSubmitCulinaryStory()) {
            abort(403, 'Hanya Contributor Level 3 ke atas atau Admin yang dapat mengirim Cerita Kuliner lengkap.');
        }

        $foods = Food::orderBy('name')->get();

        return view('stories-create', compact('foods'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! $user->canSubmitCulinaryStory()) {
            abort(403, 'Hanya Contributor Level 3 ke atas atau Admin yang dapat mengirim Cerita Kuliner lengkap.');
        }

        $validated = $request->validate([
            'food_id' => ['nullable', 'exists:foods,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:50'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'gallery_images' => ['nullable', 'array', 'max:6'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $slug = $this->generateUniqueSlug($validated['title']);
        $thumbnailPath = null;

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('stories', 'public');
            $thumbnailPath = '/storage/'.$path;
        }

        $galleryPaths = [];
        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $imageFile) {
                if ($imageFile) {
                    $path = $imageFile->store('stories/gallery', 'public');
                    $galleryPaths[] = '/storage/'.$path;
                }
            }
        }

        $approvalStatus = $user->isAdmin() ? 'approved' : 'pending';

        CulinaryStory::create([
            'food_id' => $validated['food_id'] ?: null,
            'user_id' => $user->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'content' => $validated['content'],
            'thumbnail' => $thumbnailPath,
            'gallery_images' => $galleryPaths !== [] ? $galleryPaths : null,
            'video_url' => $validated['video_url'] ?? null,
            'approval_status' => $approvalStatus,
        ]);

        $message = $approvalStatus === 'approved'
            ? 'Cerita kuliner berhasil ditambahkan dan disetujui.'
            : 'Cerita kuliner berhasil dikirim dan menunggu persetujuan admin.';

        return redirect()
            ->route('stories')
            ->with('success', $message);
    }

    public function show(string $slug): View
    {
        $story = CulinaryStory::with(['food', 'user'])
            ->where('slug', $slug)
            ->firstOrFail();

        $user = auth()->user();
        if (($story->approval_status ?? 'approved') !== 'approved' && (! $user || (! $user->isAdmin() && $user->id !== $story->user_id))) {
            abort(404);
        }

        $relatedStories = CulinaryStory::with(['food', 'user'])
            ->where('id', '!=', $story->id)
            ->where('approval_status', 'approved')
            ->latest()
            ->take(3)
            ->get();

        return view('stories-show', compact('story', 'relatedStories'));
    }

    private function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug !== '' ? $baseSlug : 'cerita-kuliner';
        $counter = 1;

        while (CulinaryStory::where('slug', $slug)->exists()) {
            $slug = ($baseSlug !== '' ? $baseSlug : 'cerita-kuliner').'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
