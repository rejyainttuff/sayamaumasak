<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Story Management — Admin Bali Culinary</title>
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
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <h1>Story Management</h1>
                    <p>Kelola seluruh Cerita Kuliner: tinjau pengajuan, periksa galeri foto, setujui, tolak, atau hapus cerita.</p>
                </div>
            </section>

            <div class="admin-body">
                @if (session('success'))
                    <div class="alert-banner success">{{ session('success') }}</div>
                @endif

                <div class="admin-card white">
                    <div class="admin-card-head" style="flex-wrap: wrap;">
                        <h2>Daftar Cerita Kuliner</h2>
                        <div class="admin-range-filters">
                            <a href="{{ route('admin.stories.index', ['status' => 'all']) }}" class="pill-tag {{ $statusFilter === 'all' ? 'active' : '' }}">Semua ({{ $counts['all'] }})</a>
                            <a href="{{ route('admin.stories.index', ['status' => 'pending']) }}" class="pill-tag {{ $statusFilter === 'pending' ? 'active' : '' }}">Pending ({{ $counts['pending'] }})</a>
                            <a href="{{ route('admin.stories.index', ['status' => 'approved']) }}" class="pill-tag {{ $statusFilter === 'approved' ? 'active' : '' }}">Approved ({{ $counts['approved'] }})</a>
                            <a href="{{ route('admin.stories.index', ['status' => 'rejected']) }}" class="pill-tag {{ $statusFilter === 'rejected' ? 'active' : '' }}">Rejected ({{ $counts['rejected'] }})</a>
                        </div>
                    </div>

                    @forelse ($stories as $story)
                        @php
                            $status = $story->approval_status ?? 'pending';
                            $galleryCount = is_array($story->gallery_images) ? count($story->gallery_images) : 0;
                        @endphp
                        <div class="admin-approval-card">
                            <div class="admin-approval-head">
                                <div class="admin-approval-media">
                                    @if ($story->thumbnail && file_exists(public_path(ltrim($story->thumbnail, '/'))))
                                        <img src="{{ asset(ltrim($story->thumbnail, '/')) }}" alt="{{ $story->title }}">
                                    @else
                                        <span>{{ Str::limit($story->title, 10, '') }}</span>
                                    @endif
                                </div>
                                <div style="flex: 1; min-width: 220px;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <h3 style="margin: 0; font-family: 'Playfair Display', serif; font-size: 19px; color: var(--terracotta);">{{ $story->title }}</h3>
                                        <span class="status-pill {{ $status }}">{{ ucfirst($status) }}</span>
                                    </div>
                                    <p style="margin: 4px 0 0; font-size: 12.5px; color: var(--muted);">
                                        Penulis: <strong>{{ $story->user?->name ?? 'Admin' }}</strong>
                                        @if ($story->food)
                                            &middot; Kuliner: <strong>{{ $story->food->name }}</strong>
                                        @endif
                                        &middot; Foto Galeri: <strong>{{ $galleryCount }}</strong>
                                        &middot; {{ $story->created_at?->format('d M Y, H:i') }}
                                    </p>
                                </div>

                                <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                                    <a href="{{ route('stories.show', $story->slug) }}" class="action-btn review">Lihat Detail</a>
                                    @if ($status !== 'approved')
                                        <form action="{{ route('admin.stories.approve', $story) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="action-btn approve">Approve</button>
                                        </form>
                                    @endif
                                    @if ($status !== 'rejected')
                                        <form action="{{ route('admin.stories.reject', $story) }}" method="POST" style="display: inline-flex; gap: 4px; align-items: center;">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="rejection_reason" placeholder="Alasan penolakan..." class="form-control" style="height: 32px; width: 170px; font-size: 12px;">
                                            <button type="submit" class="action-btn reject">Reject</button>
                                        </form>
                                    @endif
                                    @if (auth()->user()?->isAdmin() || (int) $story->user_id === (int) auth()->id())
                                        <form action="{{ route('stories.destroy', $story) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus cerita kuliner ini secara permanen?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn reject">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            @if ($status === 'rejected' && ! empty($story->rejection_reason))
                                <div class="alert-banner error" style="margin: 10px 0 0; font-size: 12.5px;">
                                    <strong>Alasan Penolakan:</strong> {{ $story->rejection_reason }}
                                </div>
                            @endif

                            <details class="admin-approval-details">
                                <summary>Baca Pratinjau Isi Cerita &amp; Foto ({{ $galleryCount }} foto tambahan)</summary>
                                <p style="margin: 10px 0; font-size: 13px; line-height: 1.65; color: var(--text); white-space: pre-line;">{{ Str::limit(strip_tags((string) $story->content), 600) }}</p>
                                @if ($galleryCount > 0)
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                                        @foreach ($story->gallery_images as $img)
                                            <img src="{{ asset(ltrim($img, '/')) }}" alt="Foto galeri" style="width: 78px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border);">
                                        @endforeach
                                    </div>
                                @endif
                            </details>
                        </div>
                    @empty
                        <p style="font-size: 13.5px; color: var(--muted); margin: 12px 0;">Tidak ada pengajuan cerita kuliner pada filter ini.</p>
                    @endforelse

                    @if ($stories->hasPages())
                        <div style="margin-top: 18px;">
                            {{ $stories->links() }}
                        </div>
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
    <script src="{{ asset('Assets/js/animations.js') }}" defer></script>
</body>

</html>
