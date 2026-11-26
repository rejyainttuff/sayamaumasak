@extends('layouts.app')

@section('title', 'Resep Kuliner Bali — Bali Culinary')

@section('content')
    <header class="bali-hero hero-explore">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <h1>Dari Rasa<br>Ke <span class="accent">Hati</span></h1>
                <p>Temukan aneka resep masakan, minuman tradisional, dan jajanan pasar khas Bali berdasarkan kategori, asal daerah, dan tingkat kesulitan.</p>
            </div>
        </div>
    </header>

    <section class="explore-wrap">
        <div class="recipe-container">
            <form action="{{ route('explore') }}" method="GET" class="recipe-toolbar" id="explore-filter-form">
                <div class="toolbar-search">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--muted); flex-shrink: 0;">
                        <circle cx="11" cy="11" r="7" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cari resep..."
                        aria-label="Cari resep Bali"
                    >
                </div>

                <div class="toolbar-filter">
                    <label for="filter-category">Kategori</label>
                    <select id="filter-category" name="category" onchange="this.form.submit()">
                        <option value="">Semua</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected($selectedCategory === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="toolbar-filter">
                    <label for="filter-sort">Urutkan</label>
                    <select id="filter-sort" name="sort" onchange="this.form.submit()">
                        <option value="default" @selected(($sort ?? 'default') === 'default')>Default</option>
                        <option value="name_asc" @selected(($sort ?? '') === 'name_asc')>Nama (A - Z)</option>
                        <option value="name_desc" @selected(($sort ?? '') === 'name_desc')>Nama (Z - A)</option>
                        <option value="region_asc" @selected(($sort ?? '') === 'region_asc')>Daerah (A - Z)</option>
                        <option value="popular" @selected(($sort ?? '') === 'popular')>Terpopuler</option>
                    </select>
                </div>
                <div class="toolbar-filter">
                    <label for="filter-difficulty">Tingkat Kesulitan</label>
                    <select id="filter-difficulty" name="difficulty" onchange="this.form.submit()">
                        <option value="">Semua</option>
                        @foreach ($difficulties as $difficulty)
                            <option value="{{ $difficulty }}" @selected($selectedDifficulty === $difficulty)>{{ $difficulty }}</option>
                        @endforeach
                    </select>
                </div>

                <a href="{{ route('recipe.create') }}" class="btn-terracotta rect" style="height: 40px;">+ Tambah Resep</a>

                @if ($search !== '' || $selectedCategory !== '' || $selectedRegion !== '' || $selectedDifficulty !== '' || ($sort ?? 'default') !== 'default')
                    <a href="{{ route('explore') }}" class="btn-white" style="height: 40px;">Reset</a>
                @endif
            </form>

            <div class="recipe-layout">
                <div>
                    @if (count($foods) > 0)
                        <div class="recipe-grid">
                            @foreach ($foods as $food)
                                @include('partials.recipe-card', ['food' => $food])
                            @endforeach
                        </div>

                        <nav class="recipe-pagination" aria-label="Navigasi halaman">
                            <a
                                href="{{ route('explore', array_filter(['search' => $search, 'category' => $selectedCategory, 'region' => $selectedRegion, 'difficulty' => $selectedDifficulty, 'page' => max(1, $currentPage - 1)])) }}"
                                class="page-dot {{ $currentPage <= 1 ? 'disabled' : '' }}"
                                aria-label="Halaman sebelumnya"
                            >&lsaquo;</a>

                            @for ($p = 1; $p <= $totalPages; $p++)
                                <a
                                    href="{{ route('explore', array_filter(['search' => $search, 'category' => $selectedCategory, 'region' => $selectedRegion, 'difficulty' => $selectedDifficulty, 'page' => $p])) }}"
                                    class="page-dot {{ $currentPage === $p ? 'active' : '' }}"
                                >{{ $p }}</a>
                            @endfor

                            <a
                                href="{{ route('explore', array_filter(['search' => $search, 'category' => $selectedCategory, 'region' => $selectedRegion, 'difficulty' => $selectedDifficulty, 'page' => min($totalPages, $currentPage + 1)])) }}"
                                class="page-dot {{ $currentPage >= $totalPages ? 'disabled' : '' }}"
                                aria-label="Halaman berikutnya"
                            >&rsaquo;</a>
                        </nav>
                    @else
                        <div class="recipe-empty">
                            <h3 style="font-family: 'Playfair Display', serif; color: var(--terracotta); margin: 0 0 8px;">Resep tidak ditemukan</h3>
                            <p style="font-size: 13px; color: var(--muted); margin: 0 0 16px;">Coba ubah kata kunci atau pilih kategori/daerah lainnya.</p>
                            <a href="{{ route('explore') }}" class="btn-terracotta">Tampilkan Semua Resep</a>
                        </div>
                    @endif
                </div>

                <aside class="popular-sidebar">
                    <div class="popular-sidebar-header">
                        <span class="starburst-icon" aria-hidden="true">&#9733;</span>
                        <h3>Resep Populer</h3>
                    </div>
                    <div class="popular-sidebar-list">
                        @foreach ($popularFoods as $index => $popular)
                            <a href="{{ route('food.detail', $popular['slug']) }}" class="popular-sidebar-item">
                                <span class="rank-circle">{{ $index + 1 }}</span>
                                <div class="popular-side-thumb">
                                    @if (file_exists(public_path($popular['image'])))
                                        <img src="{{ asset($popular['image']) }}" alt="{{ $popular['name'] }}">
                                    @else
                                        <span>{{ substr($popular['name'], 0, 12) }}</span>
                                    @endif
                                </div>
                                <div class="popular-side-info">
                                    <h4>{{ $popular['name'] }}</h4>
                                    <div class="popular-side-meta">
                                        <span><strong style="color: var(--star);">&#9733;</strong> {{ number_format($popular['rating'], 1) }}</span>
                                        <span>{{ $popular['cook_time'] }}</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </aside>
            </div>
        </div>

        <img src="{{ asset('Assets/images/decor-hibiscus.png') }}" alt="" class="corner-hibiscus bottom-right" aria-hidden="true">
    </section>
@endsection
