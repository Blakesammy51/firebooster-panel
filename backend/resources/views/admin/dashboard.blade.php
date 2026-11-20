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

        <section class="grid gap-4 sm:grid-cols-3" aria-label="Account statistics">
            @foreach ([['Total accounts', $totalUsers], ['Customers', $customerUsers], ['Administrators', $adminUsers]] as [$label, $value])
                <article class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
                    <p class="text-sm text-slate-400">{{ $label }}</p>
                    <p class="mt-3 text-3xl font-bold">{{ number_format($value) }}</p>
                </article>
            @endforeach
        </section>

        <section class="mt-8 rounded-2xl border border-violet-900/60 bg-violet-950/30 p-6">
            <h2 class="font-semibold">Foundation status</h2>
            <p class="mt-2 text-sm leading-6 text-slate-300">
                Authentication and role protection are the first backend milestone. Orders, provider integrations,
                deposits, and wallet accounting are not active yet. Do not accept customer payments at this stage.
            </p>
        </section>
    </main>
</body>
</html>
