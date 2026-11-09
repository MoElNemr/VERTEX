<?php

namespace Tests\Feature\Auth;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $plan = Plan::create([
            'name' => 'Standard Unlimited',
            'type' => 'standard',
            'price_monthly' => 0.00,
            'price_yearly' => 0.00,
            'is_active' => true,
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('businesses.index', absolute: false));
    }
}
