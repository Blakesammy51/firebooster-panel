<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Manage orders · Firebooster</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-950 text-slate-100"><main class="mx-auto max-w-6xl px-5 py-10"><a href="{{ route('admin.dashboard') }}" class="text-violet-300 underline">← Admin dashboard</a><h1 class="my-6 text-3xl font-bold">Order requests</h1>
@if (session('status'))<p class="mb-5 rounded-lg bg-emerald-950 p-4 text-emerald-200">{{ session('status') }}</p>@endif
@if ($errors->has('provider'))<p class="mb-5 rounded-lg bg-rose-950 p-4 text-rose-200">{{ $errors->first('provider') }}</p>@endif
<div class="space-y-4">@forelse ($orders as $order)<article class="rounded-xl border border-slate-800 bg-slate-900 p-5"><div class="flex flex-wrap justify-between gap-3"><div><h2 class="font-semibold">Order #{{ $order->id }} · {{ $order->service->name }}</h2><p class="mt-1 text-sm text-slate-400">{{ $order->user->email }} · {{ number_format($order->quantity) }} units · ₦{{ number_format((float) $order->total_price,2) }}</p><a class="mt-2 inline-block break-all text-sm text-violet-300 underline" href="{{ $order->target_url }}" target="_blank" rel="noopener noreferrer">{{ $order->target_url }}</a></div><span class="text-sm text-slate-300">{{ ucfirst(str_replace('_',' ',$order->status)) }}</span></div>
@if ($order->service->provider === 'smm_center')
<p class="mt-3 text-xs text-slate-400">Supplier: SMM Center · Provider order: {{ $order->provider_order_id ?? 'Not submitted' }} · Supplier status: {{ $order->provider_status ?? 'Not available' }}</p>
@if ($order->provider_charge !== null && $order->provider_currency)
<div class="mt-3 rounded-lg border border-slate-700 bg-slate-950 p-3 text-sm">
    <p class="text-slate-300">Recorded supplier charge: <strong>{{ number_format((float) $order->provider_charge, 6) }} {{ strtoupper($order->provider_currency) }}</strong></p>
    <p class="mt-1 text-slate-400">Customer sale: ₦{{ number_format((float) $order->total_price, 2) }}</p>
    @if (strtoupper($order->provider_currency) === 'NGN')
        <p class="mt-1 text-emerald-300">Gross difference before payment fees and other costs: ₦{{ number_format((float) $order->total_price - (float) $order->provider_charge, 2) }}</p>
    @else
        <p class="mt-1 text-amber-300">Margin not calculated: supplier charge is in {{ strtoupper($order->provider_currency) }}. A verified exchange-rate snapshot is required to compare it with the NGN sale.</p>
    @endif
</div>
@endif
@if ($order->provider_order_id && in_array($order->status, ['cancelled', 'failed', 'partial'], true))
<form method="POST" action="{{ route('admin.orders.refund', $order) }}" class="mt-3 flex flex-wrap items-end gap-3">@csrf
    @if ($order->status === 'partial')
        <label class="text-sm text-slate-300">Approved refund (NGN)
            <input name="refund_amount" type="number" min="0.01" step="0.01" required class="mt-1 block rounded-lg bg-slate-950 p-2" placeholder="e.g. 0.40">
        </label>
        <span class="text-xs text-amber-300">Enter the amount approved after reviewing delivered quantity.</span>
    @else
        <span class="text-xs text-amber-300">Full refund is available only when supplier confirms the entire quantity remains undelivered.</span>
    @endif
    <button class="rounded-lg border border-amber-500 px-4 py-2 text-amber-200">Issue wallet refund</button>
</form>
@endif
@if ($order->provider_order_id)
<form method="POST" action="{{ route('admin.orders.refresh-provider-status', $order) }}" class="mt-3">@csrf<button class="rounded-lg border border-emerald-500 px-4 py-2 text-emerald-200">Refresh supplier status</button></form>
@endif
@if (!$order->provider_order_id && !$order->provider_status && in_array($order->status, ['pending', 'processing'], true))
<form method="POST" action="{{ route('admin.orders.submit-to-provider', $order) }}" class="mt-3">@csrf<button class="rounded-lg bg-violet-600 px-4 py-2 font-semibold text-white">Submit to SMM Center</button></form>
@endif
@endif
<form method="POST" action="{{ route('admin.orders.update',$order) }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_2fr_auto]">@csrf @method('PATCH')<select name="status" class="rounded-lg bg-slate-950 p-3">@foreach (['pending','awaiting_payment','processing','partial','completed','cancelled','failed'] as $status)<option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>@endforeach</select><input name="notes" maxlength="2000" value="{{ $order->notes }}" placeholder="Internal note" class="rounded-lg bg-slate-950 p-3"><button class="rounded-lg border border-violet-500 px-4 py-2">Update</button></form></article>@empty<p class="text-slate-400">No order requests have been placed.</p>@endforelse</div>{{ $orders->links() }}
<p class="mt-6 text-xs text-amber-300">Provider submission is a separate admin action. If a submission cannot be confirmed, check the supplier dashboard before attempting anything else; duplicate submissions are blocked.</p></main></body></html>