<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\Channels\WhatsAppChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppChannelService $whatsappService
    ) {}

    /**
     * Handle incoming webhooks from Baileys WhatsApp microservice.
     */
    public function handle(Request $request, Business $business): JsonResponse
    {
        $result = $this->whatsappService->handleWebhook($business, $request);

        if (isset($result['status']) && $result['status'] === 'unauthorized') {
            return response()->json($result, 401);
        }

        return response()->json($result);
    }
}
