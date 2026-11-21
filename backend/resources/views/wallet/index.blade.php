<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Wallet · Firebooster</title>
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white">
<main class="mx-auto max-w-5xl px-5 py-10">
<a href="{{ route('dashboard') }}" class="text-sm text-violet-300">← Back to dashboard</a>
<h1 class="mt-5 text-3xl font-bold">My Wallet</h1>
<p class="mt-2 text-slate-400">Your Firebooster prepaid balance and transaction history.</p>
@if (session('status'))<p class="mt-5 rounded-xl border border-emerald-800 bg-emerald-950/30 p-4 text-sm text-emerald-200">{{ session('status') }}</p>@endif
@if ($errors->any())<div class="mt-5 rounded-xl border border-red-800 bg-red-950/30 p-4 text-sm text-red-200">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<section class="mt-8 rounded-2xl border border-violet-700/50 bg-violet-950/30 p-7">
<p class="text-sm text-violet-200">Available balance</p>
<p class="mt-2 text-4xl font-bold">₦{{ number_format($wallet->balance_kobo / 100, 2) }}</p>
<p class="mt-3 text-sm text-slate-300">Your wallet is credited only after server-side payment confirmation.</p>
</section>
<section class="mt-8 rounded-2xl border border-slate-800 bg-slate-900 p-6">
<h2 class="text-xl font-semibold">Add funds</h2>
<p class="mt-2 text-sm text-slate-400">Payments are processed securely by Paystack.</p>
<form method="POST" action="{{ route('wallet.deposit.store') }}" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
@csrf
<label class="flex-1 text-sm text-slate-300">Amount (NGN)
<input name="amount" type="number" inputmode="decimal" min="100" max="1000000" step="0.01" required value="{{ old('amount') }}" placeholder="e.g. 2000" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-white">
</label>
<button type="submit" class="rounded-lg bg-violet-600 px-6 py-3 font-semibold text-white hover:bg-violet-500">Continue to Paystack</button>
</form>
<p class="mt-3 text-xs text-slate-500">Minimum ₦100.00. Maximum ₦1,000,000.00 per deposit.</p>
</section>
<section class="mt-8 rounded-2xl border border-emerald-800/60 bg-emerald-950/20 p-6">
<h2 class="text-xl font-semibold text-emerald-200">Order refund history</h2>
<p class="mt-2 text-sm text-slate-400">Completed refunds returned to your wallet appear here.</p>
@if ($refundTransactions->isEmpty())
<p class="mt-4 text-sm text-slate-400">No order refunds have been credited to your wallet yet.</p>
@else
<div class="mt-4 space-y-3">
@foreach ($refundTransactions as $refund)
<div class="flex flex-wrap items-center justify-between gap-3 border-b border-emerald-900/70 pb-3">
<div>
<p class="font-medium text-emerald-200">Order refund · {{ ucfirst($refund->status) }}</p>
<p class="text-xs text-slate-400">{{ $refund->description ?? ('Refund for ' . $refund->reference) }} · {{ $refund->created_at->format('M j, Y H:i') }}</p>
</div>
<p class="font-semibold text-emerald-300">+₦{{ number_format($refund->amount_kobo / 100, 2) }}</p>
</div>
@endforeach
</div>
<div class="mt-5">{{ $refundTransactions->links() }}</div>
@endif
</section>
<section class="mt-8 rounded-2xl border border-slate-800 bg-slate-900 p-6">
<h2 class="text-xl font-semibold">Transaction history</h2>
@if ($transactions->isEmpty())
<p class="mt-4 text-sm text-slate-400">No wallet transactions yet.</p>
@else
<div class="mt-4 space-y-3">
@foreach ($transactions as $transaction)
<div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
<div><p class="font-medium">{{ str_replace('_', ' ', ucfirst($transaction->type)) }} · {{ ucfirst($transaction->status) }}</p>
<p class="text-xs text-slate-400">{{ $transaction->description ?? $transaction->reference ?? 'Wallet transaction' }} · {{ $transaction->created_at->format('M j, Y H:i') }}</p></div>
<p class="font-semibold">{{ $transaction->type === 'order_refund' ? '+' : '' }}₦{{ number_format($transaction->amount_kobo / 100, 2) }}</p>
</div>
@endforeach
</div>
<div class="mt-5">{{ $transactions->links() }}</div>
@endif
</section>
@unless (config('services.paystack.secret_key'))
<p class="mt-6 rounded-xl border border-amber-800/50 bg-amber-950/20 p-4 text-sm text-amber-200">Paystack is not configured yet. No payments can be collected.</p>
@endunless
</main>
</body>
</html>
