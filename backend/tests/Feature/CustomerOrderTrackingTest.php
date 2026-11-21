<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function createSupplierService(): Service
    {
        return Service::query()->create([
            'name' => 'Instagram Followers',
            'platform' => 'Instagram',
            'category' => 'Instagram Followers',
            'provider' => 'smm_center',
            'provider_service_id' => '27551',
            'description' => 'Supplier-backed service.',
            'price_per_1000' => '3500.00',
            'minimum_quantity' => 10,
            'maximum_quantity' => 100000,
            'is_active' => true,
        ]);
    }

    public function test_customer_sees_manual_review_explanation_for_pending_order(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $service = $this->createSupplierService();

        Order::query()->create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'target_url' => 'https://www.instagram.com/example/',
            'quantity' => 10,
            'unit_price' => '3500.00',
            'total_price' => '35.00',
            'status' => 'pending',
            'notes' => 'Paid from wallet. Awaiting manual review; not submitted to a provider.',
        ]);

        $this->actingAs($customer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Order placed')
            ->assertSee('Review')
            ->assertSee('Processing')
            ->assertSee('Your payment is recorded. This order is waiting for manual review');
    }

    public function test_customer_sees_supplier_quantity_progress_without_internal_supplier_id(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $service = $this->createSupplierService();

        Order::query()->create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'target_url' => 'https://www.instagram.com/example/',
            'quantity' => 10,
            'unit_price' => '3500.00',
            'total_price' => '35.00',
            'status' => 'processing',
            'provider_order_id' => '3557473',
            'provider_status' => 'In progress',
            'provider_remains' => 4,
        ]);

        $this->actingAs($customer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Supplier progress reported: 6 delivered · 4 remaining')
            ->assertDontSee('3557473')
            ->assertDontSee('In progress');
    }

    public function test_customer_sees_all_four_steps_completed_for_completed_order(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $service = $this->createSupplierService();

        Order::query()->create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'target_url' => 'https://www.instagram.com/example/',
            'quantity' => 10,
            'unit_price' => '3500.00',
            'total_price' => '35.00',
            'status' => 'completed',
            'provider_remains' => 0,
        ]);

        $response = $this->actingAs($customer)->get(route('orders.index'));

        $response->assertOk()
            ->assertSee('Your order has been marked as completed.')
            ->assertSee('Completed');

        $this->assertSame(4, substr_count($response->getContent(), 'bg-emerald-700 text-white'));
    }

}
