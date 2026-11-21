<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletFundedOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_place_order_and_wallet_is_debited_once(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'balance_kobo' => 5000,
            'currency' => 'NGN',
        ]);
        $service = $this->createService();

        $response = $this->actingAs($user)->post(route('orders.store'), [
            'service_id' => $service->id,
            'target_url' => 'https://example.com/profile',
            'quantity' => 100,
        ]);

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance_kobo' => 2500,
        ]);
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'service_id' => $service->id,
            'quantity' => 100,
            'total_price' => '25.00',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'type' => 'order_payment',
            'amount_kobo' => 2500,
            'status' => 'completed',
            'reference' => 'ORDER-1',
        ]);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('wallet_transactions', 1);
    }

    public function test_insufficient_wallet_balance_does_not_create_order_or_debit_wallet(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'balance_kobo' => 1000,
            'currency' => 'NGN',
        ]);
        $service = $this->createService();

        $response = $this->actingAs($user)->from(route('services.index'))->post(route('orders.store'), [
            'service_id' => $service->id,
            'target_url' => 'https://example.com/profile',
            'quantity' => 100,
        ]);

        $response->assertRedirect(route('services.index'));
        $response->assertSessionHasErrors('wallet');
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance_kobo' => 1000,
        ]);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_inactive_service_cannot_be_purchased(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'balance_kobo' => 5000,
            'currency' => 'NGN',
        ]);
        $service = $this->createService(['is_active' => false]);

        $response = $this->actingAs($user)->from(route('services.index'))->post(route('orders.store'), [
            'service_id' => $service->id,
            'target_url' => 'https://example.com/profile',
            'quantity' => 100,
        ]);

        $response->assertRedirect(route('services.index'));
        $response->assertSessionHasErrors('service_id');
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance_kobo' => 5000,
        ]);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    private function createService(array $overrides = []): Service
    {
        return Service::query()->create(array_merge([
            'name' => 'Instagram Followers',
            'platform' => 'Instagram',
            'description' => 'Test service',
            'price_per_1000' => '250.00',
            'minimum_quantity' => 50,
            'maximum_quantity' => 10000,
            'is_active' => true,
        ], $overrides));
    }
}
