<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard · Firebooster</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white">
    <main class="mx-auto max-w-5xl px-4 py-6 pb-32 sm:px-6 sm:py-10">
        <header class="mb-7 flex items-center justify-between gap-4">
            <a href="/" class="inline-flex items-center gap-2 text-sm font-semibold tracking-wide text-violet-200">
                <span class="text-xl" aria-hidden="true">🔥</span> Firebooster
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-xl border border-violet-300/20 bg-slate-900/70 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-violet-300/50 hover:text-white">Sign out</button>
            </form>
        </header>

        <section class="relative mb-6 overflow-hidden rounded-3xl border border-violet-400/20 bg-gradient-to-br from-violet-950/80 via-slate-900/95 to-fuchsia-950/40 p-6 shadow-2xl shadow-violet-950/20 sm:p-8">
            <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-fuchsia-500/10 blur-3xl" aria-hidden="true"></div>
            <div class="relative">
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-300">Your Firebooster workspace</p>
                <h1 class="mt-3 max-w-2xl text-3xl font-extrabold leading-tight text-white sm:text-4xl">Welcome back, {{ auth()->user()->name }} <span aria-hidden="true">👋</span></h1>
                <p class="mt-3 max-w-xl text-sm leading-relaxed text-slate-300 sm:text-base">Your social growth tools are ready. Choose where you want to continue.</p>
                <a href="{{ route('services.index') }}" class="mt-6 inline-flex items-center justify-center gap-2 rounded-xl border border-fuchsia-200/20 bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-violet-950/40 transition hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-fuchsia-300">
                    Explore services <span aria-hidden="true">→</span>
                </a>
            </div>
        </section>

        @include('components.community-links')

        <section aria-labelledby="workspace-actions">
            <div class="mb-4 flex items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-violet-300">Quick access</p>
                    <h2 id="workspace-actions" class="mt-1 text-xl font-bold text-white sm:text-2xl">Manage your account</h2>
                </div>
                <span class="hidden text-xs text-slate-500 sm:inline">Everything in one place</span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="{{ route('services.index') }}" class="group rounded-2xl border border-violet-400/25 bg-gradient-to-br from-violet-950/55 to-slate-900/90 p-5 shadow-lg shadow-violet-950/10 transition hover:-translate-y-1 hover:border-violet-300/50 hover:shadow-violet-950/30 sm:p-6">
                    <span class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl border border-violet-300/20 bg-violet-500/15 text-2xl text-violet-200" aria-hidden="true">✦</span>
                    <span class="flex items-center justify-between gap-3"><span class="text-lg font-bold text-white">Browse services</span><span class="text-xl text-violet-300 transition group-hover:translate-x-1" aria-hidden="true">→</span></span>
                    <span class="mt-2 block text-sm leading-relaxed text-slate-400">Explore the active catalogue and choose a service for your next campaign.</span>
                    <span class="mt-5 block text-xs font-bold uppercase tracking-wider text-violet-300">Explore catalogue</span>
                </a>

                <a href="{{ route('wallet.index') }}" class="group rounded-2xl border border-emerald-400/20 bg-gradient-to-br from-emerald-950/35 to-slate-900/90 p-5 shadow-lg shadow-emerald-950/10 transition hover:-translate-y-1 hover:border-emerald-300/40 hover:shadow-emerald-950/25 sm:p-6">
                    <span class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl border border-emerald-300/20 bg-emerald-500/10 text-2xl text-emerald-200" aria-hidden="true">₦</span>
                    <span class="flex items-center justify-between gap-3"><span class="text-lg font-bold text-white">My wallet</span><span class="text-xl text-emerald-300 transition group-hover:translate-x-1" aria-hidden="true">→</span></span>
                    <span class="mt-2 block text-sm leading-relaxed text-slate-400">Add funds, check your available balance and review your transactions.</span>
                    <span class="mt-5 block text-xs font-bold uppercase tracking-wider text-emerald-300">Manage funds</span>
                </a>

                <a href="{{ route('orders.index') }}" class="group rounded-2xl border border-fuchsia-400/20 bg-gradient-to-br from-fuchsia-950/30 to-slate-900/90 p-5 shadow-lg shadow-fuchsia-950/10 transition hover:-translate-y-1 hover:border-fuchsia-300/40 hover:shadow-fuchsia-950/25 sm:p-6">
                    <span class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl border border-fuchsia-300/20 bg-fuchsia-500/10 text-2xl text-fuchsia-200" aria-hidden="true">▤</span>
                    <span class="flex items-center justify-between gap-3"><span class="text-lg font-bold text-white">My orders</span><span class="text-xl text-fuchsia-300 transition group-hover:translate-x-1" aria-hidden="true">→</span></span>
                    <span class="mt-2 block text-sm leading-relaxed text-slate-400">Track your submitted orders and check their latest delivery status.</span>
                    <span class="mt-5 block text-xs font-bold uppercase tracking-wider text-fuchsia-300">View order history</span>
                </a>
            </div>
        </section>

        <p class="mt-8 text-center text-xs text-slate-500">Firebooster · Your growth, made simpler.</p>
    </main>

    <a href="https://t.me/FireboosterSupport" target="_blank" rel="noopener noreferrer"
       aria-label="Contact Firebooster support on Telegram"
       class="fixed bottom-6 right-4 z-50 inline-flex items-center gap-2 rounded-full border border-fuchsia-300/25 bg-gradient-to-r from-violet-600 to-fuchsia-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-violet-950/50 transition hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-fuchsia-300 focus:ring-offset-2 focus:ring-offset-slate-950">
        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true"><path d="M21.8 4.2 18.6 19c-.2 1.1-.9 1.4-1.8.9l-5-3.7-2.4 2.3c-.3.3-.5.5-1 .5l.4-5.1 9.3-8.4c.4-.4-.1-.6-.6-.2L6 12.5l-4.9-1.5c-1.1-.3-1.1-1 .2-1.5L20.5 2.7c.9-.3 1.7.2 1.3 1.5Z"/></svg>
        <span>Telegram Support</span>
    </a>
</body>
</html>
