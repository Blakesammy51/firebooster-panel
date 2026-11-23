<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\SmmCenterClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:all,pending,awaiting_payment,processing,partial,completed,cancelled,failed'],
        ]);
        $search = trim(ltrim($filters['search'] ?? '', '#'));
        $status = $filters['status'] ?? 'all';

        $query = Order::query()->with(['user', 'service'])->latest();
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                if (ctype_digit($search)) {
                    $builder->orWhere('id', (int) $search);
                }
                $builder->orWhere('provider_order_id', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn ($user) => $user->where('email', 'like', '%'.$search.'%'))
                    ->orWhereHas('service', fn ($service) => $service->where('name', 'like', '%'.$search.'%'));
            });
        }

        return view('admin.orders.index', [
            'orders' => $query->paginate(20)->withQueryString(),
            'search' => $filters['search'] ?? '',
            'status' => $status,
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

        // A local order status alone is not proof of payment. Require the completed
        // wallet debit created atomically with customer order placement.
        $paymentConfirmed = WalletTransaction::query()
            ->where('user_id', $order->user_id)
            ->where('type', 'order_payment')
            ->where('status', 'completed')
            ->where('currency', 'NGN')
            ->where('reference', 'ORDER-' . $order->id)
            ->exists();

        if (!$paymentConfirmed) {
            return back()->withErrors(['provider' => 'This order has no confirmed completed wallet payment. Provider submission is blocked.']);
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
                'notes' => trim(str_replace(
                    'Paid from wallet. Awaiting manual review; not submitted to a provider.',
                    'Paid from wallet.',
                    (string) $order->notes
                ) . "\n" . 'Submitted to SMM Center. Provider order ID: ' . $response['order']),
            ]);

            return back()->with('status', 'Order #' . $order->id . ' was submitted to SMM Center. Provider order ID: ' . $response['order']);
        } catch (Throwable $exception) {
            $order->refresh();
            $order->update([
                'provider_status' => 'submission_failed_review_required',
                'notes' => trim(($order->notes ? $order->notes . "\n" : '') . 'Provider submission could not be confirmed. Check the SMM Center dashboard before taking any further action.'),
            ]);

            report($exception);

            return back()->withErrors(['provider' => 'The provider submission could not be confirmed. Check the supplier dashboard before retrying; Firebooster has blocked automatic resubmission to prevent duplicates.']);
        }
    }

    public function refreshProviderStatus(Order $order, SmmCenterClient $client): RedirectResponse
    {
        if (!$order->provider_order_id || !is_numeric($order->provider_order_id)) {
            return back()->withErrors(['provider' => 'This order has no confirmed supplier order ID to refresh.']);
        }

        if ($order->service()->value('provider') !== 'smm_center') {
            return back()->withErrors(['provider' => 'This order is not linked to SMM Center.']);
        }

        $refundReference = 'ORDER-' . $order->id . '-REFUND';
        if (WalletTransaction::query()
            ->where('reference', $refundReference)
            ->where('type', 'order_refund')
            ->where('status', 'completed')
            ->exists()) {
            return back()->withErrors(['provider' => 'This order has already been refunded. Supplier refresh cannot reopen its status.']);
        }

        try {
            $data = $client->orderStatus((int) $order->provider_order_id);
            $providerStatus = trim((string) $data['status']);
            $normalized = strtolower($providerStatus);

            $updates = ['provider_status' => $providerStatus];

            if (isset($data['charge']) && is_numeric($data['charge'])) {
                $updates['provider_charge'] = $data['charge'];
            }
            if (isset($data['currency']) && is_string($data['currency'])) {
                $updates['provider_currency'] = strtoupper($data['currency']);
            }
            if (isset($data['start_count']) && is_numeric($data['start_count'])) {
                $updates['provider_start_count'] = max(0, (int) $data['start_count']);
            }
            if (isset($data['remains']) && is_numeric($data['remains'])) {
                $updates['provider_remains'] = max(0, (int) $data['remains']);
            }

            if ($normalized === 'completed') {
                $updates['status'] = 'completed';
            } elseif (in_array($normalized, ['canceled', 'cancelled'], true)) {
                $updates['status'] = 'cancelled';
            } elseif ($normalized === 'partial') {
                $updates['status'] = 'partial';
            } elseif ($normalized === 'failed') {
                $updates['status'] = 'failed';
            } else {
                $updates['status'] = 'processing';
            }

            // Recheck atomically in case a refund was issued during the supplier request.
            $updated = Order::query()
                ->whereKey($order->id)
                ->whereNotExists(function ($query) use ($refundReference) {
                    $query->selectRaw('1')
                        ->from('wallet_transactions')
                        ->where('reference', $refundReference)
                        ->where('type', 'order_refund')
                        ->where('status', 'completed');
                })
                ->update($updates);

            if ($updated !== 1) {
                return back()->withErrors(['provider' => 'This order has already been refunded. Supplier refresh cannot reopen its status.']);
            }

            return back()->with('status', 'Supplier status refreshed for order #' . $order->id . ': ' . $providerStatus);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['provider' => 'Could not refresh this supplier status. The existing order status was left unchanged.']);
        }
    }

    public function refundToWallet(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'refund_amount' => ['nullable', 'numeric', 'gt:0', 'decimal:0,2'],
        ]);

        try {
            $amountKobo = DB::transaction(function () use ($order, $data) {
                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

                if ($lockedOrder->service()->value('provider') !== 'smm_center'
                    || !$lockedOrder->provider_order_id
                    || !in_array($lockedOrder->status, ['cancelled', 'failed', 'partial'], true)) {
                    throw new \RuntimeException('Only supplier-backed cancelled, failed, or partial orders can be refunded.');
                }

                $expectedProviderStatus = match ($lockedOrder->status) {
                    'cancelled' => ['cancelled', 'canceled'],
                    'failed' => ['failed'],
                    'partial' => ['partial'],
                };
                if (!in_array(strtolower((string) $lockedOrder->provider_status), $expectedProviderStatus, true)) {
                    throw new \RuntimeException('The supplier status does not confirm this refund state. Refresh supplier status first.');
                }

                $payment = WalletTransaction::query()
                    ->where('user_id', $lockedOrder->user_id)
                    ->where('type', 'order_payment')
                    ->where('status', 'completed')
                    ->where('currency', 'NGN')
                    ->where('reference', 'ORDER-' . $lockedOrder->id)
                    ->lockForUpdate()
                    ->first();

                if (!$payment) {
                    throw new \RuntimeException('A completed NGN wallet payment could not be verified.');
                }

                $reference = 'ORDER-' . $lockedOrder->id . '-REFUND';
                if (WalletTransaction::query()->where('reference', $reference)->exists()) {
                    throw new \RuntimeException('A refund has already been issued for this order. Duplicate credit is blocked.');
                }

                if (in_array($lockedOrder->status, ['cancelled', 'failed'], true)) {
                    if ($lockedOrder->provider_remains === null || $lockedOrder->provider_remains !== $lockedOrder->quantity) {
                        throw new \RuntimeException('Full refund is blocked because the supplier has not confirmed that the full quantity remains undelivered.');
                    }
                    $refundKobo = (int) $payment->amount_kobo;
                } else {
                    $maximumRefundKobo = intdiv(
                        (int) $payment->amount_kobo * (int) $lockedOrder->provider_remains,
                        (int) $lockedOrder->quantity
                    );
                    $refundKobo = (int) round(((float) $data['refund_amount']) * 100);
                    if ($refundKobo < 1 || $maximumRefundKobo < 1 || $refundKobo > $maximumRefundKobo) {
                        throw new \\RuntimeException(
                            'The partial refund cannot exceed the proportional value of the confirmed undelivered quantity (NGN '
                            . number_format($maximumRefundKobo / 100, 2) . ').'
                        );
                    }
                }

                $wallet = Wallet::query()->where('user_id', $lockedOrder->user_id)->lockForUpdate()->first();
                if (!$wallet || $wallet->currency !== 'NGN') {
                    throw new \RuntimeException('The customer NGN wallet could not be verified.');
                }

                $wallet->balance_kobo += $refundKobo;
                $wallet->save();

                WalletTransaction::query()->create([
                    'wallet_id' => $wallet->id,
                    'user_id' => $lockedOrder->user_id,
                    'type' => 'order_refund',
                    'amount_kobo' => $refundKobo,
                    'currency' => 'NGN',
                    'status' => 'completed',
                    'reference' => $reference,
                    'description' => 'Wallet refund for order #' . $lockedOrder->id,
                    'metadata' => ['order_id' => $lockedOrder->id, 'original_payment_reference' => $payment->reference],
                ]);

                $lockedOrder->notes = trim(($lockedOrder->notes ? $lockedOrder->notes . "\\n" : '')
                    . 'Wallet refund issued: NGN ' . number_format($refundKobo / 100, 2) . '.');
                $lockedOrder->save();

                return $refundKobo;
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['provider' => $exception->getMessage()]);
        }

        return back()->with('status', 'Wallet refund of ₦' . number_format($amountKobo / 100, 2) . ' issued for order #' . $order->id . '.');
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'awaiting_payment', 'processing', 'partial', 'completed', 'cancelled', 'failed'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'provider_fx_rate' => ['nullable', 'numeric', 'gt:0', 'decimal:0,4'],
            'provider_fx_source' => ['nullable', 'string', 'max:160', 'required_with:provider_fx_rate'],
        ]);

        if (array_key_exists('provider_fx_rate', $data) && $data['provider_fx_rate'] !== null) {
            if (strtoupper((string) $order->provider_currency) !== 'USD'
                || $order->provider_charge === null) {
                return back()->withErrors(['provider' => 'An FX snapshot can only be saved for an order with a recorded USD supplier charge.']);
            }

            $data['provider_fx_captured_at'] = now();
            $data['provider_fx_source'] = trim($data['provider_fx_source']);
        }

        $order->update($data);

        return back()->with('status', 'Order status updated. This does not trigger provider fulfillment or payment.');
    }
}
