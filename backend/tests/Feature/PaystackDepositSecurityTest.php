<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\PaystackGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaystackDepositSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_start_a_wallet_deposit(): void
    {
        $response = $this->post(route('wallet.deposit.store'), ['amount' => '500']);

        $response->assertRedirect(route('login'));
    }

    public function test_deposit_is_not_started_when_paystack_is_not_configured(): void
    {
        config(['services.paystack.secret_key' => null]);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->from(route('wallet.index'))
            ->post(route('wallet.deposit.store'), ['amount' => '500']);

        $response->assertRedirect(route('wallet.index'));
        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_timestamped_paystack_webhook_signature_is_verified(): void
    {
        $secret = 'whsec_test_signing_secret';
        config(['services.paystack.webhook_secret' => $secret]);

        $timestamp = time();
        $body = '{"event":"charge.success","data":{"reference":"FB-test"}}';
        $signature = hash_hmac('sha512', $timestamp . '.' . $body, $secret);
        $header = 't=' . $timestamp . ',v1=whk_test:' . $signature;

        $gateway = new PaystackGateway();

        $this->assertTrue($gateway->validWebhookSignature($body, $header, null));
        $this->assertFalse($gateway->validWebhookSignature($body . ' ', $header, null));
    }

    public function test_legacy_signature_is_verified_when_no_webhook_signing_secret_is_configured(): void
    {
        $secretKey = 'sk_test_legacy_secret';
        config([
            'services.paystack.secret_key' => $secretKey,
            'services.paystack.webhook_secret' => null,
        ]);

        $body = '{"event":"charge.success","data":{"reference":"FB-legacy"}}';
        $legacySignature = hash_hmac('sha512', $body, $secretKey);
        $modernHeader = 't=' . time() . ',v1=whk_test:unusable';

        $gateway = new PaystackGateway();

        $this->assertTrue($gateway->validWebhookSignature($body, $modernHeader, $legacySignature));
        $this->assertFalse($gateway->validWebhookSignature($body . ' ', $modernHeader, $legacySignature));
    }

    public function test_invalid_modern_signature_does_not_fall_back_to_legacy_signature(): void
    {
        config([
            'services.paystack.secret_key' => 'sk_test_legacy_secret',
            'services.paystack.webhook_secret' => 'whsec_test_signing_secret',
        ]);

        $body = '{"event":"charge.success","data":{"reference":"FB-modern"}}';
        $legacySignature = hash_hmac('sha512', $body, 'sk_test_legacy_secret');
        $modernHeader = 't=' . time() . ',v1=whk_test:invalid';

        $gateway = new PaystackGateway();

        $this->assertFalse($gateway->validWebhookSignature($body, $modernHeader, $legacySignature));
    }

    public function test_successful_webhook_credits_wallet_once_even_when_retried(): void
    {
        config(['services.paystack.webhook_secret' => 'whsec_test_signing_secret']);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'balance_kobo' => 0,
            'currency' => 'NGN',
        ]);
        $transaction = WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount_kobo' => 50000,
            'currency' => 'NGN',
            'status' => 'pending',
            'reference' => 'FB-webhook-success',
            'description' => 'Wallet deposit via Paystack',
            'metadata' => ['gateway' => 'paystack'],
        ]);

        $body = json_encode([
            'event' => 'charge.success',
            'data' => [
                'id' => 123456,
                'status' => 'success',
                'reference' => $transaction->reference,
                'amount' => 50000,
                'currency' => 'NGN',
                'metadata' => [
                    'user_id' => $user->id,
                    'wallet_transaction_id' => $transaction->id,
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->sendSignedWebhook($body)->assertOk();
        $this->sendSignedWebhook($body)->assertOk();

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance_kobo' => 50000,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'id' => $transaction->id,
            'status' => 'completed',
        ]);
    }

    public function test_failed_paystack_webhook_marks_pending_deposit_failed_without_crediting_wallet(): void
    {
        config(['services.paystack.webhook_secret' => 'whsec_test_signing_secret']);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'balance_kobo' => 20000,
            'currency' => 'NGN',
        ]);
        $transaction = WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount_kobo' => 10000,
            'currency' => 'NGN',
            'status' => 'pending',
            'reference' => 'FB-webhook-failed',
            'description' => 'Wallet deposit via Paystack',
            'metadata' => ['gateway' => 'paystack'],
        ]);

        $body = json_encode([
            'event' => 'charge.failed',
            'data' => [
                'status' => 'failed',
                'reference' => $transaction->reference,
                'amount' => 10000,
                'currency' => 'NGN',
                'metadata' => [
                    'user_id' => $user->id,
                    'wallet_transaction_id' => $transaction->id,
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->sendSignedWebhook($body)->assertOk();

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance_kobo' => 20000,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'id' => $transaction->id,
            'status' => 'failed',
        ]);
    }

    public function test_cancelled_paystack_checkout_marks_verified_abandoned_deposit_abandoned_without_crediting_wallet(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'balance_kobo' => 20000,
            'currency' => 'NGN',
        ]);
        $transaction = WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount_kobo' => 10000,
            'currency' => 'NGN',
            'status' => 'pending',
            'reference' => 'FB-checkout-abandoned',
            'description' => 'Wallet deposit via Paystack',
            'metadata' => ['gateway' => 'paystack'],
        ]);

        $this->mock(PaystackGateway::class, function ($mock) use ($transaction, $user) {
            $mock->shouldReceive('verify')->once()->with($transaction->reference)->andReturn([
                'status' => 'abandoned',
                'reference' => $transaction->reference,
                'amount' => 10000,
                'currency' => 'NGN',
                'metadata' => [
                    'user_id' => $user->id,
                    'wallet_transaction_id' => $transaction->id,
                ],
                'gateway_response' => 'Payment session abandoned',
            ]);
        });

        $this->actingAs($user)
            ->get(route('wallet.deposit.cancel', ['reference' => $transaction->reference]))
            ->assertRedirect(route('wallet.index'));

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance_kobo' => 20000,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'id' => $transaction->id,
            'status' => 'abandoned',
        ]);
    }

    public function test_webhook_with_mismatched_amount_does_not_credit_wallet(): void
    {
        config(['services.paystack.webhook_secret' => 'whsec_test_signing_secret']);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'balance_kobo' => 0,
            'currency' => 'NGN',
        ]);
        $transaction = WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount_kobo' => 50000,
            'currency' => 'NGN',
            'status' => 'pending',
            'reference' => 'FB-webhook-wrong-amount',
            'description' => 'Wallet deposit via Paystack',
            'metadata' => ['gateway' => 'paystack'],
        ]);

        $body = json_encode([
            'event' => 'charge.success',
            'data' => [
                'status' => 'success',
                'reference' => $transaction->reference,
                'amount' => 100000,
                'currency' => 'NGN',
                'metadata' => [
                    'user_id' => $user->id,
                    'wallet_transaction_id' => $transaction->id,
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->sendSignedWebhook($body)->assertStatus(500);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance_kobo' => 0,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'id' => $transaction->id,
            'status' => 'pending',
        ]);
    }

    public function test_late_success_webhook_does_not_reopen_failed_or_abandoned_deposits(): void
    {
        config(['services.paystack.webhook_secret' => 'whsec_test_signing_secret']);

        foreach (['failed', 'abandoned'] as $terminalStatus) {
            $user = User::factory()->create(['email_verified_at' => now()]);
            $wallet = Wallet::query()->create([
                'user_id' => $user->id,
                'balance_kobo' => 25000,
                'currency' => 'NGN',
            ]);
            $transaction = WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount_kobo' => 10000,
                'currency' => 'NGN',
                'status' => $terminalStatus,
                'reference' => 'FB-late-success-' . $terminalStatus,
                'description' => 'Wallet deposit via Paystack',
                'metadata' => ['gateway' => 'paystack'],
            ]);

            $body = json_encode([
                'event' => 'charge.success',
                'data' => [
                    'status' => 'success',
                    'reference' => $transaction->reference,
                    'amount' => 10000,
                    'currency' => 'NGN',
                    'metadata' => [
                        'user_id' => $user->id,
                        'wallet_transaction_id' => $transaction->id,
                    ],
                ],
            ], JSON_THROW_ON_ERROR);

            $this->sendSignedWebhook($body)->assertOk();

            $this->assertDatabaseHas('wallets', [
                'id' => $wallet->id,
                'balance_kobo' => 25000,
            ]);
            $this->assertDatabaseHas('wallet_transactions', [
                'id' => $transaction->id,
                'status' => $terminalStatus,
            ]);
        }
    }

    public function test_callback_and_webhook_together_credit_a_successful_deposit_only_once(): void
    {
        config(['services.paystack.webhook_secret' => 'whsec_test_signing_secret']);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'balance_kobo' => 15000,
            'currency' => 'NGN',
        ]);
        $transaction = WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount_kobo' => 50000,
            'currency' => 'NGN',
            'status' => 'pending',
            'reference' => 'FB-callback-webhook-once',
            'description' => 'Wallet deposit via Paystack',
            'metadata' => ['gateway' => 'paystack'],
        ]);
        $verifiedPayment = [
            'id' => 987654,
            'status' => 'success',
            'reference' => $transaction->reference,
            'amount' => 50000,
            'currency' => 'NGN',
            'metadata' => [
                'user_id' => $user->id,
                'wallet_transaction_id' => $transaction->id,
            ],
        ];

        $this->mock(PaystackGateway::class, function ($mock) use ($transaction, $verifiedPayment) {
            $mock->shouldReceive('verify')->once()
                ->with($transaction->reference)
                ->andReturn($verifiedPayment);
            $mock->shouldReceive('validWebhookSignature')->once()->andReturn(true);
        });

        $this->actingAs($user)
            ->get(route('wallet.deposit.callback', ['reference' => $transaction->reference]))
            ->assertRedirect(route('wallet.index'));

        $webhookBody = json_encode([
            'event' => 'charge.success',
            'data' => $verifiedPayment,
        ], JSON_THROW_ON_ERROR);

        $this->sendSignedWebhook($webhookBody)->assertOk();

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance_kobo' => 65000,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'id' => $transaction->id,
            'status' => 'completed',
        ]);
    }

    private function sendSignedWebhook(string $body)
    {
        $timestamp = time();
        $secret = (string) config('services.paystack.webhook_secret');
        $signature = hash_hmac('sha512', $timestamp . '.' . $body, $secret);
        $header = 't=' . $timestamp . ',v1=whk_test:' . $signature;

        return $this->call(
            'POST',
            route('webhooks.paystack'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_PAYSTACK_SIGNATURE' => $header,
            ],
            $body
        );
    }
}
