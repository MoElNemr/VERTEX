<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeSuperAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:superadmin 
                            {--name= : Name of the super admin}
                            {--email= : Email of the super admin}
                            {--password= : Password for the super admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or promote a user to System Super Admin with full master access';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==================================================');
        $this->info('   VERTEX PLATFORM - SYSTEM SUPERADMIN CREATION   ');
        $this->info('==================================================');

        $name = $this->option('name') ?: ($this->input->isInteractive() ? $this->ask('Enter Super Admin Full Name', 'System Superadmin') : 'System Superadmin');
        $email = $this->option('email') ?: ($this->input->isInteractive() ? $this->ask('Enter Super Admin Email', 'admin@vertex.app') : 'admin@vertex.app');
        $password = $this->option('password');

        if (! $password) {
            $password = $this->input->isInteractive()
                ? ($this->secret('Enter Password (minimum 6 characters, or press enter for "password123")') ?: 'password123')
                : 'password123';
        }

        if (strlen($password) < 6) {
            $this->error('Password must be at least 6 characters.');

            return self::FAILURE;
        }

        // Find or create user
        $user = User::withoutGlobalScopes()->where('email', $email)->first();

        if ($user) {
            $user->update([
                'name' => $name,
                'password' => Hash::make($password),
                'is_super_admin' => true,
                'email_verified_at' => $user->email_verified_at ?: now(),
            ]);
            $this->info("Updated existing user [{$email}] to Super Admin.");
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'is_super_admin' => true,
                'email_verified_at' => now(),
            ]);
            $this->info("Created new Super Admin user [{$email}].");
        }

        // Ensure default Pricing Plans exist in the database if empty
        $premiumPlan = Plan::firstOrCreate(
            ['type' => 'premium'],
            [
                'name' => 'Enterprise Unlimited',
                'max_businesses' => null,
                'monthly_message_quota' => null,
                'price_monthly' => 99.00,
                'price_yearly' => 990.00,
                'is_active' => true,
            ]
        );

        Plan::firstOrCreate(
            ['type' => 'standard'],
            [
                'name' => 'Professional Growth',
                'max_businesses' => null,
                'monthly_message_quota' => 3000,
                'price_monthly' => 29.00,
                'price_yearly' => 290.00,
                'is_active' => true,
            ]
        );

        // Ensure Super Admin has an active subscription
        Subscription::firstOrCreate(
            ['user_id' => $user->id, 'plan_id' => $premiumPlan->id],
            [
                'status' => 'active',
                'messages_limit' => null,
                'messages_used' => 0,
                'quota_resets_at' => now()->addMonth(),
                'starts_at' => now(),
                'ends_at' => now()->addYears(10),
            ]
        );

        $this->newLine();
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $user->id],
                ['Name', $user->name],
                ['Email', $user->email],
                ['Role', 'Super Admin (Master Control)'],
                ['Plan', 'Enterprise Unlimited (10 Years)'],
                ['Admin URL', url('/admin/login')],
            ]
        );

        $this->info('Super Admin created and configured successfully!');

        return self::SUCCESS;
    }
}
