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

    public function submitToProvider(Order $order, SmmCenterClient $client): RedirectResponse
    {
        $order->load('service');

        if ($order->service->provider !== 'smm_center' || !is_numeric($order->service->provider_service_id)) {
            return back()->withErrors(['provider' => 'This order is not linked to a valid SMM Center service.']);
        }

        if ($order->provider_order_id !== null || $order->provider_status !== null) {
            return back()->withErrors(['provider' => 'This order has already been submitted or needs manual review. Duplicate submission is blocked.']);
        }

        if (!in_array($order->status, ['pending', 'processing'], true)) {
            return back()->withErrors(['provider' => 'Only pending orders can be submitted to the provider.']);
        }

        // Claim the order atomically before making the external request. This prevents
        // double-clicks and concurrent admin requests from creating duplicate supplier orders.
        $claimed = Order::query()
            ->whereKey($order->id)
            ->whereNull('provider_order_id')
            ->whereNull('provider_status')
            ->update(['provider_status' => 'submission_in_progress']);

        if ($claimed !== 1) {
            return back()->withErrors(['provider' => 'This order is already being processed or requires manual review.']);
        }

        try {
            $response = $client->addOrder(
                (int) $order->service->provider_service_id,
                $order->target_url,
                (int) $order->quantity
            );

            $order->refresh();
            $order->update([
                'provider_order_id' => (string) $response['order'],
                'provider_status' => 'Submitted',
                'provider_submitted_at' => now(),
                'status' => 'processing',
                'notes' => trim(($order->notes ? $order->notes . "\\n" : '') . 'Submitted to SMM Center. Provider order ID: ' . $response['order']),
            ]);

            return back()->with('status', 'Order #' . $order->id . ' was submitted to SMM Center. Provider order ID: ' . $response['order']);
        } catch (Throwable $exception) {
            $order->refresh();
            $order->update([
                'provider_status' => 'submission_failed_review_required',
                'notes' => trim(($order->notes ? $order->notes . "\\n" : '') . 'Provider submission could not be confirmed. Check the SMM Center dashboard before taking any further action.'),
            ]);

            report($exception);

            return back()->withErrors(['provider' => 'The provider submission could not be confirmed. Check the supplier dashboard before retrying; Firebooster has blocked automatic resubmission to prevent duplicates.']);
        }
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
