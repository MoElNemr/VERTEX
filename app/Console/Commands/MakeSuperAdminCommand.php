<?php

namespace App\Console\Commands;

use App\Models\PlatformAdmin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeSuperAdminCommand extends Command
{
    protected $signature = 'make:superadmin
                            {--name= : Full name of the platform admin}
                            {--email= : Email address}
                            {--password= : Password (min 8 chars)}
                            {--role=owner : Role: owner|operations|finance|support}';

    protected $description = 'Bootstrap a platform admin account in the dedicated platform_admins table';

    public function handle(): int
    {
        $this->info('================================================');
        $this->info('  VERTEX PLATFORM — PLATFORM ADMIN BOOTSTRAP  ');
        $this->info('================================================');

        $name     = $this->option('name')     ?: $this->ask('Full name', 'Platform Owner');
        $email    = $this->option('email')    ?: $this->ask('Email', 'admin@vertex.app');
        $role     = $this->option('role')     ?: $this->choice('Role', PlatformAdmin::ROLES, 'owner');
        $password = $this->option('password') ?: $this->secret('Password (min 8 chars)') ?: 'password123';

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');
            return self::FAILURE;
        }

        // Prevent creating a second owner via CLI without explicit confirmation
        if ($role === 'owner' && PlatformAdmin::where('role', 'owner')->exists()) {
            if (! $this->confirm('An owner already exists. Create another owner?', false)) {
                $this->warn('Aborted. Existing owner unchanged.');
                return self::SUCCESS;
            }
        }

        $admin = PlatformAdmin::updateOrCreate(
            ['email' => $email],
            [
                'name'               => $name,
                'password'           => Hash::make($password),
                'role'               => $role,
                'is_active'          => true,
                'email_verified_at'  => now(),
            ]
        );

        $this->newLine();
        $this->table(
            ['Field', 'Value'],
            [
                ['ID',        $admin->id],
                ['Name',      $admin->name],
                ['Email',     $admin->email],
                ['Role',      $admin->role],
                ['Admin URL', url('/admin/login')],
            ]
        );

        $this->info('Platform admin ready. Login at /admin/login');
        return self::SUCCESS;
    }
}
