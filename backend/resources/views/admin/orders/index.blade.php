<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Manage orders · Firebooster</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-950 text-slate-100"><main class="mx-auto max-w-6xl px-4 py-6 sm:px-5 sm:py-10">
<a href="{{ route('admin.dashboard') }}" class="text-violet-300 underline">← Admin dashboard</a>
<div class="my-5 flex flex-wrap items-end justify-between gap-3"><div><h1 class="text-2xl font-bold sm:text-3xl">Order management</h1><p class="mt-1 text-sm text-slate-400">Review payments, supplier fulfilment and refunds.</p></div><span class="rounded-full border border-slate-700 px-3 py-1 text-xs text-slate-300">{{ $orders->total() }} matching orders</span></div>
@if (session('status'))<p class="mb-4 rounded-lg bg-emerald-950 p-4 text-emerald-200">{{ session('status') }}</p>@endif
@if ($errors->has('provider'))<p class="mb-4 rounded-lg bg-rose-950 p-4 text-rose-200">{{ $errors->first('provider') }}</p>@endif
<form method="GET" action="{{ route('admin.orders.index') }}" class="mb-5 grid gap-3 rounded-xl border border-violet-800 bg-slate-900 p-4 sm:grid-cols-[1fr_190px_auto_auto]">
<input name="search" value="{{ $search }}" maxlength="120" placeholder="Search order #, customer email, service or supplier order ID…" aria-label="Search orders" class="min-w-0 rounded-lg bg-slate-950 p-3">
<select name="status" aria-label="Filter by order status" class="rounded-lg bg-slate-950 p-3"><option value="all" @selected($status === 'all')>All statuses</option>@foreach (['pending','awaiting_payment','processing','partial','completed','cancelled','failed'] as $s)<option value="{{ $s }}" @selected($status === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
<button class="rounded-lg bg-violet-600 px-5 py-3 font-semibold">Search</button><a href="{{ route('admin.orders.index') }}" class="rounded-lg border border-slate-600 px-5 py-3 text-center">Clear</a>
</form>
<div class="space-y-4">@forelse ($orders as $order)
<article class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
<div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 p-4 sm:p-5"><div><p class="text-xs font-semibold uppercase tracking-wide text-violet-300">Order #{{ $order->id }}</p><h2 class="mt-1 text-lg font-semibold">{{ $order->service->name }}</h2><p class="mt-1 break-all text-sm text-slate-400">{{ $order->user->email }}</p></div><span class="rounded-full border border-slate-600 px-3 py-1 text-xs font-semibold">{{ ucfirst(str_replace('_',' ',$order->status)) }}</span></div>
<div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-4 sm:p-5"><div><p class="text-xs text-slate-400">Quantity</p><p class="mt-1 font-semibold">{{ number_format($order->quantity) }}</p></div><div><p class="text-xs text-slate-400">Customer paid</p><p class="mt-1 font-semibold">₦{{ number_format((float) $order->total_price,2) }}</p></div><div><p class="text-xs text-slate-400">Supplier order</p><p class="mt-1 break-all text-sm">{{ $order->provider_order_id ?? 'Not submitted' }}</p></div><div><p class="text-xs text-slate-400">Supplier status</p><p class="mt-1 text-sm">{{ $order->provider_status ?? '—' }}</p></div></div>
<div class="px-4 pb-4 sm:px-5"><a class="break-all text-sm text-violet-300 underline" href="{{ $order->target_url }}" target="_blank" rel="noopener noreferrer">Open target URL</a>
@if ($order->service->provider === 'smm_center')
@if ($order->provider_charge !== null && $order->provider_currency)
<div class="mt-3 rounded-lg border border-slate-700 bg-slate-950 p-3 text-sm"><p class="text-slate-300">Supplier charge: <strong>{{ number_format((float) $order->provider_charge,6) }} {{ strtoupper($order->provider_currency) }}</strong></p><p class="mt-1 text-slate-400">Customer sale: ₦{{ number_format((float) $order->total_price,2) }}</p>
@if (strtoupper($order->provider_currency) === 'NGN')<p class="mt-1 text-emerald-300">Gross difference before payment fees and other costs: ₦{{ number_format((float) $order->total_price - (float) $order->provider_charge,2) }}</p>@else<p class="mt-1 text-amber-300">Margin not calculated: supplier charge is in {{ strtoupper($order->provider_currency) }}. A verified exchange-rate snapshot is required to compare it with the NGN sale.</p>@endif</div>@endif
<div class="mt-3 flex flex-wrap gap-2">
@if ($order->provider_order_id)<form method="POST" action="{{ route('admin.orders.refresh-provider-status',$order) }}">@csrf<button class="rounded-lg border border-emerald-500 px-4 py-2 text-sm text-emerald-200">Refresh supplier status</button></form>
@elseif (!$order->provider_status && in_array($order->status,['pending','processing'],true))<form method="POST" action="{{ route('admin.orders.submit-to-provider',$order) }}">@csrf<button class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold">Submit to SMM Center</button></form>@endif
</div>
@if ($order->provider_order_id && in_array($order->status,['cancelled','failed','partial'],true))
<form method="POST" action="{{ route('admin.orders.refund',$order) }}" class="mt-3 flex flex-wrap items-end gap-3 rounded-lg border border-amber-900 bg-slate-950 p-3">@csrf
@if ($order->status === 'partial')<label class="text-sm text-slate-300">Approved refund (NGN)<input name="refund_amount" type="number" min="0.01" step="0.01" required class="mt-1 block w-full rounded-lg bg-slate-900 p-2" placeholder="e.g. 0.40"></label><span class="text-xs text-amber-300">Review delivered quantity before approving.</span>@else<span class="text-xs text-amber-300">Full refund requires supplier confirmation that the full quantity remains undelivered.</span>@endif
<button class="rounded-lg border border-amber-500 px-4 py-2 text-sm text-amber-200">Issue wallet refund</button></form>@endif
@endif
<form method="POST" action="{{ route('admin.orders.update',$order) }}" class="mt-4 grid gap-2 border-t border-slate-800 pt-4 sm:grid-cols-[190px_1fr_auto]">@csrf @method('PATCH')
<select name="status" class="rounded-lg bg-slate-950 p-3">@foreach (['pending','awaiting_payment','processing','partial','completed','cancelled','failed'] as $s)<option value="{{ $s }}" @selected($order->status === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
<input name="notes" maxlength="2000" value="{{ $order->notes }}" placeholder="Internal note" class="min-w-0 rounded-lg bg-slate-950 p-3"><button class="rounded-lg border border-violet-500 px-4 py-3">Save order</button></form>
</div></article>
@empty<p class="rounded-xl border border-slate-800 bg-slate-900 p-6 text-slate-400">No matching orders found.</p>@endforelse</div>
<div class="mt-5">{{ $orders->links() }}</div><p class="mt-6 text-xs text-amber-300">Supplier submission is a separate admin action. If a submission cannot be confirmed, check the SMM Center dashboard before retrying. Duplicate submissions are blocked.</p>
</main></body></html>