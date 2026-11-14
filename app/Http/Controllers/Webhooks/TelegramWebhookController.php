<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\Channels\TelegramChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function __construct(
        protected TelegramChannelService $telegramService
    ) {}

    /**
     * Handle incoming Telegram bot webhook updates.
     */
    public function handle(Request $request, Business $business): JsonResponse
    {
        $account = $business->telegramAccount;
        if ($account && ! empty($account->webhook_secret)) {
            $providedSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');
            if (! $providedSecret || ! hash_equals($account->webhook_secret, $providedSecret)) {
                return response()->json(['error' => 'Unauthorized secret token'], 403);
            }
        }

        $result = $this->telegramService->handleWebhook($business, $request);

        return response()->json($result);
    }
}
