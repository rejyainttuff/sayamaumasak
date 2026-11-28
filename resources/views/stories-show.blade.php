@extends('layouts.app')

@php
    $displayTitle = $story ? $story->title : $curatedStory['title'];
    $displayCategory = $story ? ($story->food?->category?->name ?? 'Cerita Kuliner') : ($curatedStory['category'] ?? 'Cerita Kuliner');
    $displayRegion = $story ? ($story->food?->origin_area ?: 'Bali') : ($curatedStory['region'] ?? 'Bali');
    $displayAuthor = $story ? ($story->user?->name ?? 'Tim Bali Culinary') : ($curatedStory['author'] ?? 'Redaksi Bali Culinary');
    $displayThumb = $story ? ($story->thumbnail ?: $story->food?->thumbnail) : ($curatedStory['thumbnail'] ?? null);
    $displayDate = $story ? ($story->created_at?->format('d M Y') ?? '-') : 'Edisi Kurasi';
    $paragraphs = $story
        ? array_values(array_filter(array_map('trim', preg_split('/\r\n\r\n|\n\n/', trim((string) $story->content)) ?: [$story->content])))
        : ($curatedStory['paragraphs'] ?? []);
    $gallery = ($story && is_array($story->gallery_images))
        ? array_values(array_filter($story->gallery_images, fn ($item): bool => is_string($item) && trim($item) !== ''))
        : [];
    $pullQuote = $story ? Str::limit($paragraphs[0] ?? '', 170) : ($curatedStory['quote'] ?? '');

    $resolveStoryImageUrl = static function (?string $rawPath): ?string {
        if (! is_string($rawPath) || trim($rawPath) === '') {
            return null;
        }
        $trimmed = trim($rawPath);
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            return $trimmed;
        }
        $clean = ltrim($trimmed, '/');
        if (file_exists(public_path($clean))) {
            return asset($clean);
        }
        if (str_starts_with($clean, 'storage/')) {
            $diskRelative = substr($clean, strlen('storage/'));
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($diskRelative)) {
                return asset($clean);
            }
        }

        return null;
    };

    $coverImageUrl = $resolveStoryImageUrl($displayThumb);
    $validGalleryUrls = array_values(array_filter(array_map($resolveStoryImageUrl, $gallery)));
@endphp

@section('title', $displayTitle.' — Cerita Kuliner | Bali Culinary')

@section('content')
    <header class="bali-hero hero-beranda">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <span class="editorial-kicker">{{ $displayCategory }} &middot; {{ $displayRegion }}</span>
                <h1>{{ $displayTitle }}</h1>
                <p>
                    Ditulis oleh <strong>{{ $displayAuthor }}</strong>
                    @if ($story && $story->user)
                        (Level {{ $story->user->contributor_level ?? 1 }})
                    @endif
                    &middot; {{ $displayDate }}
                </p>
                <div class="bali-hero-actions">
                    <a href="{{ route('stories') }}" class="pill-tag">&larr; Kembali ke Daftar Cerita</a>
                    @if ($story && $story->food)
                        <a href="{{ route('food.detail', $story->food->slug) }}" class="btn-terracotta sm">Lihat Kuliner Terkait</a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <article class="home-section">
        <div class="recipe-container story-editorial-shell">
            @if ($coverImageUrl)
                <figure class="story-cover-media">
                    <img src="{{ $coverImageUrl }}" alt="{{ $displayTitle }}">
                </figure>
            @endif

            <div class="story-editorial-body">
                @foreach ($paragraphs as $pIndex => $paragraph)
                    <p>{{ $paragraph }}</p>

                    @if ($pIndex === 0 && $pullQuote !== '')
                        <blockquote class="story-pull-quote">
                            &ldquo;{{ $pullQuote }}&rdquo;
                        </blockquote>
                    @endif

                    @if (isset($validGalleryUrls[$pIndex]))
                        <figure class="story-inline-photo">
                            <img src="{{ $validGalleryUrls[$pIndex] }}" alt="Dokumentasi {{ $displayTitle }}" loading="lazy">
                        </figure>
                    @endif
                @endforeach
            </div>

            @if (count($validGalleryUrls) > count($paragraphs))
                <div class="story-gallery-block">
                    <h2>Galeri Foto</h2>
                    <div class="story-gallery-grid">
                        @foreach (array_slice($validGalleryUrls, count($paragraphs)) as $photoUrl)
                            <figure class="story-gallery-item">
                                <img src="{{ $photoUrl }}" alt="Foto dokumentasi {{ $displayTitle }}" loading="lazy">
                            </figure>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($story && ! empty($story->video_url))
                <div class="detail-panel" style="margin-top: 24px;">
                    <h2>Video Dokumenter</h2>
                    @php
                        $storyEmbed = $story->video_url;
                        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $storyEmbed, $m)) {
                            $storyEmbed = 'https://www.youtube.com/embed/'.$m[1];
                        }
                    @endphp
                    @if (str_contains($storyEmbed, 'youtube.com/embed/'))
                        <div class="recipe-video-embed">
                            <iframe src="{{ $storyEmbed }}" title="Video {{ $displayTitle }}" allowfullscreen loading="lazy"></iframe>
                        </div>
                    @else
                        <a href="{{ $story->video_url }}" target="_blank" rel="noopener noreferrer" class="btn-terracotta sm">Buka Video &rarr;</a>
                    @endif
                </div>
            @endif

            {{-- Prev / Next Story Navigation --}}
            @if ($prevStory || $nextStory)
                <nav class="story-prev-next" aria-label="Navigasi cerita sebelumnya dan berikutnya">
                    <div>
                        @if ($prevStory)
                            <a href="{{ route('stories.show', $prevStory['slug']) }}" class="story-nav-card">
                                <small>&larr; Cerita Sebelumnya</small>
                                <strong>{{ $prevStory['title'] }}</strong>
                            </a>
                        @endif
                    </div>
                    <div style="text-align: right;">
                        @if ($nextStory)
                            <a href="{{ route('stories.show', $nextStory['slug']) }}" class="story-nav-card">
                                <small>Cerita Berikutnya &rarr;</small>
                                <strong>{{ $nextStory['title'] }}</strong>
                            </a>
                        @endif
                    </div>
                </nav>
            @endif

            {{-- Related Stories --}}
            @if ($relatedStories->isNotEmpty() || count($curatedRelated) > 0)
                <div style="margin-top: 38px;">
                    <h2 class="section-heading" style="margin-bottom: 16px;">Cerita Terkait</h2>
                    <div class="editorial-stories-list">
                        @foreach ($relatedStories as $rel)
                            <a href="{{ route('stories.show', $rel->slug) }}" class="editorial-story-row">
                                <div class="editorial-story-meta">
                                    <span class="pill-tag">{{ $rel->food?->origin_area ?: 'Bali' }}</span>
                                    <small>{{ $rel->created_at?->format('d M Y') }}</small>
                                </div>
                                <h3>{{ $rel->title }}</h3>
                                <p>{{ Str::limit(strip_tags((string) $rel->content), 130) }}</p>
                                <span class="editorial-read-link">Baca cerita &rarr;</span>
                            </a>
                        @endforeach

                        @foreach ($curatedRelated as $rel)
                            <a href="{{ route('stories.show', $rel['slug']) }}" class="editorial-story-row">
                                <div class="editorial-story-meta">
                                    <span class="pill-tag">{{ $rel['region'] }}</span>
                                    <small>{{ $rel['read_time'] }}</small>
                                </div>
                                <h3>{{ $rel['title'] }}</h3>
                                <p>{{ Str::limit($rel['excerpt'], 130) }}</p>
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
            @if ($story)
            if (typeof window.initBaliReadingTracker === 'function') {
                window.initBaliReadingTracker('story', {{ (int) $story->id }});
            }
            @endif
        })();
    </script>
@endpush
