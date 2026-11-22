<?php

namespace App\\Services;

use App\\Models\\Order;
use App\\Models\\WalletTransaction;
use Illuminate\\Support\\Facades\\DB;
use Throwable;

class SubmitSmmCenterOrder
{
    /**
     * Submit a paid SMM Center order once. Any ambiguous supplier response is
     * deliberately left locked for manual reconciliation; never auto-retry it.
     */
    public function submit(Order $order): string
    {
        $order->loadMissing('service');

        if ($order->service->provider !== 'smm_center'
            || !is_numeric($order->service->provider_service_id)) {
            return 'not_applicable';
        }

        $paymentConfirmed = WalletTransaction::query()
            ->where('user_id', $order->user_id)
            ->where('type', 'order_payment')
            ->where('status', 'completed')
            ->where('currency', 'NGN')
            ->where('reference', 'ORDER-' . $order->id)
            ->exists();

        if (!$paymentConfirmed || !in_array($order->status, ['pending', 'processing'], true)) {
            return 'not_eligible';
        }

        $claimed = Order::query()
            ->whereKey($order->id)
            ->whereNull('provider_order_id')
            ->whereNull('provider_status')
            ->update(['provider_status' => 'submission_in_progress']);

        if ($claimed !== 1) {
            return 'already_claimed';
        }

        try {
            $response = app(SmmCenterClient::class)->addOrder(
                (int) $order->service->provider_service_id,
                $order->target_url,
                (int) $order->quantity
            );

            DB::transaction(function () use ($order, $response) {
                $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
                if ($locked->provider_status !== 'submission_in_progress' || $locked->provider_order_id !== null) {
                    throw new \\RuntimeException('Supplier submission claim changed unexpectedly.');
                }

                $locked->update([
                    'provider_order_id' => (string) $response['order'],
                    'provider_status' => 'Submitted',
                    'provider_submitted_at' => now(),
                    'status' => 'processing',
                    'notes' => trim(str_replace(
                        'Paid from wallet. Awaiting manual review; not submitted to a provider.',
                        'Paid from wallet.',
                        (string) $locked->notes
                    ) . "\\n" . 'Submitted to SMM Center. Provider order ID: ' . $response['order']),
                ]);
            });

            return 'submitted';
        } catch (Throwable $exception) {
            Order::query()->whereKey($order->id)
                ->whereNull('provider_order_id')
                ->update([
                    'provider_status' => 'submission_failed_review_required',
                    'notes' => DB::raw("TRIM(CONCAT(COALESCE(notes, ''), '\\nProvider submission could not be confirmed. Check the SMM Center dashboard before taking any further action.'))"),
                ]);

            report($exception);

            return 'review_required';
        }
    }
}
