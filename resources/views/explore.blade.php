@extends('layouts.app')

@section('title', 'Jelajahi Resep Kuliner Bali | Bali Culinary')

@php
    $resolveExploreImage = static function (?string $rawPath): ?string {
        if (! is_string($rawPath) || trim($rawPath) === '') {
            return null;
        }
        $trimmed = trim($rawPath);
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            return $trimmed;
        }
        $clean = ltrim($trimmed, '/');
        if ($clean !== '' && is_file(public_path($clean))) {
            return asset($clean);
        }
        if (str_starts_with($clean, 'storage/')) {
            $diskPath = substr($clean, strlen('storage/'));
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($diskPath)) {
                return asset($clean);
            }
        }

        return null;
    };

    $hasActiveFilter = $search !== ''
        || $selectedCategory !== ''
        || $selectedRegion !== ''
        || $selectedDifficulty !== ''
        || ($selectedCookTime ?? '') !== ''
        || ($sort ?? 'default') !== 'default';
@endphp

@section('content')
    <header class="bali-hero hero-explore">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <h1>Jelajahi Resep<br><span class="accent">Kuliner Bali</span></h1>
                <p>Temukan aneka masakan tradisional, minuman rempah, dan jajanan pasar khas Pulau Dewata dari sembilan kabupaten dan kota di Bali.</p>
            </div>
        </div>
    </header>

    <section class="explore-wrap">
        <div class="recipe-container">
            {{-- 1. Asymmetric Editorial Featured Recipe Showcase --}}
            @if (! empty($highestRatedRecipe))
                @php
                    $leadImg = $resolveExploreImage($highestRatedRecipe['image'] ?? null);
                    $leadReviewCount = (int) ($highestRatedRecipe['reviews_count'] ?? 0);
                    $leadRating = (float) ($highestRatedRecipe['rating'] ?? 0);
                    $sidePicks = $randomFeaturedRecipes ?? [];
                @endphp
                <section class="featured-showcase-section" aria-labelledby="featured-showcase-heading">
                    <div class="featured-showcase-header">
                        <div>
                            <h2 id="featured-showcase-heading" class="section-heading">Sorotan Resep Pilihan</h2>
                            <p class="featured-showcase-sub">Resep dengan ulasan terbaik komunitas bersama dua pilihan kurasi yang berganti setiap hari.</p>
                        </div>
                    </div>

                    <div class="featured-asymmetric-grid {{ count($sidePicks) === 0 ? 'is-single' : '' }}">
                        {{-- Left Dominant Card: Highest Rated Recipe (~60% on desktop) --}}
                        <article class="featured-lead-card">
                            <a href="{{ route('food.detail', $highestRatedRecipe['slug']) }}" class="featured-lead-media" aria-label="Buka resep {{ $highestRatedRecipe['name'] }}">
                                @if ($leadImg)
                                    <img
                                        src="{{ $leadImg }}"
                                        alt="{{ $highestRatedRecipe['name'] }}"
                                        width="800"
                                        height="500"
                                        fetchpriority="high"
                                    >
                                @else
                                    <div class="featured-illustrated-placeholder">
                                        <img
                                            src="{{ asset('Assets/images/image-for-reciepe.png') }}"
                                            alt=""
                                            width="160"
                                            height="128"
                                            fetchpriority="high"
                                        >
                                        <span>{{ $highestRatedRecipe['region'] }}</span>
                                    </div>
                                @endif
                                <span class="featured-badge-primary">HIGHEST RATED</span>
                            </a>

                            <div class="featured-lead-body">
                                <div class="featured-lead-meta-top">
                                    <span class="featured-rating-pill">
                                        <span class="star" aria-hidden="true">&#9733;</span>
                                        @if ($leadReviewCount > 0)
                                            <strong>{{ number_format($leadRating, 1) }}</strong>
                                            <span>&bull; {{ $leadReviewCount }} ulasan</span>
                                        @else
                                            <span>Belum ada ulasan</span>
                                        @endif
                                    </span>
                                    <span class="featured-meta-facts">
                                        {{ $highestRatedRecipe['category'] }} &bull; {{ $highestRatedRecipe['region'] }} &bull; {{ $highestRatedRecipe['cook_time'] }} &bull; {{ $highestRatedRecipe['difficulty'] }}
                                    </span>
                                </div>

                                <h3 class="featured-lead-title">
                                    <a href="{{ route('food.detail', $highestRatedRecipe['slug']) }}">{{ $highestRatedRecipe['name'] }}</a>
                                </h3>

                                <p class="featured-lead-desc">
                                    {{ Str::limit($highestRatedRecipe['description'] ?: $highestRatedRecipe['history'], 175) }}
                                </p>

                                <div class="featured-lead-actions">
                                    <a href="{{ route('food.detail', $highestRatedRecipe['slug']) }}" class="btn-terracotta featured-lead-cta">
                                        Lihat Resep
                                    </a>
                                </div>
                            </div>
                        </article>

                        {{-- Right Supporting Column: Two Stacked Daily Random Featured Cards (~40% on desktop) --}}
                        @if (count($sidePicks) > 0)
                            <div class="featured-side-stack">
                                @foreach ($sidePicks as $pick)
                                    @php
                                        $pickImg = $resolveExploreImage($pick['image'] ?? null);
                                        $pickReviews = (int) ($pick['reviews_count'] ?? 0);
                                        $pickRating = (float) ($pick['rating'] ?? 0);
                                    @endphp
                                    <article class="featured-side-card">
                                        <a href="{{ route('food.detail', $pick['slug']) }}" class="featured-side-media" aria-label="Buka resep {{ $pick['name'] }}">
                                            @if ($pickImg)
                                                <img
                                                    src="{{ $pickImg }}"
                                                    alt="{{ $pick['name'] }}"
                                                    width="320"
                                                    height="240"
                                                    loading="lazy"
                                                >
                                            @else
                                                <div class="featured-side-placeholder">
                                                    <img
                                                        src="{{ asset('Assets/images/image-for-reciepe.png') }}"
                                                        alt=""
                                                        width="84"
                                                        height="68"
                                                        loading="lazy"
                                                    >
                                                    <small>{{ $pick['region'] }}</small>
                                                </div>
                                            @endif
                                        </a>

                                        <div class="featured-side-body">
                                            <div class="featured-side-top">
                                                <span class="featured-badge-secondary">FEATURED PICK</span>
                                                <span class="featured-side-rating">
                                                    @if ($pickReviews > 0)
                                                        <span class="star" aria-hidden="true">&#9733;</span>
                                                        {{ number_format($pickRating, 1) }} &bull; {{ $pickReviews }} ulasan
                                                    @else
                                                        {{ $pick['category'] }}
                                                    @endif
                                                </span>
                                            </div>

                                            <h3 class="featured-side-title">
                                                <a href="{{ route('food.detail', $pick['slug']) }}">{{ $pick['name'] }}</a>
                                            </h3>

                                            <p class="featured-side-desc">
                                                {{ Str::limit($pick['description'] ?: $pick['history'], 96) }}
                                            </p>

                                            <div class="featured-side-footer">
                                                <span class="featured-side-meta">{{ $pick['cook_time'] }} &bull; {{ $pick['difficulty'] }}</span>
                                                <a href="{{ route('food.detail', $pick['slug']) }}" class="featured-side-link">Lihat Resep</a>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            {{-- 2. Explore All Recipes Catalogue Header + Authorized Add Recipe Button --}}
            <div class="catalogue-section-shell" id="explore-all-recipes">
                <div class="catalogue-header-row">
                    <div>
                        <h2 class="section-heading">Explore All Recipes</h2>
                        <p class="catalogue-header-sub">
                            Telusuri seluruh katalog resep tradisional Bali menggunakan pencarian, filter kategori, waktu memasak, dan urutan di bawah ini.
                        </p>
                    </div>

                    @auth
                        @if (auth()->user()->canSubmitRecipes())
                            <a href="{{ route('recipe.create') }}" class="btn-terracotta catalogue-add-btn">
                                + Tambah Resep
                            </a>
                        @endif
                    @endauth
                </div>

                <form action="{{ route('explore') }}#explore-all-recipes" method="GET" class="recipe-toolbar" id="explore-filter-form">
                    <div class="toolbar-search">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="color: var(--muted); flex-shrink: 0;">
                            <circle cx="11" cy="11" r="7" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg>
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Cari resep atau daerah..."
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
                        <label for="filter-cook-time">Waktu Masak</label>
                        <select id="filter-cook-time" name="cook_time" onchange="this.form.submit()">
                            <option value="">Semua</option>
                            <option value="fast" @selected(($selectedCookTime ?? '') === 'fast')>&le; 30 menit</option>
                            <option value="medium" @selected(($selectedCookTime ?? '') === 'medium')>31 - 60 menit</option>
                            <option value="long" @selected(($selectedCookTime ?? '') === 'long')>&gt; 60 menit</option>
                        </select>
                    </div>

                    <div class="toolbar-filter">
                        <label for="filter-sort">Urutkan</label>
                        <select id="filter-sort" name="sort" onchange="this.form.submit()">
                            <option value="default" @selected(($sort ?? 'default') === 'default')>Terbaru</option>
                            <option value="rating_desc" @selected(($sort ?? '') === 'rating_desc')>Rating Tertinggi</option>
                            <option value="popular" @selected(($sort ?? '') === 'popular')>Ulasan Terbanyak</option>
                            <option value="name_asc" @selected(($sort ?? '') === 'name_asc')>Nama (A - Z)</option>
                            <option value="name_desc" @selected(($sort ?? '') === 'name_desc')>Nama (Z - A)</option>
                            <option value="region_asc" @selected(($sort ?? '') === 'region_asc')>Daerah (A - Z)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-terracotta sm toolbar-submit-btn">Terapkan</button>
                    @if ($hasActiveFilter)
                        <a href="{{ route('explore') }}#explore-all-recipes" class="pill-tag toolbar-reset-btn">Reset</a>
                    @endif
                </form>

                {{-- Full Eligible Recipe Catalogue Grid --}}
                @if (count($foods) > 0)
                    <div class="explore-editorial-grid">
                        @foreach ($foods as $food)
                            @php
                                $cardImg = $resolveExploreImage($food['image'] ?? null);
                                $cardReviews = (int) ($food['reviews_count'] ?? 0);
                            @endphp
                            <article class="explore-recipe-item">
                                <a href="{{ route('food.detail', $food['slug']) }}" class="explore-item-media" aria-label="Lihat resep {{ $food['name'] }}">
                                    @if ($cardImg)
                                        <img
                                            src="{{ $cardImg }}"
                                            alt="{{ $food['name'] }}"
                                            width="480"
                                            height="300"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="explore-card-illustrated">
                                            <img
                                                src="{{ asset('Assets/images/image-for-reciepe.png') }}"
                                                alt=""
                                                width="96"
                                                height="78"
                                                loading="lazy"
                                            >
                                            <span>{{ $food['name'] }}</span>
                                        </div>
                                    @endif
                                    <span class="explore-item-region">{{ $food['region'] }}</span>
                                </a>
                                <div class="explore-item-body">
                                    <div class="explore-item-meta">
                                        <span>{{ $food['category'] }}</span>
                                        <span>&bull; {{ $food['cook_time'] }}</span>
                                        <span>&bull; {{ $food['difficulty'] }}</span>
                                    </div>
                                    <h3>
                                        <a href="{{ route('food.detail', $food['slug']) }}">{{ $food['name'] }}</a>
                                    </h3>
                                    <p>{{ Str::limit($food['description'] ?: $food['history'], 105) }}</p>
                                    <div class="explore-item-footer">
                                        <span class="recipe-rating">
                                            <span class="star" aria-hidden="true">&#9733;</span>
                                            @if ($cardReviews > 0)
                                                <span>{{ number_format((float) $food['rating'], 1) }} ({{ $cardReviews }})</span>
                                            @else
                                                <span style="color: var(--muted); font-weight: 500;">Belum dinilai</span>
                                            @endif
                                        </span>
                                        <a href="{{ route('food.detail', $food['slug']) }}" class="editorial-read-link">Lihat Resep</a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    @if ($totalPages > 1)
                        <nav class="recipe-pagination" aria-label="Halaman resep" style="margin-top: 32px;">
                            @for ($p = 1; $p <= $totalPages; $p++)
                                <a
                                    href="{{ route('explore', array_merge(request()->query(), ['page' => $p])) }}#explore-all-recipes"
                                    class="page-btn {{ $p === $currentPage ? 'active' : '' }}"
                                    @if ($p === $currentPage) aria-current="page" @endif
                                >{{ $p }}</a>
                            @endfor
                        </nav>
                    @endif
                @else
                    <div class="recipe-empty" style="margin-top: 24px;">
                        <h3 style="font-family: 'Playfair Display', serif; color: var(--terracotta-dark); margin: 0 0 8px;">Resep tidak ditemukan</h3>
                        <p style="font-size: 13.5px; color: var(--muted); margin: 0 0 16px;">Tidak ada resep yang cocok dengan kata kunci atau filter yang dipilih. Silakan atur ulang pencarian untuk melihat seluruh katalog.</p>
                        <a href="{{ route('explore') }}#explore-all-recipes" class="btn-terracotta sm">Tampilkan Semua Resep</a>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
