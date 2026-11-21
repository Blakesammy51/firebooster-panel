<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierOrderRefundTest extends TestCase
{
    use RefreshDatabase;

    private function makeRefundableOrder(string $status = 'partial', string $providerStatus = 'Partial', ?int $remains = 40): Order
    {
        $user = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $service = Service::query()->create([
            'name' => 'Instagram Followers',
            'platform' => 'Instagram',
            'provider' => 'smm_center',
            'provider_service_id' => '32748',
            'description' => 'Supplier-backed service.',
            'price_per_1000' => '5.00',
            'minimum_quantity' => 50,
            'maximum_quantity' => 10000,
            'is_active' => true,
        ]);
        $order = Order::query()->create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'target_url' => 'https://www.instagram.com/example/',
            'quantity' => 200,
            'unit_price' => '5.00',
            'total_price' => '1.00',
            'status' => $status,
            'provider_order_id' => '7654321',
            'provider_status' => $providerStatus,
            'provider_remains' => $remains,
        ]);
        $wallet = Wallet::query()->create(['user_id' => $user->id, 'balance_kobo' => 500, 'currency' => 'NGN']);
        WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'type' => 'order_payment',
            'amount_kobo' => 100,
            'currency' => 'NGN',
            'status' => 'completed',
            'reference' => 'ORDER-' . $order->id,
            'description' => 'Wallet payment for order #' . $order->id,
        ]);
        return $order;
    }

    public function test_admin_can_issue_a_capped_partial_wallet_refund_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $order = $this->makeRefundableOrder();

        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order), ['refund_amount' => '0.40'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('wallets', ['user_id' => $order->user_id, 'balance_kobo' => 540]);
        $this->assertDatabaseHas('wallet_transactions', [
            'reference' => 'ORDER-' . $order->id . '-REFUND',
            'type' => 'order_refund',
            'amount_kobo' => 40,
            'currency' => 'NGN',
            'status' => 'completed',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order), ['refund_amount' => '0.40'])
            ->assertRedirect()
            ->assertSessionHasErrors('provider');

        $this->assertDatabaseHas('wallets', ['user_id' => $order->user_id, 'balance_kobo' => 540]);
        $this->assertDatabaseCount('wallet_transactions', 2);
    }

    public function test_partial_refund_cannot_exceed_original_payment(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $order = $this->makeRefundableOrder();

        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order), ['refund_amount' => '1.01'])
            ->assertRedirect()
            ->assertSessionHasErrors('provider');

        $this->assertDatabaseHas('wallets', ['user_id' => $order->user_id, 'balance_kobo' => 500]);
        $this->assertDatabaseMissing('wallet_transactions', ['reference' => 'ORDER-' . $order->id . '-REFUND']);
    }

    public function test_full_refund_requires_supplier_confirmation_that_all_units_remain(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $order = $this->makeRefundableOrder('cancelled', 'Canceled', 20);

        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order))
            ->assertRedirect()
            ->assertSessionHasErrors('provider');

        $this->assertDatabaseHas('wallets', ['user_id' => $order->user_id, 'balance_kobo' => 500]);
    }

    public function test_confirmed_full_cancellation_refunds_original_payment(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $order = $this->makeRefundableOrder('cancelled', 'Canceled', 200);

        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('wallets', ['user_id' => $order->user_id, 'balance_kobo' => 600]);
        $this->assertDatabaseHas('wallet_transactions', [
            'reference' => 'ORDER-' . $order->id . '-REFUND',
            'amount_kobo' => 100,
            'type' => 'order_refund',
        ]);
    }

    public function test_customers_cannot_issue_order_refunds(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $order = $this->makeRefundableOrder();

        $this->actingAs($customer)
            ->post(route('admin.orders.refund', $order), ['refund_amount' => '0.40'])
            ->assertForbidden();

        $this->assertDatabaseHas('wallets', ['user_id' => $order->user_id, 'balance_kobo' => 500]);
    }

    public function test_customer_sees_only_their_own_completed_order_refunds(): void
    {
        $order = $this->makeRefundableOrder();
        $customer = User::query()->findOrFail($order->user_id);
        $wallet = Wallet::query()->where('user_id', $customer->id)->firstOrFail();

        WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'user_id' => $customer->id,
            'type' => 'order_refund',
            'amount_kobo' => 40,
            'currency' => 'NGN',
            'status' => 'completed',
            'reference' => 'ORDER-' . $order->id . '-REFUND',
            'description' => 'Refund for order #' . $order->id,
        ]);

        $otherUser = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $otherWallet = Wallet::query()->create(['user_id' => $otherUser->id, 'balance_kobo' => 0, 'currency' => 'NGN']);
        WalletTransaction::query()->create([
            'wallet_id' => $otherWallet->id,
            'user_id' => $otherUser->id,
            'type' => 'order_refund',
            'amount_kobo' => 99,
            'currency' => 'NGN',
            'status' => 'completed',
            'reference' => 'ORDER-999-REFUND',
            'description' => 'Another customer refund',
        ]);

        $this->actingAs($customer)
            ->get(route('wallet.index'))
            ->assertOk()
            ->assertSee('Order refund history')
            ->assertSee('+₦0.40')
            ->assertSee('Refund for order #' . $order->id)
            ->assertDontSee('Another customer refund')
            ->assertDontSee('+₦0.99');
    }

    public function test_customer_order_history_shows_only_their_credited_refund(): void
    {
        $order = $this->makeRefundableOrder('partial', 'Partial', 40);
        $customer = User::query()->findOrFail($order->user_id);
        $wallet = Wallet::query()->where('user_id', $customer->id)->firstOrFail();

        WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'user_id' => $customer->id,
            'type' => 'order_refund',
            'amount_kobo' => 40,
            'currency' => 'NGN',
            'status' => 'completed',
            'reference' => 'ORDER-' . $order->id . '-REFUND',
            'description' => 'Wallet refund for order #' . $order->id,
        ]);

        $otherUser = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $otherService = Service::query()->create([
            'name' => 'Other Instagram Service',
            'platform' => 'Instagram',
            'description' => 'Another customer service.',
            'price_per_1000' => '5.00',
            'minimum_quantity' => 50,
            'maximum_quantity' => 10000,
            'is_active' => true,
        ]);
        $otherOrder = Order::query()->create([
            'user_id' => $otherUser->id,
            'service_id' => $otherService->id,
            'target_url' => 'https://www.instagram.com/other/',
            'quantity' => 100,
            'unit_price' => '5.00',
            'total_price' => '0.50',
            'status' => 'cancelled',
        ]);
        $otherWallet = Wallet::query()->create(['user_id' => $otherUser->id, 'balance_kobo' => 0, 'currency' => 'NGN']);
        WalletTransaction::query()->create([
            'wallet_id' => $otherWallet->id,
            'user_id' => $otherUser->id,
            'type' => 'order_refund',
            'amount_kobo' => 99,
            'currency' => 'NGN',
            'status' => 'completed',
            'reference' => 'ORDER-' . $otherOrder->id . '-REFUND',
            'description' => 'Another customer refund',
        ]);

        $this->actingAs($customer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Refund status')
            ->assertSee('Refund credited')
            ->assertSee('+₦0.40')
            ->assertDontSee('Another customer refund')
            ->assertDontSee('+₦0.99');
    }

}
