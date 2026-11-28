@extends('layouts.app')

@section('title', 'Cerita Kuliner Bali — Majalah Editorial | Bali Culinary')

@section('content')
    <header class="bali-hero hero-beranda">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <span class="editorial-kicker">Majalah Editorial Kuliner</span>
                <h1>Cerita <span class="accent">Kuliner</span></h1>
                <p>Kisah, tradisi dapur, dan filosofi di balik rempah serta kebiasaan makan bersama masyarakat Pulau Dewata.</p>
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

            @if (count($stories) > 0)
                @php
                    $leadStory = ($currentPage ?? 1) === 1 ? $stories[0] : null;
                    $otherStories = ($currentPage ?? 1) === 1 ? array_slice($stories, 1) : $stories;
                    $hasStoryImg = static function (?string $raw): bool {
                        if (! is_string($raw) || trim($raw) === '') {
                            return false;
                        }
                        $clean = ltrim(trim($raw), '/');
                        if (file_exists(public_path($clean))) {
                            return true;
                        }
                        if (str_starts_with($clean, 'storage/')) {
                            return \Illuminate\Support\Facades\Storage::disk('public')->exists(substr($clean, strlen('storage/')));
                        }

                        return false;
                    };
                @endphp

                {{-- Hero Featured Story (Magazine Editorial Lead) --}}
                @if ($leadStory)
                    <a href="{{ route('stories.show', $leadStory['slug']) }}" class="magazine-lead-story" data-reveal>
                        <div class="magazine-lead-media">
                            @if ($hasStoryImg($leadStory['thumbnail'] ?? null))
                                <img src="{{ asset(ltrim($leadStory['thumbnail'], '/')) }}" alt="{{ $leadStory['title'] }}">
                            @else
                                <div class="editorial-media-fallback">{{ $leadStory['title'] }}</div>
                            @endif
                        </div>
                        <div class="magazine-lead-body">
                            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                <span class="pill-tag">{{ $leadStory['category'] ?? $leadStory['region'] }}</span>
                                <span style="font-size: 12px; color: var(--muted);">{{ $leadStory['region'] }} &middot; {{ $leadStory['read_time'] }}</span>
                            </div>
                            <h2>{{ $leadStory['title'] }}</h2>
                            <p>{{ $leadStory['excerpt'] }}</p>
                            <span class="editorial-read-link">Baca artikel lengkap &rarr;</span>
                        </div>
                    </a>
                @endif

                {{-- Editorial Horizontal Magazine List --}}
                @if (count($otherStories) > 0)
                    <div class="editorial-section-header" style="margin-top: 38px;">
                        <div>
                            <span class="editorial-eyebrow">Arsip Dapur Bali</span>
                            <h2 class="section-heading">Kisah Lainnya</h2>
                        </div>
                    </div>

                    <div class="magazine-story-feed">
                        @foreach ($otherStories as $story)
                            <a href="{{ route('stories.show', $story['slug']) }}" class="magazine-feed-item" data-reveal>
                                <div class="magazine-feed-thumb">
                                    @if ($hasStoryImg($story['thumbnail'] ?? null))
                                        <img src="{{ asset(ltrim($story['thumbnail'], '/')) }}" alt="{{ $story['title'] }}" loading="lazy">
                                    @else
                                        <span>{{ Str::limit($story['title'], 16, '') }}</span>
                                    @endif
                                </div>
                                <div class="magazine-feed-copy">
                                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; font-size: 12px; color: var(--muted);">
                                        <span class="pill-tag">{{ $story['category'] ?? $story['region'] }}</span>
                                        <span>{{ $story['region'] }}</span>
                                        <span>&middot; {{ $story['read_time'] }}</span>
                                    </div>
                                    <h3>{{ $story['title'] }}</h3>
                                    <p>{{ $story['excerpt'] }}</p>
                                    <span class="editorial-read-link">Baca selengkapnya &rarr;</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="recipe-empty">
                    <h3>Belum ada cerita kuliner</h3>
                    <p>Cerita kuliner tradisional Bali akan segera hadir di sini.</p>
                </div>
            @endif

            @if (($totalPages ?? 1) > 1)
                <nav class="recipe-pagination" aria-label="Halaman cerita" style="margin-top: 28px;">
                    @for ($p = 1; $p <= $totalPages; $p++)
                        <a href="{{ route('stories', ['page' => $p]) }}" class="page-btn {{ $p === $currentPage ? 'active' : '' }}">{{ $p }}</a>
                    @endfor
                </nav>
            @endif
        </div>
    </section>
@endsection
