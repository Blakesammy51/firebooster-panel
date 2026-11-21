<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmmCenterServiceSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_smm_center_services_without_publishing_or_pricing_them(): void
    {
        config([
            'services.smm_center.api_key' => 'test-provider-key',
            'services.smm_center.base_url' => 'https://smm-center.com',
        ]);

        Http::fake([
            'smm-center.com/api/v2' => Http::sequence()
                ->push(['balance' => '12.50', 'currency' => 'USD'])
                ->push([
                    [
                        'service' => 32748,
                        'name' => 'Instagram Followers',
                        'type' => 'Default',
                        'category' => 'Instagram Cheapest Services',
                        'rate' => '0.90',
                        'min' => '50',
                        'max' => '10000',
                        'refill' => false,
                        'cancel' => true,
                    ],
                ]),
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);

        $response = $this->actingAs($admin)->post(route('admin.services.smm-center.sync'));

        $response->assertRedirect();
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('services', [
            'provider' => 'smm_center',
            'provider_service_id' => '32748',
            'name' => 'Instagram Followers',
            'category' => 'Instagram Cheapest Services',
            'provider_rate' => '0.900000',
            'provider_currency' => 'USD',
            'minimum_quantity' => 50,
            'maximum_quantity' => 10000,
            'price_per_1000' => '0.00',
            'is_active' => false,
        ]);

        $service = Service::query()->where('provider_service_id', '32748')->firstOrFail();
        $this->assertFalse($service->is_active);
        $this->assertSame('0.00', $service->price_per_1000);
        Http::assertSentCount(2);
    }

    public function test_admin_cannot_activate_supplier_service_with_zero_selling_price(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $service = Service::query()->create([
            'name' => 'Imported Instagram Followers',
            'platform' => 'Instagram',
            'category' => 'Instagram Followers',
            'provider' => 'smm_center',
            'provider_service_id' => '98765',
            'description' => 'Imported supplier service.',
            'price_per_1000' => '0.00',
            'minimum_quantity' => 50,
            'maximum_quantity' => 10000,
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.services.update', $service), [
                'name' => $service->name,
                'platform' => $service->platform,
                'description' => $service->description,
                'price_per_1000' => '0.00',
                'minimum_quantity' => 50,
                'maximum_quantity' => 10000,
                'is_active' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('price_per_1000');

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'price_per_1000' => '0.00',
            'is_active' => false,
        ]);
    }

    public function test_customers_cannot_sync_supplier_services(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);

        $this->actingAs($customer)
            ->post(route('admin.services.smm-center.sync'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_admin_can_search_services_by_name_category_platform_and_supplier_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);

        Service::query()->create([
            'name' => 'Instagram Followers Fast',
            'platform' => 'Instagram',
            'category' => 'Instagram Cheapest Services',
            'provider' => 'smm_center',
            'provider_service_id' => '32748',
            'description' => 'Imported Instagram service.',
            'price_per_1000' => '0.00',
            'minimum_quantity' => 50,
            'maximum_quantity' => 10000,
            'is_active' => false,
        ]);
        Service::query()->create([
            'name' => 'TikTok Likes',
            'platform' => 'TikTok',
            'description' => 'TikTok service.',
            'price_per_1000' => '10.00',
            'minimum_quantity' => 1,
            'maximum_quantity' => 1000,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.services.index', ['search' => 'Instagram']))
            ->assertOk()
            ->assertSee('Instagram Followers Fast')
            ->assertDontSee('TikTok Likes')
            ->assertSee('Search catalogue');

        $this->actingAs($admin)
            ->get(route('admin.services.index', ['search' => '32748']))
            ->assertOk()
            ->assertSee('Instagram Followers Fast')
            ->assertDontSee('TikTok Likes');
    }

    public function test_customers_cannot_search_admin_service_catalogue(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);

        $this->actingAs($customer)
            ->get(route('admin.services.index', ['search' => 'Instagram']))
            ->assertForbidden();
    }

    public function test_admin_can_filter_services_by_active_status_and_see_counts(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);

        Service::query()->create([
            'name' => 'Published Instagram Followers',
            'platform' => 'Instagram',
            'description' => 'Visible service.',
            'price_per_1000' => '3500.00',
            'minimum_quantity' => 10,
            'maximum_quantity' => 100000,
            'is_active' => true,
        ]);
        Service::query()->create([
            'name' => 'Old Instagram Test Service',
            'platform' => 'Instagram',
            'description' => 'Hidden test service.',
            'price_per_1000' => '5.00',
            'minimum_quantity' => 100,
            'maximum_quantity' => 10000,
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.services.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee('Published Instagram Followers')
            ->assertDontSee('Old Instagram Test Service')
            ->assertSee('Active services')
            ->assertSee('Inactive services');

        $this->actingAs($admin)
            ->get(route('admin.services.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee('Old Instagram Test Service')
            ->assertDontSee('Published Instagram Followers');
    }

    public function test_admin_service_status_filter_preserves_search_term(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        Service::query()->create([
            'name' => 'Instagram Active Followers',
            'platform' => 'Instagram',
            'description' => 'Visible service.',
            'price_per_1000' => '3500.00',
            'minimum_quantity' => 10,
            'maximum_quantity' => 100000,
            'is_active' => true,
        ]);
        Service::query()->create([
            'name' => 'Instagram Hidden Test',
            'platform' => 'Instagram',
            'description' => 'Hidden service.',
            'price_per_1000' => '5.00',
            'minimum_quantity' => 100,
            'maximum_quantity' => 10000,
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.services.index', ['status' => 'inactive', 'search' => 'Instagram']))
            ->assertOk()
            ->assertSee('Instagram Hidden Test')
            ->assertDontSee('Instagram Active Followers');
    }


}
