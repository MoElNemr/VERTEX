<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\FacebookAccount;
use App\Models\InstagramAccount;
use App\Models\Message;
use App\Models\QuickReply;
use App\Models\TelegramAccount;
use App\Models\WhatsappAccount;
use Illuminate\Database\Seeder;

class RichDemoSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::first();
        if (!$business) return;

        // 1. Fake-connect all channels so the UI looks live and populated
        WhatsappAccount::updateOrCreate(
            ['business_id' => $business->id],
            [
                'session_id' => 'vertex_wa_session_demo',
                'phone_number' => '+20 100 892 4192',
                'status' => 'connected',
                'last_connected_at' => now(),
            ]
        );

        TelegramAccount::updateOrCreate(
            ['business_id' => $business->id],
            [
                'bot_username' => 'VertexSupportBot',
                'bot_token' => '7182938472:AAFZexampletoken_demo_vertex',
                'status' => 'connected',
            ]
        );

        FacebookAccount::updateOrCreate(
            ['business_id' => $business->id],
            [
                'page_id' => '109283746501928',
                'page_name' => 'Vertex Store Official',
                'page_access_token' => 'EAA_demo_token_page_vertex',
                'status' => 'connected',
            ]
        );

        InstagramAccount::updateOrCreate(
            ['business_id' => $business->id],
            [
                'instagram_business_id' => '178414002938471',
                'username' => 'vertex.eg',
                'access_token' => 'EAA_demo_token_ig_vertex',
                'status' => 'connected',
            ]
        );

        // 2. Rich Quick Replies
        $quickReplies = [
            ['shortcut' => '/welcome', 'title' => 'ترحيب بالعميل', 'body' => 'أهلاً بحضرتك يا فندم في Vertex! تشرفنا بيك، إزاي نقدر نساعدك النهاردة؟ ✨'],
            ['shortcut' => '/price', 'title' => 'الأسعار والشحن', 'body' => 'الشحن متاح لجميع المحافظات خلال 24 - 48 ساعة 🚚. تكلفة الشحن 45 جنيهاً ومجاني للطلبات فوق 500 جنيه.'],
            ['shortcut' => '/hours', 'title' => 'مواعيد العمل', 'body' => 'خدمة العملاء متواجدة يومياً من 9 صباحاً حتى 11 مساءً للرد على جميع استفساراتكم.'],
            ['shortcut' => '/return', 'title' => 'سياسة الاستبدال', 'body' => 'الاستبدال والاسترجاع متاح مجاناً خلال 14 يوماً من استلام المنتج بشرط سلامة التغليف.'],
            ['shortcut' => '/thanks', 'title' => 'شكر وخاتمة', 'body' => 'سعدنا بخدمتك جداً! لو احتجت أي مساعدة تانية إحنا دايماً في انتظارك، يومك سعيد 🌟'],
        ];

        foreach ($quickReplies as $qr) {
            QuickReply::updateOrCreate(
                ['business_id' => $business->id, 'shortcut' => $qr['shortcut']],
                ['title' => $qr['title'], 'body' => $qr['body']]
            );
        }

        // 3. Rich Mock Customers & Chats across all 4 platforms
        $mockData = [
            [
                'name' => 'محمود عبد الفتاح',
                'platform' => 'whatsapp',
                'sender_id' => '201099238472',
                'phone' => '+201099238472',
                'status' => 'open',
                'unread' => 2,
                'minutes_ago' => 5,
                'messages' => [
                    ['sender' => 'contact', 'body' => 'مساء الخير، لو سمحت الباقة السنوية دي تشمل كام متجر؟', 'min' => 12],
                    ['sender' => 'agent', 'body' => 'مساء النور يا فندم! الباقة السنوية تشمل متاجر غير محدودة مع ربط كل القنوات بالكامل.', 'min' => 10],
                    ['sender' => 'contact', 'body' => 'ممتاز جداً، طب ينفع أسدد عن طريق فودافون كاش ولا لازم فيزا؟', 'min' => 5],
                    ['sender' => 'contact', 'body' => 'وهل التفعيل بيكون لحظي؟', 'min' => 4],
                ]
            ],
            [
                'name' => 'سارة حسن الشريف',
                'platform' => 'facebook',
                'sender_id' => 'fb_user_10928374',
                'phone' => null,
                'status' => 'open',
                'unread' => 1,
                'minutes_ago' => 22,
                'messages' => [
                    ['sender' => 'contact', 'body' => 'السلام عليكم، كنت شفت إعلان لحضراتكم عن ربط الواتساب مع فيسبوك في مكان واحد', 'min' => 40],
                    ['sender' => 'agent', 'body' => 'وعليكم السلام يا فندم! بالظبط، نظامنا بيوفرلك صندوق وارد موحد لكل الرسائل من واتساب، ماسنجر، إنستجرام وتيليجرام بدون أي تشتت.', 'min' => 35],
                    ['sender' => 'contact', 'body' => 'حلو أوي، محتاجة أجرب تجربة مجانية الأول لو متاح.', 'min' => 22],
                ]
            ],
            [
                'name' => 'نور الهدى إبراهيم',
                'platform' => 'instagram',
                'sender_id' => 'ig_user_nour_style',
                'phone' => null,
                'status' => 'open',
                'unread' => 0,
                'minutes_ago' => 65,
                'messages' => [
                    ['sender' => 'contact', 'body' => 'هاي، التيشيرت الأسود أوفر سايز مقاس L متوفر منه كميات ولا خلص؟', 'min' => 80],
                    ['sender' => 'agent', 'body' => 'أهلاً بحضرتك يا فندم! متوفر آخر قطعتين بالمخزن للشحن الفوري.', 'min' => 70],
                    ['sender' => 'contact', 'body' => 'تمام احجزلي قطعة وهبعتلك العنوان دلوقتي على الخاص.', 'min' => 65],
                ]
            ],
            [
                'name' => 'عمر خالد المنشاوي',
                'platform' => 'telegram',
                'sender_id' => 'tg_user_83749281',
                'phone' => null,
                'status' => 'open',
                'unread' => 0,
                'minutes_ago' => 140,
                'messages' => [
                    ['sender' => 'contact', 'body' => 'البوت بيرد بسرعة ما شاء الله، هل فيه أوبشن نربط بالـ CRM بتاعنا؟', 'min' => 160],
                    ['sender' => 'agent', 'body' => 'أهلاً بك يا أستاذ عمر! نعم، نوفر Webhooks و API كاملين للربط المباشر مع أي نظام خارجي.', 'min' => 140],
                ]
            ],
            [
                'name' => 'كريم الدسوقي',
                'platform' => 'whatsapp',
                'sender_id' => '201123487654',
                'phone' => '+201123487654',
                'status' => 'closed',
                'unread' => 0,
                'minutes_ago' => 360,
                'messages' => [
                    ['sender' => 'contact', 'body' => 'استلمت الشحنة النهاردة، شكراً جداً على سرعة التوصيل والأوردر تمام 👍', 'min' => 400],
                    ['sender' => 'agent', 'body' => 'العفو يا فندم، سعداء جداً بخدمتك وفي انتظارك دائماً!', 'min' => 360],
                ]
            ],
            [
                'name' => 'فريدة زهران',
                'platform' => 'instagram',
                'sender_id' => 'ig_user_farida_z',
                'phone' => null,
                'status' => 'closed',
                'unread' => 0,
                'minutes_ago' => 720,
                'messages' => [
                    ['sender' => 'contact', 'body' => 'هل الكولكشن الجديد نزل على السايت؟', 'min' => 800],
                    ['sender' => 'agent', 'body' => 'نعم يا فندم، متاح الآن على الموقع مع كود خصم 10% بمناسبة الإطلاق.', 'min' => 720],
                ]
            ],
        ];

        foreach ($mockData as $data) {
            $contact = Contact::updateOrCreate(
                [
                    'business_id' => $business->id,
                    'platform' => $data['platform'],
                    'platform_sender_id' => $data['sender_id'],
                ],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                ]
            );

            $lastMsgTime = now()->subMinutes($data['minutes_ago']);

            $conv = Conversation::updateOrCreate(
                [
                    'business_id' => $business->id,
                    'contact_id' => $contact->id,
                ],
                [
                    'platform' => $data['platform'],
                    'status' => $data['status'],
                    'unread_count' => $data['unread'],
                    'last_message_at' => $lastMsgTime,
                ]
            );

            // Seed messages
            $conv->messages()->delete();
            foreach ($data['messages'] as $m) {
                $conv->messages()->create([
                    'sender_type' => $m['sender'],
                    'body' => $m['body'],
                    'type' => 'text',
                    'status' => $m['sender'] === 'agent' ? 'sent' : 'delivered',
                    'created_at' => now()->subMinutes($m['min']),
                ]);
            }
        }
    }
}
