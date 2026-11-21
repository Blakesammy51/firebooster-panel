<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>My orders · Firebooster</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-950 text-slate-100"><main class="mx-auto max-w-6xl px-5 py-10"><div class="flex flex-wrap items-center justify-between gap-3"><a href="{{ route('services.index') }}" class="text-violet-300 underline">← Browse services</a><a href="{{ route('wallet.index') }}" class="text-violet-300 underline">My wallet</a></div><h1 class="my-6 text-3xl font-bold">My orders</h1>
@if (session('status'))<p class="mb-5 rounded-lg bg-emerald-950 p-4 text-emerald-200">{{ session('status') }}</p>@endif
@if ($errors->any())<div class="mb-5 rounded-lg bg-red-950 p-4 text-red-200">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<div class="overflow-x-auto rounded-xl border border-slate-800"><table class="w-full text-left text-sm"><thead class="bg-slate-900 text-slate-400"><tr><th class="p-4">Service</th><th class="p-4">Quantity</th><th class="p-4">Total paid</th><th class="p-4">Fulfilment status</th><th class="p-4">Refund status</th><th class="p-4">Date</th></tr></thead><tbody>
@forelse ($orders as $order)
@php($refund = $refunds->get('ORDER-' . $order->id . '-REFUND'))
<tr class="border-t border-slate-800">
<td class="p-4">{{ $order->service->name }}</td>
<td class="p-4">{{ number_format($order->quantity) }}</td>
<td class="p-4">₦{{ number_format((float) $order->total_price, 2) }} <span class="block text-xs text-emerald-300">Paid from wallet</span></td>
<td class="p-4">{{ str_replace('_',' ',ucfirst($order->status)) }}</td>
<td class="p-4">
@if ($refund)
<span class="font-medium text-emerald-300">Refund credited</span>
<span class="block text-xs text-slate-400">+₦{{ number_format($refund->amount_kobo / 100, 2) }}</span>
@elseif (in_array($order->status, ['cancelled', 'failed', 'partial'], true))
<span class="text-amber-300">Not issued yet</span>
@else
<span class="text-slate-500">—</span>
@endif
</td>
<td class="p-4">{{ $order->created_at->format('d M Y') }}</td>
</tr>
@empty
<tr><td colspan="6" class="p-8 text-center text-slate-400">No orders yet.</td></tr>
@endforelse
</tbody></table></div>{{ $orders->links() }}
<p class="mt-6 text-xs text-slate-400">Refunds appear here after they have been credited to your wallet. You can view the full refund transaction in <a href="{{ route('wallet.index') }}" class="text-violet-300 underline">My Wallet</a>.</p>
<p class="mt-3 text-xs text-amber-300">Orders may require manual review before fulfilment. A supplier status update does not itself issue a wallet refund.</p></main></body></html>
