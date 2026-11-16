<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuidePageAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_guide_page(): void
    {
        $user = User::factory()->create();
        $business = Business::create(['name' => 'متجر الدليل', 'user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('guide.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Guide/Index'));
    }

    public function test_guest_is_redirected_to_login_when_accessing_guide(): void
    {
        $response = $this->get(route('guide.index'));

        $response->assertRedirect(route('login'));
    }
}
