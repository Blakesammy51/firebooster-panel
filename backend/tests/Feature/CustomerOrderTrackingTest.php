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

        // The responsive view renders both mobile and desktop layouts; each has four completed steps.
        $this->assertSame(8, substr_count($response->getContent(), 'bg-emerald-700 text-white'));
    }


    public function test_customer_must_select_a_service_before_the_single_order_form_is_shown(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $this->createSupplierService();

        Service::query()->create([
            'name' => 'Instagram Likes',
            'platform' => 'Instagram',
            'category' => 'Likes',
            'provider' => 'smm_center',
            'provider_service_id' => '27552',
            'description' => 'Supplier-backed likes service.',
            'price_per_1000' => '1200.00',
            'minimum_quantity' => 10,
            'maximum_quantity' => 50000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($customer)->get(route('services.index'));

        $response->assertOk()
            ->assertSee('Select service')
            ->assertSee('data-price-per-1000="3500.00"', false)
            ->assertSee('id="selected-order-panel"', false)
            ->assertSee('id="selected-order-form"', false)
            ->assertDontSee('id="order-form-', false);

        $this->assertSame(2, substr_count($response->getContent(), 'class="select-service-button'));
        $this->assertSame(2, substr_count($response->getContent(), '<form'));
    }

    public function test_customer_catalogue_uses_a_category_dropdown_instead_of_category_chips(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $this->createSupplierService();

        Service::query()->create([
            'name' => 'Facebook Video Views',
            'platform' => 'Facebook',
            'category' => 'Facebook Video Views',
            'provider' => 'smm_center',
            'provider_service_id' => '29014',
            'description' => 'Supplier-backed Facebook views.',
            'price_per_1000' => '120.00',
            'minimum_quantity' => 500,
            'maximum_quantity' => 1000000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($customer)->get(route('services.index'));

        $response->assertOk()
            ->assertSee('Browse category')
            ->assertSee('id="category-picker-trigger"', false)
            ->assertSee('id="category-picker-panel"', false)
            ->assertSee('id="category-picker-search"', false)
            ->assertSee('data-category-name="All categories"', false)
            ->assertSee('data-category-name="Instagram Followers"', false)
            ->assertSee('data-category-name="Facebook Video Views"', false)
            ->assertSee('data-category-platform="Instagram"', false)
            ->assertSee('data-category-platform="Facebook"', false)
            ->assertDontSee('id="catalogue-category"', false);

        $facebookResponse = $this->actingAs($customer)->get(route('services.index', ['platform' => 'Facebook']));
        $facebookResponse->assertOk()
            ->assertSee('data-category-name="Facebook Video Views"', false)
            ->assertDontSee('data-category-name="Instagram Followers"', false);
    }

}
