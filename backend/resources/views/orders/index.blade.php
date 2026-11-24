<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>My orders · Firebooster</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
<main class="mx-auto max-w-6xl px-4 py-6 pb-28 sm:px-6 sm:py-10 sm:pb-12">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('services.index') }}" class="text-violet-300 underline">← Browse services</a>
        <a href="{{ route('wallet.index') }}" class="text-violet-300 underline">My wallet</a>
    </div>
    @include('components.community-links')
    <section class="relative my-6 overflow-hidden rounded-3xl border border-violet-400/20 bg-gradient-to-br from-violet-950/80 via-slate-900/95 to-fuchsia-950/40 p-6 shadow-2xl shadow-violet-950/20 sm:p-8"><div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full bg-fuchsia-500/10 blur-3xl" aria-hidden="true"></div><div class="relative"><p class="text-xs font-bold uppercase tracking-[0.2em] text-violet-300">Your activity</p><h1 class="mt-2 text-3xl font-extrabold text-white sm:text-4xl">My Orders</h1><p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-300">Track your service orders, follow delivery progress and review any wallet refunds.</p></div></section>

    @if (session('status'))<p class="mb-5 rounded-lg bg-emerald-950 p-4 text-emerald-200">{{ session('status') }}</p>@endif
    @if ($errors->any())<div class="mb-5 rounded-lg bg-red-950 p-4 text-red-200">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

    @if ($orders->isEmpty())
        <div class="rounded-2xl border border-violet-300/20 bg-gradient-to-br from-violet-950/40 to-slate-900/90 p-8 text-center shadow-xl shadow-violet-950/10">
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
                <article class="rounded-2xl border border-violet-300/15 bg-slate-900/85 p-4 shadow-lg shadow-slate-950/20 transition hover:border-violet-300/30">
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
        <div class="hidden overflow-x-auto rounded-2xl border border-violet-300/15 bg-slate-900/70 shadow-xl shadow-slate-950/20 md:block">
            <table class="w-full text-left text-sm">
                <thead class="bg-violet-950/40 text-violet-200"><tr><th class="p-4">Order</th><th class="p-4">Service</th><th class="p-4">Quantity</th><th class="p-4">Total paid</th><th class="p-4">Fulfilment status</th><th class="p-4">Refund status</th><th class="p-4">Date</th></tr></thead>
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
    <p class="mt-3 text-xs text-amber-300">Orders are automatically submitted to the provider for processing after successful wallet payment. A supplier status update does not itself issue a wallet refund.</p>
</main>

<a href="https://t.me/FireboosterSupport" target="_blank" rel="noopener noreferrer"
   aria-label="Contact Firebooster support on Telegram"
   class="fixed bottom-6 right-4 z-40 inline-flex items-center gap-2 rounded-full border border-fuchsia-300/25 bg-gradient-to-r from-violet-600 to-fuchsia-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-violet-950/50 transition hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-fuchsia-300 focus:ring-offset-2 focus:ring-offset-slate-950">
    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true"><path d="M21.8 4.2 18.6 19c-.2 1.1-.9 1.4-1.8.9l-5-3.7-2.4 2.3c-.3.3-.5.5-1 .5l.4-5.1 9.3-8.4c.4-.4-.1-.6-.6-.2L6 12.5l-4.9-1.5c-1.1-.3-1.1-1 .2-1.5L20.5 2.7c.9-.3 1.7.2 1.3 1.5Z"/></svg>
    <span>Telegram Support</span>
</a>

</body>
</html>
