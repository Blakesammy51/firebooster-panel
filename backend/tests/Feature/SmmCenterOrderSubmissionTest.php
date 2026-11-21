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

        $submittedOrder = $order->fresh();
        $this->assertStringContainsString('Paid from wallet.', $submittedOrder->notes);
        $this->assertStringContainsString('Submitted to SMM Center. Provider order ID: 7654321', $submittedOrder->notes);
        $this->assertStringNotContainsString('not submitted to a provider', $submittedOrder->notes);

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

    public function test_partial_supplier_status_is_preserved_as_partial_locally(): void
    {
        config([
            'services.smm_center.api_key' => 'test-provider-key',
            'services.smm_center.base_url' => 'https://smm-center.com',
        ]);
        Http::fake([
            'smm-center.com/api/v2' => Http::response([
                'charge' => '0.12',
                'start_count' => '1200',
                'status' => 'Partial',
                'remains' => '40',
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
            'provider_status' => 'Partial',
            'provider_remains' => 40,
            'status' => 'partial',
        ]);
    }

    public function test_failed_supplier_status_maps_to_failed_locally(): void
    {
        config([
            'services.smm_center.api_key' => 'test-provider-key',
            'services.smm_center.base_url' => 'https://smm-center.com',
        ]);
        Http::fake([
            'smm-center.com/api/v2' => Http::response([
                'status' => 'Failed',
                'remains' => '200',
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
            'provider_status' => 'Failed',
            'status' => 'failed',
        ]);
    }

    public function test_admin_can_manually_set_partial_order_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $order = $this->makeSupplierOrder();

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), [
                'status' => 'partial',
                'notes' => 'Supplier fulfilled part of the quantity.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'partial',
        ]);
    }

    public function test_admin_order_page_hides_legacy_manual_review_note_after_supplier_submission(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $order = $this->makeSupplierOrder();
        $order->update([
            'provider_order_id' => '7654321',
            'provider_status' => 'Completed',
            'status' => 'completed',
            'notes' => "Paid from wallet. Awaiting manual review; not submitted to a provider.\nSubmitted to SMM Center. Provider order ID: 7654321",
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('Submitted to SMM Center. Provider order ID: 7654321')
            ->assertDontSee('not submitted to a provider');
    }

    public function test_admin_order_page_does_not_compare_usd_supplier_cost_to_ngn_sale(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $order = $this->makeSupplierOrder();
        $order->update([
            'provider_order_id' => '7654321',
            'provider_charge' => '0.180000',
            'provider_currency' => 'USD',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('0.180000 USD')
            ->assertSee('Margin not calculated')
            ->assertSee('verified exchange-rate snapshot is required');
    }

    public function test_admin_order_page_shows_gross_difference_only_for_ngn_supplier_cost(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $order = $this->makeSupplierOrder();
        $order->update([
            'provider_order_id' => '7654321',
            'provider_charge' => '0.40',
            'provider_currency' => 'NGN',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('Gross difference before payment fees and other costs: ₦0.60');
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
