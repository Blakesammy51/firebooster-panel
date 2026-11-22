<?php

namespace Tests\Feature;

use App\Models\SmmCenterBalanceCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

class SmmCenterBalanceMonitorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.smm_center.api_key' => 'test-provider-key',
            'services.smm_center.base_url' => 'https://smm-center.com',
        ]);
    }

    public function test_scheduled_command_persists_a_valid_balance_and_detects_low_funds(): void
    {
        Http::fake(['smm-center.com/api/v2' => Http::response([
            'balance' => '1.9483',
            'currency' => 'USD',
        ])]);

        Artisan::call('smm-center:check-balance');

        $this->assertDatabaseHas('smm_center_balance_checks', [
            'amount' => '1.94830000',
            'currency' => 'USD',
            'status' => 'success',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('USD 1.9483')
            ->assertSee('Low balance warning');
    }

    public function test_failed_check_is_saved_without_overwriting_last_successful_balance(): void
    {
        SmmCenterBalanceCheck::query()->create([
            'amount' => '8.00000000',
            'currency' => 'USD',
            'status' => 'success',
            'checked_at' => now()->subHour(),
        ]);

        Http::fake(['smm-center.com/api/v2' => Http::response(['unexpected' => 'payload'])]);

        $this->artisan('smm-center:check-balance')->assertExitCode(1);

        $this->assertDatabaseCount('smm_center_balance_checks', 2);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('USD 8.0000')
            ->assertSee('Latest monitor attempt: failed');
    }

    public function test_manual_refresh_uses_the_same_persistent_monitor_record(): void
    {
        Http::fake(['smm-center.com/api/v2' => Http::response([
            'balance' => '12.50',
            'currency' => 'USD',
        ])]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->post(route('admin.dashboard.supplier-balance.refresh'))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('smm_center_balance_checks', [
            'amount' => '12.50000000',
            'currency' => 'USD',
            'status' => 'success',
        ]);
    }
}
