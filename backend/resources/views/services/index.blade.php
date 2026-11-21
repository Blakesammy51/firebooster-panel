<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Services · Firebooster</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-950 text-slate-100"><main class="mx-auto max-w-5xl px-5 py-10">
<header class="mb-8 flex flex-wrap items-center justify-between gap-4"><div><p class="text-sm uppercase tracking-widest text-violet-300">Firebooster</p><h1 class="mt-2 text-3xl font-bold">Available services</h1><p class="mt-2 text-sm text-slate-300">Wallet balance: <strong class="text-emerald-300">₦{{ number_format($wallet->balance_kobo / 100, 2) }}</strong></p></div><div class="flex gap-4"><a class="text-violet-300 underline" href="{{ route('wallet.index') }}">My wallet</a><a class="text-violet-300 underline" href="{{ route('orders.index') }}">My orders</a></div></header>
@if (session('status'))<p class="mb-5 rounded-lg bg-emerald-950 p-4 text-emerald-200">{{ session('status') }}</p>@endif
@if ($errors->any())<div class="mb-5 rounded-lg bg-red-950 p-4 text-red-200">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<div class="grid gap-4 md:grid-cols-2">@forelse ($services as $service)
<article class="rounded-2xl border border-slate-800 bg-slate-900 p-5"><p class="text-xs uppercase tracking-wider text-violet-300">{{ $service->platform }}</p><h2 class="mt-2 text-xl font-semibold">{{ $service->name }}</h2><p class="mt-2 text-sm text-slate-400">{{ $service->description }}</p><p class="mt-4 font-semibold">₦{{ number_format((float) $service->price_per_1000, 2) }} <span class="text-xs text-slate-400">per 1,000</span></p>
<p class="mt-1 text-xs text-slate-400">Minimum: {{ number_format($service->minimum_quantity) }} · Maximum: {{ number_format($service->maximum_quantity) }}</p>
<form class="order-form mt-5 space-y-3" method="POST" action="{{ route('orders.store') }}" data-price-per-1000="{{ (float) $service->price_per_1000 }}" data-wallet-kobo="{{ $wallet->balance_kobo }}">@csrf<input type="hidden" name="service_id" value="{{ $service->id }}">
<label class="block text-sm">Target URL<input required type="url" name="target_url" placeholder="https://..." class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 p-3"></label>
<label class="block text-sm">Quantity<input class="quantity-input mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 p-3" required type="number" name="quantity" min="{{ $service->minimum_quantity }}" max="{{ $service->maximum_quantity }}" value="{{ $service->minimum_quantity }}" step="1"></label>
<div class="rounded-xl border border-slate-700 bg-slate-950 p-4 text-sm"><div class="flex justify-between gap-3"><span class="text-slate-400">Estimated total</span><strong class="order-total text-lg text-emerald-300">₦0.00</strong></div><div class="mt-2 flex justify-between gap-3"><span class="text-slate-400">Wallet balance</span><span class="wallet-balance">₦{{ number_format($wallet->balance_kobo / 100, 2) }}</span></div><div class="mt-2 flex justify-between gap-3"><span class="text-slate-400">Balance after order</span><strong class="balance-after">—</strong></div><p class="order-feedback mt-3 text-xs text-slate-400" aria-live="polite">Total updates when you change the quantity. Final amount is confirmed by the server.</p></div>
<button class="w-full rounded-lg bg-violet-600 px-4 py-3 font-semibold">Place order · Pay from wallet</button></form></article>
@empty<p class="rounded-xl border border-slate-800 p-6 text-slate-400">No services are available yet. Check back later.</p>@endforelse</div>{{ $services->links() }}
<p class="mt-8 text-xs text-amber-300">Orders are paid from your wallet and placed in the manual review queue. They are not automatically sent to an SMM provider yet.</p></main>
<script>
document.querySelectorAll('.order-form').forEach((form) => {
    const quantity = form.querySelector('.quantity-input');
    const total = form.querySelector('.order-total');
    const balanceAfter = form.querySelector('.balance-after');
    const feedback = form.querySelector('.order-feedback');
    const pricePer1000 = Number(form.dataset.pricePer1000);
    const walletKobo = Number(form.dataset.walletKobo);
    const money = (kobo) => '₦' + (kobo / 100).toFixed(2);

    const updateEstimate = () => {
        const amount = Number(quantity.value);
        if (!Number.isSafeInteger(amount) || amount < Number(quantity.min) || amount > Number(quantity.max)) {
            total.textContent = '—';
            balanceAfter.textContent = '—';
            feedback.textContent = 'Enter a quantity within the allowed limits.';
            feedback.className = 'order-feedback mt-3 text-xs text-amber-300';
            return;
        }

        // Match the server's kobo-based rounding calculation.
        const unitPriceKobo = Math.round(pricePer1000 * 100);
        const totalKobo = Math.floor((unitPriceKobo * amount + 500) / 1000);
        const remainingKobo = walletKobo - totalKobo;
        total.textContent = money(totalKobo);
        balanceAfter.textContent = money(remainingKobo);
        balanceAfter.className = remainingKobo < 0 ? 'balance-after text-red-300' : 'balance-after text-emerald-300';
        feedback.textContent = remainingKobo < 0 ? 'Your wallet balance is too low for this order.' : 'Estimated amount only. The server recalculates the final charge before payment.';
        feedback.className = remainingKobo < 0 ? 'order-feedback mt-3 text-xs text-red-300' : 'order-feedback mt-3 text-xs text-slate-400';
    };

    quantity.addEventListener('input', updateEstimate);
    updateEstimate();
});
</script></body></html>
