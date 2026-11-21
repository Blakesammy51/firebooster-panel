@php($tracking = $order->getAttribute('customer_tracking') ?? ['steps' => ['Order placed', 'Review', 'Processing', 'Completed'], 'active_step' => 1, 'message' => 'Your order has been received. Check back here for the latest status update.', 'delivered' => null, 'remaining' => null, 'show_progress' => false])
<section class="mt-4 rounded-xl border border-slate-800 bg-slate-950/70 p-3" aria-label="Order progress">
    <div class="grid grid-cols-4 gap-2">
        @foreach ($tracking['steps'] as $index => $step)
            @php($stepNumber = $index + 1)
            <div class="min-w-0 text-center">
                <div class="mx-auto flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold {{ $stepNumber < $tracking['active_step'] ? 'bg-emerald-700 text-white' : ($stepNumber === $tracking['active_step'] ? 'bg-violet-600 text-white ring-2 ring-violet-400/40' : 'bg-slate-800 text-slate-500') }}">
                    {{ $stepNumber < $tracking['active_step'] ? '✓' : $stepNumber }}
                </div>
                <p class="mt-2 text-[10px] leading-tight {{ $stepNumber === $tracking['active_step'] ? 'font-semibold text-violet-200' : ($stepNumber < $tracking['active_step'] ? 'text-emerald-300' : 'text-slate-500') }}">{{ $step }}</p>
            </div>
        @endforeach
    </div>
    <p class="mt-3 text-xs leading-5 text-slate-300">{{ $tracking['message'] }}</p>
    @if ($tracking['show_progress'])
        <p class="mt-2 text-xs font-medium text-sky-300">
            Supplier progress reported: {{ number_format($tracking['delivered']) }} delivered · {{ number_format($tracking['remaining']) }} remaining
        </p>
    @endif
</section>
