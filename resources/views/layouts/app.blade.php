<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Bali Culinary — Dari Rasa Ke Hati')</title>
    <link rel="icon" type="image/png" href="{{ asset('Assets/images/baliculinarylogo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('Assets/css/recipes.css') }}?v={{ filemtime(public_path('Assets/css/recipes.css')) }}">
</head>

<body class="recipe-page @hasSection('hide_main_nav') has-account-nav @endif">
    @php
        // Canonical logo asset path (replace public/Assets/images/baliculinarylogo.png to update everywhere)
        $navCompactIcon = asset('Assets/images/baliculinarylogo.png');

        // Selective bottom-to-top page transition ONLY for the 5 main sections:
        // 1. Beranda (home), 2. Resep (explore, recipe), 3. Cerita (stories), 4. Tentang (about), 5. Profile (account)
        $isPrimarySectionTransition = request()->routeIs('home', 'explore', 'recipe', 'stories', 'about', 'account');
    @endphp

    @hasSection('hide_main_nav')
    @else
        {{-- Desktop Floating Pill Navbar + Dynamic Island Scroll Morph --}}
        <div class="floating-nav-shell" id="floating-nav-shell">
            <nav class="floating-pill-nav" id="floating-pill-nav" aria-label="Navigasi Utama">
                <button type="button" class="floating-nav-island-trigger" id="floating-nav-trigger" aria-label="Buka menu navigasi" aria-expanded="true">
                    <img src="{{ $navCompactIcon }}" alt="Bali Culinary">
                </button>

                <div class="floating-pill-content">
                    <a href="{{ route('home') }}" class="bali-brand floating-brand brand-with-logo">
                        <img src="{{ $navCompactIcon }}" alt="Bali Culinary" class="brand-logo-img">
                        <span>Bali <em style="font-style: normal; color: var(--terracotta);">Culinary</em></span>
                    </a>

                    <div class="floating-pill-links">
                        <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">Tentang</a>
                        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Beranda</a>
                        <a href="{{ route('explore') }}" class="{{ request()->routeIs('explore', 'recipe', 'recipe.create', 'food.detail', 'recipes.*') ? 'active' : '' }}">Resep</a>
                        <a href="{{ route('stories') }}" class="{{ request()->routeIs('stories*') ? 'active' : '' }}">Cerita</a>
                    </div>

                    <div class="floating-pill-actions">
                        <form action="{{ route('explore') }}" method="GET" class="recipe-nav-search">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari resep..." aria-label="Cari resep Bali">
                            <button type="submit" aria-label="Cari resep">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                    <circle cx="11" cy="11" r="7" />
                                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                                </svg>
                            </button>
                        </form>

                        <a href="{{ route('account') }}" class="recipe-icon-btn {{ request()->routeIs('account*', 'favorites', 'submissions.*') ? 'active' : '' }}" title="{{ auth()->check() ? auth()->user()->name : 'Masuk / Akun Saya' }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </a>
                    </div>
                </div>
            </nav>
        </div>

        {{-- Mobile Bottom Navigation Bar --}}
        <nav class="mobile-bottom-nav" aria-label="Navigasi Mobile">
            <a href="{{ route('home') }}" class="mobile-bottom-item {{ request()->routeIs('home') ? 'active' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z" />
                </svg>
                <span>Beranda</span>
            </a>
            <a href="{{ route('explore') }}" class="mobile-bottom-item {{ request()->routeIs('explore', 'recipe', 'food.detail', 'recipes.*') ? 'active' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20" />
                </svg>
                <span>Resep</span>
            </a>
            <a href="{{ route('recipe.create') }}" class="mobile-bottom-item {{ request()->routeIs('recipe.create') ? 'active' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9" />
                    <line x1="12" y1="8" x2="12" y2="16" />
                    <line x1="8" y1="12" x2="16" y2="12" />
                </svg>
                <span>Bagikan</span>
            </a>
            <a href="{{ route('stories') }}" class="mobile-bottom-item {{ request()->routeIs('stories*') ? 'active' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
                </svg>
                <span>Cerita</span>
            </a>
            <a href="{{ route('account') }}" class="mobile-bottom-item {{ request()->routeIs('account*', 'favorites', 'submissions.*', 'login', 'register') ? 'active' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
                <span>{{ auth()->check() ? 'Akun' : 'Masuk' }}</span>
            </a>
        </nav>
    @endif

    <main class="recipe-main{{ $isPrimarySectionTransition ? ' page-rise-section' : '' }}" @if ($isPrimarySectionTransition) data-page-rise @endif>
        @yield('content')
    </main>

    @hasSection('hide_footer')
    @else
        <footer class="bali-footer-wrap">
            <div class="bali-footer-curve" aria-hidden="true"></div>
            <div class="bali-footer-inner">
                <div>
                    <a href="{{ route('home') }}" class="brand-with-logo" style="text-decoration: none; margin-bottom: 6px;">
                        <img src="{{ $navCompactIcon }}" alt="Bali Culinary" class="brand-logo-img footer-logo-img">
                        <h2 class="bali-footer-brand" style="margin: 0;">Bali Culinary</h2>
                    </a>
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
                    @if (auth()->check() && auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}">Admin</a>
                    @endif
                </div>
            </div>
        </footer>
    @endif

    <script>
        (function () {
            @auth
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    window.location.reload();
                }
            });
            @endauth

            // Desktop Floating Pill Dynamic Island Scroll Morph
            const pillNav = document.getElementById('floating-pill-nav');
            const trigger = document.getElementById('floating-nav-trigger');
            let userExpanded = false;

            function syncPillState() {
                if (!pillNav) {
                    return;
                }
                if (window.innerWidth <= 860) {
                    pillNav.classList.remove('is-collapsed', 'is-expanded');
                    return;
                }
                if (window.scrollY > 140) {
                    if (!userExpanded) {
                        pillNav.classList.add('is-collapsed');
                        pillNav.classList.remove('is-expanded');
                        if (trigger) {
                            trigger.setAttribute('aria-expanded', 'false');
                        }
                    }
                } else {
                    userExpanded = false;
                    pillNav.classList.remove('is-collapsed', 'is-expanded');
                    if (trigger) {
                        trigger.setAttribute('aria-expanded', 'true');
                    }
                }
            }

            if (trigger && pillNav) {
                trigger.addEventListener('click', function () {
                    if (pillNav.classList.contains('is-collapsed')) {
                        userExpanded = true;
                        pillNav.classList.remove('is-collapsed');
                        pillNav.classList.add('is-expanded');
                        trigger.setAttribute('aria-expanded', 'true');
                    } else if (window.scrollY > 140) {
                        userExpanded = false;
                        pillNav.classList.add('is-collapsed');
                        pillNav.classList.remove('is-expanded');
                        trigger.setAttribute('aria-expanded', 'false');
                    }
                });

                document.addEventListener('click', function (event) {
                    if (userExpanded && window.scrollY > 140 && !pillNav.contains(event.target)) {
                        userExpanded = false;
                        syncPillState();
                    }
                });

                window.addEventListener('scroll', syncPillState, { passive: true });
                window.addEventListener('resize', syncPillState, { passive: true });
                syncPillState();
            }

            // Active Reading Progress Tracker (authenticated users only, pauses on hidden tab or inactivity)
            window.initBaliReadingTracker = function (contentType, contentId) {
                @guest
                return;
                @else
                if (!contentType || !contentId) {
                    return;
                }
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!csrfToken) {
                    return;
                }

                const sessionToken = (window.crypto && crypto.randomUUID)
                    ? crypto.randomUUID().replace(/-/g, '')
                    : ('sess_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10));

                let lastActivityAt = Date.now();
                let activeSecondsBuffer = 0;
                const INACTIVITY_LIMIT_MS = 60000;
                const FLUSH_INTERVAL_SEC = 15;

                ['scroll', 'mousemove', 'keydown', 'touchstart', 'click'].forEach(function (evt) {
                    window.addEventListener(evt, function () {
                        lastActivityAt = Date.now();
                    }, { passive: true });
                });

                function flushHeartbeat(seconds) {
                    if (seconds <= 0) {
                        return;
                    }
                    fetch("{{ route('reading.heartbeat') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        credentials: 'same-origin',
                        keepalive: true,
                        body: JSON.stringify({
                            content_type: contentType,
                            content_id: Number(contentId),
                            session_token: sessionToken,
                            delta_seconds: Math.min(30, seconds)
                        })
                    }).catch(function () {});
                }

                setInterval(function () {
                    const isVisible = document.visibilityState === 'visible';
                    const isRecentlyActive = (Date.now() - lastActivityAt) <= INACTIVITY_LIMIT_MS;

                    if (isVisible && isRecentlyActive) {
                        activeSecondsBuffer += 1;
                        if (activeSecondsBuffer >= FLUSH_INTERVAL_SEC) {
                            const toSend = activeSecondsBuffer;
                            activeSecondsBuffer = 0;
                            flushHeartbeat(toSend);
                        }
                    }
                }, 1000);

                document.addEventListener('visibilitychange', function () {
                    if (document.visibilityState === 'hidden' && activeSecondsBuffer >= 5) {
                        const toSend = activeSecondsBuffer;
                        activeSecondsBuffer = 0;
                        flushHeartbeat(toSend);
                    }
                });
                @endguest
            };

            // Release transform after the 5-section bottom-to-top entrance finishes
            // and keep Back/Forward bfcache restores instant without flicker
            const riseMain = document.querySelector('[data-page-rise]');
            if (riseMain) {
                riseMain.addEventListener('animationend', function (event) {
                    if (event.target === riseMain) {
                        riseMain.classList.add('is-rise-settled');
                    }
                }, { once: true });

                window.addEventListener('pageshow', function (event) {
                    if (event.persisted) {
                        riseMain.classList.remove('is-rise-settled');
                        void riseMain.offsetWidth;
                    }
                });
            }
        })();
    </script>

    <script src="{{ asset('Assets/js/animations.js') }}?v={{ filemtime(public_path('Assets/js/animations.js')) }}" defer></script>

    @stack('scripts')
</body>

</html>
