<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\QuickReply;
use Illuminate\Database\Seeder;

class SampleInboxSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::first();
        if (!$business) return;

        // 1. Quick Replies
        QuickReply::firstOrCreate(
            ['business_id' => $business->id, 'shortcut' => '/welcome'],
            ['title' => 'ترحيب', 'body' => 'أهلاً بك في متجرنا! كيف نقدر نساعدك النهاردة؟']
        );
        QuickReply::firstOrCreate(
            ['business_id' => $business->id, 'shortcut' => '/price'],
            ['title' => 'الأسعار والتوصيل', 'body' => 'الشحن متاح لجميع المحافظات خلال 48 ساعة وسعر الشحن 50 جنيه فقط.']
        );

        // 2. Sample WhatsApp Contact & Conversation
        $waContact = Contact::firstOrCreate(
            ['business_id' => $business->id, 'platform' => 'whatsapp', 'platform_sender_id' => '201012345678'],
            ['name' => 'أحمد محمود', 'phone' => '+201012345678']
        );

        $waConv = Conversation::firstOrCreate(
            ['business_id' => $business->id, 'contact_id' => $waContact->id],
            ['platform' => 'whatsapp', 'status' => 'open', 'unread_count' => 1, 'last_message_at' => now()]
        );

        if ($waConv->messages()->count() === 0) {
            $waConv->messages()->create([
                'sender_type' => 'contact',
                'body' => 'السلام عليكم، هل المنتج متاح للشحن الفوري؟',
                'type' => 'text',
                'status' => 'delivered',
            ]);
        }

        // 3. Sample Facebook Contact & Conversation
        $fbContact = Contact::firstOrCreate(
            ['business_id' => $business->id, 'platform' => 'facebook', 'platform_sender_id' => 'psid_987654321'],
            ['name' => 'سارة إبراهيم']
        );

        $fbConv = Conversation::firstOrCreate(
            ['business_id' => $business->id, 'contact_id' => $fbContact->id],
            ['platform' => 'facebook', 'status' => 'open', 'unread_count' => 0, 'last_message_at' => now()->subHours(2)]
        );

        if ($fbConv->messages()->count() === 0) {
            $fbConv->messages()->create([
                'sender_type' => 'contact',
                'body' => 'مساء الخير، عاوزه أعرف تفاصيل العرض الموجود على الصفحة',
                'type' => 'text',
                'status' => 'delivered',
            ]);
            $fbConv->messages()->create([
                'sender_type' => 'agent',
                'body' => 'أهلاً بحضرتك يا فندم! العرض ساري حتى نهاية الأسبوع بخصم 20%.',
                'type' => 'text',
                'status' => 'sent',
            ]);
        }
    }
}
