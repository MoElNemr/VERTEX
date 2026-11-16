<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\Channels\TikTokChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TikTokWebhookController extends Controller
{
    public function __construct(
        protected TikTokChannelService $tiktokService
    ) {}

    /**
     * Handle incoming TikTok webhooks.
     */
    public function handle(Request $request, ?Business $business = null): JsonResponse
    {
        $result = $this->tiktokService->handleWebhook($business, $request);

        if (($result['status'] ?? '') === 'error') {
            return response()->json($result, 401);
        }

        return response()->json($result);
    }
}
