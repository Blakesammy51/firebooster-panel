<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Service;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\SubmitSmmCenterOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with('service')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        $refundReferences = $orders->getCollection()
            ->map(fn (Order $order) => 'ORDER-' . $order->id . '-REFUND')
            ->all();

        $refunds = WalletTransaction::query()
            ->where('user_id', $request->user()->id)
            ->where('type', 'order_refund')
            ->where('status', 'completed')
            ->whereIn('reference', $refundReferences)
            ->get()
            ->keyBy('reference');

        foreach ($orders as $order) {
            $status = strtolower((string) $order->status);
            $terminalLabel = match ($status) {
                'completed' => 'Completed',
                'partial' => 'Partially completed',
                'cancelled' => 'Cancelled',
                'failed' => 'Failed',
                default => 'Completed',
            };
            $activeStep = match ($status) {
                'pending', 'awaiting_payment' => 1,
                'processing' => 2,
                default => 3,
            };
            $message = match ($status) {
                'pending' => 'Your payment is recorded. This order is waiting for manual review and has not yet been sent for fulfilment.',
                'awaiting_payment' => 'This order is waiting for payment confirmation. It will move forward once payment is verified.',
                'processing' => 'Your order has been accepted for fulfilment. Progress is shown when an update is available.',
                'completed' => 'Your order has been marked as completed.',
                'partial' => 'Some of the requested quantity was delivered. Check the refund status below for any wallet credit.',
                'cancelled' => 'This order was cancelled. Any refund is shown separately and only appears after it has been credited to your wallet.',
                'failed' => 'This order was marked as failed. Any refund is shown separately and only appears after it has been credited to your wallet.',
                default => 'Your order status was updated. Check back for the latest information.',
            };

            $remaining = $order->provider_remains === null
                ? null
                : min((int) $order->quantity, max(0, (int) $order->provider_remains));
            $delivered = $remaining === null ? null : max(0, (int) $order->quantity - $remaining);

            $order->setAttribute('customer_tracking', [
                'steps' => ['Order placed', 'Review', 'Processing', $terminalLabel],
                'active_step' => $activeStep,
                'is_complete' => $status === 'completed',
                'message' => $message,
                'delivered' => $delivered,
                'remaining' => $remaining,
                'show_progress' => $remaining !== null && in_array($status, ['processing', 'partial', 'completed'], true),
            ]);
        }

        return view('orders.index', [
            'orders' => $orders,
            'refunds' => $refunds,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'target_url' => ['required', 'url', 'max:2048'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);

        $user = $request->user();
        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance_kobo' => 0, 'currency' => 'NGN']
        );

        try {
            $order = DB::transaction(function () use ($validated, $user, $wallet) {
                $service = Service::query()
                    ->whereKey($validated['service_id'])
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (!$service) {
                    throw ValidationException::withMessages([
                        'service_id' => 'This service is no longer available.',
                    ]);
                }

                $quantity = (int) $validated['quantity'];
                if ($quantity < $service->minimum_quantity || $quantity > $service->maximum_quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => 'The quantity is outside this service’s allowed range.',
                    ]);
                }

                // Store and calculate money in kobo to avoid floating-point wallet arithmetic.
                $unitPriceKobo = (int) round(((float) $service->price_per_1000) * 100);
                $totalKobo = intdiv(($unitPriceKobo * $quantity) + 500, 1000);

                if ($totalKobo < 1) {
                    throw ValidationException::withMessages([
                        'service_id' => 'This service does not have a payable price yet.',
                    ]);
                }

                $lockedWallet = Wallet::query()->lockForUpdate()->findOrFail($wallet->id);
                if ($lockedWallet->balance_kobo < $totalKobo) {
                    throw ValidationException::withMessages([
                        'wallet' => 'Insufficient wallet balance. Please add funds before placing this order.',
                    ]);
                }

                $order = Order::query()->create([
                    'user_id' => $user->id,
                    'service_id' => $service->id,
                    'target_url' => $validated['target_url'],
                    'quantity' => $quantity,
                    'unit_price' => $service->price_per_1000,
                    'total_price' => number_format($totalKobo / 100, 2, '.', ''),
                    'status' => 'pending',
                    'notes' => 'Paid from wallet. Awaiting fulfilment submission.',
                ]);

                $lockedWallet->balance_kobo -= $totalKobo;
                $lockedWallet->save();

                WalletTransaction::query()->create([
                    'wallet_id' => $lockedWallet->id,
                    'user_id' => $user->id,
                    'type' => 'order_payment',
                    'amount_kobo' => $totalKobo,
                    'currency' => 'NGN',
                    'status' => 'completed',
                    'reference' => 'ORDER-' . $order->id,
                    'description' => 'Wallet payment for order #' . $order->id,
                    'metadata' => [
                        'order_id' => $order->id,
                        'service_id' => $service->id,
                        'quantity' => $quantity,
                    ],
                ]);

                return $order;
            });
        } catch (ValidationException $exception) {
            throw $exception;
        }

        $fulfilment = app(SubmitSmmCenterOrder::class)->submit($order);

        $message = match ($fulfilment) {
            'submitted' => 'Order #' . $order->id . ' placed and automatically submitted to our fulfilment provider.',
            'review_required' => 'Order #' . $order->id . ' is paid, but supplier acceptance could not be confirmed. It has been held for manual review; please do not place it again.',
            'already_claimed' => 'Order #' . $order->id . ' is paid and already being processed.',
            default => 'Order #' . $order->id . ' placed and paid from your wallet. It is awaiting fulfilment review.',
        };

        return redirect()->route('orders.index')->with('status', $message);
    }
}
