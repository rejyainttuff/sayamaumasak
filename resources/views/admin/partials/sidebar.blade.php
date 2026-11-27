<aside class="admin-sidebar">
    <a href="{{ route('home') }}" class="bali-brand stacked">
        Bali
        <span>Culinary</span>
    </a>

    <nav class="admin-nav" aria-label="Menu Admin">
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('admin.recipes.approvals') }}" class="{{ request()->routeIs('admin.recipes.approvals') ? 'active' : '' }}">Persetujuan Resep</a>
        <a href="{{ route('admin.contributors.index') }}" class="{{ request()->routeIs('admin.contributors.index') ? 'active' : '' }}">Pengajuan Contributor</a>
        <a href="{{ route('foods.index') }}" class="{{ request()->routeIs('foods.*') ? 'active' : '' }}">Food Management</a>
        <a href="{{ route('recipes.index') }}" class="{{ request()->routeIs('recipes.index', 'recipes.edit') ? 'active' : '' }}">Recipe Management</a>
        <a href="{{ route('stories') }}" class="{{ request()->routeIs('stories*') ? 'active' : '' }}">Culinary Stories</a>

        <div class="admin-nav-divider"></div>

        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">User Management</a>
    </nav>

    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="account-logout-btn">&#8617; Logout</button>
    </form>
</aside>
