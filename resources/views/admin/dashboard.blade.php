<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Bali Culinary</title>
    <link rel="icon" type="image/png" href="{{ asset('Assets/images/baliculinarylogo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('Assets/css/recipes.css') }}?v={{ filemtime(public_path('Assets/css/recipes.css')) }}">
</head>

<body class="recipe-page">
    <div class="admin-shell">
        @include('admin.partials.sidebar')

        <div class="admin-workspace">
            <header class="admin-topbar">
                <div class="recipe-nav-links" style="display: flex;">
                    <a href="{{ route('home') }}">Beranda</a>
                    <a href="{{ route('explore') }}">Resep</a>
                    <a href="{{ route('stories') }}">Cerita</a>
                    <a href="{{ route('about') }}">Tentang Kami</a>
                </div>

                <div class="recipe-nav-right">
                    <form action="{{ route('explore') }}" method="GET" class="recipe-nav-search">
                        <input type="text" name="search" placeholder="Cari apapun..." aria-label="Cari apapun">
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
                    <p>Pantau statistik kuliner Bali, distribusi konten, aktivitas terbaru, dan tinjau pengajuan Contributor.</p>
                </div>
            </section>

            <div class="admin-body">
                @if (session('success'))
                    <div class="alert-banner success" style="margin-bottom: 0;">{{ session('success') }}</div>
                @endif

                {{-- A. 4 Statistik Utama (Bahasa Indonesia + Ikon + Indikator Persentase Nyata) --}}
                <div class="admin-stats-grid">
                    @php
                        $cardIcons = [
                            'foods' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>',
                            'recipes' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>',
                            'contributors' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
                            'reviews' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
                        ];
                    @endphp

                    @foreach ($statCards as $key => $card)
                        <div class="admin-stat-card detailed">
                            <div class="admin-stat-icon">{!! $cardIcons[$key] !!}</div>
                            <span class="admin-stat-label">{{ $card['label'] }}</span>
                            <strong class="admin-stat-value">{{ $card['total'] }}</strong>
                            <span class="admin-stat-delta tone-{{ $card['tone'] }}">
                                {{ $card['caption'] }}
                            </span>
                        </div>
                    @endforeach
                </div>

                {{-- B & C. Statistik Konten (Dominan) + Pie/Doughnut Chart --}}
                <div class="admin-charts-grid" id="statistik-resep">
                    <div class="admin-card white">
                        <div class="admin-card-head" style="flex-wrap: wrap;">
                            <div>
                                <h2>Statistik Konten</h2>
                                <span style="font-size: 11.5px; color: var(--muted);">Resep, Makanan, dan Pengunjung berdasarkan database</span>
                            </div>
                            <div class="admin-range-filters" role="group" aria-label="Filter rentang waktu statistik">
                                <a href="{{ route('admin.dashboard', ['range' => '7d']) }}" class="pill-tag {{ $contentSeries['range'] === '7d' ? 'active' : '' }}">7 Hari</a>
                                <a href="{{ route('admin.dashboard', ['range' => '30d']) }}" class="pill-tag {{ $contentSeries['range'] === '30d' ? 'active' : '' }}">30 Hari</a>
                                <a href="{{ route('admin.dashboard', ['range' => '12m']) }}" class="pill-tag {{ $contentSeries['range'] === '12m' ? 'active' : '' }}">12 Bulan</a>
                            </div>
                        </div>
                        <div class="chart-canvas-wrap">
                            <canvas id="contentLineChart" aria-label="Grafik Statistik Konten"></canvas>
                        </div>
                    </div>

                    <div class="admin-card white">
                        <div class="admin-card-head">
                            <h2>Distribusi Konten</h2>
                            <span style="font-size: 11.5px; color: var(--muted);">Data Asli DB</span>
                        </div>
                        <div class="chart-canvas-wrap">
                            <canvas id="contentDoughnutChart" aria-label="Grafik Distribusi Konten"></canvas>
                        </div>
                    </div>
                </div>

                {{-- D, E, F, G. Resep Terbaru (~30%) + Konten Menunggu Persetujuan (~20%) + Aktivitas Terbaru (~20%) --}}
                <div class="admin-lower-grid">
                    <div class="admin-card white">
                        <div class="admin-card-head">
                            <h2>Resep Terbaru</h2>
                            <a href="{{ route('recipes.index') }}">Lihat Semua &rarr;</a>
                        </div>

                        <div class="menu-baru-list">
                            @forelse ($recentMenu as $item)
                                <div class="menu-baru-item">
                                    <div class="menu-baru-thumb" style="overflow: hidden;">
                                        @if (! empty($item['image']) && file_exists(public_path(ltrim($item['image'], '/'))))
                                            <img src="{{ asset(ltrim($item['image'], '/')) }}" alt="{{ $item['name'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @endif
                                    </div>
                                    <div style="min-width: 0;">
                                        <a href="{{ route('recipes.show', $item['id']) }}" style="font-size: 13.5px; font-weight: 700; color: var(--terracotta); text-decoration: none;">
                                            {{ $item['name'] }}
                                        </a>
                                        <div style="font-size: 11.5px; color: var(--muted);">
                                            {{ $item['author'] }} &middot; {{ $item['published_at'] }}
                                        </div>
                                    </div>
                                    <span class="status-pill {{ $item['status'] }}">{{ ucfirst($item['status']) }}</span>
                                </div>
                            @empty
                                <p style="font-size: 13px; color: var(--muted); margin: 6px 0;">Belum ada resep yang dipublikasikan.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="admin-side-stack">
                        <div class="admin-card white" id="pending-submissions">
                            <div class="admin-card-head">
                                <h2>Menunggu Persetujuan</h2>
                                <a href="{{ route('admin.recipes.approvals') }}">Buka Daftar &rarr;</a>
                            </div>

                            <div class="pending-summary-list">
                                <a href="{{ route('admin.recipes.approvals', ['status' => 'pending']) }}" class="pending-summary-row">
                                    <span>Pengajuan Resep</span>
                                    <strong>{{ $pendingSummary['recipes'] }}</strong>
                                </a>
                                <a href="{{ route('foods.index') }}" class="pending-summary-row">
                                    <span>Pengajuan Makanan</span>
                                    <strong>{{ $pendingSummary['foods'] }}</strong>
                                </a>
                                <a href="{{ route('admin.stories.index', ['status' => 'pending']) }}" class="pending-summary-row">
                                    <span>Pengajuan Cerita Kuliner</span>
                                    <strong>{{ $pendingSummary['stories'] }}</strong>
                                </a>
                            </div>
                        </div>

                        <div class="admin-card white">
                            <div class="admin-card-head">
                                <h2>Aktivitas Terbaru</h2>
                            </div>
                            <ul class="activity-feed-list">
                                @forelse ($recentActivities as $act)
                                    <li>
                                        <span class="activity-dot type-{{ $act['type'] }}"></span>
                                        <div>
                                            <p>{{ $act['text'] }}</p>
                                            <small>{{ $act['time'] }}</small>
                                        </div>
                                    </li>
                                @empty
                                    <li style="color: var(--muted); font-size: 12.5px;">Belum ada aktivitas tercatat.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- H. Ringkasan 1 Card Pengajuan Contributor di Bagian Paling Bawah --}}
                <div class="admin-card white" id="pending-contributors">
                    <div class="admin-card-head">
                        <h2>Ringkasan Pengajuan Contributor</h2>
                        <a href="{{ route('admin.contributors.index') }}">Lihat Semua Pengajuan ({{ $pendingContributorRequests->count() }}) &rarr;</a>
                    </div>

                    @if ($latestContributorRequest)
                        <a href="{{ route('admin.contributors.index') }}" class="contributor-summary-card">
                            <div style="flex: 1; min-width: 220px;">
                                <div style="font-size: 15px; font-weight: 700; color: var(--terracotta);">
                                    {{ $latestContributorRequest->user?->name ?? 'Pengguna' }}
                                </div>
                                <div style="font-size: 12.5px; color: var(--muted); margin-top: 2px;">
                                    {{ $latestContributorRequest->user?->email ?? '-' }} &middot;
                                    Diajukan pada {{ $latestContributorRequest->created_at?->format('d M Y, H:i') }}
                                </div>
                            </div>
                            <span class="status-pill {{ $latestContributorRequest->status }}">{{ ucfirst($latestContributorRequest->status) }}</span>
                            <span class="pill-tag">Tinjau Detail &rarr;</span>
                        </a>
                    @else
                        <p style="font-size: 13px; color: var(--muted); margin: 6px 0;">Tidak ada pengajuan Contributor yang menunggu persetujuan saat ini.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const seriesPayload = @json($contentSeries);
            const distPayload = @json($contentDistribution);

            const lineCanvas = document.getElementById('contentLineChart');
            if (lineCanvas && typeof Chart !== 'undefined') {
                new Chart(lineCanvas, {
                    type: 'line',
                    data: {
                        labels: seriesPayload.labels,
                        datasets: [
                            {
                                label: 'Resep',
                                data: seriesPayload.recipes,
                                borderColor: '#c05a2b',
                                backgroundColor: 'rgba(192, 90, 43, 0.14)',
                                fill: true,
                                tension: 0.35,
                                borderWidth: 2.2
                            },
                            {
                                label: 'Makanan',
                                data: seriesPayload.foods,
                                borderColor: '#717542',
                                backgroundColor: 'rgba(113, 117, 66, 0.14)',
                                fill: true,
                                tension: 0.35,
                                borderWidth: 2.2
                            },
                            {
                                label: 'Pengunjung',
                                data: seriesPayload.visitors,
                                borderColor: '#d4952a',
                                backgroundColor: 'rgba(212, 149, 42, 0.12)',
                                fill: true,
                                tension: 0.35,
                                borderWidth: 2.2
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: { boxWidth: 12, padding: 14 }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 }
                            }
                        }
                    }
                });
            }

            const distCanvas = document.getElementById('contentDoughnutChart');
            if (distCanvas && typeof Chart !== 'undefined') {
                new Chart(distCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: distPayload.labels,
                        datasets: [{
                            data: distPayload.data,
                            backgroundColor: ['#c05a2b', '#717542', '#d4952a', '#4c2d1f'],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { boxWidth: 12, padding: 14 }
                            }
                        }
                    }
                });
            }
        })();
    </script>
    <script src="{{ asset('Assets/js/animations.js') }}" defer></script>
</body>

</html>
