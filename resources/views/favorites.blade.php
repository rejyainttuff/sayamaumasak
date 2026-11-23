@extends('layouts.app')

@section('title', 'Resep Favorit — SayaMauMasak')

@section('content')
    <header class="recipe-hero">
        <div class="recipe-hero-inner">
            <div class="recipe-hero-content">
                <a href="{{ route('explore') }}" class="recipe-back-pill">&larr; Kembali ke Jelajah</a>
                <h1>Resep <span class="accent">Tersimpan</span></h1>
                <p>Kumpulan resep kuliner Bali yang kamu simpan untuk dicoba kembali kapan saja.</p>
            </div>
        </div>
    </header>

    <section class="section-block">
        <div class="recipe-container">
            <div class="section-header">
                <div class="section-title-group">
                    <span class="starburst-badge" aria-hidden="true">&#9829;</span>
                    <h2 class="section-title">Favorit Saya</h2>
                </div>
                <a href="{{ route('explore') }}" class="btn-terracotta">Jelajahi Resep Lain &rarr;</a>
            </div>

            <div class="recipe-grid cols-3" id="favorites-grid">
                @foreach ($foods as $food)
                    @include('partials.recipe-card', ['food' => $food])
                @endforeach
            </div>

            <div class="recipe-empty" id="favorites-empty" style="display: none;">
                <h3>Belum ada resep favorit</h3>
                <p>Klik ikon hati pada kartu resep atau tombol "Simpan Resep" di halaman detail untuk menyimpannya di sini.</p>
                <a href="{{ route('explore') }}" class="btn-terracotta">Mulai Jelajahi Resep</a>
            </div>
        </div>
    </section>
@endsection

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
                const raw = localStorage.getItem('sayamaumasak_favorites');
                const parsed = raw ? JSON.parse(raw) : [];
                filterFavorites(Array.isArray(parsed) ? parsed : []);
            } catch (e) {
                filterFavorites([]);
            }
        })();
    </script>
@endpush
