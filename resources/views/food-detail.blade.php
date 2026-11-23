@extends('layouts.app')

@section('title', $food['name'].' — Detail Resep | SayaMauMasak')

@section('content')
    @php
        $titleParts = explode(' ', $food['name']);
        $lastWord = count($titleParts) > 1 ? array_pop($titleParts) : '';
        $firstWords = implode(' ', $titleParts);
    @endphp

    <header class="recipe-hero">
        <div class="recipe-hero-inner">
            <div class="recipe-hero-content">
                <a href="{{ route('explore') }}" class="recipe-back-pill">&larr; Kembali</a>

                <h1>
                    {{ $firstWords }}@if ($lastWord !== '') <span class="accent">{{ $lastWord }}</span>@endif
                </h1>
                <p>{{ $food['description'] }}</p>

                <div class="recipe-hero-meta">
                    <span class="meta-item">
                        <span style="color: var(--star);">&#9733;</span>
                        <span>{{ number_format($food['rating'], 1) }} ({{ $food['reviews_count'] }} ulasan)</span>
                    </span>
                    <span class="meta-item">
                        <span>&#9201;</span>
                        <span>{{ $food['cook_time'] }}</span>
                    </span>
                    <span class="recipe-badge">{{ $food['region'] }} &middot; {{ $food['difficulty'] }}</span>
                </div>

                <div class="recipe-hero-actions">
                    <button
                        type="button"
                        class="btn-terracotta rect js-favorite-toggle"
                        data-slug="{{ $food['slug'] }}"
                    >
                        <span>&#9829;</span>
                        <span class="js-fav-label">Simpan Resep</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <section class="recipe-container">
        @if (session('success'))
            <div class="alert-banner success">
                {{ session('success') }}
            </div>
        @endif

        <div class="detail-top-grid">
            <div class="detail-card">
                <h2>Sejarah Singkat</h2>
                <p>{{ $food['history'] }}</p>
            </div>

            <div class="detail-card">
                <h3>Foto Sajian</h3>
                <div class="detail-photo-box">
                    @if (file_exists(public_path($food['image'])))
                        <img src="{{ asset($food['image']) }}" alt="{{ $food['name'] }}">
                    @else
                        <span class="recipe-card-placeholder" style="font-size: 20px;">{{ $food['name'] }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="detail-recipe-sheet">
            <div class="detail-sheet-col">
                <h2>Bahan-bahan</h2>
                <ul class="ingredient-list">
                    @foreach ($food['ingredients'] as $ingredient)
                        <li>{{ $ingredient }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="detail-sheet-col">
                <h2>Langkah-langkah</h2>
                <ol class="step-list">
                    @foreach ($food['steps'] as $index => $step)
                        <li>
                            <span class="rank-circle">{{ $index + 1 }}</span>
                            <span>{{ $step }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>

        <div class="detail-bottom-grid">
            <div class="detail-outlined-card">
                <h3>Catatan</h3>
                <p>{{ $food['notes'] }}</p>
            </div>

            <div class="detail-outlined-card">
                <h3>Beri Rating</h3>
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
                <button type="button" id="submit-rating-btn" class="btn-terracotta rect" style="width: 100%; height: 38px;">
                    Kirim Rating
                </button>
                <div class="rating-feedback" id="rating-feedback" aria-live="polite"></div>
            </div>
        </div>

        <div class="section-divider" style="margin-top: 46px;"></div>

        <section class="section-block" style="padding-top: 8px; padding-bottom: 0;">
            <div class="section-header">
                <h2 class="section-title">Resep Lainnya</h2>
                <a href="{{ route('explore') }}" class="btn-terracotta">Lihat Semua &rarr;</a>
            </div>

            <div class="recipe-grid cols-3">
                @foreach ($relatedFoods as $related)
                    @include('partials.recipe-card', ['food' => $related])
                @endforeach
            </div>
        </section>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            const stars = document.querySelectorAll('#rating-stars .rating-star-btn');
            const submitBtn = document.getElementById('submit-rating-btn');
            const feedback = document.getElementById('rating-feedback');
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
        })();
    </script>
@endpush