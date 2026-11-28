@extends('layouts.app')

@section('title', 'Beranda — Bali Culinary Guide | Dari Rasa Ke Hati')

@section('content')
    {{-- Hero Banner inspired by beranda-referensi.png --}}
    <header class="bali-hero hero-home hero-fixed-bg">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <span class="editorial-kicker">Panduan Kuliner Tradisional Pulau Dewata</span>
                <h1>Dari Rasa<br>Ke <span class="accent">Hati</span></h1>
                <p>Jelajahi kekayaan kuliner Bali melalui resep autentik, sejarah dapur, dan tradisi yang hidup dari generasi ke generasi.</p>
                <div class="bali-hero-actions">
                    <a href="{{ route('explore') }}" class="btn-terracotta">Mulai Jelajahi &rarr;</a>
                    <a href="{{ route('photo.of.the.day') }}" class="pill-tag">Photo of the Day</a>
                </div>
            </div>
        </div>
    </header>

    {{-- Dynamic Section Arrangement (Structured Editorial Variations) --}}
    @foreach (($sectionOrder ?? ['popular', 'story_photo', 'recommended']) as $sectionKey)
        @if ($sectionKey === 'popular')
            <section class="home-dynamic-section">
                <div class="recipe-container">
                    <div class="section-heading-row" style="margin-bottom: 18px;">
                        <span class="starburst-icon" aria-hidden="true">&#9733;</span>
                        <div>
                            <h2 class="section-heading" style="margin: 0;">Lagi Populer</h2>
                            <p style="margin: 2px 0 0; font-size: 13px; color: var(--muted);">Rasakan kelezatan kuliner Bali pilihan yang berganti secara dinamis</p>
                        </div>
                    </div>

                    @if (count($popularFoods ?? []) > 0)
                        <div class="beranda-popular-grid">
                            @foreach ($popularFoods as $item)
                                <article class="beranda-popular-card">
                                    <div class="beranda-popular-thumb">
                                        @if (! empty($item['image']) && file_exists(public_path(ltrim($item['image'], '/'))))
                                            <img src="{{ asset(ltrim($item['image'], '/')) }}" alt="{{ $item['name'] }}" loading="lazy">
                                        @else
                                            <span>{{ Str::limit($item['name'], 12, '') }}</span>
                                        @endif
                                    </div>
                                    <div class="beranda-popular-info">
                                        <h3>{{ $item['name'] }}</h3>
                                        <p>{{ Str::limit($item['description'], 88) }}</p>
                                        <a href="{{ route('food.detail', $item['slug']) }}" class="btn-terracotta sm">Lihat Resep</a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="recipe-empty" style="padding: 28px;">
                            <h3>Belum ada data kuliner tersedia</h3>
                            <p>Jelajahi halaman Resep atau kirim resep pertamamu untuk mulai mengisi panduan kuliner Bali.</p>
                        </div>
                    @endif
                </div>
            </section>
        @elseif ($sectionKey === 'story_photo')
            <section class="home-dynamic-section">
                <div class="recipe-container">
                    <div class="beranda-story-photo-grid">
                        {{-- Left: Featured Culinary Story / Sejarah --}}
                        <div class="beranda-story-card">
                            <span class="editorial-eyebrow">Cerita &amp; Sejarah Kuliner</span>
                            @if ($featuredStory)
                                <h2>{{ $featuredStory['title'] }}</h2>
                                <p>{{ $featuredStory['excerpt'] }}</p>
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-top: auto;">
                                    <span class="pill-tag">{{ $featuredStory['region'] ?? 'Bali' }}</span>
                                    <a href="{{ route('stories.show', $featuredStory['slug']) }}" class="editorial-read-link">Lihat selengkapnya &rarr;</a>
                                </div>
                            @else
                                <h2>Kisah Dapur Tradisional Bali</h2>
                                <p>Jelajahi sejarah dan filosofi kuliner Pulau Dewata yang kaya akan rempah dan kebersamaan.</p>
                                <a href="{{ route('stories') }}" class="editorial-read-link">Buka Cerita Kuliner &rarr;</a>
                            @endif
                        </div>

                        {{-- Right: Photo of the Day Preview --}}
                        <a href="{{ route('photo.of.the.day') }}" class="beranda-photo-card">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 10px;">
                                <h2 style="margin: 0; font-family: 'Playfair Display', serif; font-size: 24px; color: var(--terracotta);">Photo of the Day</h2>
                                <span style="font-size: 12px; color: var(--terracotta); font-weight: 600;">Galeri &rarr;</span>
                            </div>

                            @if ($photoOfTheDay)
                                <div class="beranda-photo-frame">
                                    <img src="{{ $photoOfTheDay['url'] }}" alt="{{ $photoOfTheDay['title'] }}" loading="lazy">
                                </div>
                                <div class="beranda-photo-caption">
                                    <strong>{{ $photoOfTheDay['title'] }}</strong>
                                    <small>oleh {{ $photoOfTheDay['author'] }} &middot; {{ $photoOfTheDay['source_type'] }}</small>
                                </div>
                            @else
                                <div class="beranda-photo-empty">
                                    <span>Belum ada foto komunitas yang disetujui hari ini.</span>
                                    <small>Unggah foto makanan terbaikmu melalui halaman Kirim Resep atau Cerita Kuliner!</small>
                                </div>
                            @endif
                        </a>
                    </div>
                </div>
            </section>
        @elseif ($sectionKey === 'recommended' && count($recommendedFoods ?? []) > 0)
            <section class="home-dynamic-section">
                <div class="recipe-container">
                    <div class="editorial-section-header">
                        <div>
                            <span class="editorial-eyebrow">Rekomendasi Untukmu</span>
                            <h2 class="section-heading">{{ $recommendedHeading }}</h2>
                        </div>
                        <a href="{{ route('explore') }}" class="pill-tag">Jelajahi Semua &rarr;</a>
                    </div>

                    <div class="explore-editorial-grid">
                        @foreach ($recommendedFoods as $rec)
                            <article class="explore-recipe-item">
                                <div class="explore-item-media">
                                    @if (! empty($rec['image']) && file_exists(public_path(ltrim($rec['image'], '/'))))
                                        <img src="{{ asset(ltrim($rec['image'], '/')) }}" alt="{{ $rec['name'] }}" loading="lazy">
                                    @else
                                        <span class="recipe-card-placeholder">{{ $rec['name'] }}</span>
                                    @endif
                                    <span class="explore-item-region">{{ $rec['region'] }}</span>
                                </div>
                                <div class="explore-item-body">
                                    <div class="explore-item-meta">
                                        <span>{{ $rec['category'] }}</span>
                                        <span>&#9201; {{ $rec['cook_time'] }}</span>
                                    </div>
                                    <h3><a href="{{ route('food.detail', $rec['slug']) }}">{{ $rec['name'] }}</a></h3>
                                    <p>{{ Str::limit($rec['description'], 95) }}</p>
                                    <div class="explore-item-footer">
                                        <span class="recipe-rating"><span class="star">&#9733;</span> {{ number_format((float) $rec['rating'], 1) }}</span>
                                        <a href="{{ route('food.detail', $rec['slug']) }}" class="editorial-read-link">Lihat Resep &rarr;</a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    @endforeach

    {{-- Bottom Preservation CTA Banner --}}
    <section class="home-section">
        <div class="recipe-container">
            <div class="home-cta-banner">
                <h2>Mari lestarikan kuliner Bali bersama Bali Culinary</h2>
                <p>Punya resep masakan tradisional khas Bali atau cerita dapur keluarga? Bagikan sekarang atau ajukan diri menjadi Contributor agar cita rasa Pulau Dewata tetap hidup.</p>
                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <a href="{{ route('recipe.create') }}" class="btn-terracotta sm">+ Bagikan Resep</a>
                    <a href="{{ auth()->check() ? route('contributor.apply') : route('register') }}" class="pill-tag">Ajukan Jadi Contributor</a>
                </div>
            </div>
        </div>
    </section>
@endsection
