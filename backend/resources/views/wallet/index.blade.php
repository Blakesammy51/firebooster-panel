<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Wallet · Firebooster</title>
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white">
<main class="mx-auto max-w-5xl px-4 py-7 sm:px-5 sm:py-10">
<div class="flex flex-wrap items-center justify-between gap-3">
<a href="{{ route('dashboard') }}" class="text-sm text-violet-300 hover:text-violet-200">← Back to dashboard</a>
<nav class="flex gap-4 text-sm text-slate-300">
<a href="{{ route('services.index') }}" class="hover:text-white">Browse services</a>
<a href="{{ route('orders.index') }}" class="hover:text-white">My orders</a>
</nav>
</div>

<h1 class="mt-6 text-3xl font-bold">My Wallet</h1>
<p class="mt-2 text-sm text-slate-400">Manage your balance and keep track of every wallet transaction.</p>

@if (session('status'))
<p class="mt-5 rounded-xl border border-emerald-800 bg-emerald-950/30 p-4 text-sm text-emerald-200">{{ session('status') }}</p>
@endif
@if ($errors->any())
<div class="mt-5 rounded-xl border border-red-800 bg-red-950/30 p-4 text-sm text-red-200">
@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
</div>
@endif

<section class="mt-7 rounded-2xl border border-violet-700/50 bg-gradient-to-br from-violet-950/70 to-slate-900 p-6 sm:p-8">
<p class="text-sm font-medium text-violet-200">Available balance</p>
<p class="mt-2 text-4xl font-bold tracking-tight sm:text-5xl">₦{{ number_format($wallet->balance_kobo / 100, 2) }}</p>
<p class="mt-3 max-w-xl text-sm leading-6 text-slate-300">This is the amount currently available to pay for services. Deposits are added only after Paystack confirms payment on the server.</p>
</section>

<section class="mt-6 rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-6">
<div class="flex flex-wrap items-start justify-between gap-2">
<div>
<h2 class="text-xl font-semibold">Add funds</h2>
<p class="mt-1 text-sm text-slate-400">Top up your wallet securely through Paystack.</p>
</div>
<span class="rounded-full border border-violet-800 bg-violet-950/50 px-3 py-1 text-xs text-violet-200">Secure checkout</span>
</div>
<form method="POST" action="{{ route('wallet.deposit.store') }}" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
@csrf
<label class="flex-1 text-sm text-slate-300">Amount (NGN)
<input name="amount" type="number" inputmode="decimal" min="100" max="1000000" step="0.01" required value="{{ old('amount') }}" placeholder="e.g. 2000" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-white placeholder:text-slate-600 focus:border-violet-500 focus:outline-none">
</label>
<button type="submit" class="rounded-lg bg-violet-600 px-6 py-3 font-semibold text-white transition hover:bg-violet-500">Continue to Paystack</button>
</form>
<p class="mt-3 text-xs text-slate-500">Minimum ₦100.00 · Maximum ₦1,000,000.00 per deposit.</p>
</section>

<section class="mt-6 rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-6">
<div class="flex flex-wrap items-center justify-between gap-2">
<div>
<h2 class="text-xl font-semibold">Transaction history</h2>
<p class="mt-1 text-sm text-slate-400">Deposits, service payments, and completed refunds in one place.</p>
<p class="mt-2 text-xs text-emerald-300">Order refund history is included below with your other wallet transactions.</p>
</div>
<span class="text-xs text-slate-500">Latest first</span>
</div>

@if ($transactions->isEmpty())
<div class="mt-5 rounded-xl border border-dashed border-slate-700 bg-slate-950/50 px-4 py-8 text-center">
<p class="font-medium text-slate-200">No transactions yet</p>
<p class="mt-1 text-sm text-slate-400">Your deposits, service payments, and refunds will appear here.</p>
</div>
@else
<div class="mt-5 space-y-3">
@foreach ($transactions as $transaction)
@php
    $isCredit = in_array($transaction->type, ['deposit', 'order_refund'], true);
    $typeLabel = match ($transaction->type) {
        'deposit' => 'Wallet deposit',
        'order_payment' => 'Service order payment',
        'order_refund' => 'Order refund',
        default => ucwords(str_replace('_', ' ', $transaction->type)),
    };
    $statusClass = match ($transaction->status) {
        'completed' => 'border-emerald-800 bg-emerald-950/40 text-emerald-300',
        'pending' => 'border-amber-800 bg-amber-950/40 text-amber-300',
        'failed', 'abandoned' => 'border-red-800 bg-red-950/40 text-red-300',
        default => 'border-slate-700 bg-slate-800 text-slate-300',
    };
@endphp
<article class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
<div class="min-w-0">
<div class="flex flex-wrap items-center gap-2">
<p class="font-semibold {{ $isCredit ? 'text-emerald-200' : 'text-slate-100' }}">{{ $typeLabel }}</p>
<span class="rounded-full border px-2.5 py-1 text-xs {{ $statusClass }}">{{ ucfirst($transaction->status) }}</span>
</div>
<p class="mt-1 break-words text-xs leading-5 text-slate-400">{{ $transaction->description ?? $transaction->reference ?? 'Wallet transaction' }}</p>
<p class="mt-1 text-xs text-slate-500">{{ $transaction->created_at->format('M j, Y · H:i') }}</p>
@if ($transaction->reference)
<p class="mt-1 break-all text-xs text-slate-600">Reference: {{ $transaction->reference }}</p>
@endif
</div>
<p class="shrink-0 text-lg font-bold {{ $isCredit ? 'text-emerald-300' : 'text-slate-100' }}">
{{ $isCredit ? '+' : ($transaction->type === 'order_payment' ? '−' : '') }}₦{{ number_format(abs($transaction->amount_kobo) / 100, 2) }}
</p>
</div>
</article>
@endforeach
</div>
<div class="mt-5">{{ $transactions->links() }}</div>
@endif
</section>

<p class="mt-5 text-xs leading-5 text-slate-500">Deposits increase your balance after payment confirmation. Service payments reduce your balance when an order is placed. Completed order refunds are credited back to your wallet and shown as positive transactions.</p>

@unless (config('services.paystack.secret_key'))
<p class="mt-6 rounded-xl border border-amber-800/50 bg-amber-950/20 p-4 text-sm text-amber-200">Paystack is not configured yet. No payments can be collected.</p>
@endunless
</main>

<a href="https://t.me/FireboosterSupport" target="_blank" rel="noopener noreferrer"
   aria-label="Contact Firebooster support on Telegram"
   class="fixed bottom-6 right-4 z-50 inline-flex items-center gap-2 rounded-full border border-sky-400/30 bg-sky-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-950/40 transition hover:bg-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-300 focus:ring-offset-2 focus:ring-offset-slate-950">
    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true"><path d="M21.8 4.2 18.6 19c-.2 1.1-.9 1.4-1.8.9l-5-3.7-2.4 2.3c-.3.3-.5.5-1 .5l.4-5.1 9.3-8.4c.4-.4-.1-.6-.6-.2L6 12.5l-4.9-1.5c-1.1-.3-1.1-1 .2-1.5L20.5 2.7c.9-.3 1.7.2 1.3 1.5Z"/></svg>
    <span>Telegram Support</span>
</a>

</body>
</html>
