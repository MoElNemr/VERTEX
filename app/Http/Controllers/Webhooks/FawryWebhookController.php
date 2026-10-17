<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Services\FawryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FawryWebhookController extends Controller
{
    public function __construct(
        protected FawryService $fawryService
    ) {}

    /**
     * Handle incoming Fawry IPN Webhook callback.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('Fawry IPN Webhook received', $payload);

        $merchantRefNum = $payload['merchantRefNum'] ?? ($payload['orderNumber'] ?? null);
        $orderStatus = strtoupper($payload['orderStatus'] ?? '');
        $fawryRefNumber = $payload['fawryRefNumber'] ?? null;

        if (!$merchantRefNum) {
            return response()->json(['error' => 'Missing merchantRefNum'], 400);
        }

        $transaction = PaymentTransaction::where('reference_number', $merchantRefNum)->first();

        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        // Verify signature in live mode
        $config = $this->fawryService->getConfig();
        if ($config['mode'] === 'live') {
            if (!$this->fawryService->verifyWebhookSignature($payload)) {
                Log::warning('Fawry Webhook invalid signature for transaction: ' . $merchantRefNum);
                return response()->json(['error' => 'Invalid signature'], 403);
            }
        }

        if (in_array($orderStatus, ['PAID', 'SUCCESS'])) {
            $this->fawryService->handleSuccessfulPayment($transaction, $fawryRefNumber);
            return response()->json(['status' => 'success', 'message' => 'Payment processed']);
        }

        if (in_array($orderStatus, ['FAILED', 'CANCELLED', 'EXPIRED'])) {
            $transaction->update(['status' => strtolower($orderStatus)]);
            return response()->json(['status' => 'updated', 'message' => 'Status set to ' . $orderStatus]);
        }

        return response()->json(['status' => 'acknowledged']);
    }

    /**
     * User return URL after Fawry payment redirect.
     */
    public function callback(Request $request): RedirectResponse
    {
        $merchantRefNum = $request->query('ref');

        if ($merchantRefNum) {
            $transaction = PaymentTransaction::where('reference_number', $merchantRefNum)->first();
            if ($transaction && $transaction->status === 'paid') {
                return redirect()->route('dashboard')->with('success', 'تم تأكيد دفع الاشتراك وتفعيل باقتك بنجاح!');
            }
        }

        return redirect()->route('dashboard')->with('success', 'تم تسجيل طلب الدفع عبر فوري بنجاح. سيتم تفعيل الباقة فور اكتمال السداد.');
    }
}
