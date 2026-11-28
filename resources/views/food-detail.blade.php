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
                                <span class="detail-rating-inline">
                                    <strong style="color: var(--star);">&#9733;</strong>
                                    {{ number_format((float) $averageRating, 1) }} / 5
                                    ({{ $reviewsCount }} ulasan)
                                </span>
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

                    {{-- Read-Only Synchronized Rating Summary (No Separate Rating Form) --}}
                    <div class="detail-panel rating-summary-card">
                        <h3 style="font-size: 18px; margin: 0 0 10px;">Ringkasan Rating</h3>
                        <div class="rating-summary-score">
                            <span class="rating-summary-value">{{ number_format((float) $averageRating, 1) }}</span>
                            <div>
                                <div class="rating-summary-stars" aria-label="Rata-rata {{ number_format((float) $averageRating, 1) }} dari 5 bintang">
                                    @php $filledStars = (int) round((float) $averageRating); @endphp
                                    @for ($i = 1; $i <= 5; $i++)
                                        <span class="summary-star {{ $i <= $filledStars ? 'is-filled' : '' }}">&#9733;</span>
                                    @endfor
                                </div>
                                <p class="rating-summary-count">
                                    {{ $reviewsCount > 0 ? $reviewsCount.' ulasan terverifikasi' : 'Belum ada ulasan' }}
                                </p>
                            </div>
                        </div>
                        <a href="#ulasan-section" class="btn-white" style="width: 100%; height: 38px; font-size: 12.5px; margin-top: 12px;">
                            {{ $userReview ? 'Kelola Ulasan Anda' : 'Tulis Ulasan & Rating' }} &darr;
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <section class="recipe-container" id="ulasan-section" style="padding-bottom: 48px;">
        <div class="detail-panel">
            <div class="review-section-head">
                <div>
                    <h2 style="margin: 0;">Ulasan ({{ $reviewsCount }})</h2>
                    <p style="margin: 4px 0 0; font-size: 13px; color: var(--muted);">
                        Rata-rata: <strong style="color: var(--text);">&#9733; {{ number_format((float) $averageRating, 1) }} / 5</strong>
                        dari {{ $reviewsCount }} ulasan
                    </p>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert-banner error" style="margin-top: 12px;">
                    <ul style="margin: 0; padding-left: 18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @auth
                @if (! $userReview)
                    @php
                        $oldRating = (int) old('rating', 0);
                        $oldReasons = (array) old('feedback_reasons', []);
                        $oldOther = (string) old('feedback_other', '');
                    @endphp
                    <form action="{{ route('reviews.store', $food['slug']) }}" method="POST" class="review-interactive-form" data-star-picker data-loading-form>
                        @csrf
                        <input type="hidden" name="rating" value="{{ $oldRating > 0 ? $oldRating : '' }}" data-rating-input>

                        <div class="review-star-field">
                            <label>Pilih Rating Anda</label>
                            <div class="review-star-row">
                                <div class="interactive-stars" role="radiogroup" aria-label="Pilih rating 1 sampai 5 bintang">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <button
                                            type="button"
                                            class="interactive-star-btn {{ $i <= $oldRating ? 'is-active' : '' }}"
                                            data-star-value="{{ $i }}"
                                            aria-label="Pilih {{ $i }} bintang"
                                            aria-pressed="{{ $i <= $oldRating ? 'true' : 'false' }}"
                                        >&#9733;</button>
                                    @endfor
                                </div>
                                <span class="selected-rating-badge" data-rating-label>
                                    {{ $oldRating > 0 ? "Dipilih: {$oldRating} / 5 Bintang" : 'Klik bintang untuk memilih rating' }}
                                </span>
                            </div>
                        </div>

                        {{-- Conditional Low-Rating Feedback (Shown automatically for 1 or 2 stars) --}}
                        <div class="low-rating-feedback-box {{ ($oldRating > 0 && $oldRating < 3) ? 'is-visible' : '' }}" data-low-rating-box>
                            <p class="low-rating-title">Apa kendala yang Anda alami? <span>(Wajib dipilih untuk rating di bawah 3 bintang)</span></p>
                            <div class="low-rating-reasons-grid">
                                @foreach ($lowRatingReasons as $reason)
                                    @php $isChecked = in_array($reason, $oldReasons, true); @endphp
                                    <label class="reason-chip {{ $isChecked ? 'is-checked' : '' }}">
                                        <input
                                            type="checkbox"
                                            name="feedback_reasons[]"
                                            value="{{ $reason }}"
                                            data-reason-checkbox
                                            @checked($isChecked)
                                        >
                                        <span>{{ $reason }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <div class="low-rating-other-wrap {{ in_array('Lainnya', $oldReasons, true) ? 'is-visible' : '' }}" data-other-wrap>
                                <label for="create-feedback-other">Jelaskan alasan lainnya</label>
                                <input
                                    type="text"
                                    id="create-feedback-other"
                                    name="feedback_other"
                                    value="{{ $oldOther }}"
                                    class="form-control"
                                    placeholder="Tuliskan kendala spesifik yang Anda alami..."
                                    data-other-input
                                >
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 14px;">
                            <label for="comment">Komentar (opsional)</label>
                            <textarea name="comment" id="comment" rows="3" class="form-control" placeholder="Bagikan pengalamanmu mencoba resep ini...">{{ old('comment') }}</textarea>
                        </div>

                        <button type="submit" class="btn-terracotta rect">Kirim Ulasan</button>
                    </form>
                @else
                    <div class="alert-banner success" style="margin: 14px 0 18px; font-size: 13px;">
                        Anda sudah memberikan ulasan (<strong>{{ $userReview->rating }}/5</strong>) untuk makanan ini. Gunakan tombol <strong>Edit Ulasan</strong> atau <strong>Hapus Ulasan</strong> pada ulasan Anda di bawah.
                    </div>
                @endif
            @else
                <p style="font-size: 13px; color: var(--muted); margin: 12px 0 16px;">
                    <a href="{{ route('login') }}" style="color: var(--terracotta); font-weight: 600;">Masuk</a> terlebih dahulu untuk memberikan rating dan ulasan.
                </p>
            @endauth

            <div class="reviews-feed-list">
                @forelse ($reviews as $review)
                    @php
                        $isOwner = auth()->check() && (int) $review->user_id === (int) auth()->id();
                        $editBagName = 'edit_review_'.$review->id;
                        $editBag = $errors->getBag($editBagName);
                        $editHasErrors = $editBag->any();
                        $editRating = $editHasErrors ? (int) old('rating', $review->rating) : (int) $review->rating;
                        $editReasons = $editHasErrors ? (array) old('feedback_reasons', []) : (array) ($review->feedback_reasons ?? []);
                        $editOther = $editHasErrors ? (string) old('feedback_other', '') : (string) ($review->feedback_other ?? '');
                        $editComment = $editHasErrors ? (string) old('comment', '') : (string) ($review->comment ?? '');
                    @endphp
                    <article class="ulasan-feed-item review-card-item {{ $isOwner ? 'is-own-review' : '' }}">
                        <div class="review-card-top">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <strong>{{ $review->user->name ?? 'Pengguna' }}</strong>
                                @if ($isOwner)
                                    <span class="pill-tag" style="font-size: 11px; padding: 2px 8px;">Ulasan Anda</span>
                                @endif
                            </div>
                            <span style="font-size: 11.5px; color: var(--muted);">{{ $review->updated_at?->format('d/m/Y') ?? $review->created_at?->format('d/m/Y') }}</span>
                        </div>

                        <div class="review-card-stars" aria-label="Rating {{ $review->rating }} dari 5">
                            @for ($i = 1; $i <= 5; $i++)
                                <span class="summary-star {{ $i <= (int) $review->rating ? 'is-filled' : '' }}">&#9733;</span>
                            @endfor
                            <span class="review-card-score">{{ $review->rating }}/5</span>
                        </div>

                        @if (! empty($review->feedback_reasons) && is_array($review->feedback_reasons))
                            <div class="review-reasons-display">
                                @foreach ($review->feedback_reasons as $rLabel)
                                    <span class="review-reason-badge">{{ $rLabel }}</span>
                                @endforeach
                            </div>
                        @endif

                        @if (! empty($review->feedback_other))
                            <p class="review-other-note"><strong>Catatan kendala:</strong> {{ $review->feedback_other }}</p>
                        @endif

                        @if ($review->comment)
                            <p class="review-comment-body">{{ $review->comment }}</p>
                        @endif

                        @if ($isOwner)
                            <div class="review-owner-actions">
                                <button type="button" class="pill-tag" data-toggle-edit-review="{{ $review->id }}">
                                    Edit Ulasan
                                </button>
                                <form action="{{ route('reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus ulasan Anda? Rata-rata rating akan diperbarui otomatis.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn reject">Hapus Ulasan</button>
                                </form>
                            </div>

                            {{-- Inline Owner Edit Review Form --}}
                            <div class="review-edit-panel {{ $editHasErrors ? 'is-open' : '' }}" id="edit-review-panel-{{ $review->id }}">
                                @if ($editHasErrors)
                                    <div class="alert-banner error" style="margin-bottom: 12px;">
                                        <ul style="margin: 0; padding-left: 18px;">
                                            @foreach ($editBag->all() as $err)
                                                <li>{{ $err }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <form action="{{ route('reviews.update', $review) }}" method="POST" class="review-interactive-form" data-star-picker data-loading-form>
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="rating" value="{{ $editRating }}" data-rating-input>

                                    <div class="review-star-field">
                                        <label>Ubah Rating</label>
                                        <div class="review-star-row">
                                            <div class="interactive-stars" role="radiogroup" aria-label="Ubah rating 1 sampai 5 bintang">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <button
                                                        type="button"
                                                        class="interactive-star-btn {{ $i <= $editRating ? 'is-active' : '' }}"
                                                        data-star-value="{{ $i }}"
                                                        aria-label="Pilih {{ $i }} bintang"
                                                        aria-pressed="{{ $i <= $editRating ? 'true' : 'false' }}"
                                                    >&#9733;</button>
                                                @endfor
                                            </div>
                                            <span class="selected-rating-badge" data-rating-label>
                                                Dipilih: {{ $editRating }} / 5 Bintang
                                            </span>
                                        </div>
                                    </div>

                                    <div class="low-rating-feedback-box {{ $editRating < 3 ? 'is-visible' : '' }}" data-low-rating-box>
                                        <p class="low-rating-title">Alasan rating di bawah 3 bintang <span>(Wajib diisi)</span></p>
                                        <div class="low-rating-reasons-grid">
                                            @foreach ($lowRatingReasons as $reason)
                                                @php $checkedEdit = in_array($reason, $editReasons, true); @endphp
                                                <label class="reason-chip {{ $checkedEdit ? 'is-checked' : '' }}">
                                                    <input
                                                        type="checkbox"
                                                        name="feedback_reasons[]"
                                                        value="{{ $reason }}"
                                                        data-reason-checkbox
                                                        @checked($checkedEdit)
                                                    >
                                                    <span>{{ $reason }}</span>
                                                </label>
                                            @endforeach
                                        </div>

                                        <div class="low-rating-other-wrap {{ in_array('Lainnya', $editReasons, true) ? 'is-visible' : '' }}" data-other-wrap>
                                            <label for="edit-feedback-other-{{ $review->id }}">Jelaskan alasan lainnya</label>
                                            <input
                                                type="text"
                                                id="edit-feedback-other-{{ $review->id }}"
                                                name="feedback_other"
                                                value="{{ $editOther }}"
                                                class="form-control"
                                                placeholder="Tuliskan kendala spesifik yang Anda alami..."
                                                data-other-input
                                            >
                                        </div>
                                    </div>

                                    <div class="form-group" style="margin-top: 12px;">
                                        <label for="edit-comment-{{ $review->id }}">Komentar (opsional)</label>
                                        <textarea name="comment" id="edit-comment-{{ $review->id }}" rows="3" class="form-control">{{ $editComment }}</textarea>
                                    </div>

                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                        <button type="submit" class="btn-terracotta sm">Simpan Perubahan</button>
                                        <button type="button" class="btn-white" style="height: 36px;" data-toggle-edit-review="{{ $review->id }}">Batal</button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </article>
                @empty
                    <p style="font-size: 13px; color: var(--muted); margin: 8px 0 0;">Belum ada ulasan tersimpan untuk makanan ini. Jadilah yang pertama memberikan ulasan!</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            function initStarPicker(form) {
                const ratingInput = form.querySelector('[data-rating-input]');
                const ratingLabel = form.querySelector('[data-rating-label]');
                const starBtns = Array.from(form.querySelectorAll('[data-star-value]'));
                const lowBox = form.querySelector('[data-low-rating-box]');
                const checkboxes = Array.from(form.querySelectorAll('[data-reason-checkbox]'));
                const otherWrap = form.querySelector('[data-other-wrap]');
                const otherInput = form.querySelector('[data-other-input]');

                let currentRating = Number(ratingInput ? ratingInput.value : 0) || 0;

                function syncLowRatingUI(val) {
                    const isLow = val > 0 && val < 3;
                    if (lowBox) {
                        lowBox.classList.toggle('is-visible', isLow);
                    }
                    syncOtherField(isLow);
                }

                function syncOtherField(isLow) {
                    const otherChecked = checkboxes.some(function (cb) {
                        return cb.value === 'Lainnya' && cb.checked;
                    });
                    const showOther = isLow && otherChecked;
                    if (otherWrap) {
                        otherWrap.classList.toggle('is-visible', showOther);
                    }
                    if (otherInput) {
                        otherInput.required = showOther;
                    }
                }

                function paint(val, isPreview) {
                    starBtns.forEach(function (btn) {
                        const num = Number(btn.getAttribute('data-star-value'));
                        btn.classList.toggle('is-active', !isPreview && num <= val);
                        btn.classList.toggle('is-preview', isPreview && num <= val);
                        if (!isPreview) {
                            btn.setAttribute('aria-pressed', num <= val ? 'true' : 'false');
                        }
                    });
                }

                starBtns.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        currentRating = Number(btn.getAttribute('data-star-value'));
                        if (ratingInput) {
                            ratingInput.value = String(currentRating);
                        }
                        if (ratingLabel) {
                            ratingLabel.textContent = 'Dipilih: ' + currentRating + ' / 5 Bintang';
                        }
                        paint(currentRating, false);
                        syncLowRatingUI(currentRating);
                    });

                    btn.addEventListener('mouseenter', function () {
                        paint(Number(btn.getAttribute('data-star-value')), true);
                    });

                    btn.addEventListener('mouseleave', function () {
                        paint(currentRating, false);
                    });
                });

                checkboxes.forEach(function (cb) {
                    cb.addEventListener('change', function () {
                        const chip = cb.closest('.reason-chip');
                        if (chip) {
                            chip.classList.toggle('is-checked', cb.checked);
                        }
                        syncOtherField(currentRating > 0 && currentRating < 3);
                    });
                });

                form.addEventListener('submit', function (event) {
                    if (currentRating < 1 || currentRating > 5) {
                        event.preventDefault();
                        if (ratingLabel) {
                            ratingLabel.textContent = 'Silakan pilih 1 sampai 5 bintang terlebih dahulu!';
                            ratingLabel.classList.add('is-error');
                        }
                        return;
                    }
                    if (currentRating < 3) {
                        const anyReason = checkboxes.some(function (cb) { return cb.checked; });
                        if (!anyReason) {
                            event.preventDefault();
                            if (lowBox) {
                                lowBox.classList.add('is-visible');
                                lowBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                            alert('Untuk rating di bawah 3 bintang, mohon pilih minimal satu alasan feedback.');
                            return;
                        }
                    }
                });

                paint(currentRating, false);
                syncLowRatingUI(currentRating);
            }

            document.querySelectorAll('[data-star-picker]').forEach(initStarPicker);

            document.querySelectorAll('[data-toggle-edit-review]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const id = btn.getAttribute('data-toggle-edit-review');
                    const panel = document.getElementById('edit-review-panel-' + id);
                    if (panel) {
                        panel.classList.toggle('is-open');
                    }
                });
            });

            const videoBox = document.getElementById('sample-video-box');
            const videoLabel = document.getElementById('sample-video-label');

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
