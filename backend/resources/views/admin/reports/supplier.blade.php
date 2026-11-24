<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Supplier spending & profit · Firebooster</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="admin-page min-h-screen bg-slate-950 text-slate-100">
@include('components.admin-navigation')
<main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10">
<a href="{{ route('admin.dashboard') }}" class="text-violet-300 underline">← Admin dashboard</a>
<header class="my-6"><p class="text-xs font-semibold uppercase tracking-wider text-violet-300">Financial overview</p><h1 class="mt-2 text-2xl font-bold sm:text-3xl">Supplier spending & order profit</h1><p class="mt-2 text-sm text-slate-400">SMM Center orders, plus Paystack wallet-deposit fees recorded during the selected period. Fees are shown separately because deposits fund the wallet and are not attributable to individual orders.</p></header>

<form method="GET" action="{{ route('admin.reports.supplier') }}" class="mb-6 grid gap-3 rounded-xl border border-slate-800 bg-slate-900 p-4 sm:grid-cols-[1fr_1fr_auto_auto]">
<label class="text-xs text-slate-400">From<input type="date" name="from" value="{{ $from }}" class="mt-1 block w-full rounded-lg bg-slate-950 p-3 text-sm text-slate-100"></label>
<label class="text-xs text-slate-400">To<input type="date" name="to" value="{{ $to }}" class="mt-1 block w-full rounded-lg bg-slate-950 p-3 text-sm text-slate-100"></label>
<button class="self-end rounded-lg bg-violet-600 px-5 py-3 font-semibold">Apply dates</button><a href="{{ route('admin.reports.supplier') }}" class="self-end rounded-lg border border-slate-600 px-5 py-3 text-center">This month</a>
</form>

<section class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
@foreach ([['Confirmed customer payments','₦'.number_format($totalPaid,2)],['Refunds issued','₦'.number_format($totalRefunded,2)],['Net customer sales','₦'.number_format($netSales,2)],['Gross margin before payment fees','₦'.number_format($totalMargin,2)],['Recorded Paystack fees','₦'.number_format($paystackFeeTotal,2)],['Margin after recorded Paystack fees','₦'.number_format($marginAfterPaystackFees,2)]] as [$label,$value])
<article class="rounded-xl border border-slate-800 bg-slate-900 p-4"><p class="text-xs text-slate-400">{{ $label }}</p><p class="mt-2 text-xl font-bold">{{ $value }}</p></article>
@endforeach
</section>

<section class="mt-5 grid gap-3 sm:grid-cols-2">
<article class="rounded-xl border border-slate-800 bg-slate-900 p-4"><h2 class="font-semibold">Supplier charges recorded</h2>
@if ($chargedByCurrency->isEmpty())<p class="mt-2 text-sm text-slate-400">No supplier charges recorded in this period.</p>@else
@foreach ($chargedByCurrency as $currency => $amount)<p class="mt-2 text-lg font-bold">{{ $currency }} {{ number_format($amount,6) }}</p>@endforeach
@endif</article>
<article class="rounded-xl border border-slate-800 bg-slate-900 p-4"><h2 class="font-semibold">Paystack fee coverage</h2><p class="mt-2 text-sm text-slate-300">{{ $paystackFeeCount }} deposits have a recorded Paystack fee.</p><p class="mt-1 text-sm text-amber-300">{{ $paystackFeeMissingCount }} completed Paystack deposits have no fee recorded.</p><p class="mt-2 text-xs text-slate-500">Fees are counted by deposit date and shown separately; they are not allocated to individual orders.</p></article>
<article class="rounded-xl border border-slate-800 bg-slate-900 p-4"><h2 class="font-semibold">Coverage & exclusions</h2><p class="mt-2 text-sm text-slate-300">{{ $paidOrderCount }} of {{ $orderCount }} orders have confirmed payments.</p><p class="mt-1 text-sm text-amber-300">{{ $unpricedOrders }} supplier orders have no recorded charge yet.</p><p class="mt-1 text-sm text-amber-300">{{ $unconvertedUsdOrders }} USD charges lack a verified FX snapshot.</p><p class="mt-1 text-xs text-slate-500">Gross margin calculated for {{ $marginOrders }} paid orders with a comparable NGN supplier cost.</p></article>
</section>

<div class="mt-8 mb-3"><h2 class="text-xl font-semibold">Order breakdown</h2><p class="mt-1 text-sm text-slate-400">{{ $from }} to {{ $to }}</p></div>
<div class="space-y-3">
@forelse ($rows as $row)
<article class="rounded-xl border border-slate-800 bg-slate-900 p-4 sm:p-5">
<div class="flex flex-wrap items-start justify-between gap-2"><div><p class="text-xs font-semibold uppercase tracking-wide text-violet-300">Order #{{ $row['order']->id }}</p><h3 class="mt-1 font-semibold">{{ $row['order']->service->name }}</h3><p class="mt-1 text-xs text-slate-400">{{ $row['order']->created_at?->format('d M Y, H:i') }} · {{ ucfirst($row['order']->status) }}</p></div><span class="rounded-full border border-slate-700 px-3 py-1 text-xs">{{ $row['paid'] ? 'Payment confirmed' : 'Payment not confirmed' }}</span></div>
<div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
<div><p class="text-xs text-slate-400">Customer payment</p><p class="mt-1 font-semibold">₦{{ number_format($row['sale'],2) }}</p></div>
<div><p class="text-xs text-slate-400">Refund</p><p class="mt-1 font-semibold">₦{{ number_format($row['refund'],2) }}</p></div>
<div><p class="text-xs text-slate-400">Supplier charge</p><p class="mt-1 font-semibold">{{ $row['charge'] === null ? 'Not recorded' : number_format($row['charge'],6).' '.($row['currency'] ?: '') }}</p></div>
<div><p class="text-xs text-slate-400">Gross margin</p>
@if ($row['margin'] !== null)<p class="mt-1 font-semibold {{ $row['margin'] >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">₦{{ number_format($row['margin'],2) }}</p>
@elseif (!$row['paid'])<p class="mt-1 text-sm text-amber-300">No confirmed payment</p>
@elseif ($row['charge'] === null)<p class="mt-1 text-sm text-amber-300">Awaiting supplier charge</p>
@else<p class="mt-1 text-sm text-amber-300">Verified FX rate needed</p>@endif
</div>
</div>
@if ($row['cost_ngn'] !== null && $row['currency'] === 'USD')<p class="mt-3 text-xs text-slate-500">NGN cost uses saved FX snapshot: ₦{{ number_format((float) $row['order']->provider_fx_rate,4) }} per USD · {{ $row['order']->provider_fx_source }}</p>@endif
</article>
@empty
<p class="rounded-xl border border-slate-800 bg-slate-900 p-6 text-sm text-slate-400">No SMM Center orders found for this date range.</p>
@endforelse
</div>
<p class="mt-6 text-xs leading-5 text-slate-500">Gross margin is net confirmed customer payment minus recorded supplier cost. “Margin after recorded Paystack fees” subtracts verified fees from completed wallet deposits created in the selected date range; deposits are not allocated to individual orders, so fee and order timing may differ. Missing historical fees are not estimated. Other exclusions include crypto conversion costs, operating expenses and taxes. USD costs are included in NGN margin only when an order has a saved verified FX rate and source. This report is read-only and does not submit orders, move wallet funds, or change fulfilment status.</p>
</main></body></html>
