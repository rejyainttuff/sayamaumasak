<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Makanan — Admin Bali Culinary</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('Assets/css/recipes.css') }}?v={{ filemtime(public_path('Assets/css/recipes.css')) }}">
</head>

<body class="recipe-page">
    <div class="admin-shell">
        @include('admin.partials.sidebar')

        <div class="admin-workspace">
            <section class="admin-hero">
                <div class="admin-hero-content" style="display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                    <div>
                        <h1>Tambah Makanan Baru</h1>
                        <p>Tambahkan entri hidangan khas Bali baru ke dalam katalog Bali Culinary Guide.</p>
                    </div>
                    <a href="{{ route('foods.index') }}" class="pill-tag">&larr; Kembali ke Food Management</a>
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

                <div class="admin-card white" style="max-width: 780px;">
                    <form action="{{ route('foods.store') }}" method="POST" enctype="multipart/form-data" data-loading-form>
                        @csrf

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="name">Nama Makanan</label>
                                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" placeholder="Contoh: Ayam Betutu Gilimanuk" required>
                            </div>

                            <div class="form-group">
                                <label for="category_id">Kategori</label>
                                <select name="category_id" id="category_id" class="form-control" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="origin_area">Asal Daerah</label>
                                <input type="text" name="origin_area" id="origin_area" class="form-control" value="{{ old('origin_area') }}" placeholder="Contoh: Gianyar, Jembrana, Klungkung" required>
                            </div>

                            <div class="form-group">
                                <label for="thumbnail">Foto Makanan (JPG, PNG, WEBP maks. 2MB)</label>
                                <input type="file" name="thumbnail" id="thumbnail" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="short_description">Deskripsi Singkat</label>
                            <textarea name="short_description" id="short_description" rows="4" class="form-control" placeholder="Deskripsikan cita rasa, bahan utama, dan keunikan hidangan ini..." required>{{ old('short_description') }}</textarea>
                        </div>

                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button type="submit" class="btn-terracotta rect">Simpan Makanan</button>
                            <a href="{{ route('foods.index') }}" class="btn-white">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('Assets/js/animations.js') }}" defer></script>
</body>

</html>
