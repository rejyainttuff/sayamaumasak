@extends('layouts.app')

@section('title', $story->title.' — Cerita Kuliner | Bali Culinary')

@section('content')
    <header class="bali-hero hero-beranda">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <span class="editorial-kicker">{{ $story->food?->origin_area ?: 'Warisan Kuliner Bali' }}</span>
                <h1>{{ $story->title }}</h1>
                <p>
                    Ditulis oleh <strong>{{ $story->user?->name ?? 'Tim Bali Culinary' }}</strong>
                    @if ($story->user)
                        (Contributor Level {{ $story->user->contributor_level ?? 1 }})
                    @endif
                    &middot; {{ $story->created_at?->format('d M Y') }}
                </p>
                <div class="bali-hero-actions">
                    <a href="{{ route('stories') }}" class="pill-tag">&larr; Semua Cerita</a>
                    @if ($story->food)
                        <a href="{{ route('food.detail', $story->food->slug) }}" class="btn-terracotta sm">Lihat Kuliner Terkait</a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <article class="home-section">
        <div class="recipe-container story-editorial-shell">
            @if ($story->thumbnail && file_exists(public_path(ltrim($story->thumbnail, '/'))))
                <figure class="story-cover-media">
                    <img src="{{ asset(ltrim($story->thumbnail, '/')) }}" alt="{{ $story->title }}">
                </figure>
            @endif

            <div class="story-editorial-body">
                @foreach (preg_split('/\r\n\r\n|\n\n/', trim((string) $story->content)) ?: [$story->content] as $paragraph)
                    @if (trim($paragraph) !== '')
                        <p>{{ trim($paragraph) }}</p>
                    @endif
                @endforeach
            </div>

            @if (! empty($story->gallery_images) && is_array($story->gallery_images))
                <div class="story-gallery-block">
                    <h2>Galeri Foto</h2>
                    <div class="story-gallery-grid">
                        @foreach ($story->gallery_images as $photoPath)
                            <figure class="story-gallery-item">
                                <img src="{{ asset(ltrim($photoPath, '/')) }}" alt="Foto dokumentasi {{ $story->title }}" loading="lazy">
                            </figure>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (! empty($story->video_url))
                <div class="detail-panel" style="margin-top: 24px;">
                    <h2>Video Dokumenter / Tutorial</h2>
                    @php
                        $storyEmbed = $story->video_url;
                        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $storyEmbed, $m)) {
                            $storyEmbed = 'https://www.youtube.com/embed/'.$m[1];
                        }
                    @endphp
                    @if (str_contains($storyEmbed, 'youtube.com/embed/'))
                        <div class="recipe-video-embed">
                            <iframe src="{{ $storyEmbed }}" title="Video {{ $story->title }}" allowfullscreen loading="lazy"></iframe>
                        </div>
                    @else
                        <a href="{{ $story->video_url }}" target="_blank" rel="noopener noreferrer" class="btn-terracotta sm">Buka Video &rarr;</a>
                    @endif
                </div>
            @endif

            @if ($relatedStories->isNotEmpty())
                <div style="margin-top: 38px;">
                    <h2 class="section-heading" style="margin-bottom: 16px;">Cerita Lainnya</h2>
                    <div class="editorial-stories-list">
                        @foreach ($relatedStories as $rel)
                            <a href="{{ route('stories.show', $rel->slug) }}" class="editorial-story-row">
                                <div class="editorial-story-meta">
                                    <span class="pill-tag">{{ $rel->food?->origin_area ?: 'Bali' }}</span>
                                    <small>{{ $rel->created_at?->format('d M Y') }}</small>
                                </div>
                                <h3>{{ $rel->title }}</h3>
                                <p>{{ Str::limit(strip_tags((string) $rel->content), 140) }}</p>
                                <span class="editorial-read-link">Baca cerita &rarr;</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </article>
@endsection

@push('scripts')
    <script>
        (function () {
            if (typeof window.initBaliReadingTracker === 'function') {
                window.initBaliReadingTracker('story', {{ (int) $story->id }});
            }
        })();
    </script>
@endpush
