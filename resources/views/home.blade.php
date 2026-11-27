@extends('layouts.app')

@section('title', 'Beranda — Bali Culinary Guide | Dari Rasa Ke Hati')

@section('content')
    {{-- Editorial Fixed-Visual Hero Banner --}}
    <header class="bali-hero hero-home hero-fixed-bg">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <span class="editorial-kicker">Panduan Kuliner Tradisional Pulau Dewata</span>
                <h1>Dari Rasa<br>Ke <span class="accent">Hati</span></h1>
                <p>Menyelami warisan bumbu <em>base genep</em>, teknik memasak turun-temurun, dan kisah hangat di balik setiap sajian tradisional Bali.</p>
                <div class="bali-hero-actions">
                    <a href="{{ route('explore') }}" class="btn-terracotta">Jelajahi Resep &rarr;</a>
                    <a href="{{ route('stories') }}" class="pill-tag">Baca Cerita Kuliner</a>
                </div>
            </div>
        </div>
    </header>

    @php
        $featuredFood = $popularFoods[0] ?? ($foods[0] ?? null);
        $curatedFoods = array_slice($popularFoods, 1, 3);
        if (count($curatedFoods) < 3) {
            $curatedFoods = array_slice($foods, 1, 3);
        }
    @endphp

    {{-- Section 1: Featured Editorial Spotlight (Asymmetric Split, Not Generic Cards) --}}
    @if ($featuredFood)
        <section class="editorial-spotlight-section">
            <div class="recipe-container">
                <div class="editorial-spotlight">
                    <div class="editorial-spotlight-media">
                        @if (file_exists(public_path(ltrim($featuredFood['image'], '/'))))
                            <img src="{{ asset(ltrim($featuredFood['image'], '/')) }}" alt="{{ $featuredFood['name'] }}">
                        @else
                            <div class="editorial-media-fallback">{{ $featuredFood['name'] }}</div>
                        @endif
                        <span class="editorial-region-tag">{{ $featuredFood['region'] }}</span>
                    </div>

                    <div class="editorial-spotlight-copy">
                        <span class="editorial-eyebrow">Sorotan Kuliner Pilihan</span>
                        <h2>{{ $featuredFood['name'] }}</h2>
                        <p class="editorial-lead">{{ $featuredFood['description'] }}</p>

                        <div class="editorial-meta-strip">
                            <div>
                                <small>Kategori</small>
                                <strong>{{ $featuredFood['category'] }}</strong>
                            </div>
                            <div>
                                <small>Waktu Masak</small>
                                <strong>{{ $featuredFood['cook_time'] }}</strong>
                            </div>
                            <div>
                                <small>Tingkat</small>
                                <strong>{{ $featuredFood['difficulty'] }}</strong>
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px;">
                            <a href="{{ route('food.detail', $featuredFood['slug']) }}" class="btn-terracotta">Buka Panduan Lengkap &rarr;</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Section 2: Curated Culinary Mosaic (Asymmetric Feature + Stacked Editorial List) --}}
    @if (count($curatedFoods) > 0)
        <section class="editorial-collection-section">
            <div class="recipe-container">
                <div class="editorial-section-header">
                    <div>
                        <span class="editorial-eyebrow">Kurasi Dapur Bali</span>
                        <h2 class="section-heading">Cita Rasa yang Wajib Dicoba</h2>
                    </div>
                    <a href="{{ route('explore') }}" class="pill-tag">Lihat Semua Koleksi &rarr;</a>
                </div>

                <div class="editorial-mosaic">
                    @php $primaryPick = $curatedFoods[0]; @endphp
                    <a href="{{ route('food.detail', $primaryPick['slug']) }}" class="editorial-mosaic-lead">
                        <div class="editorial-mosaic-lead-img">
                            @if (file_exists(public_path(ltrim($primaryPick['image'], '/'))))
                                <img src="{{ asset(ltrim($primaryPick['image'], '/')) }}" alt="{{ $primaryPick['name'] }}">
                            @else
                                <span>{{ $primaryPick['name'] }}</span>
                            @endif
                        </div>
                        <div class="editorial-mosaic-lead-body">
                            <span class="pill-tag">{{ $primaryPick['region'] }}</span>
                            <h3>{{ $primaryPick['name'] }}</h3>
                            <p>{{ $primaryPick['description'] }}</p>
                        </div>
                    </a>

                    <div class="editorial-mosaic-side">
                        @foreach (array_slice($curatedFoods, 1, 2) as $sideFood)
                            <a href="{{ route('food.detail', $sideFood['slug']) }}" class="editorial-side-item">
                                <div class="editorial-side-thumb">
                                    @if (file_exists(public_path(ltrim($sideFood['image'], '/'))))
                                        <img src="{{ asset(ltrim($sideFood['image'], '/')) }}" alt="{{ $sideFood['name'] }}">
                                    @else
                                        <span>{{ Str::limit($sideFood['name'], 12, '') }}</span>
                                    @endif
                                </div>
                                <div class="editorial-side-copy">
                                    <span style="font-size: 11px; font-weight: 600; color: var(--terracotta); text-transform: uppercase; letter-spacing: 0.05em;">{{ $sideFood['region'] }} &middot; {{ $sideFood['cook_time'] }}</span>
                                    <h3>{{ $sideFood['name'] }}</h3>
                                    <p>{{ Str::limit($sideFood['description'], 110) }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Section 3: Visual Storytelling Strip (Culinary Stories) --}}
    @if (count($stories) > 0)
        <section class="editorial-stories-section">
            <div class="recipe-container">
                <div class="editorial-section-header">
                    <div>
                        <span class="editorial-eyebrow">Jejak Budaya &amp; Sejarah</span>
                        <h2 class="section-heading">Cerita dari Dapur Bali</h2>
                    </div>
                    <a href="{{ route('stories') }}" class="pill-tag">Semua Cerita &rarr;</a>
                </div>

                <div class="editorial-stories-list">
                    @foreach ($stories as $story)
                        <a href="{{ route('stories.show', $story['slug']) }}" class="editorial-story-row">
                            <div class="editorial-story-meta">
                                <span class="pill-tag">{{ $story['region'] }}</span>
                                <small>oleh {{ $story['author'] }}</small>
                            </div>
                            <h3>{{ $story['title'] }}</h3>
                            <p>{{ $story['excerpt'] }}</p>
                            <span class="editorial-read-link">Baca cerita lengkap &rarr;</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Section 4: Community Preservation Callout --}}
    <section class="home-section">
        <div class="recipe-container">
            <div class="home-cta-banner">
                <h2>Mari lestarikan kuliner Bali bersama Bali Culinary Guide</h2>
                <p>Punya resep masakan tradisional khas Bali atau cerita dapur keluarga? Bagikan sekarang atau ajukan diri menjadi Contributor agar cita rasa Pulau Dewata tetap hidup.</p>
                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <a href="{{ route('recipe.create') }}" class="btn-terracotta sm">+ Bagikan Resep</a>
                    <a href="{{ auth()->check() ? route('contributor.apply') : route('register') }}" class="pill-tag">Gabung Contributor</a>
                </div>
            </div>
        </div>
    </section>
@endsection
