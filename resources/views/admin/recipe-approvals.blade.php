<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persetujuan Resep — Admin Bali Culinary</title>
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
                    <h1>Persetujuan Resep</h1>
                    <p>Tinjau detail, edit informasi resep, dan setujui atau tolak pengajuan resep dari Contributor.</p>
                </div>
            </section>

            <div class="admin-body">
                @if (session('success'))
                    <div class="alert-banner success">{{ session('success') }}</div>
                @endif

                <div class="admin-card white">
                    <div class="admin-card-head" style="flex-wrap: wrap;">
                        <h2>Daftar Pengajuan Resep</h2>
                        <div class="admin-range-filters">
                            <a href="{{ route('admin.recipes.approvals', ['status' => 'pending']) }}" class="pill-tag {{ $statusFilter === 'pending' ? 'active' : '' }}">Pending ({{ $counts['pending'] }})</a>
                            <a href="{{ route('admin.recipes.approvals', ['status' => 'approved']) }}" class="pill-tag {{ $statusFilter === 'approved' ? 'active' : '' }}">Approved ({{ $counts['approved'] }})</a>
                            <a href="{{ route('admin.recipes.approvals', ['status' => 'rejected']) }}" class="pill-tag {{ $statusFilter === 'rejected' ? 'active' : '' }}">Rejected ({{ $counts['rejected'] }})</a>
                            <a href="{{ route('admin.recipes.approvals', ['status' => 'all']) }}" class="pill-tag {{ $statusFilter === 'all' ? 'active' : '' }}">Semua ({{ $counts['all'] }})</a>
                        </div>
                    </div>

                    @forelse ($recipes as $recipe)
                        <div class="admin-approval-card">
                            <div class="admin-approval-head">
                                <div class="admin-approval-media">
                                    @if ($recipe->food?->thumbnail && file_exists(public_path(ltrim($recipe->food->thumbnail, '/'))))
                                        <img src="{{ asset(ltrim($recipe->food->thumbnail, '/')) }}" alt="{{ $recipe->title }}">
                                    @else
                                        <span>{{ Str::limit($recipe->title, 14, '') }}</span>
                                    @endif
                                </div>
                                <div style="flex: 1; min-width: 200px;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <h3 style="margin: 0; font-family: 'Playfair Display', serif; font-size: 19px; color: var(--terracotta);">{{ $recipe->title }}</h3>
                                        <span class="status-pill {{ $recipe->approval_status }}">{{ ucfirst($recipe->approval_status) }}</span>
                                    </div>
                                    <p style="margin: 4px 0 0; font-size: 12.5px; color: var(--muted);">
                                        Makanan: <strong>{{ $recipe->food?->name ?? '-' }}</strong> &middot;
                                        Contributor: <strong>{{ $recipe->user?->name ?? 'Admin' }}</strong>
                                        @if ($recipe->user)
                                            (Level {{ $recipe->user->contributor_level ?? 1 }})
                                        @endif
                                        &middot; {{ $recipe->created_at?->format('d M Y') }}
                                    </p>
                                    <p style="margin: 4px 0 0; font-size: 12px; color: var(--muted);">
                                        Persiapan: {{ $recipe->prep_time_minutes ?? 0 }} mnt &middot;
                                        Memasak: {{ $recipe->cook_time_minutes ?? 0 }} mnt &middot;
                                        Porsi: {{ $recipe->servings ?? '-' }} &middot;
                                        Kesulitan: {{ ucfirst((string) $recipe->difficulty) }}
                                    </p>
                                </div>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                                    <a href="{{ route('recipes.show', $recipe) }}" class="action-btn review">Detail</a>
                                    <a href="{{ route('recipes.edit', $recipe) }}" class="pill-tag">Edit</a>
                                    @if (! $recipe->isApproved())
                                        <form action="{{ route('recipes.approve', $recipe) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="action-btn approve">Approve</button>
                                        </form>
                                    @endif
                                    @if (! $recipe->isRejected())
                                        <form action="{{ route('recipes.reject', $recipe) }}" method="POST" style="display: inline-flex; gap: 4px; align-items: center;">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="rejection_reason" placeholder="Alasan tolak..." class="form-control" style="height: 32px; width: 150px; font-size: 12px;">
                                            <button type="submit" class="action-btn reject">Reject</button>
                                        </form>
                                    @endif
                                    @if (auth()->user()?->isAdmin() || (int) $recipe->user_id === (int) auth()->id())
                                        <form action="{{ route('recipes.destroy', $recipe) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus pengajuan resep ini secara permanen?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn reject">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <details class="admin-approval-details">
                                <summary>Lihat Ringkasan Bahan ({{ $recipe->recipeIngredients->count() }}) &amp; Langkah ({{ $recipe->recipeSteps->count() }})</summary>
                                <div class="admin-approval-detail-grid">
                                    <div>
                                        <h4>Bahan-Bahan</h4>
                                        <ul>
                                            @forelse ($recipe->recipeIngredients as $ri)
                                                <li>{{ trim(($ri->amount ?? '').' '.($ri->unit ?? '').' '.($ri->ingredient?->name ?? '')) }}</li>
                                            @empty
                                                <li>Belum ada bahan tercatat.</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                    <div>
                                        <h4>Langkah Memasak</h4>
                                        <ol>
                                            @forelse ($recipe->recipeSteps as $step)
                                                <li>{{ $step->instruction }}</li>
                                            @empty
                                                <li>Belum ada langkah tercatat.</li>
                                            @endforelse
                                        </ol>
                                    </div>
                                </div>
                            </details>
                        </div>
                    @empty
                        <p style="font-size: 13.5px; color: var(--muted); margin: 12px 0;">Tidak ada pengajuan resep pada filter ini.</p>
                    @endforelse

                    @if ($recipes->hasPages())
                        <div style="margin-top: 18px;">
                            {{ $recipes->links() }}
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
</body>

</html>
