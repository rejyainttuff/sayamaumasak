@extends('layouts.app')

@section('title', 'My Account — Bali Culinary')

@section('content')
    <div class="account-banner-strip" aria-hidden="true"></div>

    <section class="account-wrap">
        <div class="recipe-container">
            @if (session('success'))
                <div class="alert-banner success">{{ session('success') }}</div>
            @endif

            <div class="account-shell">
                <aside class="account-sidebar">
                    <h2>My Account</h2>
                    <p class="account-sidebar-sub">Kelola profil &amp; aktivitas kulinermu</p>

                    <nav class="account-menu" aria-label="Navigasi Akun">
                        <a href="#profile" class="active">My Profile</a>
                        <a href="{{ route('favorites') }}">My Favorites</a>
                        <a href="#reviews">My Reviews</a>
                        <a href="#recent">Recently Viewed</a>
                        <a href="{{ route('explore') }}">My Submissions</a>
                        <a href="{{ route('recipe.create') }}">Bagikan Resep</a>
                        <a href="{{ route('password.change') }}">Ganti Password</a>
                        <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
                    </nav>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="account-logout-btn">&#8617; Logout</button>
                    </form>
                </aside>

                <div class="account-main-col" id="profile">
                    <div class="account-profile-card">
                        <div class="account-avatar" aria-hidden="true">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </div>

                        <div class="account-profile-info">
                            <h1>{{ $profile['name'] }}</h1>
                            <p>{{ $profile['handle'] }} &middot; {{ $profile['email'] }}</p>
                            <a href="{{ route('password.change') }}" class="pill-tag">Edit Profile</a>
                        </div>
                    </div>

                    <div class="account-grid-2">
                        <div class="account-stack">
                            <div class="account-widget">
                                <div class="account-widget-head">
                                    <h3>My Favorites</h3>
                                    <a href="{{ route('favorites') }}">Lihat Semua &rarr;</a>
                                </div>
                                <div class="account-mini-grid">
                                    @foreach ($favoriteSamples as $fav)
                                        <a href="{{ route('food.detail', $fav['slug']) }}" class="account-mini-item">
                                            <div class="account-mini-thumb">{{ $fav['name'] }}</div>
                                            <span>{{ $fav['name'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            <div class="account-widget" id="reviews">
                                <div class="account-widget-head">
                                    <h3>My Review</h3>
                                    <a href="#ulasan-feed">Lihat Semua &rarr;</a>
                                </div>
                                <div class="account-review-preview">
                                    <div class="account-mini-thumb" style="height: 76px;">Lawar Bali</div>
                                    <div>
                                        <strong style="font-family: 'Playfair Display', serif; font-size: 16px; color: var(--terracotta);">Lawar Bali</strong>
                                        <div style="color: var(--star); font-size: 13px; margin: 2px 0;">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                                        <p style="font-size: 11.5px; color: var(--muted); margin: 0;">Bumbu base genepnya segar dan mudah diikuti!</p>
                                    </div>
                                </div>
                            </div>

                            <div class="account-widget" id="recent">
                                <div class="account-widget-head">
                                    <h3>Recently Viewed</h3>
                                    <a href="{{ route('explore') }}">Lihat Semua &rarr;</a>
                                </div>
                                <div class="account-mini-grid">
                                    @foreach ($recentSamples as $recent)
                                        <a href="{{ route('food.detail', $recent['slug']) }}" class="account-mini-item">
                                            <div class="account-mini-thumb">{{ $recent['name'] }}</div>
                                            <span>{{ $recent['name'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="account-ulasan-panel" id="ulasan-feed">
                            <div class="account-widget-head" style="margin-bottom: 2px;">
                                <h3>Ulasan</h3>
                                <span style="font-size: 11.5px; color: var(--muted);">{{ count($reviews) }} ulasan terbaru</span>
                            </div>
                            <div class="account-ulasan-inner">
                                @foreach ($reviews as $review)
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
                                        <p>{{ $review['comment'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <img src="{{ asset('Assets/images/decor-hibiscus.png') }}" alt="" class="corner-hibiscus bottom-left" aria-hidden="true">
    </section>
@endsection
