<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\PaystackGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class WalletDepositController extends Controller
{
    public function store(Request $request, PaystackGateway $paystack): RedirectResponse
    {
        $request->validate([
            'amount' => ['required', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
        ]);

        if (!config('services.paystack.secret_key')) {
            return back()->withErrors(['amount' => 'Deposits are not configured yet. Please try again later.']);
        }

        [$whole, $fraction] = array_pad(explode('.', $request->input('amount'), 2), 2, '0');
        $amountKobo = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');

        if ($amountKobo < config('services.paystack.minimum_deposit_kobo')
            || $amountKobo > config('services.paystack.maximum_deposit_kobo')) {
            return back()->withErrors(['amount' => 'Enter an amount within the allowed deposit limits.']);
        }

        $user = $request->user();
        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance_kobo' => 0, 'currency' => 'NGN']
        );
        $reference = 'FB-' . Str::ulid();

        $transaction = WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount_kobo' => $amountKobo,
            'currency' => 'NGN',
            'status' => 'pending',
            'reference' => $reference,
            'description' => 'Wallet deposit via Paystack',
            'metadata' => ['gateway' => 'paystack'],
        ]);

        try {
            $payment = $paystack->initialize([
                'email' => $user->email,
                'amount' => $amountKobo,
                'currency' => 'NGN',
                'reference' => $reference,
                'callback_url' => route('wallet.deposit.callback'),
                'metadata' => [
                    'user_id' => $user->id,
                    'wallet_transaction_id' => $transaction->id,
                    'custom_fields' => [
                        ['display_name' => 'Wallet deposit', 'variable_name' => 'wallet_deposit', 'value' => 'Firebooster wallet'],
                    ],
                ],
            ]);

            if (empty($payment['authorization_url'])) {
                throw new \RuntimeException('Paystack did not return a checkout link.');
            }

            return redirect()->away($payment['authorization_url']);
        } catch (Throwable $exception) {
            $transaction->update(['status' => 'failed']);
            report($exception);

            return back()->withErrors(['amount' => 'We could not start the payment. No money has been added to your wallet.']);
        }
    }

    public function callback(Request $request, PaystackGateway $paystack): RedirectResponse
    {
        $reference = (string) $request->query('reference', '');
        if ($reference === '' || strlen($reference) > 120) {
            return redirect()->route('wallet.index')->withErrors(['payment' => 'Payment reference is missing.']);
        }

        $transaction = WalletTransaction::query()
            ->where('reference', $reference)
            ->where('user_id', $request->user()->id)
            ->where('type', 'deposit')
            ->first();

        if (!$transaction) {
            return redirect()->route('wallet.index')->withErrors(['payment' => 'This deposit reference was not found.']);
        }

        try {
            $data = $paystack->verify($reference);
            $this->creditVerifiedDeposit($data, $transaction->id);
            return redirect()->route('wallet.index')->with('status', 'Payment verification completed. Your wallet has been refreshed.');
        } catch (Throwable $exception) {
            report($exception);
            return redirect()->route('wallet.index')->withErrors(['payment' => 'Payment is not confirmed yet. Your wallet will update after Paystack confirms it.']);
        }
    }

    public function webhook(Request $request, PaystackGateway $paystack): JsonResponse
    {
        $rawBody = $request->getContent();
        if (!$paystack->validWebhookSignature(
            $rawBody,
            $request->header('Paystack-Signature'),
            $request->header('x-paystack-signature')
        )) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = json_decode($rawBody, true);
        if (!is_array($event)) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        if (($event['event'] ?? null) !== 'charge.success') {
            return response()->json(['received' => true]);
        }

        $data = $event['data'] ?? [];
        $reference = $data['reference'] ?? null;
        if (!is_string($reference) || $reference === '') {
            return response()->json(['message' => 'Missing reference'], 400);
        }

        $transaction = WalletTransaction::query()
            ->where('reference', $reference)
            ->where('type', 'deposit')
            ->first();

        if (!$transaction) {
            return response()->json(['received' => true]);
        }

        try {
            $this->creditVerifiedDeposit($data, $transaction->id);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'Deposit could not be processed'], 500);
        }

        return response()->json(['received' => true]);
    }

    private function creditVerifiedDeposit(array $data, int $transactionId): void
    {
        DB::transaction(function () use ($data, $transactionId) {
            $transaction = WalletTransaction::query()->lockForUpdate()->findOrFail($transactionId);

            if ($transaction->status === 'completed') {
                return; // Idempotency: a callback and webhook may arrive for the same payment.
            }

            $metadata = $data['metadata'] ?? [];
            $valid = ($data['status'] ?? null) === 'success'
                && ($data['reference'] ?? null) === $transaction->reference
                && (int) ($data['amount'] ?? 0) === (int) $transaction->amount_kobo
                && ($data['currency'] ?? null) === 'NGN'
                && (int) ($metadata['user_id'] ?? 0) === (int) $transaction->user_id
                && (int) ($metadata['wallet_transaction_id'] ?? 0) === (int) $transaction->id;

            if (!$valid) {
                throw new \RuntimeException('Paystack transaction details did not match the pending wallet deposit.');
            }

            $wallet = Wallet::query()->lockForUpdate()->findOrFail($transaction->wallet_id);
            $wallet->balance_kobo = $wallet->balance_kobo + $transaction->amount_kobo;
            $wallet->save();

            $transaction->update([
                'status' => 'completed',
                'metadata' => array_merge((array) $transaction->metadata, [
                    'gateway' => 'paystack',
                    'paystack_transaction_id' => $data['id'] ?? null,
                    'paid_at' => now()->toISOString(),
                ]),
            ]);
        });
    }
}
