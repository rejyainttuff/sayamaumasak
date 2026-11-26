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

            <div class="detail-bento">
                <div class="detail-left-col">
                    <div class="detail-hero-split">
                        <div class="detail-sample-image">
                            @if (file_exists(public_path($food['image'])))
                                <img src="{{ asset($food['image']) }}" alt="{{ $food['name'] }}">
                            @else
                                <span class="detail-sample-label">Sample<br>gambar</span>
                            @endif

                            <button
                                type="button"
                                class="recipe-card-heart js-favorite-toggle"
                                data-slug="{{ $food['slug'] }}"
                                aria-label="Simpan {{ $food['name'] }} ke favorit"
                                title="Simpan ke Favorit"
                            >
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                                </svg>
                            </button>
                        </div>

                        <div class="detail-info-col">
                            <div class="detail-pill-row">
                                <a href="{{ route('explore') }}" class="pill-tag">&larr; Kembali</a>
                                <button type="button" class="pill-tag js-favorite-toggle" data-slug="{{ $food['slug'] }}">
                                    <span>&#9829;</span>
                                    <span class="js-fav-label">Simpan Resep</span>
                                </button>
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
                        <button type="button" id="submit-rating-btn" class="btn-terracotta rect" style="width: 100%; height: 36px; font-size: 12.5px;">
                            Kirim Rating
                        </button>
                        <div class="rating-feedback" id="rating-feedback" aria-live="polite"></div>
                    </div>
                </aside>
            </div>
        </div>

        <img src="{{ asset('Assets/images/decor-hibiscus.png') }}" alt="" class="corner-hibiscus bottom-right" aria-hidden="true">
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            const stars = document.querySelectorAll('#rating-stars .rating-star-btn');
            const submitBtn = document.getElementById('submit-rating-btn');
            const feedback = document.getElementById('rating-feedback');
            const videoBox = document.getElementById('sample-video-box');
            const videoLabel = document.getElementById('sample-video-label');
            let selectedRating = 1;

            function paintStars(val) {
                stars.forEach(function (btn) {
                    const num = Number(btn.getAttribute('data-value'));
                    btn.classList.toggle('active', num <= val);
                });
            }

            stars.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    selectedRating = Number(btn.getAttribute('data-value'));
                    paintStars(selectedRating);
                });
            });

            if (submitBtn && feedback) {
                submitBtn.addEventListener('click', function () {
                    feedback.textContent = 'Terima kasih! Kamu memberi ' + selectedRating + ' bintang untuk resep ini.';
                });
            }

            if (videoBox && videoLabel) {
                videoBox.addEventListener('click', function () {
                    videoLabel.textContent = 'Preview video tutorial memasak {{ $food['name'] }}';
                });
            }
        })();
    </script>
@endpush
