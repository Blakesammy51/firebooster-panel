<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage services · Firebooster</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
<main class="mx-auto max-w-6xl px-5 py-10">
    <a href="{{ route('admin.dashboard') }}" class="text-violet-300 underline">← Admin dashboard</a>
    <h1 class="my-6 text-3xl font-bold">Service catalogue</h1>

    <section class="mb-8 rounded-xl border border-violet-800 bg-slate-900 p-5">
        <h2 class="text-xl font-semibold">SMM Center supplier</h2>
        <p class="mt-2 text-sm text-slate-300">Imported supplier services: {{ $providerServiceCount }}. Sync retrieves the latest categories, service IDs, supplier rates, limits and refill/cancel flags.</p>
        <p class="mt-2 text-sm text-amber-200">New imports stay inactive with no customer selling price. Review supplier rates, set your own NGN price, and activate only the services you want to sell.</p>
        <form method="POST" action="{{ route('admin.services.smm-center.sync') }}" class="mt-4">
            @csrf
            <button class="rounded-lg bg-violet-600 px-4 py-3 font-semibold">Sync SMM Center services</button>
        </form>
    </section>

    @if (session('status'))
        <p class="mb-5 rounded-lg bg-emerald-950 p-4 text-emerald-200">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-lg bg-red-950 p-4 text-red-200">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <a href="{{ route('admin.services.index', array_filter(['status' => 'all', 'search' => $search])) }}"
           class="rounded-xl border p-4 {{ $status === 'all' ? 'border-violet-400 bg-violet-950' : 'border-slate-800 bg-slate-900' }}">
            <span class="block text-sm text-slate-300">All services</span>
            <strong class="mt-1 block text-2xl">{{ $activeServiceCount + $inactiveServiceCount }}</strong>
        </a>
        <a href="{{ route('admin.services.index', array_filter(['status' => 'active', 'search' => $search])) }}"
           class="rounded-xl border p-4 {{ $status === 'active' ? 'border-emerald-400 bg-emerald-950' : 'border-slate-800 bg-slate-900' }}">
            <span class="block text-sm text-slate-300">Active services</span>
            <strong class="mt-1 block text-2xl text-emerald-300">{{ $activeServiceCount }}</strong>
            <span class="text-xs text-slate-400">Visible to customers</span>
        </a>
        <a href="{{ route('admin.services.index', array_filter(['status' => 'inactive', 'search' => $search])) }}"
           class="rounded-xl border p-4 {{ $status === 'inactive' ? 'border-amber-400 bg-amber-950' : 'border-slate-800 bg-slate-900' }}">
            <span class="block text-sm text-slate-300">Inactive services</span>
            <strong class="mt-1 block text-2xl text-amber-300">{{ $inactiveServiceCount }}</strong>
            <span class="text-xs text-slate-400">Hidden from customers</span>
        </a>
    </section>

    <form method="GET" action="{{ route('admin.services.index') }}" class="mb-6 flex flex-col gap-3 rounded-xl border border-violet-800 bg-slate-900 p-4 sm:flex-row">
        <input type="hidden" name="status" value="{{ $status }}">
        <input name="search" value="{{ $search }}" maxlength="120" placeholder="Search name, platform, category or SMM Center ID…" aria-label="Search services" class="min-w-0 flex-1 rounded-lg bg-slate-950 p-3 text-slate-100">
        <button class="rounded-lg bg-violet-600 px-5 py-3 font-semibold">Search catalogue</button>
        @if ($search !== '' || $status !== 'all')
            <a href="{{ route('admin.services.index') }}" class="rounded-lg border border-slate-600 px-5 py-3 text-center">Clear filters</a>
        @endif
    </form>

    <p class="mb-4 text-sm text-slate-400">
        Showing {{ $services->total() }}
        @if ($status === 'active')
            active
        @elseif ($status === 'inactive')
            inactive
        @else
            total
        @endif
        services
        @if ($search !== '')
            matching <span class="font-semibold text-slate-200">{{ $search }}</span>
        @endif
        . Use the tabs above to switch service status.
    </p>

    <form method="POST" action="{{ route('admin.services.store') }}" class="mb-8 grid gap-3 rounded-xl border border-slate-800 bg-slate-900 p-5 md:grid-cols-2">
        @csrf
        <h2 class="text-xl font-semibold md:col-span-2">Add a service</h2>
        <input name="name" required maxlength="120" placeholder="Service name" class="rounded-lg bg-slate-950 p-3">
        <input name="platform" required maxlength="40" placeholder="Platform (Instagram, TikTok…)" class="rounded-lg bg-slate-950 p-3">
        <textarea name="description" required maxlength="2000" placeholder="Description" class="rounded-lg bg-slate-950 p-3 md:col-span-2"></textarea>
        <input name="price_per_1000" type="number" min="0" step="0.01" required placeholder="Price per 1,000 (NGN)" class="rounded-lg bg-slate-950 p-3">
        <input name="minimum_quantity" type="number" min="1" value="1" required placeholder="Minimum quantity" class="rounded-lg bg-slate-950 p-3">
        <input name="maximum_quantity" type="number" min="1" value="1000" required placeholder="Maximum quantity" class="rounded-lg bg-slate-950 p-3">
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" class="w-auto">
            Publish service
        </label>
        <button class="rounded-lg bg-violet-600 px-4 py-3 font-semibold md:col-span-2">Create service</button>
    </form>

    <div class="space-y-4">
        @forelse ($services as $service)
            <form method="POST" action="{{ route('admin.services.update', $service) }}" class="grid gap-3 rounded-xl border border-slate-800 bg-slate-900 p-5 md:grid-cols-2">
                @csrf
                @method('PUT')
                <h2 class="font-semibold md:col-span-2">#{{ $service->id }} · {{ $service->name }}</h2>

                @if ($service->provider === 'smm_center')
                    <p class="text-xs text-violet-200 md:col-span-2">
                        SMM Center ID {{ $service->provider_service_id }} · {{ $service->category }} ·
                        Supplier rate {{ $service->provider_rate }} {{ $service->provider_currency }} / 1,000 ·
                        Type {{ $service->provider_type }} ·
                        Refill {{ $service->provider_refill ? 'Yes' : 'No' }} ·
                        Cancel {{ $service->provider_cancel ? 'Yes' : 'No' }}
                    </p>
                @endif

                <input name="name" value="{{ $service->name }}" required class="rounded-lg bg-slate-950 p-3">
                <input name="platform" value="{{ $service->platform }}" required class="rounded-lg bg-slate-950 p-3">
                <textarea name="description" required class="rounded-lg bg-slate-950 p-3 md:col-span-2">{{ $service->description }}</textarea>
                <input name="price_per_1000" type="number" min="0" step="0.01" value="{{ $service->price_per_1000 }}" required class="rounded-lg bg-slate-950 p-3">
                <input name="minimum_quantity" type="number" min="1" value="{{ $service->minimum_quantity }}" required class="rounded-lg bg-slate-950 p-3">
                <input name="maximum_quantity" type="number" min="1" value="{{ $service->maximum_quantity }}" required class="rounded-lg bg-slate-950 p-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" @checked($service->is_active) class="w-auto">
                    Active and visible to customers
                </label>
                <button class="rounded-lg border border-violet-500 px-4 py-2 md:col-span-2">Save changes</button>
            </form>
        @empty
            <p class="text-slate-400">No matching services found.</p>
        @endforelse
    </div>

    {{ $services->links() }}
</main>
</body>
</html>
