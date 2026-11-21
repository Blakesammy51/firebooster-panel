<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>My orders · Firebooster</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
<main class="mx-auto max-w-6xl px-4 py-6 sm:px-5 sm:py-10">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('services.index') }}" class="text-violet-300 underline">← Browse services</a>
        <a href="{{ route('wallet.index') }}" class="text-violet-300 underline">My wallet</a>
    </div>
    <h1 class="my-6 text-3xl font-bold">My orders</h1>

    @if (session('status'))<p class="mb-5 rounded-lg bg-emerald-950 p-4 text-emerald-200">{{ session('status') }}</p>@endif
    @if ($errors->any())<div class="mb-5 rounded-lg bg-red-950 p-4 text-red-200">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

    @if ($orders->isEmpty())
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-8 text-center">
            <p class="text-lg font-semibold">No orders yet</p>
            <p class="mt-2 text-sm text-slate-400">Your placed orders will appear here.</p>
            <a href="{{ route('services.index') }}" class="mt-5 inline-flex rounded-xl bg-violet-600 px-5 py-3 text-sm font-semibold text-white">Browse services</a>
        </div>
    @else
        {{-- Mobile order cards --}}
        <div class="space-y-3 md:hidden">
            @foreach ($orders as $order)
                @php
                    $refund = $refunds->get('ORDER-' . $order->id . '-REFUND');
                    $statusLabel = str_replace('_', ' ', ucfirst($order->status));
                    $statusClass = match ($order->status) {
                        'completed' => 'bg-emerald-950 text-emerald-300',
                        'processing' => 'bg-sky-950 text-sky-300',
                        'pending' => 'bg-amber-950 text-amber-300',
                        'partial' => 'bg-orange-950 text-orange-300',
                        'cancelled', 'failed' => 'bg-red-950 text-red-300',
                        default => 'bg-slate-800 text-slate-300',
                    };
                @endphp
                <article class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs text-slate-500">Order #{{ $order->id }}</p>
                            <h2 class="mt-1 break-words font-semibold">{{ $order->service->name }}</h2>
                        </div>
                        <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-800 pt-3 text-sm">
                        <div><p class="text-xs text-slate-500">Quantity</p><p class="mt-1 font-medium">{{ number_format($order->quantity) }}</p></div>
                        <div><p class="text-xs text-slate-500">Total paid</p><p class="mt-1 font-semibold text-emerald-300">₦{{ number_format((float) $order->total_price, 2) }}</p></div>
                        <div><p class="text-xs text-slate-500">Refund status</p>
                            @if ($refund)
                                <p class="mt-1 font-medium text-emerald-300">Credited · ₦{{ number_format($refund->amount_kobo / 100, 2) }}</p>
                            @elseif (in_array($order->status, ['cancelled', 'failed', 'partial'], true))
                                <p class="mt-1 text-amber-300">Not issued yet</p>
                            @else
                                <p class="mt-1 text-slate-400">—</p>
                            @endif
                        </div>
                        <div><p class="text-xs text-slate-500">Date</p><p class="mt-1">{{ $order->created_at->format('d M Y') }}</p></div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">Paid from wallet</p>
                    @include('orders._tracking', ['order' => $order])
                </article>
            @endforeach
        </div>

        {{-- Wider-screen table --}}
        <div class="hidden overflow-x-auto rounded-xl border border-slate-800 md:block">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-900 text-slate-400"><tr><th class="p-4">Order</th><th class="p-4">Service</th><th class="p-4">Quantity</th><th class="p-4">Total paid</th><th class="p-4">Fulfilment status</th><th class="p-4">Refund status</th><th class="p-4">Date</th></tr></thead>
                <tbody>
                @foreach ($orders as $order)
                    @php($refund = $refunds->get('ORDER-' . $order->id . '-REFUND'))
                    <tr class="border-t border-slate-800">
                        <td class="p-4 text-slate-400">#{{ $order->id }}</td>
                        <td class="p-4">{{ $order->service->name }}</td>
                        <td class="p-4">{{ number_format($order->quantity) }}</td>
                        <td class="p-4">₦{{ number_format((float) $order->total_price, 2) }} <span class="block text-xs text-emerald-300">Paid from wallet</span></td>
                        <td class="p-4">{{ str_replace('_', ' ', ucfirst($order->status)) }}</td>
                        <td class="p-4">
                            @if ($refund)
                                <span class="font-medium text-emerald-300">Refund credited</span><span class="block text-xs text-slate-400">+₦{{ number_format($refund->amount_kobo / 100, 2) }}</span>
                            @elseif (in_array($order->status, ['cancelled', 'failed', 'partial'], true))
                                <span class="text-amber-300">Not issued yet</span>
                            @else
                                <span class="text-slate-500">—</span>
                            @endif
                        </td>
                        <td class="p-4">{{ $order->created_at->format('d M Y') }}</td>
                    </tr>
                    <tr class="border-t border-slate-800 bg-slate-950/40">
                        <td colspan="7" class="px-4 pb-4">@include('orders._tracking', ['order' => $order])</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{ $orders->links() }}
    <p class="mt-6 text-xs text-slate-400">Refunds appear here after they have been credited to your wallet. You can view the full refund transaction in <a href="{{ route('wallet.index') }}" class="text-violet-300 underline">My Wallet</a>.</p>
    <p class="mt-3 text-xs text-amber-300">Orders may require manual review before fulfilment. A supplier status update does not itself issue a wallet refund.</p>
</main>
</body>
</html>
