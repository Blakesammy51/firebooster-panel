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

    <section class="mt-8 rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-6" aria-labelledby="supplier-balance-heading">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-violet-300">Supplier account</p>
                <h2 id="supplier-balance-heading" class="mt-1 text-xl font-semibold">SMM Center balance</h2>
                <p class="mt-1 text-sm text-slate-400">Refresh to retrieve the live balance directly from SMM Center.</p>
            </div>
            <form method="POST" action="{{ route('admin.dashboard.supplier-balance.refresh') }}">
                @csrf
                <button type="submit" class="rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-300">
                    Refresh balance
                </button>
            </form>
        </div>

        @if ($balance = session('supplier_balance'))
            <div class="mt-5 rounded-xl border {{ $balance['is_low'] ? 'border-amber-700 bg-amber-950/30' : 'border-emerald-800 bg-emerald-950/30' }} p-4" role="status">
                <p class="text-sm {{ $balance['is_low'] ? 'text-amber-200' : 'text-emerald-200' }}">Current supplier balance</p>
                <p class="mt-1 text-3xl font-bold">{{ $balance['currency'] }} {{ number_format($balance['amount'], 4) }}</p>
                @if ($balance['is_low'])
                    <p class="mt-2 text-sm text-amber-200">Low balance warning: below the $5.00 USD reminder threshold. Review your supplier funds before submitting more orders.</p>
                @endif
                <p class="mt-2 text-xs text-slate-400">Last checked: {{ $balance['checked_at'] }}</p>
            </div>
        @else
            <p class="mt-5 rounded-xl border border-slate-700 bg-slate-950/60 p-4 text-sm text-slate-300">Balance has not been checked in this session. Use Refresh balance to request the latest supplier balance.</p>
        @endif

        @error('supplier_balance')
            <p class="mt-4 rounded-lg border border-rose-800 bg-rose-950/30 p-3 text-sm text-rose-200" role="alert">{{ $message }}</p>
        @enderror
        <p class="mt-3 text-xs text-slate-500">Read-only monitor. Firebooster will never deposit, withdraw, or change your supplier balance.</p>
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
            <a href="{{ route('admin.reports.supplier') }}" class="rounded-xl border border-emerald-800 bg-emerald-950/30 p-5 transition hover:bg-emerald-950/60">
                <span class="text-xs font-semibold uppercase tracking-wide text-emerald-300">Reports</span>
                <h3 class="mt-2 font-semibold">Supplier spending & profit →</h3>
                <p class="mt-2 text-sm text-slate-300">Review supplier charges, confirmed sales and verified margins</p>
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
