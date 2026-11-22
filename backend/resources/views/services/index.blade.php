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
    <form method="GET" action="{{ route('services.index') }}" class="mt-4 space-y-3">
        <input id="catalogue-platform-value" type="hidden" name="platform" value="{{ $selectedPlatform }}">
        <input id="catalogue-category-value" type="hidden" name="category" value="{{ $selectedCategory }}">
        <label class="block">
            <span class="mb-2 block text-xs font-semibold uppercase tracking-wider text-slate-400">Quick search</span>
            <input id="catalogue-search" type="search" name="q" value="{{ $search }}" placeholder="Search by service name or ID…" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-violet-500 focus:outline-none">
        </label>
        <div class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
            <div class="relative">
                <span class="mb-2 block text-xs font-semibold uppercase tracking-wider text-slate-400">Browse category</span>
                <button id="category-picker-trigger" type="button" aria-expanded="false" aria-controls="category-picker-panel" class="flex w-full items-center justify-between gap-3 rounded-xl border border-slate-700 bg-slate-950 px-4 py-3.5 text-left text-sm text-white transition hover:border-violet-500 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30">
                    <span class="flex min-w-0 items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300" aria-hidden="true">▦</span>
                        <span class="min-w-0"><span class="block text-[10px] uppercase tracking-wider text-slate-500">Selected category</span><span id="category-picker-label" class="block truncate font-semibold">{{ $selectedCategory !== '' ? $selectedCategory : ($selectedPlatform !== '' ? 'All ' . $selectedPlatform . ' categories' : 'All categories') }}</span></span>
                    </span>
                    <svg id="category-picker-chevron" class="h-5 w-5 shrink-0 text-slate-400 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div id="category-picker-panel" class="absolute inset-x-0 top-full z-30 mt-2 hidden overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl shadow-black/40" hidden>
                    <div class="border-b border-slate-800 p-3">
                        <label class="relative block">
                            <span class="sr-only">Search categories</span>
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
                            <input id="category-picker-search" type="search" placeholder="Search categories…" class="w-full rounded-xl border border-slate-700 bg-slate-950 py-3 pl-9 pr-3 text-sm text-white placeholder:text-slate-500 focus:border-violet-500 focus:outline-none">
                        </label>
                        <div class="mt-3 flex gap-2 overflow-x-auto pb-1" aria-label="Filter categories by platform">
                            <button type="button" data-category-platform="" class="category-platform-chip shrink-0 rounded-full bg-violet-600 px-3 py-2 text-xs font-semibold text-white">All</button>
                            @foreach ($platforms as $platform)
                                <button type="button" data-category-platform="{{ $platform }}" class="category-platform-chip shrink-0 rounded-full border border-slate-700 bg-slate-950 px-3 py-2 text-xs font-semibold text-slate-300">{{ $platform }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div id="category-picker-options" class="max-h-64 overflow-y-auto p-2">
                        <button type="button" data-category-value="" data-category-name="{{ $selectedPlatform !== '' ? 'All ' . $selectedPlatform . ' categories' : 'All categories' }}" class="category-picker-option flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-sm transition hover:bg-slate-800 {{ $selectedCategory === '' ? 'bg-violet-500/10 text-violet-200' : 'text-slate-200' }}">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-slate-300" aria-hidden="true">▦</span>
                            <span class="min-w-0 flex-1"><span class="block font-semibold">{{ $selectedPlatform !== '' ? 'All ' . $selectedPlatform . ' categories' : 'All categories' }}</span><span class="block text-xs text-slate-500">{{ $selectedPlatform !== '' ? 'Browse every available ' . $selectedPlatform . ' service' : 'Browse every available service' }}</span></span>
                            @if ($selectedCategory === '')<span class="text-violet-300" aria-label="Selected">✓</span>@endif
                        </button>
                        @foreach ($categories as $category)
                            <button type="button" data-category-value="{{ $category }}" data-category-name="{{ $category }}" class="category-picker-option flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-sm transition hover:bg-slate-800 {{ mb_strtolower($selectedCategory) === mb_strtolower($category) ? 'bg-violet-500/10 text-violet-200' : 'text-slate-200' }}">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-violet-300" aria-hidden="true">◇</span>
                                <span class="min-w-0 flex-1"><span class="block font-semibold">{{ $category }}</span><span class="block text-xs text-slate-500">View matching services</span></span>
                                @if (mb_strtolower($selectedCategory) === mb_strtolower($category))<span class="text-violet-300" aria-label="Selected">✓</span>@endif
                            </button>
                        @endforeach
                        <p id="category-picker-empty" class="hidden px-3 py-6 text-center text-sm text-slate-500">No matching categories found.</p>
                    </div>
                </div>
            </div>
            <button class="rounded-xl bg-violet-600 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-violet-500">Search services</button>
        </div>
    </form>
</section>

<div class="mb-4 flex items-center justify-between gap-3"><h2 class="text-lg font-semibold">{{ $selectedPlatform !== '' ? $selectedPlatform : 'All services' }}</h2><span class="text-xs text-slate-400">{{ $services->total() }} available</span></div>
<div class="grid gap-4 md:grid-cols-2">
@forelse ($services as $service)
<article class="flex h-full flex-col rounded-2xl border border-slate-800 bg-slate-900 p-5 transition hover:border-violet-500/60 hover:shadow-xl hover:shadow-violet-950/20">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs font-semibold uppercase tracking-wider text-violet-300">{{ $platformMap[$service->platform] ?? $service->platform }}@if($service->category) · {{ $service->category }}@endif</p>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-950 px-2.5 py-1 text-xs font-medium text-emerald-300"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>Available</span>
    </div>
    <h3 class="mt-3 text-lg font-bold leading-snug">{{ $service->display_name }}</h3>
    @if ($service->description && !str_starts_with((string) $service->description, 'Imported from SMM Center.'))
        <p class="mt-2 text-sm leading-relaxed text-slate-400">{{ $service->display_description }}</p>
    @endif
    <div class="mt-4 rounded-xl border border-slate-800 bg-slate-950/70 p-3">
        <p class="text-xs text-slate-400">Price per 1,000</p>
        <p class="mt-1 text-xl font-bold text-emerald-300">₦{{ number_format((float) $service->price_per_1000, 2) }}</p>
    </div>
    <div class="mt-3 grid grid-cols-2 gap-2">
        <div class="rounded-lg bg-slate-800/70 px-3 py-2.5">
            <p class="text-[11px] uppercase tracking-wide text-slate-400">Minimum order</p>
            <p class="mt-1 text-sm font-semibold text-slate-100">{{ number_format($service->minimum_quantity) }}</p>
        </div>
        <div class="rounded-lg bg-slate-800/70 px-3 py-2.5">
            <p class="text-[11px] uppercase tracking-wide text-slate-400">Maximum order</p>
            <p class="mt-1 text-sm font-semibold text-slate-100">{{ number_format($service->maximum_quantity) }}</p>
        </div>
    </div>
    @if ($service->provider_refill || $service->provider_cancel)
        <div class="mt-3 flex flex-wrap gap-2">
            @if($service->provider_refill)<span class="rounded-md border border-slate-700 bg-slate-800 px-2 py-1 text-xs text-slate-300">↻ Refill support</span>@endif
            @if($service->provider_cancel)<span class="rounded-md border border-slate-700 bg-slate-800 px-2 py-1 text-xs text-slate-300">✓ Cancellation support</span>@endif
        </div>
    @endif
    <button
        type="button"
        class="select-service-button mt-5 w-full rounded-xl bg-violet-600 px-4 py-3 font-semibold text-white transition hover:bg-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-300"
        data-service-id="{{ $service->id }}"
        data-service-name="{{ $service->display_name }}"
        data-service-description="{{ $service->description && !str_starts_with((string) $service->description, 'Imported from SMM Center.') ? $service->display_description : '' }}"
        data-price-per-1000="{{ number_format((float) $service->price_per_1000, 2, '.', '') }}"
        data-minimum="{{ $service->minimum_quantity }}"
        data-maximum="{{ $service->maximum_quantity }}"
        data-refill="{{ $service->provider_refill ? 'Yes' : 'No' }}"
        data-cancel="{{ $service->provider_cancel ? 'Yes' : 'No' }}"
        data-speed="{{ $service->display_speed ?? '' }}"
        data-start-time="{{ $service->display_start_time ?? '' }}"
        aria-controls="selected-order-panel"
        aria-expanded="false"
    >Select service</button>
</article>
@empty
<p class="rounded-xl border border-slate-800 p-6 text-slate-400 md:col-span-2">No matching services are available. Try another platform or search term.</p>
@endforelse
</div>

<section id="selected-order-panel" class="mt-6 hidden rounded-2xl border border-violet-500/40 bg-slate-900 p-5 shadow-xl sm:p-6" aria-labelledby="selected-service-title" hidden>
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-violet-300">Selected service</p>
            <h2 id="selected-service-title" class="mt-2 text-xl font-bold">Choose a service above</h2>
            <p id="selected-service-description" class="mt-2 hidden text-sm text-slate-400"></p>
            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                <span id="selected-service-speed" class="hidden rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-slate-300"></span>
                <span id="selected-service-start-time" class="hidden rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-slate-300"></span>
                <span id="selected-service-refill" class="hidden rounded-md bg-slate-800 px-2 py-1 text-slate-300"></span>
                <span id="selected-service-cancel" class="hidden rounded-md bg-slate-800 px-2 py-1 text-slate-300"></span>
            </div>
        </div>
        <button id="change-selected-service" type="button" class="rounded-lg border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-300 hover:border-violet-400 hover:text-white">Choose another service</button>
    </div>

    <form id="selected-order-form" method="POST" action="{{ route('orders.store') }}" class="space-y-4" data-wallet-kobo="{{ $wallet->balance_kobo }}">
        @csrf
        <input id="selected-service-id" type="hidden" name="service_id" value="{{ old('service_id') }}">
        <label class="block text-sm font-medium text-slate-200" for="selected-target-url">Target URL
            <input id="selected-target-url" required type="url" name="target_url" value="{{ old('target_url') }}" placeholder="https://..." class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 p-3 text-white placeholder:text-slate-500 focus:border-violet-500 focus:outline-none">
        </label>
        <label class="block text-sm font-medium text-slate-200" for="selected-quantity">Quantity
            <input id="selected-quantity" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 p-3 text-white focus:border-violet-500 focus:outline-none" required type="number" name="quantity" min="1" max="1" value="{{ old('quantity') }}" step="1">
            <span id="selected-quantity-limits" class="mt-1 block text-xs text-slate-400">Select a service to see its quantity limits.</span>
            <div id="quantity-presets" class="mt-3 flex flex-wrap gap-2" aria-label="Quick quantity choices">
                <button type="button" data-quantity-preset="1000" class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs font-semibold text-slate-300 transition hover:border-violet-500 hover:text-white">1,000</button>
                <button type="button" data-quantity-preset="5000" class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs font-semibold text-slate-300 transition hover:border-violet-500 hover:text-white">5,000</button>
                <button type="button" data-quantity-preset="10000" class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs font-semibold text-slate-300 transition hover:border-violet-500 hover:text-white">10,000</button>
            </div>
        </label>
        <div class="rounded-xl border border-slate-700 bg-slate-950 p-4 text-sm">
            <div class="flex justify-between gap-3"><span class="text-slate-400">Price per 1,000</span><strong id="selected-unit-price" class="text-slate-200">—</strong></div>
            <div class="mt-3 flex justify-between gap-3"><span class="text-slate-400">Estimated charge</span><strong id="selected-order-total" class="text-lg text-emerald-300">—</strong></div>
            <div class="mt-2 flex justify-between gap-3"><span class="text-slate-400">Wallet balance</span><span>₦{{ number_format($wallet->balance_kobo / 100, 2) }}</span></div>
            <div class="mt-2 flex justify-between gap-3"><span class="text-slate-400">Balance after order</span><strong id="selected-balance-after">—</strong></div>
            <p id="selected-order-feedback" class="mt-3 text-xs text-slate-400" aria-live="polite">Choose a service and enter a quantity to see your estimate.</p>
        </div>
        <button id="selected-submit" type="submit" class="w-full rounded-xl bg-violet-600 px-4 py-3 font-semibold text-white transition hover:bg-violet-500 disabled:cursor-not-allowed disabled:opacity-40" disabled>Place order</button>
    </form>
</section>
@if ($services->count() > 0)
<div id="sticky-order-summary" class="fixed inset-x-0 bottom-0 z-40 hidden border-t border-slate-700 bg-slate-950/95 px-4 pt-3 pb-[calc(1rem+env(safe-area-inset-bottom))] shadow-2xl backdrop-blur md:hidden" hidden>
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
            <button id="sticky-submit" type="submit" form="selected-order-form" class="shrink-0 rounded-xl bg-violet-600 px-4 py-3 text-sm font-bold text-white shadow-lg transition hover:bg-violet-500 disabled:cursor-not-allowed disabled:opacity-40" disabled>Place order</button>
        </div>
        <p id="sticky-order-feedback" class="mt-2 text-[10px] text-slate-400" aria-live="polite">Select a service to begin.</p>
    </div>
</div>
@endif

<div class="mt-6">{{ $services->links() }}</div>
<p class="mt-8 text-xs text-amber-300">Orders are paid from your wallet and placed in the manual review queue. They are not automatically sent to a provider yet.</p>
</main>
<script>
(() => {
    const panel = document.getElementById('selected-order-panel');
    const form = document.getElementById('selected-order-form');
    if (!panel || !form) return;

    const serviceId = document.getElementById('selected-service-id');
    const serviceTitle = document.getElementById('selected-service-title');
    const serviceDescription = document.getElementById('selected-service-description');
    const refillBadge = document.getElementById('selected-service-refill');
    const cancelBadge = document.getElementById('selected-service-cancel');
    const speedBadge = document.getElementById('selected-service-speed');
    const startTimeBadge = document.getElementById('selected-service-start-time');
    const quantity = document.getElementById('selected-quantity');
    const quantityLimits = document.getElementById('selected-quantity-limits');
    const unitPrice = document.getElementById('selected-unit-price');
    const total = document.getElementById('selected-order-total');
    const balanceAfter = document.getElementById('selected-balance-after');
    const feedback = document.getElementById('selected-order-feedback');
    const submit = document.getElementById('selected-submit');
    const sticky = document.getElementById('sticky-order-summary');
    const stickyName = document.getElementById('sticky-service-name');
    const stickyTotal = document.getElementById('sticky-order-total');
    const stickyBalance = document.getElementById('sticky-balance-after');
    const stickyFeedback = document.getElementById('sticky-order-feedback');
    const stickySubmit = document.getElementById('sticky-submit');
    const targetUrl = document.getElementById('selected-target-url');
    const walletKobo = Number.parseInt(form.dataset.walletKobo, 10);
    let selectedService = null;

    const money = (kobo) => '₦' + (kobo / 100).toLocaleString('en-NG', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    const setMessage = (message, tone = 'muted') => {
        feedback.textContent = message;
        feedback.className = 'mt-3 text-xs ' + (tone === 'error' ? 'text-red-300' : tone === 'warning' ? 'text-amber-300' : 'text-slate-400');
        stickyFeedback.textContent = message;
    };

    const updateEstimate = () => {
        if (!selectedService) return;
        const amount = Number(quantity.value);
        const validQuantity = Number.isSafeInteger(amount)
            && amount >= selectedService.minimum
            && amount <= selectedService.maximum;
        if (!validQuantity) {
            total.textContent = '—';
            balanceAfter.textContent = '—';
            balanceAfter.className = '';
            stickyTotal.textContent = '—';
            stickyBalance.textContent = '—';
            setMessage('Enter a whole-number quantity between ' + selectedService.minimum.toLocaleString() + ' and ' + selectedService.maximum.toLocaleString() + '.', 'warning');
            submit.disabled = true;
            stickySubmit.disabled = true;
            return;
        }

        const unitPriceKobo = Math.round(selectedService.pricePer1000 * 100);
        const totalKobo = Math.round((unitPriceKobo * amount) / 1000);
        const remainingKobo = walletKobo - totalKobo;
        total.textContent = money(totalKobo);
        balanceAfter.textContent = money(remainingKobo);
        balanceAfter.className = remainingKobo < 0 ? 'text-red-300' : 'text-emerald-300';
        stickyTotal.textContent = money(totalKobo);
        stickyBalance.textContent = money(remainingKobo);
        stickyBalance.className = remainingKobo < 0 ? 'text-red-300' : 'text-emerald-300';

        if (remainingKobo < 0) {
            setMessage('Your wallet balance is too low for this order.', 'error');
            submit.disabled = true;
            stickySubmit.disabled = true;
        } else {
            setMessage('Estimated amount only. The server recalculates the final charge securely.', 'muted');
            submit.disabled = false;
            stickySubmit.disabled = false;
        }
    };

    const selectService = (button, shouldScroll = true) => {
        selectedService = {
            id: button.dataset.serviceId,
            name: button.dataset.serviceName,
            description: button.dataset.serviceDescription || '',
            pricePer1000: Number(String(button.getAttribute('data-price-per-1000') || '').replace(/[^0-9.-]/g, '')),
            minimum: Number.parseInt(button.dataset.minimum, 10),
            maximum: Number.parseInt(button.dataset.maximum, 10),
            refill: button.dataset.refill === 'Yes',
            cancel: button.dataset.cancel === 'Yes',
            speed: button.dataset.speed || '',
            startTime: button.dataset.startTime || ''
        };

        if (!Number.isFinite(selectedService.pricePer1000) || selectedService.pricePer1000 <= 0) {
            selectedService = null;
            setMessage('This service price could not be loaded. Please refresh the page or choose another service.', 'error');
            submit.disabled = true;
            stickySubmit.disabled = true;
            return;
        }

        serviceId.value = selectedService.id;
        serviceTitle.textContent = selectedService.name;
        serviceDescription.textContent = selectedService.description;
        serviceDescription.classList.toggle('hidden', !selectedService.description);
        speedBadge.textContent = selectedService.speed ? '⚡ Speed: ' + selectedService.speed : '';
        speedBadge.classList.toggle('hidden', !selectedService.speed);
        startTimeBadge.textContent = selectedService.startTime ? '⏳ Start time: ' + selectedService.startTime : '';
        startTimeBadge.classList.toggle('hidden', !selectedService.startTime);
        refillBadge.textContent = selectedService.refill ? '♻️ Refill available' : '❌ No refill';
        refillBadge.classList.remove('hidden');
        cancelBadge.textContent = selectedService.cancel ? '✓ Cancellation supported' : '✕ Cancellation not available';
        cancelBadge.classList.remove('hidden');
        quantity.min = String(selectedService.minimum);
        quantity.max = String(selectedService.maximum);
        quantity.value = String(selectedService.minimum);
        quantityLimits.textContent = 'Minimum: ' + selectedService.minimum.toLocaleString() + ' · Maximum: ' + selectedService.maximum.toLocaleString();
        unitPrice.textContent = '₦' + selectedService.pricePer1000.toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' per 1,000';
        stickyName.textContent = selectedService.name;

        document.querySelectorAll('.select-service-button').forEach((item) => {
            const isSelected = item === button;
            item.textContent = isSelected ? 'Selected service' : 'Select service';
            item.setAttribute('aria-expanded', isSelected ? 'true' : 'false');
            item.classList.toggle('ring-2', isSelected);
            item.classList.toggle('ring-violet-300', isSelected);
            item.classList.toggle('bg-slate-700', isSelected);
            item.classList.toggle('bg-violet-600', !isSelected);
        });

        panel.hidden = false;
        panel.classList.remove('hidden');
        sticky.hidden = false;
        sticky.classList.remove('hidden');
        updateEstimate();

        if (shouldScroll) {
            panel.scrollIntoView({behavior: 'smooth', block: 'start'});
            window.setTimeout(() => targetUrl.focus({preventScroll: true}), 350);
        }
    };

    document.querySelectorAll('.select-service-button').forEach((button) => {
        button.addEventListener('click', () => selectService(button));
    });

    document.getElementById('change-selected-service').addEventListener('click', () => {
        document.querySelector('.select-service-button')?.scrollIntoView({behavior: 'smooth', block: 'center'});
    });

    quantity.addEventListener('input', updateEstimate);
    document.querySelectorAll('[data-quantity-preset]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!selectedService) return;
            const preset = Number.parseInt(button.dataset.quantityPreset, 10);
            if (preset < selectedService.minimum || preset > selectedService.maximum) return;
            quantity.value = String(preset);
            updateEstimate();
        });
    });

    const previouslySelectedId = @json((string) old('service_id', ''));
    if (previouslySelectedId) {
        const previousButton = Array.from(document.querySelectorAll('.select-service-button'))
            .find((button) => button.dataset.serviceId === previouslySelectedId);
        if (previousButton) {
            selectService(previousButton, false);
            if (@json((string) old('quantity', ''))) quantity.value = @json((string) old('quantity', ''));
            updateEstimate();
        }
    }
})();
    const categoryForm = document.querySelector('form[action="{{ route('services.index') }}"]');
    const categoryTrigger = document.getElementById('category-picker-trigger');
    const categoryPanel = document.getElementById('category-picker-panel');
    const categoryLabel = document.getElementById('category-picker-label');
    const categoryValue = document.getElementById('catalogue-category-value');
    const platformValue = document.getElementById('catalogue-platform-value');
    const categorySearch = document.getElementById('category-picker-search');
    const categoryChevron = document.getElementById('category-picker-chevron');
    const categoryOptions = Array.from(document.querySelectorAll('.category-picker-option'));
    const categoryChips = Array.from(document.querySelectorAll('.category-platform-chip'));
    const categoryEmpty = document.getElementById('category-picker-empty');
    let activeCategoryPlatform = platformValue?.value || '';

    const closeCategoryPicker = () => {
        categoryPanel?.classList.add('hidden');
        if (categoryPanel) categoryPanel.hidden = true;
        categoryTrigger?.setAttribute('aria-expanded', 'false');
        categoryChevron?.classList.remove('rotate-180');
    };
    const openCategoryPicker = () => {
        categoryPanel?.classList.remove('hidden');
        if (categoryPanel) categoryPanel.hidden = false;
        categoryTrigger?.setAttribute('aria-expanded', 'true');
        categoryChevron?.classList.add('rotate-180');
        window.setTimeout(() => categorySearch?.focus(), 40);
    };
    const filterCategoryOptions = () => {
        const term = (categorySearch?.value || '').trim().toLocaleLowerCase();
        let visible = 0;
        categoryOptions.forEach((option) => {
            const matches = option.dataset.categoryName.toLocaleLowerCase().includes(term);
            option.classList.toggle('hidden', !matches);
            if (matches) visible++;
        });
        if (categoryEmpty) categoryEmpty.classList.toggle('hidden', visible > 0);
    };
    const paintCategoryChips = () => {
        categoryChips.forEach((chip) => {
            const active = chip.dataset.categoryPlatform.toLocaleLowerCase() === activeCategoryPlatform.toLocaleLowerCase();
            chip.className = 'category-platform-chip shrink-0 rounded-full px-3 py-2 text-xs font-semibold transition ' + (active ? 'bg-violet-600 text-white' : 'border border-slate-700 bg-slate-950 text-slate-300 hover:border-violet-500');
        });
    };
    categoryTrigger?.addEventListener('click', () => {
        if (categoryTrigger.getAttribute('aria-expanded') === 'true') closeCategoryPicker();
        else openCategoryPicker();
    });
    categorySearch?.addEventListener('input', filterCategoryOptions);
    categoryChips.forEach((chip) => chip.addEventListener('click', () => {
        activeCategoryPlatform = chip.dataset.categoryPlatform;
        platformValue.value = activeCategoryPlatform;
        categoryValue.value = '';
        if (categoryForm) categoryForm.requestSubmit();
    }));
    categoryOptions.forEach((option) => option.addEventListener('click', () => {
        categoryValue.value = option.dataset.categoryValue;
        categoryLabel.textContent = option.dataset.categoryName;
        closeCategoryPicker();
        if (categoryForm) categoryForm.requestSubmit();
    }));
    document.addEventListener('click', (event) => {
        if (categoryPanel && !categoryPanel.contains(event.target) && !categoryTrigger?.contains(event.target)) closeCategoryPicker();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeCategoryPicker();
    });
    paintCategoryChips();
    filterCategoryOptions();
</script>

<a href="https://t.me/FireboosterSupport" target="_blank" rel="noopener noreferrer"
   aria-label="Contact Firebooster support on Telegram"
   class="fixed bottom-40 right-4 z-50 inline-flex items-center gap-2 rounded-full border border-sky-400/30 bg-sky-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-950/40 transition hover:bg-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-300 focus:ring-offset-2 focus:ring-offset-slate-950">
    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true"><path d="M21.8 4.2 18.6 19c-.2 1.1-.9 1.4-1.8.9l-5-3.7-2.4 2.3c-.3.3-.5.5-1 .5l.4-5.1 9.3-8.4c.4-.4-.1-.6-.6-.2L6 12.5l-4.9-1.5c-1.1-.3-1.1-1 .2-1.5L20.5 2.7c.9-.3 1.7.2 1.3 1.5Z"/></svg>
    <span>Telegram Support</span>
</a>

</body></html>
