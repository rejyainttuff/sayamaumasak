<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Contributor — Admin Bali Culinary</title>
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
                    <h1>Pengajuan Contributor</h1>
                    <p>Tinjau seluruh pengajuan pengguna yang ingin bergabung menjadi Contributor Bali Culinary Guide.</p>
                </div>
            </section>

            <div class="admin-body">
                @if (session('success'))
                    <div class="alert-banner success">{{ session('success') }}</div>
                @endif

                <div class="admin-card white">
                    <div class="admin-card-head" style="flex-wrap: wrap;">
                        <h2>Daftar Pengajuan Contributor</h2>
                        <div class="admin-range-filters">
                            <a href="{{ route('admin.contributors.index', ['status' => 'all']) }}" class="pill-tag {{ $statusFilter === 'all' ? 'active' : '' }}">Semua ({{ $counts['all'] }})</a>
                            <a href="{{ route('admin.contributors.index', ['status' => 'pending']) }}" class="pill-tag {{ $statusFilter === 'pending' ? 'active' : '' }}">Pending ({{ $counts['pending'] }})</a>
                            <a href="{{ route('admin.contributors.index', ['status' => 'approved']) }}" class="pill-tag {{ $statusFilter === 'approved' ? 'active' : '' }}">Approved ({{ $counts['approved'] }})</a>
                            <a href="{{ route('admin.contributors.index', ['status' => 'rejected']) }}" class="pill-tag {{ $statusFilter === 'rejected' ? 'active' : '' }}">Rejected ({{ $counts['rejected'] }})</a>
                        </div>
                    </div>

                    @forelse ($requests as $req)
                        <div class="admin-approval-card">
                            <div class="admin-approval-head">
                                <div style="flex: 1; min-width: 240px;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <h3 style="margin: 0; font-family: 'Playfair Display', serif; font-size: 19px; color: var(--terracotta);">
                                            {{ $req->user?->name ?? 'Pengguna' }}
                                        </h3>
                                        <span class="status-pill {{ $req->status }}">{{ ucfirst($req->status) }}</span>
                                    </div>
                                    <p style="margin: 4px 0 0; font-size: 12.5px; color: var(--muted);">
                                        Username: <strong>{{ $req->user?->display_username ?? '-' }}</strong> &middot;
                                        Email: <strong>{{ $req->user?->email ?? '-' }}</strong> &middot;
                                        Tanggal: <strong>{{ $req->created_at?->format('d M Y, H:i') }}</strong>
                                    </p>
                                    @if ($req->specialty)
                                        <p style="margin: 4px 0 0; font-size: 12px; color: var(--text);">
                                            Keahlian: <strong>{{ $req->specialty }}</strong>
                                        </p>
                                    @endif
                                </div>

                                <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                                    @if ($req->status !== 'approved')
                                        <form action="{{ route('admin.contributor.status', $req) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" class="action-btn approve">Setujui</button>
                                        </form>
                                    @endif
                                    @if ($req->status !== 'rejected')
                                        <form action="{{ route('admin.contributor.status', $req) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" class="action-btn reject">Tolak</button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <details class="admin-approval-details" open>
                                <summary>Alasan Pengajuan ({{ mb_strlen((string) $req->reason) }} karakter)</summary>
                                <p style="margin: 8px 0 0; font-size: 13px; line-height: 1.65; color: var(--text); white-space: pre-line;">{{ $req->reason }}</p>
                            </details>
                        </div>
                    @empty
                        <p style="font-size: 13.5px; color: var(--muted); margin: 12px 0;">Belum ada pengajuan Contributor.</p>
                    @endforelse

                    @if ($requests->hasPages())
                        <div style="margin-top: 18px;">
                            {{ $requests->links() }}
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
