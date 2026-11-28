{{-- Mobile Capsule Trigger for Admin/Helper Dashboard --}}
<div class="mobile-dash-capsule-bar">
    <button type="button" class="mobile-dash-capsule-btn" data-drawer-open="admin-mobile-sidebar" aria-expanded="false">
        <img src="{{ asset('Assets/images/baliculinarylogo.png') }}" alt="Bali Culinary" class="capsule-mini-logo">
        <span>Menu Dashboard</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <line x1="4" y1="7" x2="20" y2="7" />
            <line x1="4" y1="12" x2="20" y2="12" />
            <line x1="4" y1="17" x2="20" y2="17" />
        </svg>
    </button>
    <a href="{{ route('home') }}" class="pill-tag">Beranda</a>
</div>

<div class="mobile-drawer-backdrop" data-drawer-backdrop="admin-mobile-sidebar"></div>

<aside class="admin-sidebar" id="admin-mobile-sidebar" data-mobile-drawer>
    <div class="mobile-drawer-head">
        <a href="{{ route('home') }}" class="bali-brand brand-with-logo">
            <img src="{{ asset('Assets/images/baliculinarylogo.png') }}" alt="Bali Culinary" class="brand-logo-img">
            <span>Bali <em style="font-style: normal; color: var(--terracotta);">Culinary</em></span>
        </a>
        <button type="button" class="mobile-drawer-close" data-drawer-close="admin-mobile-sidebar" aria-label="Tutup menu">&times;</button>
    </div>

    <nav class="admin-nav" aria-label="Menu Admin">
        @if (auth()->user()?->isAdmin())
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
        @endif
        <a href="{{ route('admin.recipes.approvals') }}" class="{{ request()->routeIs('admin.recipes.approvals') ? 'active' : '' }}">Persetujuan Resep</a>
        @if (auth()->user()?->isAdmin())
            <a href="{{ route('admin.contributors.index') }}" class="{{ request()->routeIs('admin.contributors.index') ? 'active' : '' }}">Pengajuan Contributor</a>
            <a href="{{ route('foods.index') }}" class="{{ request()->routeIs('foods.*') ? 'active' : '' }}">Food Management</a>
        @endif
        <a href="{{ route('admin.stories.index') }}" class="{{ request()->routeIs('admin.stories.*') ? 'active' : '' }}">Story Management</a>

        @if (auth()->user()?->isAdmin())
            <div class="admin-nav-divider"></div>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">User Management</a>
        @endif
    </nav>

    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="account-logout-btn">&#8617; Logout</button>
    </form>
</aside>
