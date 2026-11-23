<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SayaMauMasak — Dari Rasa Ke Hati')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('Assets/css/recipes.css') }}">
</head>

<body class="recipe-page">
    <nav class="recipe-nav">
        <div class="recipe-nav-inner">
            <a href="{{ route('home') }}" class="recipe-brand">SayaMau<span>Masak</span></a>

            <div class="recipe-nav-links">
                <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">Tentang Kami</a>
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Beranda</a>
                <a href="{{ route('explore') }}" class="{{ request()->routeIs('explore') ? 'active' : '' }}">Jelajah</a>
                <a href="{{ route('recipe') }}" class="{{ request()->routeIs('recipe', 'recipe.create') ? 'active' : '' }}">Aneka Resep</a>
                <a href="{{ route('stories') }}" class="{{ request()->routeIs('stories') ? 'active' : '' }}">Cerita</a>
            </div>

            <div class="recipe-nav-right">
                <form action="{{ route('explore') }}" method="GET" class="recipe-nav-search">
                    <button type="submit" aria-label="Cari resep">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <circle cx="11" cy="11" r="7" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg>
                    </button>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari resep Bali..." aria-label="Cari resep Bali">
                </form>

                <a href="{{ route('recipe.create') }}" class="recipe-icon-btn {{ request()->routeIs('recipe.create') ? 'active' : '' }}" title="Bagikan Resep">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                </a>

                <a href="{{ route('favorites') }}" class="recipe-icon-btn {{ request()->routeIs('favorites') ? 'active' : '' }}" title="Resep Tersimpan">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                    </svg>
                    <span class="recipe-fav-count" id="nav-fav-count">0</span>
                </a>

                @auth
                    @if (in_array(auth()->user()->role, ['admin', 'helper'], true))
                        <a href="{{ route('recipes.index') }}">Kelola Resep</a>
                    @endif

                    @if (auth()->user()->role === 'admin')
                        <a href="{{ route('foods.index') }}">Kelola Makanan</a>
                    @endif

                    <span>{{ auth()->user()->name }} ({{ auth()->user()->role }})</span>

                    <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit">Keluar</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Masuk</a>
                @endauth

            </div>
        </div>
    </nav>

    <main class="recipe-main">
        @yield('content')
    </main>

    <div class="footer-wave-wrap" aria-hidden="true">
        <svg viewBox="0 0 1440 80" preserveAspectRatio="none">
            <path d="M0,45 C320,90 1040,0 1440,42 L1440,80 L0,80 Z" fill="#f6e7cd" />
            <path d="M0,58 C360,92 1080,20 1440,54 L1440,80 L0,80 Z" fill="#899477" />
        </svg>
    </div>

    <footer class="site-footer">
        <div class="site-footer-inner">
            <div>
                <h2 class="site-footer-brand">SayaMauMasak</h2>
                <p>Dari Rasa Ke Hati — melestarikan warisan kuliner tradisional Bali melalui resep autentik dan cerita dapur Nusantara.</p>
            </div>
            <div class="site-footer-links">
                <a href="{{ route('home') }}">Beranda</a>
                <a href="{{ route('explore') }}">Jelajah Resep</a>
                <a href="{{ route('recipe') }}">Aneka Resep</a>
                <a href="{{ route('recipe.create') }}">Bagikan Resep</a>
                <a href="{{ route('favorites') }}">Favorit</a>
                <a href="{{ route('stories') }}">Cerita</a>
                <a href="{{ route('about') }}">Tentang Kami</a>
            </div>
        </div>
        <div class="site-footer-bottom">
            &copy; {{ date('Y') }} SayaMauMasak. Kuliner Tradisional Bali.
        </div>
    </footer>

    <script>
        (function () {
            const STORAGE_KEY = 'sayamaumasak_favorites';

            function getFavorites() {
                try {
                    const raw = localStorage.getItem(STORAGE_KEY);
                    const parsed = raw ? JSON.parse(raw) : [];
                    return Array.isArray(parsed) ? parsed : [];
                } catch (e) {
                    return [];
                }
            }

            function saveFavorites(list) {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
            }

            function syncUI() {
                const favs = getFavorites();
                const badge = document.getElementById('nav-fav-count');
                if (badge) {
                    badge.textContent = favs.length;
                    badge.classList.toggle('has-items', favs.length > 0);
                }

                document.querySelectorAll('.js-favorite-toggle').forEach(function (btn) {
                    const slug = btn.getAttribute('data-slug');
                    const isSaved = favs.includes(slug);
                    btn.classList.toggle('is-saved', isSaved);
                    btn.setAttribute('aria-pressed', isSaved ? 'true' : 'false');

                    const label = btn.querySelector('.js-fav-label');
                    if (label) {
                        label.textContent = isSaved ? 'Tersimpan di Favorit' : 'Simpan Resep';
                    }
                });

                window.dispatchEvent(new CustomEvent('favorites:updated', { detail: { favs: favs } }));
            }

            document.addEventListener('click', function (event) {
                const btn = event.target.closest('.js-favorite-toggle');
                if (!btn) {
                    return;
                }
                event.preventDefault();
                const slug = btn.getAttribute('data-slug');
                if (!slug) {
                    return;
                }

                const favs = getFavorites();
                const idx = favs.indexOf(slug);
                if (idx >= 0) {
                    favs.splice(idx, 1);
                } else {
                    favs.push(slug);
                }
                saveFavorites(favs);
                syncUI();
            });

            syncUI();
        })();
    </script>

    @stack('scripts')
</body>

</html>
