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
        $result = $this->metaService->handleWebhook($business, $request);

        if (isset($result['status']) && $result['status'] === 'rejected') {
            return response($result['error'] ?? 'Unauthorized', 401);
        }

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return response('EVENT_RECEIVED', 200);
    }
}
