<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_report_uses_confirmed_payments_and_verified_fx_for_margin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $service = Service::query()->create([
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
        $order = Order::query()->create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'target_url' => 'https://www.instagram.com/example/',
            'quantity' => 10,
            'unit_price' => '3500.00',
            'total_price' => '35.00',
            'status' => 'completed',
            'provider_order_id' => '3557473',
            'provider_charge' => '0.020000',
            'provider_currency' => 'USD',
            'provider_fx_rate' => '1500.0000',
            'provider_fx_source' => 'Verified funding receipt',
            'provider_fx_captured_at' => now(),
        ]);
        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $customer->id],
            ['balance_kobo' => 0, 'currency' => 'NGN']
        );
        WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'user_id' => $customer->id,
            'type' => 'order_payment',
            'amount_kobo' => 3500,
            'currency' => 'NGN',
            'status' => 'completed',
            'reference' => 'ORDER-'.$order->id,
            'description' => 'Wallet payment',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.supplier', [
                'from' => now()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Supplier spending &amp; order profit', false)
            ->assertSee('USD 0.020000')
            ->assertSee('₦5.00')
            ->assertSee('Verified funding receipt');
    }

    public function test_customers_cannot_access_supplier_report(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);

        $this->actingAs($customer)
            ->get(route('admin.reports.supplier'))
            ->assertForbidden();
    }
}
