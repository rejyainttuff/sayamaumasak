@extends('layouts.app')

@section('title', $recipe->title.' — Detail Resep | Bali Culinary')

@section('content')
    <header class="bali-hero hero-explore">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <span class="editorial-kicker">{{ $recipe->food?->category?->name ?? 'Resep Bali' }}</span>
                <h1>{{ $recipe->title }}</h1>
                <p>
                    Kuliner: <strong>{{ $recipe->food?->name ?? '-' }}</strong>
                    @if ($recipe->user)
                        &middot; Dikirim oleh <strong>{{ $recipe->user->name }}</strong> (Level {{ $recipe->user->contributor_level ?? 1 }})
                    @endif
                </p>
                <div class="bali-hero-actions">
                    <a href="{{ route('recipes.index') }}" class="pill-tag">&larr; Semua Resep</a>
                    @if ($recipe->food)
                        <a href="{{ route('food.detail', $recipe->food->slug) }}" class="btn-terracotta sm">Lihat Detail Makanan</a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <section class="home-section">
        <div class="recipe-container">
            <div class="detail-bento">
                <div class="detail-left-col">
                    @if ($recipe->food?->thumbnail && file_exists(public_path(ltrim($recipe->food->thumbnail, '/'))))
                        <div class="story-cover-media" style="margin-bottom: 18px;">
                            <img src="{{ asset(ltrim($recipe->food->thumbnail, '/')) }}" alt="{{ $recipe->title }}">
                        </div>
                    @endif

                    <div class="detail-panel" style="margin-bottom: 18px;">
                        <div class="editorial-meta-strip">
                            <div>
                                <small>Persiapan</small>
                                <strong>{{ $recipe->prep_time_minutes ?? 0 }} menit</strong>
                            </div>
                            <div>
                                <small>Memasak</small>
                                <strong>{{ $recipe->cook_time_minutes ?? 0 }} menit</strong>
                            </div>
                            <div>
                                <small>Porsi</small>
                                <strong>{{ $recipe->servings ?? '-' }} porsi</strong>
                            </div>
                            <div>
                                <small>Kesulitan</small>
                                <strong>{{ ucfirst((string) ($recipe->difficulty ?: 'Sedang')) }}</strong>
                            </div>
                        </div>
                    </div>

                    @if (! empty($recipe->video_url))
                        <div class="detail-panel" style="margin-bottom: 18px;">
                            <h2>Video Tutorial</h2>
                            @php
                                $embedUrl = $recipe->video_url;
                                if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $embedUrl, $m)) {
                                    $embedUrl = 'https://www.youtube.com/embed/'.$m[1];
                                }
                            @endphp
                            @if (str_contains($embedUrl, 'youtube.com/embed/'))
                                <div class="recipe-video-embed">
                                    <iframe src="{{ $embedUrl }}" title="Video Tutorial {{ $recipe->title }}" allowfullscreen loading="lazy"></iframe>
                                </div>
                            @else
                                <a href="{{ $recipe->video_url }}" target="_blank" rel="noopener noreferrer" class="btn-terracotta sm">Tonton Video Tutorial &rarr;</a>
                            @endif
                        </div>
                    @endif

                    <div class="detail-panel">
                        <h2>Langkah Memasak</h2>
                        <ol class="detail-steps-list">
                            @forelse ($recipe->recipeSteps as $step)
                                <li>{{ $step->instruction }}</li>
                            @empty
                                <li>Langkah memasak belum tersedia.</li>
                            @endforelse
                        </ol>
                    </div>
                </div>

                <aside class="detail-right-col">
                    <div class="detail-panel">
                        <h2>Bahan-Bahan</h2>
                        <ul style="padding-left: 18px; line-height: 1.75;">
                            @forelse ($recipe->recipeIngredients as $ri)
                                <li>
                                    {{ trim(($ri->amount ?? '').' '.($ri->unit ?? '').' '.($ri->ingredient?->name ?? 'Bahan')) }}
                                    @if (! empty($ri->notes))
                                        <small style="color: var(--muted);">({{ $ri->notes }})</small>
                                    @endif
                                </li>
                            @empty
                                <li>Belum ada daftar bahan.</li>
                            @endforelse
                        </ul>
                    </div>

                    @if ($recipe->user)
                        <div class="detail-panel">
                            <h3>Contributor</h3>
                            <p style="margin: 6px 0 0; font-size: 14px; font-weight: 600;">{{ $recipe->user->name }}</p>
                            <p style="margin: 2px 0 0; font-size: 12.5px; color: var(--muted);">
                                {{ $recipe->user->display_username }} &middot; Level {{ $recipe->user->contributor_level ?? 1 }}
                            </p>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            if (typeof window.initBaliReadingTracker === 'function') {
                window.initBaliReadingTracker('recipe', {{ (int) $recipe->id }});
            }
        })();
    </script>
@endpush
