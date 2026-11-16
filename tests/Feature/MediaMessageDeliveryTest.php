<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\FacebookAccount;
use App\Models\Message;
use App\Models\User;
use App\Services\Channels\MetaChannelService;
use App\Services\Channels\WhatsAppChannelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class MediaMessageDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_incoming_meta_webhook_with_image_saves_direct_cdn_url_without_server_download(): void
    {
        $user = User::factory()->create();
        $business = Business::create(['name' => 'متجر الأحذية', 'user_id' => $user->id]);

        FacebookAccount::create([
            'business_id' => $business->id,
            'page_id' => '100200300',
            'page_name' => 'Shoes Store',
            'page_access_token' => 'token_abc',
        ]);

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '100200300',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'customer_sender_111'],
                            'recipient' => ['id' => '100200300'],
                            'message' => [
                                'mid' => 'mid_img_99999',
                                'attachments' => [
                                    [
                                        'type' => 'image',
                                        'payload' => [
                                            'url' => 'https://cdn.facebook.com/v21.0/image_direct_link.jpg',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $request = Request::create('/webhooks/meta', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));
        $metaService = app(MetaChannelService::class);
        $result = $metaService->handleIncomingWebhook($request, $business);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(1, $result['processed_count']);

        $message = Message::where('platform_message_id', 'mid_img_99999')->first();
        $this->assertNotNull($message);
        $this->assertEquals('image', $message->type);
        $this->assertEquals('https://cdn.facebook.com/v21.0/image_direct_link.jpg', $message->metadata['media_url']);
        $this->assertCount(1, $message->attachments);
        $this->assertEquals('https://cdn.facebook.com/v21.0/image_direct_link.jpg', $message->attachments->first()->file_path);
    }

    public function test_incoming_meta_webhook_with_audio_voice_note_saves_direct_cdn_url(): void
    {
        $user = User::factory()->create();
        $business = Business::create(['name' => 'متجر الأحذية', 'user_id' => $user->id]);

        FacebookAccount::create([
            'business_id' => $business->id,
            'page_id' => '100200300',
            'page_name' => 'Shoes Store',
            'page_access_token' => 'token_abc',
        ]);

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '100200300',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'customer_sender_222'],
                            'recipient' => ['id' => '100200300'],
                            'message' => [
                                'mid' => 'mid_audio_88888',
                                'attachments' => [
                                    [
                                        'type' => 'audio',
                                        'payload' => [
                                            'url' => 'https://cdn.facebook.com/v21.0/voice_note_direct.m4a',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $request = Request::create('/webhooks/meta', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));
        $metaService = app(MetaChannelService::class);
        $metaService->handleIncomingWebhook($request, $business);

        $message = Message::where('platform_message_id', 'mid_audio_88888')->first();
        $this->assertNotNull($message);
        $this->assertEquals('audio', $message->type);
        $this->assertEquals('https://cdn.facebook.com/v21.0/voice_note_direct.m4a', $message->metadata['media_url']);
    }

    public function test_incoming_whatsapp_media_message_saves_direct_url(): void
    {
        $user = User::factory()->create();
        $business = Business::create(['name' => 'متجر تجريبي', 'user_id' => $user->id]);

        $payload = [
            'event' => 'messages.upsert',
            'data' => [
                'from' => '201011112222@s.whatsapp.net',
                'message_id' => 'WA_IMG_55555',
                'type' => 'image',
                'media_url' => 'https://whatsapp-media-cdn.net/sample_photo.jpg',
            ],
        ];

        $request = Request::create('/webhooks/whatsapp', 'POST', $payload);
        $waService = app(WhatsAppChannelService::class);
        $waService->handleWebhook($business, $request);

        $message = Message::where('platform_message_id', 'WA_IMG_55555')->first();
        $this->assertNotNull($message);
        $this->assertEquals('image', $message->type);
        $this->assertEquals('https://whatsapp-media-cdn.net/sample_photo.jpg', $message->metadata['media_url']);
    }
}
