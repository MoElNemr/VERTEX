<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Plan;
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
     */
    public function run(): void
    {
        // 1. إنشاء الباقات
        $standardPlan = Plan::firstOrCreate(
            ['type' => 'standard'],
            [
                'name' => 'Standard Unlimited',
                'max_businesses' => null,
                'price_monthly' => 29.00,
                'price_yearly' => 290.00,
                'is_active' => true,
            ]
        );

        $premiumPlan = Plan::firstOrCreate(
            ['type' => 'premium'],
            [
                'name' => 'Premium Unlimited',
                'max_businesses' => null,
                'price_monthly' => 99.00,
                'price_yearly' => 990.00,
                'is_active' => true,
            ]
        );

        // 2. إنشاء حساب المالك الرئيسي
        $admin = User::firstOrCreate(
            ['email' => 'admin@vertex.app'],
            [
                'name' => 'Mohamed Nemr',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // 3. تفعيل اشتراك غير محدود للمالك
        Subscription::firstOrCreate(
            ['user_id' => $admin->id, 'plan_id' => $premiumPlan->id],
            [
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addYears(10),
            ]
        );

        // 4. إنشاء متاجر تجريبية للمالك
        $business1 = Business::firstOrCreate(
            ['user_id' => $admin->id, 'name' => 'Vertex Hub'],
            ['logo' => null]
        );

        $business2 = Business::firstOrCreate(
            ['user_id' => $admin->id, 'name' => 'Vertex Fashion'],
            ['logo' => null]
        );

        // 5. إنشاء أعضاء فريق العمل (المرحلة 4)
        // أ. مسؤول (Admin) - مربوط بالمتجرين
        $sara = TeamMember::firstOrCreate(
            ['email' => 'sara@vertex.app'],
            [
                'user_id' => $admin->id,
                'name' => 'سارة أحمد',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );
        $sara->businesses()->syncWithoutDetaching([$business1->id, $business2->id]);

        // ب. مشرف (Supervisor) - مربوط بالمتجر الأول فقط
        $omar = TeamMember::firstOrCreate(
            ['email' => 'omar@vertex.app'],
            [
                'user_id' => $admin->id,
                'name' => 'عمر خالد',
                'password' => Hash::make('password123'),
                'role' => 'supervisor',
                'is_active' => true,
            ]
        );
        $omar->businesses()->syncWithoutDetaching([$business1->id]);

        // ج. وكيل دعم 1 (Support Agent) - مربوط بـ Vertex Hub
        $ali = TeamMember::firstOrCreate(
            ['email' => 'ali@vertex.app'],
            [
                'user_id' => $admin->id,
                'name' => 'علي محمود',
                'password' => Hash::make('password123'),
                'role' => 'agent',
                'is_active' => true,
            ]
        );
        $ali->businesses()->syncWithoutDetaching([$business1->id]);

        // د. وكيل دعم 2 (Support Agent) - مربوط بـ Vertex Fashion فقط (لعزل المتاجر)
        $nour = TeamMember::firstOrCreate(
            ['email' => 'nour@vertex.app'],
            [
                'user_id' => $admin->id,
                'name' => 'نور الهدى',
                'password' => Hash::make('password123'),
                'role' => 'agent',
                'is_active' => true,
            ]
        );
        $nour->businesses()->syncWithoutDetaching([$business2->id]);

        // 6. إضافة جهات اتصال ومحادثات تجريبية لاختبار عزل الوكلاء
        // محادثة 1: مسندة للوكيل علي في Vertex Hub
        $contact1 = Contact::firstOrCreate(
            ['business_id' => $business1->id, 'platform' => 'whatsapp', 'platform_sender_id' => '+201001112233'],
            ['name' => 'محمود سلامة', 'phone' => '+201001112233', 'email' => 'salama@example.com']
        );

        $conv1 = Conversation::firstOrCreate(
            ['business_id' => $business1->id, 'contact_id' => $contact1->id],
            [
                'platform' => 'whatsapp',
                'status' => 'open',
                'unread_count' => 0,
                'assigned_to' => $ali->id,
                'last_message_at' => now()->subMinutes(10),
            ]
        );

        Message::firstOrCreate(
            ['conversation_id' => $conv1->id, 'body' => 'السلام عليكم، استفسار عن موعد توصيل الطلب'],
            [
                'sender_type' => 'contact',
                'type' => 'text',
                'status' => 'delivered',
                'created_at' => now()->subMinutes(10),
            ]
        );

        // محادثة 2: غير مسندة في Vertex Hub
        $contact2 = Contact::firstOrCreate(
            ['business_id' => $business1->id, 'platform' => 'telegram', 'platform_sender_id' => '987654321'],
            ['name' => 'كريم فهمي', 'phone' => null, 'email' => 'karim@example.com']
        );

        $conv2 = Conversation::firstOrCreate(
            ['business_id' => $business1->id, 'contact_id' => $contact2->id],
            [
                'platform' => 'telegram',
                'status' => 'open',
                'unread_count' => 1,
                'assigned_to' => null,
                'last_message_at' => now()->subMinutes(30),
            ]
        );

        Message::firstOrCreate(
            ['conversation_id' => $conv2->id, 'body' => 'أهلاً، هل متاح شحن للمحافظات؟'],
            [
                'sender_type' => 'contact',
                'type' => 'text',
                'status' => 'delivered',
                'created_at' => now()->subMinutes(30),
            ]
        );

        // محادثة 3: في المتجر الثاني Vertex Fashion ومسندة للوكيلة نور
        $contact3 = Contact::firstOrCreate(
            ['business_id' => $business2->id, 'platform' => 'instagram', 'platform_sender_id' => 'insta_user_88'],
            ['name' => 'ياسمين صبري', 'phone' => null, 'email' => 'yasmine@example.com']
        );

        $conv3 = Conversation::firstOrCreate(
            ['business_id' => $business2->id, 'contact_id' => $contact3->id],
            [
                'platform' => 'instagram',
                'status' => 'open',
                'unread_count' => 0,
                'assigned_to' => $nour->id,
                'last_message_at' => now()->subHour(),
            ]
        );

        Message::firstOrCreate(
            ['conversation_id' => $conv3->id, 'body' => 'مساء الخير، الفستان الأسود متوفر مقاس L؟'],
            [
                'sender_type' => 'contact',
                'type' => 'text',
                'status' => 'delivered',
                'created_at' => now()->subHour(),
            ]
        );

        // ردود سريعة
        QuickReply::firstOrCreate(
            ['business_id' => $business1->id, 'shortcut' => '/welcome'],
            ['title' => 'ترحيب بالعميل', 'body' => 'أهلاً بك! كيف يمكنني مساعدتك اليوم؟']
        );
    }
}
