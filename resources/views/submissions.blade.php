@extends('layouts.app')

@section('title', 'My Submissions — Bali Culinary')

@section('content')
    <header class="bali-hero hero-dashboard">
        <div class="bali-hero-inner">
            <div class="bali-hero-content">
                <h1>My <span class="accent">Submissions</span></h1>
                <p>Kelola draft, pantau status pengajuan resep, perbaiki resep yang ditolak, dan lihat progres level kamu.</p>
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
                    <h3 style="font-family: 'Playfair Display', serif; color: var(--terracotta); margin: 0 0 8px;">Halaman Khusus Pengajuan Resep</h3>
                    <p style="font-size: 13.5px; color: var(--muted); margin: 0 0 16px;">
                        Ajukan izin Contributor terlebih dahulu untuk mulai menulis draft dan mengirim resep kuliner Bali.
                    </p>
                    <a href="{{ route('contributor.apply') }}" class="btn-terracotta">Ajukan Izin Kirim Resep</a>
                </div>
            @else
                @if ($contributorRequest)
                    <div class="admin-card white" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                        <div>
                            <strong style="font-size: 14px;">Status Pengajuan Izin Kirim Resep:</strong>
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
                            <h2 style="margin: 6px 0 2px; font-family: 'Playfair Display', serif; font-size: 22px;">Progres Pengajuan Resep &amp; Membaca</h2>
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
                        <a href="{{ route('recipe.create') }}" class="btn-terracotta sm">+ Kirim Resep</a>
                        @if ($levelProgress['can_submit_stories'])
                            <a href="{{ route('stories.create') }}" class="pill-tag">+ Kirim Cerita Kuliner</a>
                        @else
                            <span class="pill-tag" style="opacity: 0.75;" title="Membutuhkan Level 3">
                                &#128274; Kirim Cerita Lengkap (Khusus Level 3+)
                            </span>
                        @endif
                    </div>
                @endif

                {{-- Daftar Pengajuan Resep (Draft, Pending, Approved, Rejected + Revisi) --}}
                <div class="admin-card white" style="margin-bottom: 18px;">
                    <div class="admin-card-head">
                        <h2>Pengajuan Resep Saya ({{ $recipeSubmissions->count() }})</h2>
                    </div>

                    @forelse ($recipeSubmissions as $item)
                        @php $status = $item->approval_status ?? 'pending'; @endphp
                        <div class="admin-approval-card">
                            <div class="admin-approval-head">
                                <div style="flex: 1; min-width: 220px;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <strong style="font-size: 16px; font-family: 'Playfair Display', serif; color: var(--terracotta);">{{ $item->title }}</strong>
                                        <span class="status-pill {{ $status }}">{{ ucfirst($status) }}</span>
                                        @if (($item->revision_count ?? 0) > 0)
                                            <span style="font-size: 11px; color: var(--muted);">Revisi ke-{{ $item->revision_count }}</span>
                                        @endif
                                    </div>
                                    <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">
                                        {{ $item->food?->name ?? 'Resep Bali' }} &middot;
                                        Diperbarui {{ ($item->last_revised_at ?? $item->updated_at)?->diffForHumans() }}
                                    </div>
                                </div>

                                <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                                    <a href="{{ route('recipes.show', $item) }}" class="action-btn review">Detail</a>
                                    @if (in_array($status, ['draft', 'pending', 'rejected'], true))
                                        <a href="{{ route('recipes.edit', $item) }}" class="btn-terracotta sm">
                                            {{ $status === 'rejected' ? 'Revisi & Kirim Ulang' : ($status === 'draft' ? 'Lanjutkan Draft' : 'Edit Resep') }}
                                        </a>
                                    @else
                                        <a href="{{ route('recipes.edit', $item) }}" class="pill-tag">Ajukan Revisi</a>
                                    @endif
                                    <form action="{{ route('recipes.destroy', $item) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus pengajuan resep ini secara permanen?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-btn reject">Hapus</button>
                                    </form>
                                </div>
                            </div>

                            @if ($status === 'rejected' && ! empty($item->rejection_reason))
                                <div class="alert-banner error" style="margin: 12px 0 0; font-size: 12.5px;">
                                    <strong>Alasan Penolakan:</strong> {{ $item->rejection_reason }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <p style="font-size: 13px; color: var(--muted); margin: 6px 0;">Belum ada pengiriman resep. Klik <strong>+ Kirim Resep</strong> untuk memulai.</p>
                    @endforelse
                </div>

                <div class="admin-card white">
                    <div class="admin-card-head">
                        <h2>Kiriman Cerita Kuliner Saya ({{ $storySubmissions->count() }})</h2>
                    </div>
                    @forelse ($storySubmissions as $item)
                        @php $status = $item->approval_status ?? 'pending'; @endphp
                        <div class="admin-approval-card">
                            <div class="admin-approval-head">
                                <div style="flex: 1; min-width: 220px;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <strong style="font-size: 16px; font-family: 'Playfair Display', serif; color: var(--terracotta);">{{ $item->title }}</strong>
                                        <span class="status-pill {{ $status }}">{{ ucfirst($status) }}</span>
                                    </div>
                                    <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">
                                        {{ $item->food?->name ?? 'Cerita Kuliner Bali' }} &middot;
                                        Dikirim {{ $item->created_at?->diffForHumans() }}
                                    </div>
                                </div>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                                    <a href="{{ route('stories.show', $item->slug) }}" class="action-btn review">Lihat Detail</a>
                                    <form action="{{ route('stories.destroy', $item) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus cerita kuliner ini secara permanen?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-btn reject">Hapus</button>
                                    </form>
                                </div>
                            </div>

                            @if ($status === 'rejected' && ! empty($item->rejection_reason))
                                <div class="alert-banner error" style="margin: 12px 0 0; font-size: 12.5px;">
                                    <strong>Alasan Penolakan:</strong> {{ $item->rejection_reason }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <p style="font-size: 13px; color: var(--muted); margin: 6px 0;">Belum ada pengiriman cerita kuliner.</p>
                    @endforelse
                </div>
            @endif
        </div>
    </section>
@endsection
