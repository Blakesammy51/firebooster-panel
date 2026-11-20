<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Firebooster Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto max-w-6xl px-5 py-10">
        <header class="mb-10 flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.2em] text-violet-300">Firebooster control room</p>
                <h1 class="mt-2 text-3xl font-bold">Admin dashboard</h1>
                <p class="mt-2 text-sm text-slate-400">Signed in as {{ auth()->user()->email }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-lg border border-slate-700 px-4 py-2 text-sm hover:bg-slate-800">Sign out</button>
            </form>
        </header>
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="Workspace statistics">
            @foreach ([['Total accounts', $totalUsers], ['Customers', $customerUsers], ['Services', $totalServices], ['Order requests', $totalOrders]] as [$label, $value])
                <article class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
                    <p class="text-sm text-slate-400">{{ $label }}</p>
                    <p class="mt-3 text-3xl font-bold">{{ number_format($value) }}</p>
                </article>
            @endforeach
        </section>
        <section class="mt-8 grid gap-4 sm:grid-cols-2">
            <a href="{{ route('admin.services.index') }}" class="rounded-xl border border-violet-800 bg-violet-950/30 p-6 hover:bg-violet-950/60">
                <h2 class="font-semibold">Manage service catalogue →</h2>
                <p class="mt-2 text-sm text-slate-300">{{ $activeServices }} active of {{ $totalServices }} services</p>
            </a>
            <a href="{{ route('admin.orders.index') }}" class="rounded-xl border border-slate-800 bg-slate-900 p-6 hover:bg-slate-800">
                <h2 class="font-semibold">Review order requests →</h2>
                <p class="mt-2 text-sm text-slate-300">{{ $pendingOrders }} pending requests · {{ $totalOrders }} total</p>
            </a>
        </section>
        <section class="mt-4 rounded-xl border border-slate-800 bg-slate-900 p-6">
            <a href="{{ route('admin.users.index') }}" class="font-semibold text-violet-300">View customer accounts →</a>
            <p class="mt-2 text-sm text-slate-400">Search registered accounts and check their roles and email-verification status.</p>
        </section>
        <section class="mt-8 rounded-2xl border border-amber-800/60 bg-amber-950/20 p-6">
            <h2 class="font-semibold text-amber-200">Important: manual review only</h2>
            <p class="mt-2 text-sm leading-6 text-slate-300">
                Customers can submit order requests and admins can update their statuses. No payment is collected,
                no wallet is credited, and no SMM provider receives these orders. Do not accept customer funds yet.
            </p>
        </section>
    </main>
</body>
</html>
