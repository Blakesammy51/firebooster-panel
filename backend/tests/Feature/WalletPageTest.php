<?php

namespace Tests\\Feature;

use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

class WalletPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_customer_can_view_an_empty_wallet(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('wallet.index'));

        $response->assertOk();
        $response->assertSee('My Wallet');
        $response->assertSee('₦0.00');
        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance_kobo' => 0,
            'currency' => 'NGN',
        ]);
    }

    public function test_unverified_customer_cannot_view_wallet(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('wallet.index'));

        $response->assertRedirect(route('verification.notice'));
    }
}
