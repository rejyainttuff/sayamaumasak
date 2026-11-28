@extends('layouts.app')

@section('title', 'My Account — Bali Culinary')
@section('hide_main_nav', '1')

@section('content')
    {{-- Simplified Account Search Navigation ("Cari Apapun") --}}
    <header class="account-search-topbar">
        <div class="recipe-container account-search-topbar-inner">
            <a href="{{ route('home') }}" class="bali-brand brand-with-logo">
                <img src="{{ asset('Assets/images/baliculinarylogo.png') }}" alt="Bali Culinary" class="brand-logo-img">
                <span>Bali <em style="font-style: normal; color: var(--terracotta);">Culinary</em></span>
            </a>

            <form action="{{ route('explore') }}" method="GET" class="account-global-search">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="color: var(--muted); flex-shrink: 0;">
                    <circle cx="11" cy="11" r="7" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Apapun" aria-label="Cari Apapun">
                <button type="submit" class="btn-terracotta sm">Cari</button>
            </form>

            <a href="{{ route('home') }}" class="pill-tag">&larr; Beranda</a>
        </div>
    </header>

    {{-- Full, Uncropped Account Dashboard Banner --}}
    <div class="account-banner-full" aria-hidden="true"></div>

    <section class="account-wrap-clean">
        <div class="recipe-container">
            @if (session('success'))
                <div class="alert-banner success">{{ session('success') }}</div>
            @endif

            {{-- Floating Bottom-Left Capsule Trigger (Logo + 3 Dots) for Mobile Account Sidebar --}}
            <div class="mobile-dash-capsule-bar mobile-account-fab-wrap">
                <button type="button" class="mobile-dash-capsule-btn mobile-account-fab-btn" data-drawer-open="account-mobile-sidebar" aria-expanded="false" aria-label="Buka menu akun">
                    <img src="{{ asset('Assets/images/baliculinarylogo.png') }}" alt="Bali Culinary" class="capsule-mini-logo">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <circle cx="12" cy="5" r="2.2" />
                        <circle cx="12" cy="12" r="2.2" />
                        <circle cx="12" cy="19" r="2.2" />
                    </svg>
                </button>
            </div>

            <div class="mobile-drawer-backdrop" data-drawer-backdrop="account-mobile-sidebar"></div>

            <div class="account-shell">
                <aside class="account-sidebar" id="account-mobile-sidebar" data-mobile-drawer>
                    <div class="account-sidebar-banner">
                        <img src="{{ asset('Assets/images/banner-dashboard.png') }}" alt="Bali Culinary Dashboard" class="account-sidebar-banner-img">
                        <div class="account-sidebar-banner-overlay"></div>
                        <button type="button" class="mobile-drawer-close account-banner-close" data-drawer-close="account-mobile-sidebar" aria-label="Tutup menu">&times;</button>
                        <div class="account-sidebar-banner-text">
                            <h2>My Account</h2>
                            <p>Kelola profil &amp; aktivitas kulinermu</p>
                        </div>
                    </div>

                    <nav class="account-menu" aria-label="Navigasi Akun">
                        <a href="#profile" class="active">My Profile</a>
                        <a href="{{ route('favorites') }}">My Favorites</a>
                        <a href="#reviews">My Reviews</a>
                        <a href="#recent">Recently Viewed</a>
                        @if (auth()->user()->isContributor() || auth()->user()->isAdmin() || $contributorRequest !== null)
                            <a href="{{ route('submissions.index') }}">My Submissions</a>
                        @else
                            <a href="{{ route('contributor.apply') }}">Jadi Contributor</a>
                        @endif
                        <a href="{{ route('account.edit') }}">Edit Profile</a>
                        <a href="{{ route('password.change') }}">Ganti Password</a>
                        @if (auth()->user()->isHelper())
                            <a href="{{ route('admin.recipes.approvals') }}">Persetujuan Resep</a>
                            <a href="{{ route('admin.stories.index') }}">Story Management</a>
                        @endif
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
                        @endif
                    </nav>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="account-logout-btn">&#8617; Logout</button>
                    </form>
                </aside>

                <div class="account-main-col" id="profile">
                    <div class="account-profile-card">
                        <div class="account-avatar" style="overflow: hidden;" aria-hidden="true">
                            @if (! empty($profile['avatar']))
                                <img src="{{ asset(ltrim($profile['avatar'], '/')) }}" alt="{{ $profile['name'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            @endif
                        </div>

                        <div class="account-profile-info">
                            <h1>{{ $profile['name'] }}</h1>
                            <p>{{ $profile['handle'] }} &middot; {{ $profile['email'] }}</p>
                            @if (! empty($profile['bio']))
                                <p style="margin-top: 6px; font-size: 13px; color: var(--text);">{{ $profile['bio'] }}</p>
                            @endif
                            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                                <a href="{{ route('account.edit') }}" class="pill-tag">Edit Profile</a>
                                @if (! auth()->user()->isContributor() && ! auth()->user()->isAdmin() && $contributorRequest === null)
                                    <a href="{{ route('contributor.apply') }}" class="pill-tag">Ajukan Jadi Contributor</a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="account-grid-2">
                        <div class="account-stack">
                            <div class="account-widget">
                                <div class="account-widget-head">
                                    <h3>My Favorites</h3>
                                    <a href="{{ route('favorites') }}">Lihat Semua &rarr;</a>
                                </div>
                                @if (count($favoriteSamples) > 0)
                                    <div class="account-mini-grid">
                                        @foreach ($favoriteSamples as $fav)
                                            <a href="{{ route('food.detail', $fav['slug']) }}" class="account-mini-item">
                                                <div class="account-mini-thumb">{{ $fav['name'] }}</div>
                                                <span>{{ $fav['name'] }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <p style="font-size: 12.5px; color: var(--muted); margin: 6px 0 10px;">Belum ada makanan favorit. Jelajahi kuliner Bali untuk menyimpan favoritmu.</p>
                                    <a href="{{ route('explore') }}" class="pill-tag">+ Tambah Favorit</a>
                                @endif
                            </div>

                            <div class="account-widget" id="reviews">
                                <div class="account-widget-head">
                                    <h3>My Review</h3>
                                    <a href="#ulasan-feed">Lihat Semua &rarr;</a>
                                </div>
                                @if (count($reviews) > 0)
                                    @php $latestReview = $reviews[0]; @endphp
                                    <div class="account-review-preview">
                                        <div class="account-mini-thumb" style="height: 76px;">{{ $latestReview['dish'] }}</div>
                                        <div>
                                            <strong style="font-family: 'Playfair Display', serif; font-size: 16px; color: var(--terracotta);">{{ $latestReview['dish'] }}</strong>
                                            <div style="color: var(--star); font-size: 13px; margin: 2px 0;">
                                                @for ($s = 1; $s <= 5; $s++)
                                                    {{ $s <= $latestReview['rating'] ? '★' : '☆' }}
                                                @endfor
                                            </div>
                                            @if ($latestReview['comment'] !== '')
                                                <p style="font-size: 11.5px; color: var(--muted); margin: 0;">{{ $latestReview['comment'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <p style="font-size: 12.5px; color: var(--muted); margin: 6px 0 10px;">Kamu belum menulis ulasan apa pun.</p>
                                    <a href="{{ route('explore') }}" class="pill-tag">+ Tulis Ulasan</a>
                                @endif
                            </div>

                            <div class="account-widget" id="recent">
                                <div class="account-widget-head">
                                    <h3>Recently Viewed</h3>
                                    <a href="{{ route('explore') }}">Lihat Semua &rarr;</a>
                                </div>
                                @if (count($recentSamples) > 0)
                                    <div class="account-mini-grid">
                                        @foreach ($recentSamples as $recent)
                                            <a href="{{ route('food.detail', $recent['slug']) }}" class="account-mini-item">
                                                <div class="account-mini-thumb">{{ $recent['name'] }}</div>
                                                <span>{{ $recent['name'] }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <p style="font-size: 12.5px; color: var(--muted); margin: 6px 0 0;">Belum ada makanan yang baru dilihat.</p>
                                @endif
                            </div>
                        </div>

                        <div class="account-ulasan-panel" id="ulasan-feed">
                            <div class="account-widget-head" style="margin-bottom: 2px;">
                                <h3>Ulasan Saya</h3>
                                <span style="font-size: 11.5px; color: var(--muted);">{{ count($reviews) }} ulasan</span>
                            </div>
                            <div class="account-ulasan-inner">
                                @forelse ($reviews as $review)
                                    <div class="ulasan-feed-item">
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                            <a href="{{ route('food.detail', $review['slug']) }}" style="text-decoration: none;">
                                                <strong>{{ $review['dish'] }}</strong>
                                            </a>
                                            <span style="font-size: 11px; color: var(--muted);">{{ $review['date'] }}</span>
                                        </div>
                                        <div style="color: var(--star); font-size: 12px;">
                                            @for ($s = 1; $s <= 5; $s++)
                                                {{ $s <= $review['rating'] ? '★' : '☆' }}
                                            @endfor
                                        </div>
                                        @if ($review['comment'] !== '')
                                            <p>{{ $review['comment'] }}</p>
                                        @endif
                                    </div>
                                @empty
                                    <p style="font-size: 12.5px; color: var(--muted); margin: 8px 0;">Belum ada ulasan. Kunjungi halaman makanan untuk memberi rating dan ulasan pertamamu.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
