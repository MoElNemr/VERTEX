<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MakeSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_make_superadmin_command_creates_superadmin_with_options(): void
    {
        $this->artisan('make:superadmin', [
            '--name' => 'Root Administrator',
            '--email' => 'root@vertex.app',
            '--password' => 'secret123',
            '--no-interaction' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'name' => 'Root Administrator',
            'email' => 'root@vertex.app',
            'is_super_admin' => true,
        ]);

        $user = User::where('email', 'root@vertex.app')->first();
        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue(Hash::check('secret123', $user->password));

        $sub = $user->activeSubscription;
        $this->assertNotNull($sub);
        $this->assertEquals('active', $sub->status);
        $this->assertNull($sub->messages_limit); // unlimited quota
    }

    public function test_make_superadmin_command_promotes_existing_user(): void
    {
        $user = User::factory()->create([
            'email' => 'existing@vertex.app',
            'is_super_admin' => false,
        ]);

        $this->artisan('make:superadmin', [
            '--email' => 'existing@vertex.app',
            '--name' => 'Promoted Admin',
            '--password' => 'promoted123',
            '--no-interaction' => true,
        ])->assertSuccessful();

        $user->refresh();
        $this->assertTrue($user->isSuperAdmin());
        $this->assertEquals('Promoted Admin', $user->name);
    }
}
