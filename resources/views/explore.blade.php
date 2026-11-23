@extends('layouts.app')

@section('title', 'Jelajah Resep Bali — SayaMauMasak')

@section('content')
    <header class="recipe-hero">
        <div class="recipe-hero-inner">
            <div class="recipe-hero-content">
                <h1>Dari Rasa<br>Ke <span class="accent">Hati</span></h1>
                <p>Temukan berbagai resep masakan, minuman tradisional, dan jajanan khas Bali berdasarkan kategori, asal daerah, maupun tingkat kesulitan.</p>
                <div class="recipe-hero-actions">
                    <a href="{{ route('recipe.create') }}" class="btn-terracotta">+ Tambah Resep</a>
                    <a href="{{ route('recipe') }}" class="btn-outline">Lihat Aneka Resep</a>
                </div>
            </div>
        </div>
    </header>

    <section class="recipe-container">
        <form action="{{ route('explore') }}" method="GET" class="recipe-toolbar">
            <div class="recipe-search">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--muted); flex-shrink: 0;">
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

            <div class="filter-pill-group">
                <label for="filter-category">Kategori</label>
                <select id="filter-category" name="category">
                    <option value="">Semua</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected($selectedCategory === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-pill-group">
                <label for="filter-region">Asal Daerah</label>
                <select id="filter-region" name="region">
                    <option value="">Semua</option>
                    @foreach ($regions as $region)
                        <option value="{{ $region }}" @selected($selectedRegion === $region)>{{ $region }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-pill-group">
                <label for="filter-difficulty">Tingkat Kesulitan</label>
                <select id="filter-difficulty" name="difficulty">
                    <option value="">Semua</option>
                    @foreach ($difficulties as $difficulty)
                        <option value="{{ $difficulty }}" @selected($selectedDifficulty === $difficulty)>{{ $difficulty }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="recipe-filter-button">Terapkan Filter</button>

            @if ($search !== '' || $selectedCategory !== '' || $selectedRegion !== '' || $selectedDifficulty !== '')
                <a href="{{ route('explore') }}" class="btn-outline" style="height: 44px;">Reset</a>
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
                        <h3>Resep tidak ditemukan</h3>
                        <p>Coba ubah kata kunci atau filter kategori/daerah untuk menemukan kuliner Bali lainnya.</p>
                        <a href="{{ route('explore') }}" class="btn-terracotta">Tampilkan Semua Resep</a>
                    </div>
                @endif
            </div>

            <aside class="popular-sidebar">
                <div class="popular-sidebar-header">
                    <span class="starburst-badge" aria-hidden="true">&#9733;</span>
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
                                    <span>{{ \Illuminate\Support\Str::limit($popular['name'], 12, '') }}</span>
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
    </section>
@endsection