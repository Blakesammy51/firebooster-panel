<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin dashboard · Firebooster</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
<main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10">
    <header class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.2em] text-violet-300">Firebooster control room</p>
            <h1 class="mt-2 text-3xl font-bold">Admin dashboard</h1>
            <p class="mt-2 break-all text-sm text-slate-400">Signed in as {{ auth()->user()->email }}</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="rounded-lg border border-slate-700 px-4 py-2 text-sm hover:bg-slate-800">Sign out</button>
        </form>
    </header>

    <section class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4" aria-label="Workspace statistics">
        @foreach ([['Total accounts', $totalUsers], ['Customers', $customerUsers], ['Total services', $totalServices], ['Total orders', $totalOrders]] as [$label, $value])
            <article class="rounded-xl border border-slate-800 bg-slate-900 p-4 sm:rounded-2xl sm:p-6">
                <p class="text-xs text-slate-400 sm:text-sm">{{ $label }}</p>
                <p class="mt-2 text-2xl font-bold sm:mt-3 sm:text-3xl">{{ number_format($value) }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
            <div>
                <h2 class="text-xl font-semibold">Quick actions</h2>
                <p class="mt-1 text-sm text-slate-400">Manage the main parts of your store.</p>
            </div>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('admin.services.index') }}" class="rounded-xl border border-violet-800 bg-violet-950/30 p-5 transition hover:bg-violet-950/60">
                <span class="text-xs font-semibold uppercase tracking-wide text-violet-300">Catalogue</span>
                <h3 class="mt-2 font-semibold">Manage services →</h3>
                <p class="mt-2 text-sm text-slate-300">{{ $activeServices }} active of {{ $totalServices }} services</p>
            </a>
            <a href="{{ route('admin.orders.index') }}" class="rounded-xl border border-slate-800 bg-slate-900 p-5 transition hover:bg-slate-800">
                <span class="text-xs font-semibold uppercase tracking-wide text-amber-300">Fulfilment</span>
                <h3 class="mt-2 font-semibold">Review orders →</h3>
                <p class="mt-2 text-sm text-slate-300">{{ $pendingOrders }} pending · {{ $totalOrders }} total orders</p>
            </a>
            <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-slate-800 bg-slate-900 p-5 transition hover:bg-slate-800">
                <span class="text-xs font-semibold uppercase tracking-wide text-sky-300">Customers</span>
                <h3 class="mt-2 font-semibold">View customer accounts →</h3>
                <p class="mt-2 text-sm text-slate-300">{{ $customerUsers }} registered customers</p>
            </a>
        </div>
    </section>

    <section class="mt-8">
        <h2 class="mb-4 text-xl font-semibold">System overview</h2>
        <div class="grid gap-3 md:grid-cols-2">
            <article class="rounded-xl border border-emerald-900 bg-emerald-950/30 p-5">
                <h3 class="font-semibold text-emerald-200">Paystack & wallet</h3>
                <p class="mt-2 text-sm leading-6 text-slate-300">Deposits are credited after server-side payment confirmation. Wallet activity is recorded in the customer transaction history.</p>
            </article>
            <article class="rounded-xl border border-violet-900 bg-violet-950/30 p-5">
                <h3 class="font-semibold text-violet-200">SMM Center fulfilment</h3>
                <p class="mt-2 text-sm leading-6 text-slate-300">Provider submission is a separate admin action. Review each order and confirm supplier status before retrying an uncertain submission.</p>
            </article>
            <article class="rounded-xl border border-slate-800 bg-slate-900 p-5 md:col-span-2">
                <h3 class="font-semibold">Refund controls</h3>
                <p class="mt-2 text-sm leading-6 text-slate-300">Eligible order refunds are handled from order review and appear in the customer's wallet history after they are credited.</p>
            </article>
        </div>
    </section>
</main>
</body>
</html>
