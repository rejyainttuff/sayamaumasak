@extends('layouts.app')

@section('title', 'Beranda — SayaMauMasak | Dari Rasa Ke Hati')

@section('content')
    <header class="recipe-hero">
        <div class="recipe-hero-inner">
            <div class="recipe-hero-content">
                <h1>Dari Rasa<br>Ke <span class="accent">Hati</span></h1>
                <p>Menyelami kekayaan cita rasa kuliner tradisional Bali — dari racikan bumbu base genep hingga sajian hangat di meja keluarga.</p>
                <div class="recipe-hero-actions">
                    <a href="{{ route('explore') }}" class="btn-terracotta">Lihat Resep &rarr;</a>
                    <a href="{{ route('recipe.create') }}" class="btn-outline">+ Bagikan Resep</a>
                </div>
            </div>

            <div class="recipe-hero-visual">
                <div class="recipe-swatch-card">
                    <strong>{{ count($foods) }}+</strong>
                    <span>Resep Khas Bali</span>
                </div>
                <div class="recipe-swatch-card">
                    <strong>9</strong>
                    <span>Kabupaten &amp; Kota</span>
                </div>
                <div class="recipe-swatch-card">
                    <strong>3</strong>
                    <span>Kategori Sajian</span>
                </div>
                <div class="recipe-swatch-card">
                    <strong>4.8&#9733;</strong>
                    <span>Rating Rata-rata</span>
                </div>
            </div>
        </div>
    </header>

    <section class="section-block">
        <div class="recipe-container">
            <div class="section-header">
                <div class="section-title-group">
                    <span class="starburst-badge" aria-hidden="true">&#10022;</span>
                    <h2 class="section-title underlined">Lagi Populer Lho</h2>
                </div>
                <a href="{{ route('explore') }}" class="btn-terracotta">Lihat Semua &rarr;</a>
            </div>

            @if (count($popularFoods) >= 3)
                <div class="popular-bento">
                    @php $featured = $popularFoods[0]; @endphp
                    <a href="{{ route('food.detail', $featured['slug']) }}" class="popular-featured-card">
                        <div class="popular-featured-media">
                            @if (file_exists(public_path($featured['image'])))
                                <img src="{{ asset($featured['image']) }}" alt="{{ $featured['name'] }}">
                            @else
                                <span class="recipe-card-placeholder" style="font-size: 24px;">{{ $featured['name'] }}</span>
                            @endif
                        </div>
                        <div>
                            <span class="recipe-badge" style="margin-bottom: 8px;">{{ $featured['region'] }} &middot; &#9733; {{ number_format($featured['rating'], 1) }}</span>
                            <h3>{{ $featured['name'] }}</h3>
                            <p>{{ $featured['description'] }}</p>
                        </div>
                    </a>

                    <div class="popular-stack">
                        @foreach (array_slice($popularFoods, 1, 2) as $item)
                            <a href="{{ route('food.detail', $item['slug']) }}" class="popular-mini-card">
                                <div class="popular-mini-media">
                                    @if (file_exists(public_path($item['image'])))
                                        <img src="{{ asset($item['image']) }}" alt="{{ $item['name'] }}">
                                    @else
                                        <span class="recipe-card-placeholder" style="font-size: 14px;">{{ $item['name'] }}</span>
                                    @endif
                                </div>
                                <div class="popular-mini-body">
                                    <span class="recipe-badge" style="margin-bottom: 6px;">{{ $item['region'] }}</span>
                                    <h3>{{ $item['name'] }}</h3>
                                    <p>{{ $item['description'] }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="popular-strip">
                @foreach (array_slice($foods, 0, 4) as $item)
                    <a href="{{ route('food.detail', $item['slug']) }}" class="popular-strip-card">
                        <div class="popular-strip-thumb">
                            @if (file_exists(public_path($item['image'])))
                                <img src="{{ asset($item['image']) }}" alt="{{ $item['name'] }}">
                            @else
                                <span class="recipe-card-placeholder" style="font-size: 12px;">{{ $item['name'] }}</span>
                            @endif
                        </div>
                        <div>
                            <h4>{{ $item['name'] }}</h4>
                            <p>{{ $item['description'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="about-wave-section">
        <div class="recipe-container">
            <div class="about-wave-grid">
                <div>
                    <h2 class="section-title" style="margin-bottom: 14px;">Tentang Kami</h2>
                    <p style="font-size: 14.5px; line-height: 1.75; color: var(--muted); margin: 0 0 18px;">
                        SayaMauMasak hadir sebagai ruang dokumentasi dan eksplorasi kuliner Bali yang menghubungkan resep turun-temurun dengan generasi masa kini. Setiap hidangan membawa cerita tentang rempah, tradisi gotong royong, dan kehangatan keluarga.
                    </p>
                    <a href="{{ route('about') }}" class="btn-terracotta">Selengkapnya &rarr;</a>
                </div>
                <div class="detail-photo-box" style="height: 200px;">
                    <span class="recipe-card-placeholder" style="font-size: 22px;">Warisan Kuliner Pulau Dewata</span>
                </div>
            </div>
        </div>
    </section>

    <section class="section-block" style="padding-bottom: 0;">
        <div class="recipe-container">
            <div class="cta-banner">
                <div>
                    <h2>Mari lestarikan kuliner Bali bersama SayaMauMasak</h2>
                    <p>Punya resep keluarga khas Bali seperti lawar, betutu, atau jajanan pasar tradisional? Bagikan resepmu agar tetap hidup dari generasi ke generasi.</p>
                </div>
                <a href="{{ route('recipe.create') }}" class="btn-terracotta">+ Bagikan Resep Sekarang</a>
            </div>
        </div>
    </section>
@endsection