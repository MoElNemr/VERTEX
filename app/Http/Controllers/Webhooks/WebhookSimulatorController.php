<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\Channels\ChannelManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookSimulatorController extends Controller
{
    public function __construct(
        protected ChannelManager $channelManager
    ) {
        if (app()->isProduction()) {
            abort(404, 'Webhook simulator is not available in production.');
        }
    }

    /**
     * Simulate an incoming Telegram message.
     */
    public function simulateTelegram(Request $request, Business $business): JsonResponse
    {
        $validated = $request->validate([
            'sender_id' => ['nullable', 'string'],
            'sender_name' => ['nullable', 'string'],
            'text' => ['required', 'string'],
        ]);

        $senderId = $validated['sender_id'] ?? rand(100000000, 999999999);
        $senderName = $validated['sender_name'] ?? 'مستخدم تجريبي تيليجرام';

        $fakeRequest = new Request([
            'update_id' => rand(100000, 999999),
            'message' => [
                'message_id' => rand(1000, 9999),
                'from' => [
                    'id' => $senderId,
                    'first_name' => $senderName,
                    'username' => 'user_' . substr($senderId, -4),
                ],
                'chat' => [
                    'id' => $senderId,
                    'type' => 'private',
                ],
                'text' => $validated['text'],
                'date' => time(),
            ],
        ]);

        $fakeRequest->attributes->set('is_simulator', true);
        $result = $this->channelManager->telegram()->handleWebhook($business, $fakeRequest);

        return response()->json([
            'simulated_platform' => 'telegram',
            'result' => $result,
        ]);
    }

    /**
     * Simulate an incoming Facebook Messenger message.
     */
    public function simulateFacebook(Request $request, Business $business): JsonResponse
    {
        $validated = $request->validate([
            'sender_id' => ['nullable', 'string'],
            'sender_name' => ['nullable', 'string'],
            'text' => ['required', 'string'],
        ]);

        $senderId = $validated['sender_id'] ?? ('fb_psid_' . rand(100000, 999999));

        $fakeRequest = new Request([
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'page_12345',
                    'time' => round(microtime(true) * 1000),
                    'messaging' => [
                        [
                            'sender' => ['id' => $senderId],
                            'recipient' => ['id' => 'page_12345'],
                            'message' => [
                                'mid' => 'mid.' . uniqid(),
                                'text' => $validated['text'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $fakeRequest->attributes->set('is_simulator', true);
        $result = $this->channelManager->meta()->handleWebhook($business, $fakeRequest);

        return response()->json([
            'simulated_platform' => 'facebook',
            'result' => $result,
        ]);
    }

    /**
     * Simulate an incoming Facebook post comment (Comment-to-DM trigger).
     */
    public function simulateFacebookComment(Request $request, Business $business): JsonResponse
    {
        $validated = $request->validate([
            'sender_id' => ['nullable', 'string'],
            'sender_name' => ['nullable', 'string'],
            'text' => ['required', 'string'],
        ]);

        $senderId = $validated['sender_id'] ?? ('fb_user_' . rand(100000, 999999));
        $senderName = $validated['sender_name'] ?? 'معلق فيسبوك تجريبي';
        $commentId = 'fb_cmt_' . rand(100000, 999999);

        $fakeRequest = new Request([
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'page_12345',
                    'time' => round(microtime(true) * 1000),
                    'changes' => [
                        [
                            'field' => 'feed',
                            'value' => [
                                'item' => 'comment',
                                'verb' => 'add',
                                'comment_id' => $commentId,
                                'post_id' => 'post_' . rand(1000, 9999),
                                'from' => [
                                    'id' => $senderId,
                                    'name' => $senderName,
                                ],
                                'message' => $validated['text'],
                                'created_time' => time(),
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $fakeRequest->attributes->set('is_simulator', true);
        $result = $this->channelManager->meta()->handleWebhook($business, $fakeRequest);

        return response()->json([
            'simulated_platform' => 'facebook_comment',
            'result' => $result,
        ]);
    }

    /**
     * Simulate an incoming Instagram Direct message.
     */
    public function simulateInstagram(Request $request, Business $business): JsonResponse
    {
        $validated = $request->validate([
            'sender_id' => ['nullable', 'string'],
            'text' => ['required', 'string'],
        ]);

        $senderId = $validated['sender_id'] ?? ('ig_id_' . rand(100000, 999999));

        $fakeRequest = new Request([
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig_12345',
                    'time' => round(microtime(true) * 1000),
                    'messaging' => [
                        [
                            'sender' => ['id' => $senderId],
                            'recipient' => ['id' => 'ig_12345'],
                            'message' => [
                                'mid' => 'mid.' . uniqid(),
                                'text' => $validated['text'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $fakeRequest->attributes->set('is_simulator', true);
        $result = $this->channelManager->meta()->handleWebhook($business, $fakeRequest);

        return response()->json([
            'simulated_platform' => 'instagram',
            'result' => $result,
        ]);
    }

    /**
     * Simulate an incoming Instagram post comment (Comment-to-DM trigger).
     */
    public function simulateInstagramComment(Request $request, Business $business): JsonResponse
    {
        $validated = $request->validate([
            'sender_id' => ['nullable', 'string'],
            'username' => ['nullable', 'string'],
            'text' => ['required', 'string'],
        ]);

        $senderId = $validated['sender_id'] ?? ('ig_user_' . rand(100000, 999999));
        $username = $validated['username'] ?? ('insta_buyer_' . rand(100, 999));
        $commentId = 'ig_cmt_' . rand(100000, 999999);

        $fakeRequest = new Request([
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig_12345',
                    'time' => round(microtime(true) * 1000),
                    'changes' => [
                        [
                            'field' => 'comments',
                            'value' => [
                                'id' => $commentId,
                                'text' => $validated['text'],
                                'from' => [
                                    'id' => $senderId,
                                    'username' => $username,
                                ],
                                'media' => [
                                    'id' => 'media_' . rand(1000, 9999),
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $fakeRequest->attributes->set('is_simulator', true);
        $result = $this->channelManager->meta()->handleWebhook($business, $fakeRequest);

        return response()->json([
            'simulated_platform' => 'instagram_comment',
            'result' => $result,
        ]);
    }

    /**
     * Simulate an incoming WhatsApp message from Baileys.
     */
    public function simulateWhatsApp(Request $request, Business $business): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['nullable', 'string'],
            'sender_name' => ['nullable', 'string'],
            'text' => ['required', 'string'],
        ]);

        $phone = $validated['phone'] ?? ('2010' . rand(10000000, 99999999));
        $name = $validated['sender_name'] ?? 'عميل واتساب تجريبي';

        $fakeRequest = new Request([
            'event' => 'messages.upsert',
            'data' => [
                'from' => $phone . '@s.whatsapp.net',
                'push_name' => $name,
                'message_id' => 'BAE5_' . strtoupper(bin2hex(random_bytes(6))),
                'text' => $validated['text'],
                'timestamp' => time(),
            ],
        ]);

        $fakeRequest->attributes->set('is_simulator', true);
        $result = $this->channelManager->whatsapp()->handleWebhook($business, $fakeRequest);

        return response()->json([
            'simulated_platform' => 'whatsapp',
            'result' => $result,
        ]);
    }
}
