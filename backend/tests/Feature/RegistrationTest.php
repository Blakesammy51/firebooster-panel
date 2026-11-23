<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
class RegistrationTest extends TestCase
{
    use RefreshDatabase;
    public function test_new_registration_creates_customer_and_requires_email_verification(): void
    {
        Notification::fake();
        $response = $this->post('/register', [
            'name' => 'Demo Customer',
            'email' => 'customer@example.test',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);
        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', [
            'email' => 'customer@example.test',
            'role' => 'customer',
            'email_verified_at' => null,
        ]);
    }

    public function test_registration_is_rate_limited_after_five_attempts_per_minute(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/register', [])->assertSessionHasErrors();
        }

        $this->post('/register', [])->assertTooManyRequests();
    }
}
