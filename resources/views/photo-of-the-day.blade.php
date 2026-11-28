@extends('layouts.app')

@section('title', 'Photo of the Day — Galeri Kuliner Bali | Bali Culinary')

@section('content')
    <header class="bali-hero hero-beranda">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <span class="editorial-kicker">Galeri Fotografi Komunitas</span>
                <h1>Photo of <span class="accent">the Day</span></h1>
                <p>Kumpulan foto makanan tradisional Bali yang diunggah dan telah disetujui dari kiriman resep serta cerita kuliner komunitas.</p>
                <div class="bali-hero-actions">
                    <a href="{{ route('home') }}" class="pill-tag">&larr; Kembali ke Beranda</a>
                    <a href="{{ route('recipe.create') }}" class="btn-terracotta sm">+ Bagikan Foto &amp; Resep</a>
                </div>
            </div>
        </div>
    </header>

    <section class="home-section">
        <div class="recipe-container">
            @if ($featuredPhoto)
                <div class="potd-spotlight" data-reveal>
                    <div class="potd-spotlight-media">
                        <img src="{{ $featuredPhoto['url'] }}" alt="{{ $featuredPhoto['title'] }}">
                    </div>
                    <div class="potd-spotlight-body">
                        <span class="pill-tag">{{ $featuredPhoto['source_type'] }}</span>
                        <h2>{{ $featuredPhoto['title'] }}</h2>
                        <p style="font-size: 13.5px; color: var(--muted); margin: 0 0 16px;">
                            Diunggah oleh <strong style="color: var(--text);">{{ $featuredPhoto['author'] }}</strong> &middot; {{ $featuredPhoto['date'] }}
                        </p>
                        <a href="{{ $featuredPhoto['detail_url'] }}" class="btn-terracotta sm">Lihat Sumber Konten &rarr;</a>
                    </div>
                </div>

                @if (count($photos) > 1)
                    <div class="editorial-section-header" style="margin-top: 34px;">
                        <div>
                            <span class="editorial-eyebrow">Koleksi Galeri</span>
                            <h2 class="section-heading">Foto Kuliner Komunitas ({{ count($photos) }})</h2>
                        </div>
                    </div>

                    <div class="potd-gallery-grid">
                        @foreach ($photos as $photo)
                            <a href="{{ $photo['detail_url'] }}" class="potd-gallery-card" data-reveal>
                                <div class="potd-gallery-img">
                                    <img src="{{ $photo['url'] }}" alt="{{ $photo['title'] }}" loading="lazy">
                                </div>
                                <div class="potd-gallery-meta">
                                    <span style="font-size: 11px; color: var(--terracotta); font-weight: 600;">{{ $photo['source_type'] }}</span>
                                    <h3>{{ $photo['title'] }}</h3>
                                    <small>oleh {{ $photo['author'] }} &middot; {{ $photo['date'] }}</small>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="recipe-empty">
                    <h3 style="font-family: 'Playfair Display', serif; color: var(--terracotta); margin: 0 0 8px;">Belum Ada Foto Komunitas yang Disetujui</h3>
                    <p style="font-size: 13.5px; color: var(--muted); margin: 0 0 16px;">
                        Foto makanan dari pengajuan Resep dan Cerita Kuliner yang telah disetujui Admin akan otomatis tampil di galeri Photo of the Day.
                    </p>
                    <a href="{{ route('recipe.create') }}" class="btn-terracotta sm">+ Kirim Resep dengan Foto</a>
                </div>
            @endif
        </div>
    </section>
@endsection
