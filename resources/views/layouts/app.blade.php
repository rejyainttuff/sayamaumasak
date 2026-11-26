<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Bali Culinary — Dari Rasa Ke Hati')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('Assets/css/recipes.css') }}?v={{ filemtime(public_path('Assets/css/recipes.css')) }}">
</head>

<body class="recipe-page">
    <nav class="recipe-nav">
        <div class="recipe-nav-inner">
            <a href="{{ route('home') }}" class="bali-brand">Bali <span>Culinary</span></a>

            <div class="recipe-nav-links" id="main-nav-links">
                <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">Tentang Kami</a>
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Beranda</a>
                <a href="{{ route('explore') }}" class="{{ request()->routeIs('explore', 'recipe', 'recipe.create', 'food.detail') ? 'active' : '' }}">Resep</a>
                <a href="{{ route('stories') }}" class="{{ request()->routeIs('stories') ? 'active' : '' }}">Cerita</a>
            </div>

            <div class="recipe-nav-right">
                <form action="{{ route('explore') }}" method="GET" class="recipe-nav-search">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari resep Bali..." aria-label="Cari resep Bali">
                    <button type="submit" aria-label="Cari resep">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <circle cx="11" cy="11" r="7" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg>
                    </button>
                </form>

                <a href="{{ auth()->check() ? route('account') : route('account') }}" class="recipe-icon-btn {{ request()->routeIs('account', 'favorites') ? 'active' : '' }}" title="{{ auth()->check() ? auth()->user()->name : 'Akun Saya' }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    <span class="recipe-fav-count" id="nav-fav-count">0</span>
                </a>

                <button type="button" class="nav-mobile-toggle" id="nav-mobile-toggle" aria-label="Buka menu">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    <main class="recipe-main">
        @yield('content')
    </main>

    @hasSection('hide_footer')
    @else
        <footer class="bali-footer-wrap">
            <div class="bali-footer-curve" aria-hidden="true"></div>
            <div class="bali-footer-inner">
                <div>
                    <h2 class="bali-footer-brand">Bali Culinary</h2>
                    <p class="bali-footer-desc">Dari Rasa Ke Hati — melestarikan kuliner tradisional Pulau Dewata.</p>
                </div>
                <div class="bali-footer-links">
                    <a href="{{ route('home') }}">Beranda</a>
                    <a href="{{ route('explore') }}">Resep</a>
                    <a href="{{ route('recipe.create') }}">Bagikan Resep</a>
                    <a href="{{ route('stories') }}">Cerita</a>
                    <a href="{{ route('about') }}">Tentang Kami</a>
                    <a href="{{ route('account') }}">Akun</a>
                    @guest
                        <a href="{{ route('login') }}">Login</a>
                    @endguest
                    <a href="{{ route('admin.dashboard') }}">Admin</a>
                </div>
            </div>
        </footer>
    @endif

    <script>
        (function () {
            const STORAGE_KEY = 'baliculinary_favorites';

            const toggleBtn = document.getElementById('nav-mobile-toggle');
            const navLinks = document.getElementById('main-nav-links');
            if (toggleBtn && navLinks) {
                toggleBtn.addEventListener('click', function () {
                    navLinks.classList.toggle('is-open');
                });
            }

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
                        label.textContent = isSaved ? 'Tersimpan' : 'Simpan Resep';
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

    <script src="{{ asset('Assets/js/animations.js') }}?v={{ filemtime(public_path('Assets/js/animations.js')) }}" defer></script>

    @stack('scripts')
</body>

</html>
