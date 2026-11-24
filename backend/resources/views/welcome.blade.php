<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Firebooster · Social growth made simple</title>
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white">
<nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
<a href="/" class="text-xl font-black tracking-tight">🔥 FIREBOOSTER</a>
<div class="flex items-center gap-3">
@auth
<a href="{{ route('dashboard') }}" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold">Dashboard</a>
@else
<a href="{{ route('login') }}" class="px-3 py-2 text-sm text-slate-300">Sign in</a>
<a href="{{ route('register') }}" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold">Get started</a>
@endauth
</div>
</nav>
<main class="mx-auto grid max-w-6xl gap-12 px-6 py-20 md:grid-cols-2 md:items-center">
<section>
<p class="text-sm font-bold uppercase tracking-[.25em] text-violet-300">Your social media growth desk</p>
<h1 class="mt-5 text-5xl font-black leading-tight md:text-6xl">Make every campaign <span class="text-violet-400">count.</span></h1>
<p class="mt-6 max-w-xl text-lg leading-8 text-slate-300">Browse social media services, submit campaign requests and keep everything organised in one simple dashboard.</p>
<div class="mt-9 flex flex-wrap gap-3">
@auth
<a href="{{ route('services.index') }}" class="rounded-xl bg-violet-600 px-6 py-3 font-semibold">Browse services</a>
@else
<a href="{{ route('register') }}" class="rounded-xl bg-violet-600 px-6 py-3 font-semibold">Create free account</a>
<a href="{{ route('login') }}" class="rounded-xl border border-slate-700 px-6 py-3 font-semibold">Sign in</a>
@endauth
</div>
</section>
<section class="rounded-3xl border border-violet-500/30 bg-gradient-to-br from-violet-950/80 to-slate-900 p-8 shadow-2xl shadow-violet-950/40">
<div class="text-sm font-semibold text-violet-200">FIREBOOSTER WORKSPACE</div>
<div class="mt-8 grid grid-cols-2 gap-4">
<div class="rounded-2xl bg-slate-950/70 p-5"><div class="text-3xl">📣</div><h2 class="mt-3 font-bold">Campaigns</h2><p class="mt-1 text-sm text-slate-400">Keep requests in one place.</p></div>
<div class="rounded-2xl bg-slate-950/70 p-5"><div class="text-3xl">🧩</div><h2 class="mt-3 font-bold">Services</h2><p class="mt-1 text-sm text-slate-400">Explore available options.</p></div>
</div>
</section>
</main>
<footer class="mx-auto max-w-6xl px-6 pb-10 text-sm text-slate-500">© {{ date('Y') }} Firebooster. Built for campaign management.</footer>
</body></html>
