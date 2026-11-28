<?php

namespace App\Http\Controllers;

use App\Models\CulinaryStory;
use App\Models\Food;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            if ($path === false) {
                return back()->withInput()->withErrors(['thumbnail' => 'Gagal menyimpan foto utama. Silakan coba lagi.']);
            }
            $thumbnailPath = '/storage/'.$path;
        }

        $galleryPaths = [];
        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $imageFile) {
                if ($imageFile) {
                    $path = $imageFile->store('stories/gallery', 'public');
                    if ($path === false) {
                        return back()->withInput()->withErrors(['gallery_images' => 'Salah satu foto galeri gagal disimpan. Silakan coba lagi.']);
                    }
                    $galleryPaths[] = '/storage/'.$path;
                }
            }
        }

        $approvalStatus = $user->isAdmin() ? 'approved' : 'pending';

        $story = CulinaryStory::create([
            'food_id' => $validated['food_id'] ?? null,
            'user_id' => $user->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'content' => $validated['content'],
            'thumbnail' => $thumbnailPath,
            'gallery_images' => $galleryPaths !== [] ? $galleryPaths : null,
            'video_url' => $validated['video_url'] ?? null,
            'approval_status' => $approvalStatus,
        ]);

        if ($approvalStatus === 'approved') {
            return redirect()
                ->route('stories.show', $story->slug)
                ->with('success', 'Cerita kuliner "'.$story->title.'" berhasil dipublikasikan.');
        }

        return redirect()
            ->route('submissions.index')
            ->with('success', 'Cerita kuliner "'.$story->title.'" berhasil dikirim dengan status Pending dan sedang menunggu persetujuan Admin.');
    }

    public function adminIndex(Request $request): View
    {
        $statusFilter = trim((string) $request->query('status', 'all'));

        $query = CulinaryStory::with(['food', 'user'])->latest();
        if (in_array($statusFilter, ['pending', 'approved', 'rejected'], true)) {
            $query->where('approval_status', $statusFilter);
        }

        $stories = $query->paginate(10)->withQueryString();
        $counts = [
            'all' => CulinaryStory::count(),
            'pending' => CulinaryStory::where('approval_status', 'pending')->count(),
            'approved' => CulinaryStory::where('approval_status', 'approved')->count(),
            'rejected' => CulinaryStory::where('approval_status', 'rejected')->count(),
        ];

        return view('admin.stories', compact('stories', 'statusFilter', 'counts'));
    }

    public function approve(CulinaryStory $story): RedirectResponse
    {
        $story->update([
            'approval_status' => 'approved',
            'rejection_reason' => null,
        ]);

        return back()->with('success', 'Cerita kuliner "'.$story->title.'" berhasil disetujui dan tampil di halaman publik.');
    }

    public function reject(Request $request, CulinaryStory $story): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $story->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'] ?: 'Cerita perlu perbaikan konten, referensi, atau foto pendukung.',
        ]);

        return back()->with('success', 'Cerita kuliner "'.$story->title.'" ditolak beserta alasan penolakan.');
    }

    public function destroy(Request $request, CulinaryStory $story): RedirectResponse
    {
        $user = $request->user();
        if (! $user || (! $user->isAdmin() && (int) $story->user_id !== (int) $user->id)) {
            abort(403, 'Anda hanya dapat menghapus cerita kuliner milik Anda sendiri.');
        }

        $title = $story->title;

        $pathsToDelete = [];
        if (is_string($story->thumbnail) && str_starts_with($story->thumbnail, '/storage/stories/')) {
            $pathsToDelete[] = substr($story->thumbnail, strlen('/storage/'));
        }
        if (is_array($story->gallery_images)) {
            foreach ($story->gallery_images as $img) {
                if (is_string($img) && str_starts_with($img, '/storage/stories/')) {
                    $pathsToDelete[] = substr($img, strlen('/storage/'));
                }
            }
        }
        if ($pathsToDelete !== []) {
            Storage::disk('public')->delete($pathsToDelete);
        }

        $story->delete();

        return back()->with('success', 'Cerita kuliner "'.$title.'" berhasil dihapus.');
    }

    public function show(string $slug): View
    {
        $story = CulinaryStory::with(['food.category', 'user'])
            ->where('slug', $slug)
            ->first();

        if ($story) {
            $user = auth()->user();
            if (($story->approval_status ?? 'approved') !== 'approved' && (! $user || (! $user->isAdmin() && ! $user->isHelper() && $user->id !== $story->user_id))) {
                abort(404);
            }

            $prevStory = CulinaryStory::where('approval_status', 'approved')
                ->where('id', '<', $story->id)
                ->orderByDesc('id')
                ->first();

            $nextStory = CulinaryStory::where('approval_status', 'approved')
                ->where('id', '>', $story->id)
                ->orderBy('id')
                ->first();

            $relatedStories = CulinaryStory::with(['food', 'user'])
                ->where('id', '!=', $story->id)
                ->where('approval_status', 'approved')
                ->latest()
                ->take(3)
                ->get();

            return view('stories-show', [
                'story' => $story,
                'curatedStory' => null,
                'prevStory' => $prevStory ? ['slug' => $prevStory->slug, 'title' => $prevStory->title] : null,
                'nextStory' => $nextStory ? ['slug' => $nextStory->slug, 'title' => $nextStory->title] : null,
                'relatedStories' => $relatedStories,
                'curatedRelated' => [],
            ]);
        }

        $curatedList = self::curatedEditorialStories();
        $index = collect($curatedList)->search(fn (array $item): bool => $item['slug'] === $slug);

        if ($index === false) {
            abort(404);
        }

        $curatedStory = $curatedList[$index];
        $prevStory = $index > 0 ? ['slug' => $curatedList[$index - 1]['slug'], 'title' => $curatedList[$index - 1]['title']] : null;
        $nextStory = isset($curatedList[$index + 1]) ? ['slug' => $curatedList[$index + 1]['slug'], 'title' => $curatedList[$index + 1]['title']] : null;
        $curatedRelated = array_values(array_filter($curatedList, fn (array $item): bool => $item['slug'] !== $slug));

        return view('stories-show', [
            'story' => null,
            'curatedStory' => $curatedStory,
            'prevStory' => $prevStory,
            'nextStory' => $nextStory,
            'relatedStories' => collect(),
            'curatedRelated' => array_slice($curatedRelated, 0, 3),
        ]);
    }

    /**
     * Curated editorial stories with unique slugs so every story card is clickable even on a fresh database.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function curatedEditorialStories(): array
    {
        return [
            [
                'slug' => 'filosofi-base-genep-dapur-bali',
                'title' => 'Filosofi Base Genep dalam Dapur Tradisional Bali',
                'category' => 'Bumbu & Tradisi',
                'region' => 'Denpasar',
                'author' => 'Redaksi Bali Culinary',
                'read_time' => '4 menit baca',
                'thumbnail' => 'Assets/images/banner-beranda.png',
                'quote' => 'Base genep bukan sekadar racikan rempah, melainkan keseimbangan rasa yang menyatukan dapur adat dan meja makan keluarga Bali.',
                'excerpt' => 'Base genep merupakan fondasi bumbu utama dalam banyak masakan tradisional Bali yang memadukan rimpang, bawang, cabai, terasi, dan rempah aromatik.',
                'paragraphs' => [
                    'Dalam tradisi kuliner Bali, base genep (sering juga disebut bumbu lengkap) menjadi fondasi utama berbagai hidangan seperti betutu, lawar, dan sate lilit. Istilah "genep" dalam bahasa Bali berarti lengkap atau utuh.',
                    'Racikan ini memadukan unsur rimpang seperti kunyit, lengkuas, kencur, dan jahe bersama bawang merah, bawang putih, cabai, kemiri, ketumbar, serta terasi. Teknik perajangan tradisional menggunakan pisau besar di atas talenan kayu menghasilkan tekstur bumbu yang khas saat dimasak perlahan.',
                    'Selain memperkaya aroma dan rasa, proses meracik base genep secara bersama-sama menjelang upacara adat mencerminkan semangat ngayah dan kebersamaan masyarakat Bali.',
                ],
            ],
            [
                'slug' => 'tradisi-megibung-karangasem',
                'title' => 'Tradisi Megibung di Karangasem: Makan Bersama Tanpa Sekat',
                'category' => 'Budaya Makan',
                'region' => 'Karangasem',
                'author' => 'Redaksi Bali Culinary',
                'read_time' => '5 menit baca',
                'thumbnail' => 'Assets/images/banner-home.png',
                'quote' => 'Duduk melingkar dalam satu dulang mengajarkan bahwa kelezatan sejati hadir ketika makanan dinikmati bersama dalam kesetaraan.',
                'excerpt' => 'Megibung adalah tradisi makan bersama khas masyarakat Karangasem, Bali Timur, di mana beberapa orang duduk melingkar menikmati nasi dan aneka lauk dalam satu wadah.',
                'paragraphs' => [
                    'Megibung merupakan tradisi makan bersama yang dikenal luas di wilayah Karangasem, Bali Timur. Peserta duduk melingkar mengelilingi satu dulang atau nampan berisi nasi putih dan berbagai lauk khas Bali seperti lawar, sate lilit, sayur urab, dan olahan daging.',
                    'Tradisi ini mengutamakan tata krama, kebersamaan, dan rasa saling menghormati. Setiap orang mengambil makanan di bagian yang paling dekat dengannya, lalu menikmati hidangan secara serempak hingga selesai.',
                    'Hingga kini, megibung tetap lestari dalam berbagai perayaan adat, upacara keluarga, maupun acara komunitas sebagai simbol persaudaraan masyarakat Bali.',
                ],
            ],
            [
                'slug' => 'mengenal-ayam-betutu-khas-bali',
                'title' => 'Mengenal Ayam Betutu: Rempah Kaya dan Masak Perlahan',
                'category' => 'Hidangan Ikonik',
                'region' => 'Jembrana & Gianyar',
                'author' => 'Redaksi Bali Culinary',
                'read_time' => '4 menit baca',
                'thumbnail' => 'Assets/images/banner-resep.png',
                'quote' => 'Kesabaran dalam memasak perlahan adalah kunci utama yang membuat bumbu base genep meresap hingga ke tulang.',
                'excerpt' => 'Ayam betutu dikenal dengan baluran bumbu base genep melimpah yang dimasak perlahan hingga daging empuk dan aromanya kuat.',
                'paragraphs' => [
                    'Ayam betutu merupakan salah satu sajian paling dikenal dari Pulau Dewata. Hidangan ini menggunakan ayam utuh atau potongan besar yang dibaluri bumbu base genep di bagian luar maupun rongga dalamnya.',
                    'Secara tradisional, betutu dibungkus daun pisang lalu dimasak dalam waktu lama menggunakan panas bara sekam atau dikukus dan dipanggang perlahan. Metode masak lambat ini membuat daging sangat empuk dan bumbu meresap sempurna.',
                    'Di berbagai daerah Bali seperti Gilimanuk (Jembrana) dan Gianyar, terdapat variasi karakter rasa—mulai dari versi berkuah pedas hangat hingga versi kering yang harum asap rempah.',
                ],
            ],
            [
                'slug' => 'jaje-laklak-jajanan-pasar-bali',
                'title' => 'Jaje Laklak: Hangatnya Jajanan Pasar Pagi di Bali',
                'category' => 'Jajanan Tradisional',
                'region' => 'Klungkung & Tabanan',
                'author' => 'Redaksi Bali Culinary',
                'read_time' => '3 menit baca',
                'thumbnail' => 'Assets/images/banner-pengisian.png',
                'quote' => 'Aroma daun suji dan gula merah cair di atas cetakan tanah liat menghadirkan nostalgia pasar pagi Bali.',
                'excerpt' => 'Jaje laklak adalah kue tradisional berbahan tepung beras yang dipanggang di cetakan tanah liat, disajikan dengan kelapa parut dan saus gula merah.',
                'paragraphs' => [
                    'Di pasar tradisional Bali pada pagi hari, jaje laklak hampir selalu menjadi incaran. Kue kecil berwarna hijau alami dari daun suji atau pandan ini terbuat dari adonan tepung beras dan santan.',
                    'Adonan dituang ke dalam cetakan tanah liat yang dipanaskan di atas tungku, menghasilkan bagian luar yang sedikit renyah dengan bagian dalam yang lembut berpori.',
                    'Disajikan hangat bersama taburan kelapa parut gurih dan siraman juruh (gula merah cair), jaje laklak menjadi pasangan klasik untuk secangkir kopi Bali.',
                ],
            ],
        ];
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
