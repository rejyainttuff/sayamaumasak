<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Makanan: {{ $food->name }} | Admin Bali Culinary</title>
    <link rel="icon" type="image/png" href="{{ asset('Assets/images/baliculinarylogo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('Assets/css/recipes.css') }}?v={{ filemtime(public_path('Assets/css/recipes.css')) }}">
</head>

@php
    $currentThumbUrl = null;
    if (! empty($food->thumbnail)) {
        $cleanThumb = ltrim((string) $food->thumbnail, '/');
        if ($cleanThumb !== '' && is_file(public_path($cleanThumb))) {
            $currentThumbUrl = asset($cleanThumb);
        } elseif (str_starts_with($cleanThumb, 'storage/') && \Illuminate\Support\Facades\Storage::disk('public')->exists(substr($cleanThumb, strlen('storage/')))) {
            $currentThumbUrl = asset($cleanThumb);
        }
    }
@endphp

<body class="recipe-page">
    <div class="admin-shell">
        @include('admin.partials.sidebar')

        <main class="admin-workspace">
            <section class="admin-hero">
                <div class="admin-hero-content food-edit-hero-bar">
                    <div>
                        <h1>Edit Makanan: {{ $food->name }}</h1>
                        <p>Perbarui informasi katalog, kategori, asal daerah, deskripsi singkat, dan foto makanan.</p>
                    </div>
                    <a href="{{ route('foods.index') }}" class="pill-tag food-edit-back-link">&larr; Kembali ke Food Management</a>
                </div>
            </section>

            <div class="admin-body">
                @if ($errors->any())
                    <div class="alert-banner error">
                        <strong>Mohon periksa kembali formulir:</strong>
                        <ul style="margin: 6px 0 0; padding-left: 18px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="admin-card white food-edit-card">
                    <div class="food-edit-card-head">
                        <div>
                            <h2>Detail Katalog Makanan</h2>
                            <p>Perubahan yang disimpan akan langsung diperbarui pada halaman Jelajahi Resep dan Detail Makanan.</p>
                        </div>
                        <span class="status-pill {{ $food->approval_status ?? 'approved' }}">
                            {{ ucfirst($food->approval_status ?? 'approved') }}
                        </span>
                    </div>

                    <form action="{{ route('foods.update', $food) }}" method="POST" enctype="multipart/form-data" data-loading-form>
                        @csrf
                        @method('PUT')

                        <div class="food-edit-layout">
                            {{-- Left Column: Primary Text & Classification Fields --}}
                            <div class="food-edit-main-col">
                                <div class="form-group">
                                    <label for="name">Nama Makanan</label>
                                    <input
                                        type="text"
                                        name="name"
                                        id="name"
                                        class="form-control"
                                        value="{{ old('name', $food->name) }}"
                                        placeholder="Contoh: Ayam Betutu Gilimanuk"
                                        required
                                    >
                                    <span class="field-helper-text">Gunakan nama kuliner yang jelas dan mudah dikenali pengunjung.</span>
                                </div>

                                <div class="form-grid-2 food-edit-meta-row">
                                    <div class="form-group">
                                        <label for="category_id">Kategori Kuliner</label>
                                        <select name="category_id" id="category_id" class="form-control" required>
                                            <option value="">Pilih Kategori</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}" @selected(old('category_id', $food->category_id) == $category->id)>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="origin_area">Asal Daerah</label>
                                        <input
                                            type="text"
                                            name="origin_area"
                                            id="origin_area"
                                            class="form-control"
                                            value="{{ old('origin_area', $food->origin_area) }}"
                                            placeholder="Contoh: Gianyar, Karangasem, Buleleng"
                                            required
                                        >
                                    </div>
                                </div>

                                <div class="form-group food-edit-desc-group">
                                    <label for="short_description">Deskripsi Singkat</label>
                                    <textarea
                                        name="short_description"
                                        id="short_description"
                                        rows="5"
                                        class="form-control"
                                        placeholder="Jelaskan bahan utama, karakter rasa, dan keunikan hidangan ini..."
                                        required
                                    >{{ old('short_description', $food->short_description) }}</textarea>
                                    <span class="field-helper-text">Ditampilkan sebagai ringkasan pada kartu katalog resep dan pengantar halaman detail.</span>
                                </div>
                            </div>

                            {{-- Right Column: Dedicated Thumbnail Preview & Upload Area --}}
                            <aside class="food-edit-media-col" aria-label="Pengaturan foto makanan">
                                <div class="food-edit-upload-panel">
                                    <label for="thumbnail" class="food-edit-upload-title">Foto Makanan</label>

                                    <div class="food-edit-preview-frame" id="food-thumb-preview-frame">
                                        @if ($currentThumbUrl)
                                            <img
                                                src="{{ $currentThumbUrl }}"
                                                alt="{{ $food->name }}"
                                                id="food-thumb-preview-img"
                                                width="420"
                                                height="280"
                                            >
                                        @else
                                            <div class="food-edit-preview-empty" id="food-thumb-preview-empty">
                                                <img
                                                    src="{{ asset('Assets/images/image-for-reciepe.png') }}"
                                                    alt=""
                                                    width="96"
                                                    height="76"
                                                >
                                                <span>Belum ada foto khusus untuk makanan ini</span>
                                            </div>
                                            <img
                                                src=""
                                                alt="Pratinjau foto baru"
                                                id="food-thumb-preview-img"
                                                width="420"
                                                height="280"
                                                style="display: none;"
                                            >
                                        @endif
                                    </div>

                                    <div class="food-edit-file-control">
                                        <input
                                            type="file"
                                            name="thumbnail"
                                            id="thumbnail"
                                            class="form-control food-file-input"
                                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                        >
                                        <span class="field-helper-text">
                                            Opsional. Unggah gambar baru (JPG, PNG, atau WEBP, maks. 2 MB) bila ingin mengganti foto saat ini.
                                        </span>
                                    </div>
                                </div>
                            </aside>
                        </div>

                        <div class="food-edit-actions">
                            <button type="submit" class="btn-terracotta food-edit-submit-btn">Perbarui Makanan</button>
                            <a href="{{ route('foods.index') }}" class="btn-white food-edit-cancel-btn">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        (function () {
            const fileInput = document.getElementById('thumbnail');
            const previewImg = document.getElementById('food-thumb-preview-img');
            const emptyState = document.getElementById('food-thumb-preview-empty');

            if (!fileInput || !previewImg) {
                return;
            }

            fileInput.addEventListener('change', function () {
                const file = fileInput.files && fileInput.files[0];
                if (!file || !file.type.startsWith('image/')) {
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                    if (emptyState) {
                        emptyState.style.display = 'none';
                    }
                };
                reader.readAsDataURL(file);
            });
        })();
    </script>
    <script src="{{ asset('Assets/js/animations.js') }}" defer></script>
</body>

</html>
