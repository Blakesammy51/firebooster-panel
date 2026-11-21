<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Services · Firebooster</title>
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
<main class="mx-auto max-w-5xl px-4 py-6 pb-40 sm:px-5 sm:py-10 sm:pb-40">
<header class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div><p class="text-sm uppercase tracking-widest text-violet-300">Firebooster</p><h1 class="mt-2 text-3xl font-bold">Choose a service</h1><p class="mt-2 text-sm text-slate-300">Wallet balance: <strong class="text-emerald-300">₦{{ number_format($wallet->balance_kobo / 100, 2) }}</strong></p></div>
    <nav class="flex gap-4 text-sm"><a class="text-violet-300 underline" href="{{ route('wallet.index') }}">My wallet</a><a class="text-violet-300 underline" href="{{ route('orders.index') }}">My orders</a></nav>
</header>

@if (session('status'))<p class="mb-5 rounded-lg bg-emerald-950 p-4 text-emerald-200">{{ session('status') }}</p>@endif
@if ($errors->any())<div class="mb-5 rounded-lg bg-red-950 p-4 text-red-200">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

<section class="mb-5 rounded-2xl border border-slate-800 bg-slate-900 p-4 sm:p-5">
    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Browse platforms</p>
    <div class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-2" aria-label="Service platforms">
        <a href="{{ route('services.index', array_filter(['q' => $search, 'category' => null])) }}" aria-label="All platforms" class="flex min-w-[76px] shrink-0 flex-col items-center gap-2 rounded-xl px-3 py-3 text-xs font-semibold transition {{ $selectedPlatform === '' ? 'bg-violet-600 text-white' : 'border border-slate-700 bg-slate-950 text-slate-300 hover:border-violet-500' }}">
            <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
            <span>All</span>
        </a>
        @foreach ($platforms as $platform)
            <a href="{{ route('services.index', array_filter(['platform' => $platform, 'q' => $search])) }}" aria-label="{{ $platform }}" class="flex min-w-[76px] shrink-0 flex-col items-center gap-2 rounded-xl px-3 py-3 text-xs font-semibold transition {{ mb_strtolower($selectedPlatform) === mb_strtolower($platform) ? 'bg-violet-600 text-white' : 'border border-slate-700 bg-slate-950 text-slate-300 hover:border-violet-500' }}">
                <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    @switch(mb_strtolower($platform))
                        @case('instagram')
                            <rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8" fill="currentColor" stroke="none"/>
                            @break
                        @case('facebook')
                            <path d="M14.2 21v-8h2.7l.4-3.1h-3.1V8c0-.9.3-1.5 1.6-1.5h1.7V3.7c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3v2H8v3.1h2.8v8z" fill="currentColor" stroke="none"/>
                            @break
                        @case('telegram')
                            <path d="m22 3-7 18-4-7-7-4z"/><path d="m11 14 5-5"/>
                            @break
                        @case('youtube')
                            <rect x="2.5" y="5" width="19" height="14" rx="4"/><path d="m10 9 5 3-5 3z" fill="currentColor" stroke="none"/>
                            @break
                        @case('tiktok')
                            <path d="M14 3v11.2a4.2 4.2 0 1 1-3-4V7.1c-1.8-.2-3.5.3-4.8 1.5A7.2 7.2 0 1 0 18 14.2V8.8c1.1.8 2.4 1.3 4 1.4V7c-3.2-.2-5.5-1.8-6.2-4z" fill="currentColor" stroke="none"/>
                            @break
                        @case('x (twitter)')
                            <path d="M4 4 20 20M20 4 4 20" stroke-width="2.4"/>
                            @break
                        @default
                            <circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>
                    @endswitch
                </svg>
                <span class="whitespace-nowrap">{{ $platform }}</span>
            </a>
        @endforeach
    </div>
    <form method="GET" action="{{ route('services.index') }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto]">
        @if ($selectedPlatform !== '')<input type="hidden" name="platform" value="{{ $selectedPlatform }}">@endif
        @if ($selectedCategory !== '')<input type="hidden" name="category" value="{{ $selectedCategory }}">@endif
        <label class="sr-only" for="catalogue-search">Quick search by service name or ID</label>
        <input id="catalogue-search" type="search" name="q" value="{{ $search }}" placeholder="Quick search by service name or ID…" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-violet-500 focus:outline-none">
        <button class="rounded-xl bg-violet-600 px-6 py-3 text-sm font-semibold text-white">Search</button>
    </form>
    @if ($categories->isNotEmpty())
    <div class="mt-4 flex flex-wrap gap-2">
        <a href="{{ route('services.index', array_filter(['platform' => $selectedPlatform ?: null, 'q' => $search ?: null])) }}" class="rounded-lg px-3 py-2 text-xs {{ $selectedCategory === '' ? 'bg-slate-700 text-white' : 'bg-slate-950 text-slate-300 ring-1 ring-slate-700' }}">All categories</a>
        @foreach ($categories as $category)
            <a href="{{ route('services.index', array_filter(['platform' => $selectedPlatform ?: null, 'category' => $category, 'q' => $search ?: null])) }}" class="rounded-lg px-3 py-2 text-xs {{ mb_strtolower($selectedCategory) === mb_strtolower($category) ? 'bg-slate-700 text-white' : 'bg-slate-950 text-slate-300 ring-1 ring-slate-700' }}">{{ $category }}</a>
        @endforeach
    </div>
    @endif
</section>

<div class="mb-4 flex items-center justify-between gap-3"><h2 class="text-lg font-semibold">{{ $selectedPlatform !== '' ? $selectedPlatform : 'All services' }}</h2><span class="text-xs text-slate-400">{{ $services->total() }} available</span></div>
<div class="grid gap-4 md:grid-cols-2">
@forelse ($services as $service)
<article class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
    <div class="flex flex-wrap items-center justify-between gap-2"><p class="text-xs font-semibold uppercase tracking-wider text-violet-300">{{ $platformMap[$service->platform] ?? $service->platform }}@if($service->category) · {{ $service->category }}@endif</p><span class="rounded-full bg-emerald-950 px-2.5 py-1 text-xs text-emerald-300">Available</span></div>
    <h3 class="mt-2 text-lg font-bold">{{ $service->name }}</h3>
    @if ($service->provider_service_id)<p class="mt-1 text-xs text-slate-500">Service ID: {{ $service->provider_service_id }}</p>@endif
    @if ($service->description && !str_starts_with((string) $service->description, 'Imported from SMM Center.'))<p class="mt-2 text-sm text-slate-400">{{ $service->description }}</p>@endif
    <p class="mt-4 text-lg font-bold text-emerald-300">₦{{ number_format((float) $service->price_per_1000, 2) }} <span class="text-xs font-normal text-slate-400">per 1,000</span></p>
    <p class="mt-1 text-xs text-slate-400">Minimum: {{ number_format($service->minimum_quantity) }} · Maximum: {{ number_format($service->maximum_quantity) }}</p>
    @if ($service->provider_refill || $service->provider_cancel)
    <div class="mt-3 flex flex-wrap gap-2">@if($service->provider_refill)<span class="rounded-md bg-slate-800 px-2 py-1 text-xs text-slate-300">Refill available</span>@endif @if($service->provider_cancel)<span class="rounded-md bg-slate-800 px-2 py-1 text-xs text-slate-300">Cancellation supported</span>@endif</div>
    @endif
    <form id="order-form-{{ $service->id }}" class="order-form mt-5 space-y-3" method="POST" action="{{ route('orders.store') }}" data-service-name="{{ $service->name }}" data-price-per-1000="{{ (float) $service->price_per_1000 }}" data-wallet-kobo="{{ $wallet->balance_kobo }}">
        @csrf<input type="hidden" name="service_id" value="{{ $service->id }}">
        <label class="block text-sm">Target URL<input required type="url" name="target_url" placeholder="https://..." class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 p-3"></label>
        <label class="block text-sm">Quantity<input class="quantity-input mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 p-3" required type="number" name="quantity" min="{{ $service->minimum_quantity }}" max="{{ $service->maximum_quantity }}" value="{{ $service->minimum_quantity }}" step="1"></label>
        <div class="rounded-xl border border-slate-700 bg-slate-950 p-4 text-sm">
            <div class="flex justify-between gap-3"><span class="text-slate-400">Estimated charge</span><strong class="order-total text-lg text-emerald-300">₦0.00</strong></div>
            <div class="mt-2 flex justify-between gap-3"><span class="text-slate-400">Wallet balance</span><span>₦{{ number_format($wallet->balance_kobo / 100, 2) }}</span></div>
            <div class="mt-2 flex justify-between gap-3"><span class="text-slate-400">Balance after order</span><strong class="balance-after">—</strong></div>
            <p class="order-feedback mt-3 text-xs text-slate-400" aria-live="polite">Charge updates when you change the quantity. The server confirms the final amount.</p>
        </div>
        <button class="w-full rounded-lg bg-violet-600 px-4 py-3 font-semibold">Submit order</button>
    </form>
</article>
@empty
<p class="rounded-xl border border-slate-800 p-6 text-slate-400 md:col-span-2">No matching services are available. Try another platform or search term.</p>
@endforelse
</div>
@if ($services->count() > 0)
<div id="sticky-order-summary" class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-700 bg-slate-950/95 px-4 pt-3 pb-[calc(1rem+env(safe-area-inset-bottom))] shadow-2xl backdrop-blur md:hidden">
    <div class="mx-auto max-w-5xl">
        <div class="flex items-center justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-violet-300">Order summary</p>
                <p id="sticky-service-name" class="truncate text-xs text-slate-300">Select a service</p>
                <p class="mt-1 text-xs text-slate-400">After order: <strong id="sticky-balance-after" class="text-slate-200">—</strong></p>
            </div>
            <div class="shrink-0 text-right">
                <p class="text-[10px] text-slate-400">Estimated total</p>
                <p id="sticky-order-total" class="text-lg font-bold text-emerald-300">—</p>
            </div>
            <button id="sticky-submit" type="submit" form="order-form-{{ $services->first()->id }}" class="shrink-0 rounded-xl bg-violet-600 px-4 py-3 text-sm font-bold text-white shadow-lg transition hover:bg-violet-500 disabled:cursor-not-allowed disabled:opacity-40">Place order</button>
        </div>
        <p id="sticky-order-feedback" class="mt-2 text-[10px] text-slate-400" aria-live="polite">Choose a quantity to see your order total.</p>
    </div>
</div>
@endif

<div class="mt-6">{{ $services->links() }}</div>
<p class="mt-8 text-xs text-amber-300">Orders are paid from your wallet and placed in the manual review queue. They are not automatically sent to a provider yet.</p>
</main>
<script>
const stickyName = document.getElementById('sticky-service-name');
const stickyTotal = document.getElementById('sticky-order-total');
const stickyBalance = document.getElementById('sticky-balance-after');
const stickyFeedback = document.getElementById('sticky-order-feedback');
const stickySubmit = document.getElementById('sticky-submit');

document.querySelectorAll('.order-form').forEach((form) => {
    const quantity = form.querySelector('.quantity-input');
    const total = form.querySelector('.order-total');
    const balanceAfter = form.querySelector('.balance-after');
    const feedback = form.querySelector('.order-feedback');
    const pricePer1000 = Number.parseFloat(form.getAttribute('data-price-per-1000'));
    const walletKobo = Number.parseInt(form.getAttribute('data-wallet-kobo'), 10);
    const money = (kobo) => '₦' + (kobo / 100).toFixed(2);
    const activateForm = () => {
        if (stickyName) stickyName.textContent = form.getAttribute('data-service-name') || 'Selected service';
        if (stickySubmit) stickySubmit.setAttribute('form', form.id);
    };
    const updateEstimate = () => {
        const amount = Number(quantity.value);
        activateForm();
        if (!Number.isSafeInteger(amount) || !Number.isFinite(pricePer1000) || !Number.isFinite(walletKobo) || amount < Number(quantity.min) || amount > Number(quantity.max)) {
            total.textContent = '—'; balanceAfter.textContent = '—';
            feedback.textContent = 'Enter a quantity within the allowed limits.';
            feedback.className = 'order-feedback mt-3 text-xs text-amber-300';
            if (stickyTotal) stickyTotal.textContent = '—';
            if (stickyBalance) stickyBalance.textContent = '—';
            if (stickyFeedback) stickyFeedback.textContent = 'Enter a valid quantity within the service limits.';
            if (stickySubmit) stickySubmit.disabled = true;
            return;
        }
        const unitPriceKobo = Math.round(pricePer1000 * 100);
        const totalKobo = Math.floor((unitPriceKobo * amount + 500) / 1000);
        const remainingKobo = walletKobo - totalKobo;
        total.textContent = money(totalKobo); balanceAfter.textContent = money(remainingKobo);
        balanceAfter.className = remainingKobo < 0 ? 'balance-after text-red-300' : 'balance-after text-emerald-300';
        feedback.textContent = remainingKobo < 0 ? 'Your wallet balance is too low for this order.' : 'Estimated amount only. The server recalculates the final charge before payment.';
        feedback.className = remainingKobo < 0 ? 'order-feedback mt-3 text-xs text-red-300' : 'order-feedback mt-3 text-xs text-slate-400';
        if (stickyTotal) stickyTotal.textContent = money(totalKobo);
        if (stickyBalance) stickyBalance.textContent = money(remainingKobo);
        if (stickyBalance) stickyBalance.className = remainingKobo < 0 ? 'text-red-300' : 'text-emerald-300';
        if (stickyFeedback) stickyFeedback.textContent = remainingKobo < 0 ? 'Insufficient wallet balance for this quantity.' : 'Final charge is recalculated securely by the server.';
        if (stickySubmit) stickySubmit.disabled = remainingKobo < 0;
    };
    form.addEventListener('focusin', activateForm);
    quantity.addEventListener('input', updateEstimate);
    updateEstimate();
});
</script>
</body></html>
