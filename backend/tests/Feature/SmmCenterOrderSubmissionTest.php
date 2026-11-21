<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmmCenterOrderSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function makeSupplierOrder(): Order
    {
        $user = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $service = Service::query()->create([
            'name' => 'Instagram Followers',
            'platform' => 'Instagram',
            'category' => 'Instagram Followers',
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
            'status' => 'pending',
            'notes' => 'Paid from wallet.',
        ]);

        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance_kobo' => 0, 'currency' => 'NGN']
        );
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

    public function test_admin_can_submit_a_supplier_order_once(): void
    {
        config([
            'services.smm_center.api_key' => 'test-provider-key',
            'services.smm_center.base_url' => 'https://smm-center.com',
        ]);
        Http::fake([
            'smm-center.com/api/v2' => Http::response(['order' => 7654321]),
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $order = $this->makeSupplierOrder();

        $this->actingAs($admin)
            ->post(route('admin.orders.submit-to-provider', $order))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'provider_order_id' => '7654321',
            'provider_status' => 'Submitted',
            'status' => 'processing',
        ]);

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['action'] === 'add'
            && $request['service'] == 32748
            && $request['quantity'] == 200
            && $request['key'] === 'test-provider-key');
    }

    public function test_order_without_confirmed_wallet_payment_cannot_be_submitted(): void
    {
        config([
            'services.smm_center.api_key' => 'test-provider-key',
            'services.smm_center.base_url' => 'https://smm-center.com',
        ]);
        Http::fake();

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $order = $this->makeSupplierOrder();
        WalletTransaction::query()->where('reference', 'ORDER-' . $order->id)->delete();

        $this->actingAs($admin)
            ->post(route('admin.orders.submit-to-provider', $order))
            ->assertRedirect()
            ->assertSessionHasErrors('provider');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'provider_order_id' => null,
            'provider_status' => null,
        ]);
        Http::assertNothingSent();
    }

    public function test_already_claimed_order_cannot_be_submitted_again(): void
    {
        config([
            'services.smm_center.api_key' => 'test-provider-key',
            'services.smm_center.base_url' => 'https://smm-center.com',
        ]);
        Http::fake();

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $order = $this->makeSupplierOrder();
        $order->update(['provider_status' => 'submission_in_progress']);

        $this->actingAs($admin)
            ->post(route('admin.orders.submit-to-provider', $order))
            ->assertRedirect()
            ->assertSessionHasErrors('provider');

        Http::assertNothingSent();
    }

    public function test_admin_can_refresh_supplier_status_and_update_order_progress(): void
    {
        config([
            'services.smm_center.api_key' => 'test-provider-key',
            'services.smm_center.base_url' => 'https://smm-center.com',
        ]);
        Http::fake([
            'smm-center.com/api/v2' => Http::response([
                'charge' => '0.18',
                'start_count' => '1200',
                'status' => 'Completed',
                'remains' => '0',
                'currency' => 'USD',
            ]),
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $order = $this->makeSupplierOrder();
        $order->update([
            'provider_order_id' => '7654321',
            'provider_status' => 'In progress',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.orders.refresh-provider-status', $order))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'provider_order_id' => '7654321',
            'provider_status' => 'Completed',
            'provider_charge' => '0.180000',
            'provider_currency' => 'USD',
            'provider_start_count' => 1200,
            'provider_remains' => 0,
            'status' => 'completed',
        ]);

        Http::assertSent(fn ($request) => $request['action'] === 'status'
            && $request['order'] == 7654321);
    }

    public function test_customers_cannot_refresh_supplier_status(): void
    {
        Http::fake();
        $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $order = $this->makeSupplierOrder();
        $order->update(['provider_order_id' => '7654321']);

        $this->actingAs($customer)
            ->post(route('admin.orders.refresh-provider-status', $order))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_customers_cannot_submit_orders_to_the_provider(): void
    {
        Http::fake();
        $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $order = $this->makeSupplierOrder();

        $this->actingAs($customer)
            ->post(route('admin.orders.submit-to-provider', $order))
            ->assertForbidden();

        Http::assertNothingSent();
    }
}
