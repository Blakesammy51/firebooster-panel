<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmmCenterScheduledStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_updates_open_supplier_orders_in_one_batch_and_leaves_terminal_orders_alone(): void
    {
        config([
            'services.smm_center.api_key' => 'test-provider-key',
            'services.smm_center.base_url' => 'https://smm-center.com',
        ]);

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

        $makeOrder = function (string $providerId, string $status = 'processing') use ($user, $service): Order {
            return Order::query()->create([
                'user_id' => $user->id,
                'service_id' => $service->id,
                'target_url' => 'https://www.instagram.com/example/',
                'quantity' => 100,
                'unit_price' => '5.00',
                'total_price' => '0.50',
                'status' => $status,
                'provider_order_id' => $providerId,
                'provider_status' => 'Submitted',
            ]);
        };

        $completed = $makeOrder('7654321');
        $inProgress = $makeOrder('7654322');
        $alreadyFinal = $makeOrder('7654323', 'completed');

        Http::fake([
            'smm-center.com/api/v2' => Http::response([
                '7654321' => [
                    'charge' => '0.90',
                    'start_count' => '120',
                    'status' => 'Completed',
                    'remains' => '0',
                    'currency' => 'USD',
                ],
                '7654322' => [
                    'charge' => '0.45',
                    'start_count' => '50',
                    'status' => 'In progress',
                    'remains' => '40',
                    'currency' => 'USD',
                ],
            ]),
        ]);

        $this->artisan('smm-center:sync-order-statuses')
            ->expectsOutputToContain('2 updated, 0 skipped, 0 failed batches')
            ->assertSuccessful();

        $this->assertDatabaseHas('orders', [
            'id' => $completed->id,
            'status' => 'completed',
            'provider_status' => 'Completed',
            'provider_charge' => '0.900000',
            'provider_currency' => 'USD',
            'provider_remains' => 0,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $inProgress->id,
            'status' => 'processing',
            'provider_status' => 'In progress',
            'provider_remains' => 40,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $alreadyFinal->id,
            'status' => 'completed',
            'provider_status' => 'Submitted',
        ]);

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['action'] === 'status'
            && $request['orders'] === '7654321,7654322');
    }

    public function test_command_does_not_change_an_order_when_supplier_omits_its_status(): void
    {
        config([
            'services.smm_center.api_key' => 'test-provider-key',
            'services.smm_center.base_url' => 'https://smm-center.com',
        ]);

        $user = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $service = Service::query()->create([
            'name' => 'Instagram Likes',
            'platform' => 'Instagram',
            'provider' => 'smm_center',
            'provider_service_id' => '32749',
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
            'quantity' => 100,
            'unit_price' => '5.00',
            'total_price' => '0.50',
            'status' => 'processing',
            'provider_order_id' => '7654324',
            'provider_status' => 'Submitted',
        ]);

        Http::fake(['smm-center.com/api/v2' => Http::response([])]);

        $this->artisan('smm-center:sync-order-statuses')
            ->expectsOutputToContain('0 updated, 0 skipped, 1 failed batches')
            ->assertSuccessful();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
            'provider_status' => 'Submitted',
        ]);
    }
}
