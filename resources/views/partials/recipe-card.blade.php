<article class="recipe-card" data-food-slug="{{ $food['slug'] }}">
    <div class="recipe-card-media">
        @if (file_exists(public_path($food['image'])))
            <img src="{{ asset($food['image']) }}" alt="{{ $food['name'] }}">
        @else
            <span class="recipe-card-placeholder">{{ $food['name'] }}</span>
        @endif

        <button
            type="button"
            class="recipe-card-heart js-favorite-toggle"
            data-slug="{{ $food['slug'] }}"
            aria-label="Simpan {{ $food['name'] }} ke favorit"
            title="Simpan ke Favorit"
        >
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
            </svg>
        </button>
    </div>

    <div class="recipe-card-body">
        <h3 class="recipe-card-title">
            <a href="{{ route('food.detail', $food['slug']) }}">{{ $food['name'] }}</a>
        </h3>

        <div class="recipe-card-footer">
            <span class="recipe-rating">
                <span class="star">&#9733;</span>
                <span>{{ number_format($food['rating'], 1) }}</span>
            </span>

            <a
                href="{{ route('food.detail', $food['slug']) }}"
                class="recipe-arrow-btn"
                aria-label="Lihat detail {{ $food['name'] }}"
                title="Lihat Detail"
            >&rarr;</a>
        </div>
    </div>
</article>
