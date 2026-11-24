<header class="admin-topbar">
    <a href="{{ route('admin.dashboard') }}" class="admin-brand">
        <span class="admin-brand-mark">F</span>
        <span><strong>Firebooster</strong><small>ADMIN CONTROL ROOM</small></span>
    </a>
    <nav class="admin-nav" aria-label="Admin navigation">
        <a href="{{ route('admin.dashboard') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.dashboard')])>Overview</a>
        <a href="{{ route('admin.orders.index') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.orders.*')])>Orders</a>
        <a href="{{ route('admin.services.index') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.services.*')])>Catalogue</a>
        <a href="{{ route('admin.reports.supplier') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.reports.*')])>Finance</a>
        <a href="{{ route('admin.users.index') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.users.*')])>Customers</a>
    </nav>
    <div class="admin-account">
        <span class="admin-account-email">{{ auth()->user()->email }}</span>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button type="submit" class="admin-signout">Sign out</button>
        </form>
    </div>
</header>