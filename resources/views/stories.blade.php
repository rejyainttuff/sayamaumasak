@extends('layouts.app')

@section('title', 'Cerita Kuliner Bali — Bali Culinary')

@section('content')
    <header class="bali-hero hero-beranda">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <h1>Cerita <span class="accent">Kuliner</span></h1>
                <p>Kisah, sejarah, dan filosofi di balik rempah serta tradisi makan bersama masyarakat Pulau Dewata.</p>
                @if (auth()->check() && auth()->user()->canSubmitCulinaryStory())
                    <div class="bali-hero-actions">
                        <a href="{{ route('stories.create') }}" class="btn-terracotta sm">+ Tulis Cerita Lengkap</a>
                    </div>
                @endif
            </div>
        </div>
    </header>

    <section class="home-section">
        <div class="recipe-container">
            @if (session('success'))
                <div class="alert-banner success">{{ session('success') }}</div>
            @endif

            <div class="section-heading-row">
                <span class="starburst-icon" aria-hidden="true">&#9733;</span>
                <h2 class="section-heading">Kisah dari Dapur Bali</h2>
            </div>

            <div class="stories-grid">
                @foreach ($stories as $story)
                    <article class="story-card">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                            <span class="pill-tag">{{ $story['region'] }}</span>
                            <span style="font-size: 12px; color: var(--muted);">{{ $story['read_time'] }}</span>
                        </div>
                        <h3>{{ $story['title'] }}</h3>
                        <p>{{ $story['excerpt'] }}</p>
                        @if (! empty($story['slug']))
                            <a href="{{ route('stories.show', $story['slug']) }}" class="editorial-read-link" style="margin-top: 8px; display: inline-block;">Baca selengkapnya &rarr;</a>
                        @endif
                    </article>
                @endforeach
            </div>

            @if (($totalPages ?? 1) > 1)
                <nav class="recipe-pagination" aria-label="Halaman cerita" style="margin-top: 26px;">
                    @for ($p = 1; $p <= $totalPages; $p++)
                        <a href="{{ route('stories', ['page' => $p]) }}" class="page-btn {{ $p === $currentPage ? 'active' : '' }}">{{ $p }}</a>
                    @endfor
                </nav>
            @endif
        </div>
    </section>
@endsection
