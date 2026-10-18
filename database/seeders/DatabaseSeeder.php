<?php

namespace Database\Seeders;

use App\Models\BulkMessageWallet;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Plan;
use App\Models\PlatformAdmin;
use App\Models\QuickReply;
use App\Models\Subscription;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Design rules (per VERTEX_SYSTEM_PLAN.md §14):
     *  - PlatformAdmin seeds go to platform_admins table via admin guard.
     *  - No User gets is_super_admin=true in this seeder.
     *  - Customer users own businesses; platform admins do not.
     */
    public function run(): void
    {
        // ── 1. Platform Admin ─────────────────────────────────────────────────
        PlatformAdmin::firstOrCreate(
            ['email' => 'admin@vertex.app'],
            [
                'name'              => 'Mohamed Nemr',
                'password'          => Hash::make('password123'),
                'role'              => 'owner',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        // ── 2. Subscription Plans ─────────────────────────────────────────────
        $starterPlan = Plan::firstOrCreate(
            ['type' => 'starter'],
            [
                'name'           => 'Starter',
                'max_businesses' => 1,
                'price_monthly'  => 0.00,
                'price_yearly'   => 0.00,
                'is_active'      => true,
                'description'    => 'Shared inbox, core channels, basic reports.',
                'features'       => ['inbox', 'channels', 'basic_reports'],
            ]
        );

        $growthPlan = Plan::firstOrCreate(
            ['type' => 'growth'],
            [
                'name'           => 'Growth',
                'max_businesses' => 5,
                'price_monthly'  => 29.00,
                'price_yearly'   => 290.00,
                'is_active'      => true,
                'description'    => 'More seats, routing, SLA rules, campaign journeys, analytics.',
                'features'       => ['inbox', 'channels', 'campaigns', 'sla', 'analytics', 'ai_suggestions'],
            ]
        );

        $scalePlan = Plan::firstOrCreate(
            ['type' => 'scale'],
            [
                'name'           => 'Scale',
                'max_businesses' => null,
                'price_monthly'  => 99.00,
                'price_yearly'   => 990.00,
                'is_active'      => true,
                'description'    => 'Advanced roles, audit/export, API/webhooks, multi-business oversight.',
                'features'       => ['inbox', 'channels', 'campaigns', 'sla', 'analytics', 'ai_suggestions', 'api', 'webhooks', 'audit_export', 'priority_support'],
            ]
        );

        // ── 3. Demo Customer (owner, no is_super_admin) ───────────────────────
        $owner = User::firstOrCreate(
            ['email' => 'owner@vertex.app'],
            [
                'name'              => 'Ahmed Demo',
                'password'          => Hash::make('password123'),
                'is_super_admin'    => false, // never true in seeder
                'email_verified_at' => now(),
            ]
        );

        BulkMessageWallet::firstOrCreate(
            ['user_id' => $owner->id],
            ['balance' => 500.00, 'credits' => 1000, 'currency' => 'EGP']
        );

        Subscription::firstOrCreate(
            ['user_id' => $owner->id, 'plan_id' => $growthPlan->id],
            [
                'status'    => 'active',
                'starts_at' => now(),
                'ends_at'   => now()->addYear(),
            ]
        );

        // ── 4. Demo Businesses ────────────────────────────────────────────────
        $business1 = Business::firstOrCreate(
            ['user_id' => $owner->id, 'name' => 'Vertex Hub'],
            ['logo' => null]
        );

        $business2 = Business::firstOrCreate(
            ['user_id' => $owner->id, 'name' => 'Vertex Fashion'],
            ['logo' => null]
        );

        // ── 5. Team Members ───────────────────────────────────────────────────
        $sara = TeamMember::firstOrCreate(
            ['email' => 'sara@vertex.app'],
            [
                'user_id'   => $owner->id,
                'name'      => 'سارة أحمد',
                'password'  => Hash::make('password123'),
                'role'      => 'admin',
                'is_active' => true,
            ]
        );
        $sara->businesses()->syncWithoutDetaching([$business1->id, $business2->id]);

        $omar = TeamMember::firstOrCreate(
            ['email' => 'omar@vertex.app'],
            [
                'user_id'   => $owner->id,
                'name'      => 'عمر خالد',
                'password'  => Hash::make('password123'),
                'role'      => 'supervisor',
                'is_active' => true,
            ]
        );
        $omar->businesses()->syncWithoutDetaching([$business1->id]);

        $ali = TeamMember::firstOrCreate(
            ['email' => 'ali@vertex.app'],
            [
                'user_id'   => $owner->id,
                'name'      => 'علي محمود',
                'password'  => Hash::make('password123'),
                'role'      => 'agent',
                'is_active' => true,
            ]
        );
        $ali->businesses()->syncWithoutDetaching([$business1->id]);

        $nour = TeamMember::firstOrCreate(
            ['email' => 'nour@vertex.app'],
            [
                'user_id'   => $owner->id,
                'name'      => 'نور الهدى',
                'password'  => Hash::make('password123'),
                'role'      => 'agent',
                'is_active' => true,
            ]
        );
        $nour->businesses()->syncWithoutDetaching([$business2->id]);

        // ── 6. Demo Conversations ─────────────────────────────────────────────
        $contact1 = Contact::firstOrCreate(
            ['business_id' => $business1->id, 'platform' => 'whatsapp', 'platform_sender_id' => '+201001112233'],
            ['name' => 'محمود سلامة', 'phone' => '+201001112233', 'email' => 'salama@example.com']
        );

        $conv1 = Conversation::firstOrCreate(
            ['business_id' => $business1->id, 'contact_id' => $contact1->id],
            [
                'platform'        => 'whatsapp',
                'status'          => 'open',
                'unread_count'    => 0,
                'assigned_to'     => $ali->id,
                'last_message_at' => now()->subMinutes(10),
            ]
        );

        Message::firstOrCreate(
            ['conversation_id' => $conv1->id, 'body' => 'السلام عليكم، استفسار عن موعد توصيل الطلب'],
            [
                'sender_type' => 'contact',
                'type'        => 'text',
                'status'      => 'delivered',
                'created_at'  => now()->subMinutes(10),
            ]
        );

        $contact2 = Contact::firstOrCreate(
            ['business_id' => $business1->id, 'platform' => 'telegram', 'platform_sender_id' => '987654321'],
            ['name' => 'كريم فهمي', 'phone' => null, 'email' => 'karim@example.com']
        );

        $conv2 = Conversation::firstOrCreate(
            ['business_id' => $business1->id, 'contact_id' => $contact2->id],
            [
                'platform'        => 'telegram',
                'status'          => 'open',
                'unread_count'    => 1,
                'assigned_to'     => null,
                'last_message_at' => now()->subMinutes(30),
            ]
        );

        Message::firstOrCreate(
            ['conversation_id' => $conv2->id, 'body' => 'أهلاً، هل متاح شحن للمحافظات؟'],
            [
                'sender_type' => 'contact',
                'type'        => 'text',
                'status'      => 'delivered',
                'created_at'  => now()->subMinutes(30),
            ]
        );

        $contact3 = Contact::firstOrCreate(
            ['business_id' => $business2->id, 'platform' => 'instagram', 'platform_sender_id' => 'insta_user_88'],
            ['name' => 'ياسمين صبري', 'phone' => null, 'email' => 'yasmine@example.com']
        );

        $conv3 = Conversation::firstOrCreate(
            ['business_id' => $business2->id, 'contact_id' => $contact3->id],
            [
                'platform'        => 'instagram',
                'status'          => 'open',
                'unread_count'    => 0,
                'assigned_to'     => $nour->id,
                'last_message_at' => now()->subHour(),
            ]
        );

        Message::firstOrCreate(
            ['conversation_id' => $conv3->id, 'body' => 'مساء الخير، الفستان الأسود متوفر مقاس L؟'],
            [
                'sender_type' => 'contact',
                'type'        => 'text',
                'status'      => 'delivered',
                'created_at'  => now()->subHour(),
            ]
        );

        // ── 7. Quick Replies ──────────────────────────────────────────────────
        QuickReply::firstOrCreate(
            ['business_id' => $business1->id, 'shortcut' => '/welcome'],
            ['title' => 'ترحيب بالعميل', 'body' => 'أهلاً بك! كيف يمكنني مساعدتك اليوم؟']
        );
    }
}
