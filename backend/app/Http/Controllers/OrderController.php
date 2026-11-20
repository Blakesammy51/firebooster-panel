<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('orders.index', [
            'orders' => Order::query()
                ->with('service')
                ->where('user_id', $request->user()->id)
                ->latest()
                ->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'target_url' => ['required', 'url', 'max:2048'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);

        $service = Service::query()
            ->whereKey($validated['service_id'])
            ->where('is_active', true)
            ->firstOrFail();

        abort_unless(
            $validated['quantity'] >= $service->minimum_quantity
                && $validated['quantity'] <= $service->maximum_quantity,
            422,
            'The quantity is outside this service’s allowed range.'
        );

        // Price is calculated on the server; browser-submitted prices are never accepted.
        $unitPrice = (float) $service->price_per_1000;
        $totalPrice = round(($unitPrice * $validated['quantity']) / 1000, 2);

        Order::query()->create([
            'user_id' => $request->user()->id,
            'service_id' => $service->id,
            'target_url' => $validated['target_url'],
            'quantity' => $validated['quantity'],
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'status' => 'pending',
            'notes' => 'Awaiting payment and manual review. Not submitted to a provider.',
        ]);

        return redirect()->route('orders.index')
            ->with('status', 'Order request saved. It has not been paid for or sent to a provider.');
    }
}
