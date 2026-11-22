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

    public function test_admin_can_save_verified_usd_to_ngn_snapshot_and_view_gross_margin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $order = $this->makeSupplierOrder();
        $order->update([
            'provider_order_id' => '7654321',
            'provider_charge' => '0.180000',
            'provider_currency' => 'USD',
            'total_price' => '500.00',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), [
                'status' => 'completed',
                'notes' => 'Paid from wallet.',
                'provider_fx_rate' => '1500.0000',
                'provider_fx_source' => 'SMM Center top-up receipt',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('1500.0000', $order->provider_fx_rate);
        $this->assertSame('SMM Center top-up receipt', $order->provider_fx_source);
        $this->assertNotNull($order->provider_fx_captured_at);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('Supplier cost estimate: ₦270.00')
            ->assertSee('Gross margin before payment fees and other costs: ₦230.00')
            ->assertSee('SMM Center top-up receipt');
    }

    public function test_fx_snapshot_requires_a_source(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $order = $this->makeSupplierOrder();
        $order->update([
            'provider_order_id' => '7654321',
            'provider_charge' => '0.180000',
            'provider_currency' => 'USD',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), [
                'status' => 'completed',
                'provider_fx_rate' => '1500.0000',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('provider_fx_source');

        $this->assertNull($order->fresh()->provider_fx_rate);
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
            ->assertSee('save a verified USD-to-NGN rate and its source below');
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


    private function placeFundedCustomerOrder(string $provider = 'smm_center'): array
    {
        $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $service = Service::query()->create([
            'name' => 'Test service',
            'platform' => 'Instagram',
            'category' => 'Followers',
            'provider' => $provider,
            'provider_service_id' => $provider === 'smm_center' ? '32748' : null,
            'description' => 'Test service.',
            'price_per_1000' => '5.00',
            'minimum_quantity' => 50,
            'maximum_quantity' => 10000,
            'is_active' => true,
        ]);
        $wallet = Wallet::query()->create([
            'user_id' => $customer->id,
            'balance_kobo' => 1000,
            'currency' => 'NGN',
        ]);

        return [$customer, $service, $wallet];
    }

    public function test_paid_customer_order_is_automatically_submitted_to_smm_center(): void
    {
        config([
            'services.smm_center.api_key' => 'test-provider-key',
            'services.smm_center.base_url' => 'https://smm-center.com',
        ]);
        Http::fake(['smm-center.com/api/v2' => Http::response(['order' => 7654321])]);
        [$customer, $service, $wallet] = $this->placeFundedCustomerOrder();

        $this->actingAs($customer)
            ->post(route('orders.store'), [
                'service_id' => $service->id,
                'target_url' => 'https://www.instagram.com/example/',
                'quantity' => 200,
            ])
            ->assertRedirect(route('orders.index'))
            ->assertSessionHas('status', fn ($message) => str_contains($message, 'automatically submitted'));

        $order = Order::query()->where('user_id', $customer->id)->firstOrFail();
        $this->assertSame('7654321', $order->provider_order_id);
        $this->assertSame('Submitted', $order->provider_status);
        $this->assertSame('processing', $order->status);
        $this->assertSame(900, $wallet->fresh()->balance_kobo);
        $this->assertDatabaseHas('wallet_transactions', [
            'reference' => 'ORDER-' . $order->id,
            'type' => 'order_payment',
            'status' => 'completed',
        ]);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['action'] === 'add'
            && $request['service'] == 32748
            && $request['quantity'] == 200);
    }

    public function test_non_smm_center_customer_order_keeps_existing_manual_fulfilment_flow(): void
    {
        Http::fake();
        [$customer, $service] = $this->placeFundedCustomerOrder('manual');

        $this->actingAs($customer)
            ->post(route('orders.store'), [
                'service_id' => $service->id,
                'target_url' => 'https://www.instagram.com/example/',
                'quantity' => 200,
            ])
            ->assertRedirect(route('orders.index'))
            ->assertSessionHas('status');

        $order = Order::query()->where('user_id', $customer->id)->firstOrFail();
        $this->assertNull($order->provider_order_id);
        $this->assertNull($order->provider_status);
        $this->assertSame('pending', $order->status);
        Http::assertNothingSent();
    }

    public function test_uncertain_supplier_response_locks_paid_order_for_manual_review(): void
    {
        config([
            'services.smm_center.api_key' => 'test-provider-key',
            'services.smm_center.base_url' => 'https://smm-center.com',
        ]);
        Http::fake(['smm-center.com/api/v2' => Http::response(['error' => 'Temporary supplier error'], 500)]);
        [$customer, $service] = $this->placeFundedCustomerOrder();

        $this->actingAs($customer)
            ->post(route('orders.store'), [
                'service_id' => $service->id,
                'target_url' => 'https://www.instagram.com/example/',
                'quantity' => 200,
            ])
            ->assertRedirect(route('orders.index'))
            ->assertSessionHas('status', fn ($message) => str_contains($message, 'manual review'));

        $order = Order::query()->where('user_id', $customer->id)->firstOrFail();
        $this->assertNull($order->provider_order_id);
        $this->assertSame('submission_failed_review_required', $order->provider_status);
        $this->assertSame('pending', $order->status);
        Http::assertSentCount(1);

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $this->actingAs($admin)
            ->post(route('admin.orders.submit-to-provider', $order))
            ->assertRedirect()
            ->assertSessionHasErrors('provider');
        Http::assertSentCount(1);
    }

}
