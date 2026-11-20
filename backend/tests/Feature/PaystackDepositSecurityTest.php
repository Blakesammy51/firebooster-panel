<?php

namespace Tests\Feature;

use App\Models\User;
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
}
