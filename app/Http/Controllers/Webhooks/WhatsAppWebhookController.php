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
        $webhookSecret = config('services.whatsapp.webhook_secret') ?: config('services.whatsapp.api_key');
        if (! empty($webhookSecret)) {
            $providedSecret = $request->header('x-webhook-secret') ?: $request->header('x-api-key');
            if ($providedSecret && ! hash_equals($webhookSecret, $providedSecret)) {
                return response()->json(['error' => 'Unauthorized webhook token'], 403);
            }
        }

        $result = $this->whatsappService->handleWebhook($business, $request);

        return response()->json($result);
    }
}
