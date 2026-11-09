<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\Channels\MetaChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MetaWebhookController extends Controller
{
    public function __construct(
        protected MetaChannelService $metaService
    ) {}

    /**
     * Handle Meta webhook verification challenge (GET).
     */
    public function verify(Request $request, Business $business): Response
    {
        $challenge = $this->metaService->verifyWebhook($business, $request);

        if ($challenge !== null) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming Meta webhook events (POST) for Facebook and Instagram.
     */
    public function handle(Request $request, Business $business): Response|JsonResponse
    {
        $appSecret = config('services.meta.app_secret')
            ?: config('services.meta.client_secret');

        if (! empty($appSecret)) {
            $signatureHeader = $request->header('X-Hub-Signature-256');

            if (! $signatureHeader) {
                return response('Signature missing', 403);
            }

            $providedSignature = str_starts_with($signatureHeader, 'sha256=')
                ? substr($signatureHeader, 7)
                : $signatureHeader;

            $expectedSignature = hash_hmac('sha256', $request->getContent(), $appSecret);

            if (! hash_equals($expectedSignature, $providedSignature)) {
                return response('Invalid signature', 403);
            }
        }

        $result = $this->metaService->handleWebhook($business, $request);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return response('EVENT_RECEIVED', 200);
    }
}
