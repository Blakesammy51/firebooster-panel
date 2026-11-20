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

        $this->actingAs($customer)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_open_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Admin dashboard')
            ->assertSee('Total accounts');
    }
}
