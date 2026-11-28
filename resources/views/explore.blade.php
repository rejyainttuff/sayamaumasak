@extends('layouts.app')

@section('title', 'Jelajahi Resep Kuliner Bali — Bali Culinary')

@section('content')
    <header class="bali-hero hero-explore">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <span class="editorial-kicker">Katalog Resep Autentik</span>
                <h1>Jelajahi Resep<br><span class="accent">Kuliner Bali</span></h1>
                <p>Temukan aneka masakan, minuman tradisional, dan jajanan pasar khas Bali berdasarkan kategori, asal daerah, dan tingkat kesulitan.</p>
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
                        placeholder="Cari nama resep atau bahan..."
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
                    <label for="filter-region">Asal Daerah</label>
                    <select id="filter-region" name="region" onchange="this.form.submit()">
                        <option value="">Semua</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region }}" @selected($selectedRegion === $region)>{{ $region }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="toolbar-filter">
                    <label for="filter-difficulty">Kesulitan</label>
                    <select id="filter-difficulty" name="difficulty" onchange="this.form.submit()">
                        <option value="">Semua</option>
                        @foreach ($difficulties as $difficulty)
                            <option value="{{ $difficulty }}" @selected($selectedDifficulty === $difficulty)>{{ $difficulty }}</option>
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

                <button type="submit" class="btn-terracotta sm">Terapkan</button>
                @if ($search !== '' || $selectedCategory !== '' || $selectedRegion !== '' || $selectedDifficulty !== '' || ($sort ?? 'default') !== 'default')
                    <a href="{{ route('explore') }}" class="pill-tag">Reset</a>
                @endif
            </form>

            @if (count($foods) > 0)
                @php
                    $isFirstPageNoFilter = $currentPage === 1 && $search === '' && $selectedCategory === '' && $selectedRegion === '' && $selectedDifficulty === '';
                    $featuredExplore = $isFirstPageNoFilter ? $foods[0] : null;
                    $gridFoods = $isFirstPageNoFilter ? array_slice($foods, 1) : $foods;
                @endphp

                {{-- Editorial Featured Highlight on Page 1 --}}
                @if ($featuredExplore)
                    <div class="explore-featured-banner" data-reveal>
                        <div class="explore-featured-media">
                            @if (file_exists(public_path(ltrim($featuredExplore['image'], '/'))))
                                <img src="{{ asset(ltrim($featuredExplore['image'], '/')) }}" alt="{{ $featuredExplore['name'] }}">
                            @else
                                <div class="editorial-media-fallback">{{ $featuredExplore['name'] }}</div>
                            @endif
                        </div>
                        <div class="explore-featured-body">
                            <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                                <span class="pill-tag">{{ $featuredExplore['category'] }}</span>
                                <span style="font-size: 12px; color: var(--muted);">{{ $featuredExplore['region'] }} &middot; {{ $featuredExplore['cook_time'] }} &middot; {{ $featuredExplore['difficulty'] }}</span>
                            </div>
                            <h2>{{ $featuredExplore['name'] }}</h2>
                            <p>{{ $featuredExplore['description'] }}</p>
                            <a href="{{ route('food.detail', $featuredExplore['slug']) }}" class="btn-terracotta sm">Lihat Detail Resep &rarr;</a>
                        </div>
                    </div>
                @endif

                {{-- Editorial Recipe Cards Grid --}}
                <div class="explore-editorial-grid">
                    @foreach ($gridFoods as $food)
                        <article class="explore-recipe-item" data-reveal>
                            <div class="explore-item-media">
                                @if (file_exists(public_path(ltrim($food['image'], '/'))))
                                    <img src="{{ asset(ltrim($food['image'], '/')) }}" alt="{{ $food['name'] }}" loading="lazy">
                                @else
                                    <span class="recipe-card-placeholder">{{ $food['name'] }}</span>
                                @endif
                                <span class="explore-item-region">{{ $food['region'] }}</span>
                            </div>
                            <div class="explore-item-body">
                                <div class="explore-item-meta">
                                    <span>{{ $food['category'] }}</span>
                                    <span>&#9201; {{ $food['cook_time'] }}</span>
                                    <span>{{ $food['difficulty'] }}</span>
                                </div>
                                <h3>
                                    <a href="{{ route('food.detail', $food['slug']) }}">{{ $food['name'] }}</a>
                                </h3>
                                <p>{{ Str::limit($food['description'], 105) }}</p>
                                <div class="explore-item-footer">
                                    <span class="recipe-rating">
                                        <span class="star">&#9733;</span>
                                        <span>{{ number_format((float) $food['rating'], 1) }}</span>
                                    </span>
                                    <a href="{{ route('food.detail', $food['slug']) }}" class="editorial-read-link">Lihat Detail &rarr;</a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($totalPages > 1)
                    <nav class="recipe-pagination" aria-label="Halaman resep" style="margin-top: 28px;">
                        @for ($p = 1; $p <= $totalPages; $p++)
                            <a
                                href="{{ route('explore', array_merge(request()->query(), ['page' => $p])) }}"
                                class="page-btn {{ $p === $currentPage ? 'active' : '' }}"
                            >{{ $p }}</a>
                        @endfor
                    </nav>
                @endif
            @else
                <div class="recipe-empty" style="margin-top: 24px;">
                    <h3 style="font-family: 'Playfair Display', serif; color: var(--terracotta); margin: 0 0 8px;">Resep tidak ditemukan</h3>
                    <p style="font-size: 13.5px; color: var(--muted); margin: 0 0 16px;">Coba ubah kata kunci pencarian atau filter kategori, asal daerah, maupun tingkat kesulitan.</p>
                    <a href="{{ route('explore') }}" class="btn-terracotta sm">Tampilkan Semua Resep</a>
                </div>
            @endif
        </div>
    </section>
@endsection
