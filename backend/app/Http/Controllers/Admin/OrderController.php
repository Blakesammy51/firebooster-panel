<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        return view('admin.orders.index', [
            'orders' => Order::query()->with(['user', 'service'])->latest()->paginate(25),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'awaiting_payment', 'processing', 'completed', 'cancelled', 'failed'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $order->update($data);

        return back()->with('status', 'Order status updated. This does not trigger provider fulfillment or payment.');
    }
}
