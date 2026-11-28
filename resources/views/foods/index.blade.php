<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Management — Admin Bali Culinary</title>
    <link rel="icon" type="image/png" href="{{ asset('Assets/images/baliculinarylogo.png') }}">
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
                <div class="admin-hero-content">
                    <h1>Food Management</h1>
                    <p>Kelola katalog makanan tradisional Bali, kategori, asal daerah, foto, dan status publikasinya.</p>
                </div>
            </section>

            <div class="admin-body">
                @if (session('success'))
                    <div class="alert-banner success" style="margin-bottom: 0;">{{ session('success') }}</div>
                @endif

                <div class="admin-stats-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
                    <div class="admin-stat-card">
                        <span>Total Makanan (Halaman Ini / Total)</span>
                        <strong>{{ $foods->total() }}</strong>
                    </div>
                    <div class="admin-stat-card">
                        <span>Total Kategori</span>
                        <strong>{{ $categories->count() }}</strong>
                    </div>
                    <div class="admin-stat-card">
                        <span>Menampilkan</span>
                        <strong>{{ $foods->count() }} item</strong>
                    </div>
                </div>

                @php
                    $currentSearch = $search ?? request('search', '');
                    $currentCategory = (string) ($categoryFilter ?? request('category', ''));
                @endphp

                <div class="admin-card white">
                    <div class="admin-card-head" style="flex-wrap: wrap;">
                        <h2>Daftar Makanan Bali</h2>
                        <form action="{{ route('foods.index') }}" method="GET" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                            <input
                                type="text"
                                name="search"
                                value="{{ $currentSearch }}"
                                placeholder="Cari makanan atau daerah..."
                                class="form-control"
                                style="width: 220px; height: 38px;"
                            >
                            <select name="category" class="form-control" style="width: 170px; height: 38px;" onchange="this.form.submit()">
                                <option value="">Semua Kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected($currentCategory === (string) $category->id || $currentCategory === (string) $category->slug)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn-terracotta sm">Cari</button>
                            @if ($currentSearch !== '' || $currentCategory !== '')
                                <a href="{{ route('foods.index') }}" class="pill-tag">Reset</a>
                            @endif
                        </form>
                    </div>

                    @if ($foods->isEmpty())
                        <div class="recipe-empty" style="margin: 12px 0;">
                            <h3 style="font-family: 'Playfair Display', serif; color: var(--terracotta); margin: 0 0 6px;">Belum ada makanan ditemukan</h3>
                            <p style="font-size: 13px; color: var(--muted); margin: 0 0 14px;">
                                {{ ($currentSearch !== '' || $currentCategory !== '') ? 'Coba ubah kata kunci pencarian atau filter kategori.' : 'Mulai tambahkan data kuliner Bali pertama melalui tombol di bawah.' }}
                            </p>
                            <a href="{{ route('foods.create') }}" class="btn-terracotta sm">+ Tambah Makanan</a>
                        </div>
                    @else
                        <div class="admin-table-wrap">
                            <table class="admin-editorial-table">
                                <thead>
                                    <tr>
                                        <th>Makanan</th>
                                        <th>Kategori</th>
                                        <th>Asal Daerah</th>
                                        <th>Deskripsi Singkat</th>
                                        <th>Status</th>
                                        <th style="text-align: right;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($foods as $food)
                                        @php $foodStatus = $food->approval_status ?? 'approved'; @endphp
                                        <tr>
                                            <td data-label="Makanan">
                                                <div class="table-food-cell">
                                                    <div class="table-food-thumb">
                                                        @if ($food->thumbnail)
                                                            <img src="{{ asset(ltrim($food->thumbnail, '/')) }}" alt="{{ $food->name }}">
                                                        @else
                                                            <span>{{ Str::limit($food->name, 8, '') }}</span>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <strong style="color: var(--text); font-size: 14px;">{{ $food->name }}</strong>
                                                        <div style="font-size: 11.5px; color: var(--muted);">/{{ $food->slug }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td data-label="Kategori">
                                                <span class="pill-tag">{{ $food->category?->name ?? '-' }}</span>
                                            </td>
                                            <td data-label="Asal Daerah">{{ $food->origin_area ?: 'Bali' }}</td>
                                            <td data-label="Deskripsi" style="max-width: 280px; font-size: 12.5px; color: var(--muted);">
                                                {{ Str::limit((string) $food->short_description, 95) }}
                                            </td>
                                            <td data-label="Status">
                                                <span class="status-pill {{ $foodStatus }}">{{ ucfirst($foodStatus) }}</span>
                                            </td>
                                            <td data-label="Aksi">
                                                <div style="display: flex; gap: 6px; justify-content: flex-end; flex-wrap: wrap;">
                                                    <a href="{{ route('food.detail', $food->slug) }}" class="action-btn review">Detail</a>
                                                    <a href="{{ route('foods.edit', $food) }}" class="pill-tag">Edit</a>
                                                    <form action="{{ route('foods.destroy', $food) }}" method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus {{ addslashes($food->name) }}?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="action-btn reject">Hapus</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($foods->hasPages())
                            <div style="margin-top: 18px;">
                                {{ $foods->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
    <script src="{{ asset('Assets/js/animations.js') }}" defer></script>
</body>

</html>
