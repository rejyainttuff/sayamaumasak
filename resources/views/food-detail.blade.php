@extends('layouts.app')

@section('title', $food['name'].' — Detail Resep | Bali Culinary')

@section('content')
    @php
        $titleParts = explode(' ', $food['name']);
        $lastWord = count($titleParts) > 1 ? array_pop($titleParts) : '';
        $firstWords = implode(' ', $titleParts);
        $groups = $food['ingredient_groups'] ?? ['Bahan Utama' => $food['ingredients']];
    @endphp

    <section class="detail-page-wrap">
        <div class="recipe-container">
            @if (session('success'))
                <div class="alert-banner success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert-banner error">{{ session('error') }}</div>
            @endif

            <div class="detail-bento">
                <div class="detail-left-col">
                    <div class="detail-hero-split">
                        <div class="detail-sample-image">
                            @if (file_exists(public_path($food['image'])))
                                <img src="{{ asset($food['image']) }}" alt="{{ $food['name'] }}">
                            @else
                                <span class="detail-sample-label">Sample<br>gambar</span>
                            @endif

                            @auth
                                <form action="{{ route('favorites.toggle', $food['slug']) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="recipe-card-heart {{ ! empty($isFavorited) ? 'is-saved' : '' }}"
                                        aria-label="Simpan {{ $food['name'] }} ke favorit"
                                        title="Simpan ke Favorit"
                                    >
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                                        </svg>
                                    </button>
                                </form>
                            @else
                                <a
                                    href="{{ route('login') }}"
                                    class="recipe-card-heart"
                                    aria-label="Login untuk menyimpan {{ $food['name'] }} ke favorit"
                                    title="Login untuk Simpan ke Favorit"
                                >
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                                    </svg>
                                </a>
                            @endauth
                        </div>

                        <div class="detail-info-col">
                            <div class="detail-pill-row">
                                <a href="{{ route('explore') }}" class="pill-tag">&larr; Kembali</a>

                                @auth
                                    <form action="{{ route('favorites.toggle', $food['slug']) }}" method="POST" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="pill-tag">
                                            <span>&#9829;</span>
                                            <span>{{ ! empty($isFavorited) ? 'Tersimpan di Favorit' : 'Simpan Resep' }}</span>
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('login') }}" class="pill-tag">
                                        <span>&#9829;</span>
                                        <span>Simpan Resep</span>
                                    </a>
                                @endauth
                            </div>

                            <h1 class="detail-title">
                                {{ $firstWords }}@if ($lastWord !== '') <span class="accent">{{ $lastWord }}</span>@endif
                            </h1>

                            <p class="detail-subtitle">{{ $food['description'] }}</p>

                            <div class="detail-meta-inline">
                                <span><strong style="color: var(--star);">&#9733;</strong> {{ number_format($food['rating'], 1) }} ({{ $food['reviews_count'] }})</span>
                                <span>&#9201; {{ $food['cook_time'] }}</span>
                                <span style="color: var(--terracotta);">{{ $food['region'] }}</span>
                            </div>

                            <div class="detail-history-box">
                                <h3>Sejarah Singkat</h3>
                                <p>{{ $food['history'] }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="detail-video-box" id="sample-video-box" role="button" tabindex="0" aria-label="Putar preview video memasak">
                        <div class="detail-video-play">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                <polygon points="6,4 20,12 6,20" />
                            </svg>
                        </div>
                        <span id="sample-video-label">Sample video</span>
                    </div>

                    <div class="detail-panel">
                        <h2>Langkah-Langkah</h2>
                        <ol class="detail-steps-list">
                            @foreach ($food['steps'] as $step)
                                <li>{{ $step }}</li>
                            @endforeach
                        </ol>
                    </div>
                </div>

                <aside class="detail-right-col">
                    <div class="detail-panel">
                        <h2>Bahan-Bahan</h2>
                        @foreach ($groups as $groupTitle => $items)
                            <div class="ingredient-group">
                                <h4>{{ $groupTitle }}</h4>
                                <ul>
                                    @foreach ($items as $ingredient)
                                        <li>{{ $ingredient }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>

                    <div class="detail-panel">
                        <h3 style="font-size: 19px; margin-bottom: 8px;">Beri Rating</h3>
                        @auth
                            <form action="{{ route('reviews.store', $food['slug']) }}" method="POST">
                                @csrf
                                <input type="hidden" name="rating" id="sidebar-rating-input" value="5">
                                <div class="rating-stars" id="rating-stars" role="radiogroup" aria-label="Beri rating resep">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <button
                                            type="button"
                                            class="rating-star-btn active"
                                            data-value="{{ $i }}"
                                            aria-label="Rating {{ $i }} bintang"
                                        >&#9733;</button>
                                    @endfor
                                </div>
                                <button type="submit" class="btn-terracotta rect" style="width: 100%; height: 36px; font-size: 12.5px;">
                                    Kirim Rating
                                </button>
                            </form>
                        @else
                            <div class="rating-stars" id="rating-stars" role="radiogroup" aria-label="Beri rating resep">
                                @for ($i = 1; $i <= 5; $i++)
                                    <button
                                        type="button"
                                        class="rating-star-btn {{ $i === 1 ? 'active' : '' }}"
                                        data-value="{{ $i }}"
                                        aria-label="Rating {{ $i }} bintang"
                                    >&#9733;</button>
                                @endfor
                            </div>
                            <a href="{{ route('login') }}" class="btn-terracotta rect" style="width: 100%; height: 36px; font-size: 12.5px;">
                                Masuk untuk Beri Rating
                            </a>
                        @endauth
                    </div>
                </aside>
            </div>
        </div>
    </section>
    <section class="recipe-container" style="padding-bottom: 48px;">
        <div class="detail-panel">
            <h2 style="margin-bottom: 14px;">Ulasan ({{ $reviewsCount }}) &mdash; &#9733; {{ number_format((float) $averageRating, 1) }} / 5</h2>

            @if ($errors->any())
                <div class="alert-banner error">
                    <ul style="margin: 0; padding-left: 18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @auth
                <form action="{{ route('reviews.store', $food['slug']) }}" method="POST" style="margin-bottom: 20px;">
                    @csrf
                    <div class="form-grid-2" style="margin-bottom: 12px;">
                        <div class="form-group">
                            <label for="rating">Rating (1 - 5)</label>
                            <select name="rating" id="rating" class="form-control" required>
                                <option value="">Pilih rating</option>
                                @for ($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}" @selected((string) old('rating') === (string) $i)>
                                        {{ $i }} Bintang
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="comment">Komentar (opsional)</label>
                            <textarea name="comment" id="comment" rows="2" class="form-control" style="min-height: 44px;" placeholder="Tulis pengalamanmu mencoba resep ini...">{{ old('comment') }}</textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn-terracotta rect">Kirim Ulasan</button>
                </form>
            @else
                <p style="font-size: 13px; color: var(--muted); margin: 0 0 16px;">
                    <a href="{{ route('login') }}" style="color: var(--terracotta); font-weight: 600;">Masuk</a> terlebih dahulu untuk menulis ulasan.
                </p>
            @endauth

            @forelse ($reviews as $review)
                <div class="ulasan-feed-item" style="padding: 12px 0;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                        <strong>{{ $review->user->name ?? 'Pengguna' }}</strong>
                        <span style="font-size: 11.5px; color: var(--muted);">{{ $review->created_at?->format('d/m/Y') }}</span>
                    </div>
                    <div style="color: var(--star); font-size: 12.5px;">&#9733; {{ $review->rating }}/5</div>
                    @if ($review->comment)
                        <p>{{ $review->comment }}</p>
                    @endif
                </div>
            @empty
                <p style="font-size: 13px; color: var(--muted); margin: 0;">Belum ada ulasan tersimpan untuk makanan ini.</p>
            @endforelse
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            const stars = document.querySelectorAll('#rating-stars .rating-star-btn');
            const ratingInput = document.getElementById('sidebar-rating-input');
            const videoBox = document.getElementById('sample-video-box');
            const videoLabel = document.getElementById('sample-video-label');
            let selectedRating = ratingInput ? Number(ratingInput.value) : 1;

            function paintStars(val) {
                stars.forEach(function (btn) {
                    const num = Number(btn.getAttribute('data-value'));
                    btn.classList.toggle('active', num <= val);
                });
            }

            stars.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    selectedRating = Number(btn.getAttribute('data-value'));
                    if (ratingInput) {
                        ratingInput.value = String(selectedRating);
                    }
                    paintStars(selectedRating);
                });
            });

            if (videoBox && videoLabel) {
                videoBox.addEventListener('click', function () {
                    videoLabel.textContent = 'Preview video tutorial memasak {{ $food['name'] }}';
                });
            }

            if (typeof window.initBaliReadingTracker === 'function' && {{ (int) ($food['id'] ?? 0) }} > 0) {
                window.initBaliReadingTracker('food', {{ (int) ($food['id'] ?? 0) }});
            }
        })();
    </script>
@endpush
