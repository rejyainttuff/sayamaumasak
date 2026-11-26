<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Bali Culinary</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('Assets/css/recipes.css') }}?v={{ filemtime(public_path('Assets/css/recipes.css')) }}">
</head>

<body class="recipe-page">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a href="{{ route('home') }}" class="bali-brand stacked">
                Bali
                <span>Culinary</span>
            </a>

            <nav class="admin-nav" aria-label="Menu Admin">
                <a href="{{ route('admin.dashboard') }}" class="active">Dashboard</a>
                <a href="{{ route('explore') }}">Food Management</a>
                <a href="{{ route('recipes.index') }}">Recipe Management</a>
                <a href="{{ route('explore', ['category' => 'Makanan']) }}">Category Management</a>
                <a href="#pending-submissions">Pending Recipe Submissions</a>
                <a href="{{ route('account') }}#reviews">Reviews Management</a>
                <a href="{{ route('stories') }}">Culinary Stories / Culture</a>

                <div class="admin-nav-divider"></div>

                <a href="{{ route('account') }}">User Management</a>
                <a href="{{ route('admin.dashboard') }}">Admin &amp; Role Management</a>

                <div class="admin-nav-divider"></div>

                <a href="#statistik-resep">Reports &amp; Analytics</a>
                <a href="{{ route('about') }}">Website Settings</a>
                <a href="{{ route('password.change') }}">Profile Settings</a>
            </nav>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="account-logout-btn">&#8617; Logout</button>
            </form>
        </aside>

        <div class="admin-workspace">
            <header class="admin-topbar">
                <div class="recipe-nav-links" style="display: flex;">
                    <a href="{{ route('about') }}">Tentang Kami</a>
                    <a href="{{ route('home') }}">Beranda</a>
                    <a href="{{ route('explore') }}">Resep</a>
                    <a href="{{ route('stories') }}">Cerita</a>
                </div>

                <div class="recipe-nav-right">
                    <form action="{{ route('explore') }}" method="GET" class="recipe-nav-search">
                        <input type="text" name="search" placeholder="Cari resep..." aria-label="Cari resep">
                        <button type="submit" aria-label="Cari">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <circle cx="11" cy="11" r="7" />
                                <line x1="21" y1="21" x2="16.65" y2="16.65" />
                            </svg>
                        </button>
                    </form>
                    <a href="{{ route('account') }}" class="recipe-icon-btn" title="Profil Admin">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </a>
                </div>
            </header>

            <section class="admin-hero">
                <div class="admin-hero-content">
                    <h1>Selamat Datang, Admin!</h1>
                    <p>Pantau aktivitas kuliner Bali, statistik resep, dan tinjau kiriman resep terbaru di sini.</p>
                </div>
            </section>

            <div class="admin-body">
                @if (session('success'))
                    <div class="alert-banner success" style="margin-bottom: 0;">{{ session('success') }}</div>
                @endif

                <div class="admin-stats-grid">
                    <div class="admin-stat-card">
                        <span>Total Makanan</span>
                        <strong>{{ $stats['foods'] }}</strong>
                    </div>
                    <div class="admin-stat-card">
                        <span>Total Resep</span>
                        <strong>{{ $stats['recipes'] }}</strong>
                    </div>
                    <div class="admin-stat-card">
                        <span>Resep Menunggu Review</span>
                        <strong>{{ $stats['pending'] }}</strong>
                    </div>
                    <div class="admin-stat-card">
                        <span>Total Ulasan</span>
                        <strong>{{ $stats['reviews'] }}</strong>
                    </div>
                </div>

                <div class="admin-mid-grid">
                    <div class="admin-card white" id="statistik-resep">
                        <div class="admin-card-head">
                            <h2>Statistik Resep</h2>
                            <span style="font-size: 11px; color: var(--muted);">Jan &ndash; Jul</span>
                        </div>

                        <div style="display: flex; gap: 14px; font-size: 11px; color: var(--muted); margin-bottom: 10px;">
                            <span><strong style="color: var(--terracotta);">&bull;</strong> Resep Baru</span>
                            <span><strong style="color: var(--sage);">&bull;</strong> Disetujui</span>
                            <span><strong style="color: #b79c7d;">&bull;</strong> Ulasan</span>
                        </div>

                        <svg viewBox="0 0 420 175" style="width: 100%; height: auto; display: block;" role="img" aria-label="Grafik Statistik Resep">
                            <g stroke="#efe2d0" stroke-width="1">
                                <line x1="30" y1="25" x2="405" y2="25" />
                                <line x1="30" y1="60" x2="405" y2="60" />
                                <line x1="30" y1="95" x2="405" y2="95" />
                                <line x1="30" y1="130" x2="405" y2="130" />
                            </g>

                            <path d="M35,102 L95,72 L155,86 L215,58 L275,70 L335,40 L395,28 L395,148 L35,148 Z" fill="rgba(183, 156, 125, 0.22)" />
                            <polyline class="chart-line-draw" points="35,102 95,72 155,86 215,58 275,70 335,40 395,28" fill="none" stroke="#b79c7d" stroke-width="2" />

                            <path d="M35,118 L95,88 L155,102 L215,78 L275,90 L335,68 L395,56 L395,148 L35,148 Z" fill="rgba(144, 150, 94, 0.24)" />
                            <polyline class="chart-line-draw delay-1" points="35,118 95,88 155,102 215,78 275,90 335,68 395,56" fill="none" stroke="#717542" stroke-width="2.2" />

                            <path d="M35,134 L95,112 L155,124 L215,106 L275,116 L335,96 L395,84 L395,148 L35,148 Z" fill="rgba(192, 90, 43, 0.2)" />
                            <polyline class="chart-line-draw delay-2" points="35,134 95,112 155,124 215,106 275,116 335,96 395,84" fill="none" stroke="#c05a2b" stroke-width="2.2" />

                            <g fill="#6e5849" font-size="10" text-anchor="middle" font-family="Poppins, sans-serif">
                                <text x="35" y="165">Jan</text>
                                <text x="95" y="165">Feb</text>
                                <text x="155" y="165">Mar</text>
                                <text x="215" y="165">Apr</text>
                                <text x="275" y="165">Mei</text>
                                <text x="335" y="165">Jun</text>
                                <text x="395" y="165">Jul</text>
                            </g>
                        </svg>
                    </div>

                    <div class="admin-card">
                        <div class="admin-card-head">
                            <h2>Menu Baru</h2>
                            <a href="{{ route('explore') }}">Lihat Semua &rarr;</a>
                        </div>

                        <div class="menu-baru-list">
                            @foreach ($recentMenu as $item)
                                <div class="menu-baru-item">
                                    <div class="menu-baru-thumb"></div>
                                    <div>
                                        <a href="{{ route('food.detail', $item['slug']) }}" style="font-size: 13.5px; font-weight: 700; color: var(--terracotta); text-decoration: none;">
                                            {{ $item['name'] }}
                                        </a>
                                        <div style="font-size: 11.5px; color: var(--muted);">{{ $item['author'] }}</div>
                                    </div>
                                    <span class="status-pill {{ $item['status'] }}">{{ ucfirst($item['status']) }}</span>
                                    <span style="font-size: 11px; color: var(--muted); white-space: nowrap;">{{ $item['time'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="admin-card" id="pending-submissions">
                    <div class="admin-card-head">
                        <h2>Resep Menunggu Persetujuan</h2>
                        <a href="{{ route('recipes.index') }}">Lihat Semua &rarr;</a>
                    </div>

                    @foreach ($pendingSubmissions as $sub)
                        <div class="pending-row">
                            <div class="pending-thumb"></div>
                            <div>
                                <div style="font-size: 14px; font-weight: 700; color: var(--terracotta);">{{ $sub['name'] }}</div>
                                <div style="font-size: 12px; color: var(--muted);">{{ $sub['author'] }} &middot; Status: <strong>{{ ucfirst($sub['status']) }}</strong></div>
                            </div>

                            <a href="{{ route('food.detail', $sub['slug']) }}" class="action-btn review">Review</a>

                            <div style="display: flex; gap: 6px;">
                                <form action="{{ route('admin.submission.status', $sub['slug']) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="action-btn approve">Approve</button>
                                </form>
                                <form action="{{ route('admin.submission.status', $sub['slug']) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="action-btn reject">Reject</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('Assets/js/animations.js') }}" defer></script>
</body>

</html>
