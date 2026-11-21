<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_customers_cannot_open_admin_dashboard(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_can_open_admin_dashboard_with_current_system_information(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Admin dashboard')
            ->assertSee('Total accounts')
            ->assertSee('Quick actions')
            ->assertSee('Paystack')
            ->assertSee('wallet')
            ->assertSee('SMM Center fulfilment')
            ->assertSee('Refund controls')
            ->assertDontSee('No payment is collected')
            ->assertDontSee('Do not accept customer funds yet');
    }
    public function test_admin_can_refresh_supplier_balance_and_sees_low_balance_warning(): void
    {
        config([
            'services.smm_center.api_key' => 'test-provider-key',
            'services.smm_center.base_url' => 'https://smm-center.com',
        ]);

        Http::fake([
            'smm-center.com/api/v2' => Http::response([
                'balance' => '4.25',
                'currency' => 'USD',
            ]),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.dashboard.supplier-balance.refresh'))
            ->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('USD 4.2500')
            ->assertSee('Low balance warning')
            ->assertSee('Read-only monitor');

        Http::assertSent(fn ($request) => $request['action'] === 'balance'
            && $request['key'] === 'test-provider-key');
    }

    public function test_supplier_balance_refresh_handles_unexpected_provider_response_safely(): void
    {
        config([
            'services.smm_center.api_key' => 'test-provider-key',
            'services.smm_center.base_url' => 'https://smm-center.com',
        ]);

        Http::fake([
            'smm-center.com/api/v2' => Http::response(['unexpected' => 'response']),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.dashboard'))
            ->post(route('admin.dashboard.supplier-balance.refresh'))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHasErrors('supplier_balance');

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Could not retrieve the SMM Center balance.')
            ->assertDontSee('Current supplier balance');
    }

}
