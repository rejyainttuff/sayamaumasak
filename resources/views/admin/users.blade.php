<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management — Admin Bali Culinary</title>
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
                    <h1>User Management</h1>
                    <p>Kelola seluruh pengguna terdaftar, hak akses role, level Contributor, serta ringkasan analitik.</p>
                </div>
            </section>

            <div class="admin-body">
                @if (session('success'))
                    <div class="alert-banner success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert-banner error">{{ session('error') }}</div>
                @endif

                <div class="admin-stats-grid">
                    <div class="admin-stat-card">
                        <span>Total Pengguna</span>
                        <strong>{{ $analyticsSummary['total_users'] }}</strong>
                    </div>
                    <div class="admin-stat-card">
                        <span>Admin</span>
                        <strong>{{ $analyticsSummary['admins'] }}</strong>
                    </div>
                    <div class="admin-stat-card">
                        <span>Kontributor</span>
                        <strong>{{ $analyticsSummary['contributors'] }}</strong>
                    </div>
                    <div class="admin-stat-card">
                        <span>Total Kunjungan</span>
                        <strong>{{ $analyticsSummary['total_visits'] }}</strong>
                    </div>
                </div>

                <div class="admin-card white">
                    <div class="admin-card-head" style="flex-wrap: wrap;">
                        <h2>Daftar Pengguna Terdaftar</h2>
                        <form action="{{ route('admin.users.index') }}" method="GET" style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama / username..." class="form-control" style="width: 210px; height: 36px;">
                            <select name="role" class="form-control" style="width: 140px; height: 36px;" onchange="this.form.submit()">
                                <option value="">Semua Role</option>
                                <option value="admin" @selected($roleFilter === 'admin')>Admin</option>
                                <option value="contributor" @selected($roleFilter === 'contributor')>Contributor</option>
                                <option value="helper" @selected($roleFilter === 'helper')>Helper</option>
                                <option value="user" @selected($roleFilter === 'user')>User</option>
                            </select>
                            <button type="submit" class="btn-terracotta sm">Cari</button>
                        </form>
                    </div>

                    <div class="admin-users-grid">
                        @forelse ($users as $member)
                            <article class="admin-user-card">
                                <div class="admin-user-card-top">
                                    <div class="admin-user-avatar">
                                        @if (! empty($member->avatar))
                                            <img src="{{ asset(ltrim($member->avatar, '/')) }}" alt="{{ $member->name }}">
                                        @else
                                            <span>{{ strtoupper(substr($member->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div style="min-width: 0;">
                                        <h3 class="admin-user-name">{{ $member->name }}</h3>
                                        <p class="admin-user-handle">{{ $member->display_username }}</p>
                                    </div>
                                </div>

                                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin: 10px 0;">
                                    <span class="pill-tag">{{ ucfirst($member->role) }}</span>
                                    @if ($member->isContributor() || $member->isAdmin())
                                        <span class="pill-tag">Level {{ $member->contributor_level ?? 1 }}</span>
                                    @endif
                                </div>

                                <details class="admin-approval-details">
                                    <summary>Detail &amp; Kelola Role</summary>
                                    <div style="margin-top: 8px; font-size: 12.5px; color: var(--muted); display: flex; flex-direction: column; gap: 4px;">
                                        <div>Email: <strong style="color: var(--text);">{{ $member->email }}</strong></div>
                                        <div>Waktu Baca: <strong style="color: var(--text);">{{ (int) floor(($member->reading_seconds ?? 0) / 60) }} menit</strong></div>
                                        <div>Resep: <strong>{{ $member->recipes_count }}</strong> &middot; Ulasan: <strong>{{ $member->reviews_count }}</strong></div>
                                        <div>Bergabung: {{ $member->created_at?->format('d M Y') }}</div>
                                    </div>

                                    <form action="{{ route('admin.users.role', $member) }}" method="POST" style="margin-top: 10px; display: flex; gap: 6px; align-items: center;">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" class="form-control" style="height: 34px; font-size: 12px;">
                                            <option value="user" @selected($member->role === 'user')>User</option>
                                            <option value="contributor" @selected($member->role === 'contributor')>Contributor</option>
                                            <option value="helper" @selected($member->role === 'helper')>Helper</option>
                                            <option value="admin" @selected($member->role === 'admin')>Admin</option>
                                        </select>
                                        <button type="submit" class="action-btn approve" style="height: 34px;">Simpan</button>
                                    </form>
                                </details>
                            </article>
                        @empty
                            <p style="font-size: 13.5px; color: var(--muted);">Tidak ada pengguna ditemukan.</p>
                        @endforelse
                    </div>

                    @if ($users->hasPages())
                        <div style="margin-top: 18px;">
                            {{ $users->links() }}
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
