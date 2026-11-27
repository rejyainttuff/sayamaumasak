@extends('layouts.app')

@section('title', 'Resep Favorit — Bali Culinary')

@section('content')
    <header class="bali-hero hero-dashboard">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <h1>Resep <span class="accent">Favorit</span></h1>
                <p>Daftar resep masakan tradisional Bali yang telah kamu simpan untuk dimasak kembali kapan saja.</p>
                <div class="bali-hero-actions">
                    <a href="{{ route('account') }}" class="pill-tag">&larr; Kembali ke Akun</a>
                </div>
            </div>
        </div>
    </header>

    <section class="home-section">
        <div class="recipe-container">
            @if (session('success'))
                <div class="alert-banner success">{{ session('success') }}</div>
            @endif

            @auth
                @if (count($foods) > 0)
                    <div class="recipe-grid cols-3">
                        @foreach ($foods as $food)
                            @include('partials.recipe-card', ['food' => $food])
                        @endforeach
                    </div>
                @else
                    <div class="recipe-empty">
                        <h3 style="font-family: 'Playfair Display', serif; color: var(--terracotta); margin: 0 0 8px;">Belum ada resep favorit</h3>
                        <p style="font-size: 13px; color: var(--muted); margin: 0 0 16px;">Buka halaman detail resep dan klik tombol "Simpan Resep" untuk menyimpannya di akunmu.</p>
                        <a href="{{ route('explore') }}" class="btn-terracotta">Jelajahi Resep</a>
                    </div>
                @endif
            @else
                <div class="recipe-grid cols-3" id="favorites-grid">
                    @foreach ($foods as $food)
                        @include('partials.recipe-card', ['food' => $food])
                    @endforeach
                </div>

                <div class="recipe-empty" id="favorites-empty" style="display: none;">
                    <h3 style="font-family: 'Playfair Display', serif; color: var(--terracotta); margin: 0 0 8px;">Belum ada resep favorit</h3>
                    <p style="font-size: 13px; color: var(--muted); margin: 0 0 16px;">Klik ikon hati pada kartu resep atau tombol "Simpan Resep" di halaman detail untuk menyimpannya di sini.</p>
                    <a href="{{ route('explore') }}" class="btn-terracotta">Jelajahi Resep</a>
                </div>
            @endauth
        </div>
    </section>
@endsection

@guest
    @push('scripts')
        <script>
            (function () {
                function filterFavorites(favs) {
                    const grid = document.getElementById('favorites-grid');
                    const empty = document.getElementById('favorites-empty');
                    if (!grid || !empty) {
                        return;
                    }

                    let visibleCount = 0;
                    grid.querySelectorAll('[data-food-slug]').forEach(function (card) {
                        const slug = card.getAttribute('data-food-slug');
                        const show = favs.includes(slug);
                        card.style.display = show ? '' : 'none';
                        if (show) {
                            visibleCount++;
                        }
                    });

                    grid.style.display = visibleCount > 0 ? '' : 'none';
                    empty.style.display = visibleCount > 0 ? 'none' : '';
                }

                window.addEventListener('favorites:updated', function (e) {
                    filterFavorites(e.detail.favs || []);
                });

                try {
                    const raw = localStorage.getItem('baliculinary_favorites');
                    const parsed = raw ? JSON.parse(raw) : [];
                    filterFavorites(Array.isArray(parsed) ? parsed : []);
                } catch (e) {
                    filterFavorites([]);
                }
            })();
        </script>
    @endpush
@endguest
