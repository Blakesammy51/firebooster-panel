<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My Wallet · Firebooster</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white">
<main class="mx-auto max-w-5xl px-5 py-10">
    <a href="{{ route('dashboard') }}" class="text-sm text-violet-300">← Back to dashboard</a>
    <h1 class="mt-5 text-3xl font-bold">My Wallet</h1>
    <p class="mt-2 text-slate-400">Your Firebooster prepaid balance and transaction history.</p>

    <section class="mt-8 rounded-2xl border border-violet-700/50 bg-violet-950/30 p-7">
        <p class="text-sm text-violet-200">Available balance</p>
        <p class="mt-2 text-4xl font-bold">₦{{ number_format($wallet->balance_kobo / 100, 2) }}</p>
        <p class="mt-3 text-sm text-slate-300">Wallet is being set up. Deposits are not enabled yet.</p>
    </section>

    <section class="mt-8 rounded-2xl border border-slate-800 bg-slate-900 p-6">
        <h2 class="text-xl font-semibold">Transaction history</h2>
        @if ($transactions->isEmpty())
            <p class="mt-4 text-sm text-slate-400">No wallet transactions yet.</p>
        @else
            <div class="mt-4 space-y-3">
                @foreach ($transactions as $transaction)
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                        <div>
                            <p class="font-medium">{{ str_replace('_', ' ', ucfirst($transaction->type)) }}</p>
                            <p class="text-xs text-slate-400">{{ $transaction->description ?? $transaction->reference ?? 'Wallet transaction' }} · {{ $transaction->created_at->format('M j, Y H:i') }}</p>
                        </div>
                        <p class="font-semibold">₦{{ number_format($transaction->amount_kobo / 100, 2) }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-5">{{ $transactions->links() }}</div>
        @endif
    </section>

    <p class="mt-6 rounded-xl border border-amber-800/50 bg-amber-950/20 p-4 text-sm text-amber-200">
        Staging mode: no deposits, wallet credits, or wallet-funded orders are enabled. Never send money to an account claiming to be Firebooster.
    </p>
</main>
</body>
</html>