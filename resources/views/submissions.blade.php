@extends('layouts.app')

@section('title', 'My Submissions — Bali Culinary')

@section('content')
    <header class="bali-hero hero-dashboard">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <h1>My <span class="accent">Submissions</span></h1>
                <p>Pantau progres Level Contributor, waktu membaca, serta seluruh kiriman makanan, resep, dan cerita kuliner Bali.</p>
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

            @if (! $isContributor && $contributorRequest === null)
                <div class="recipe-empty">
                    <h3 style="font-family: 'Playfair Display', serif; color: var(--terracotta); margin: 0 0 8px;">Halaman Khusus Contributor</h3>
                    <p style="font-size: 13.5px; color: var(--muted); margin: 0 0 16px;">
                        Halaman <strong>My Submissions</strong> tersedia setelah kamu mengajukan diri sebagai Contributor Bali Culinary.
                    </p>
                    <a href="{{ route('contributor.apply') }}" class="btn-terracotta">Ajukan Jadi Contributor</a>
                </div>
            @else
                @if ($contributorRequest)
                    <div class="admin-card white" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                        <div>
                            <strong style="font-size: 14px;">Status Pengajuan Contributor:</strong>
                            <span class="status-pill {{ $contributorRequest->status }}">{{ ucfirst($contributorRequest->status) }}</span>
                            @if ($contributorRequest->specialty)
                                <div style="font-size: 12.5px; color: var(--muted); margin-top: 4px;">Bidang: {{ $contributorRequest->specialty }}</div>
                            @endif
                        </div>
                        @if ($contributorRequest->status === 'rejected')
                            <a href="{{ route('contributor.apply') }}" class="pill-tag">Ajukan Ulang</a>
                        @endif
                    </div>
                @endif

                {{-- Contributor Level Progress Panel --}}
                @php
                    $totalMins = (int) floor($levelProgress['total_reading_seconds'] / 60);
                    $totalRemSecs = $levelProgress['total_reading_seconds'] % 60;
                    $remainMins = (int) ceil($levelProgress['remaining_seconds'] / 60);
                @endphp
                <div class="contributor-level-panel">
                    <div class="contributor-level-head">
                        <div>
                            <span class="level-badge">Level {{ $levelProgress['current_level'] }}</span>
                            <h2 style="margin: 6px 0 2px; font-family: 'Playfair Display', serif; font-size: 22px;">Progres Contributor</h2>
                            <p style="margin: 0; font-size: 13px; color: var(--muted);">
                                Tingkatkan level melalui aktivitas membaca artikel/resep atau dapatkan bonus <strong>+5 level</strong> setiap resep disetujui Admin.
                            </p>
                        </div>
                        <div class="contributor-level-metrics">
                            <div>
                                <small>Waktu Membaca</small>
                                <strong>{{ $totalMins }} mnt {{ $totalRemSecs }} dtk</strong>
                            </div>
                            <div>
                                <small>Resep Disetujui</small>
                                <strong>{{ $levelProgress['approved_recipes_count'] }} resep</strong>
                            </div>
                            <div>
                                <small>Cerita Lengkap</small>
                                <strong>{{ $levelProgress['can_submit_stories'] ? 'Terbuka (Lv 3+)' : 'Terkunci (Min Lv 3)' }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="level-progress-wrap">
                        <div style="display: flex; justify-content: space-between; font-size: 12.5px; margin-bottom: 6px;">
                            <span>Progres membaca menuju level membaca berikutnya ({{ $levelProgress['progress_percent'] }}%)</span>
                            <strong>Sisa ~{{ $remainMins }} menit membaca</strong>
                        </div>
                        <div class="level-progress-track" role="progressbar" aria-valuenow="{{ $levelProgress['progress_percent'] }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="level-progress-fill" style="width: {{ $levelProgress['progress_percent'] }}%;"></div>
                        </div>
                    </div>

                    @if ($levelProgress['logs']->isNotEmpty())
                        <div style="margin-top: 16px;">
                            <h3 style="font-size: 14px; margin: 0 0 8px;">Riwayat Kenaikan Level</h3>
                            <ul class="level-history-list">
                                @foreach ($levelProgress['logs'] as $log)
                                    <li>
                                        <span class="pill-tag">{{ $log->source === 'recipe_bonus' ? 'Bonus Resep +'.$log->levels_gained : 'Membaca +'.$log->levels_gained }}</span>
                                        <span>{{ $log->description }}</span>
                                        <small>{{ $log->created_at?->format('d M Y') }}</small>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                @if ($isContributor)
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 22px; align-items: center;">
                        <a href="{{ route('foods.create') }}" class="btn-terracotta sm">+ Kirim Makanan</a>
                        <a href="{{ route('recipe.create') }}" class="btn-terracotta sm">+ Kirim Resep</a>
                        @if ($levelProgress['can_submit_stories'])
                            <a href="{{ route('stories.create') }}" class="btn-terracotta sm">+ Kirim Cerita Kuliner</a>
                        @else
                            <span class="pill-tag" style="opacity: 0.75;" title="Membutuhkan Contributor Level 3">
                                &#128274; Kirim Cerita Lengkap (Khusus Level 3+)
                            </span>
                        @endif
                    </div>
                @endif

                <div class="admin-card white" style="margin-bottom: 18px;">
                    <div class="admin-card-head">
                        <h2>Kiriman Makanan</h2>
                    </div>
                    @forelse ($foodSubmissions as $item)
                        @php $status = $item->approval_status ?? 'pending'; @endphp
                        <div class="pending-row">
                            <div class="pending-thumb"></div>
                            <div>
                                <div style="font-size: 14px; font-weight: 700; color: var(--terracotta);">{{ $item->name }}</div>
                                <div style="font-size: 12px; color: var(--muted);">{{ $item->origin_area ?: 'Bali' }}</div>
                            </div>
                            <span class="status-pill {{ $status }}">{{ ucfirst($status) }}</span>
                        </div>
                    @empty
                        <p style="font-size: 13px; color: var(--muted); margin: 6px 0;">Belum ada pengiriman makanan.</p>
                    @endforelse
                </div>

                <div class="admin-card white" style="margin-bottom: 18px;">
                    <div class="admin-card-head">
                        <h2>Kiriman Resep</h2>
                    </div>
                    @forelse ($recipeSubmissions as $item)
                        @php $status = $item->approval_status ?? 'pending'; @endphp
                        <div class="pending-row">
                            <div class="pending-thumb"></div>
                            <div>
                                <div style="font-size: 14px; font-weight: 700; color: var(--terracotta);">{{ $item->title }}</div>
                                <div style="font-size: 12px; color: var(--muted);">{{ $item->food?->name ?? 'Resep Bali' }}</div>
                            </div>
                            <span class="status-pill {{ $status }}">{{ ucfirst($status) }}</span>
                        </div>
                    @empty
                        <p style="font-size: 13px; color: var(--muted); margin: 6px 0;">Belum ada pengiriman resep.</p>
                    @endforelse
                </div>

                <div class="admin-card white">
                    <div class="admin-card-head">
                        <h2>Kiriman Culinary Stories</h2>
                    </div>
                    @forelse ($storySubmissions as $item)
                        @php $status = $item->approval_status ?? 'pending'; @endphp
                        <div class="pending-row">
                            <div class="pending-thumb"></div>
                            <div>
                                <div style="font-size: 14px; font-weight: 700; color: var(--terracotta);">{{ $item->title }}</div>
                                <div style="font-size: 12px; color: var(--muted);">{{ $item->food?->name ?? 'Cerita Kuliner' }}</div>
                            </div>
                            <span class="status-pill {{ $status }}">{{ ucfirst($status) }}</span>
                        </div>
                    @empty
                        <p style="font-size: 13px; color: var(--muted); margin: 6px 0;">Belum ada pengiriman cerita kuliner.</p>
                    @endforelse
                </div>
            @endif
        </div>
    </section>
@endsection
