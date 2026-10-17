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
        $configuredKey = config('services.whatsapp.api_key');
        if (!empty($configuredKey)) {
            $incomingKey = $request->header('x-api-key')
                ?: $request->header('x-webhook-secret')
                ?: $request->bearerToken();

            if (!$incomingKey || !hash_equals((string) $configuredKey, (string) $incomingKey)) {
                return response()->json([
                    'status' => 'unauthorized',
                    'error' => 'Invalid or missing webhook signature/API key.',
                ], 401);
            }
        }

        $result = $this->whatsappService->handleWebhook($business, $request);

        return response()->json($result);
    }
}
