<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\WhatsappAccount;
use App\Services\CampaignService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class WhatsAppSenderController extends Controller
{
    public function __construct(
        protected CampaignService $campaignService,
        protected WalletService $walletService
    ) {}

    /**
     * Display WhatsApp bulk sender view and campaign logs.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $currentBusinessId = session('current_business_id');

        if (!$currentBusinessId) {
            $user = $request->user('web') ?: $request->user('team');
            $firstBusiness = $user ? $user->businesses()->first() : null;
            if ($firstBusiness) {
                session(['current_business_id' => $firstBusiness->id]);
                $currentBusinessId = $firstBusiness->id;
            } else {
                return redirect()->route('businesses.index');
            }
        }

        $business = Business::findOrFail($currentBusinessId);

        $whatsappAccount = WhatsappAccount::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->first();

        $campaigns = Campaign::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('type', 'whatsapp_bulk')
            ->latest()
            ->paginate(15);

        $totalSent = Campaign::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('type', 'whatsapp_bulk')
            ->sum('sent_count');

        $totalCampaigns = Campaign::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('type', 'whatsapp_bulk')
            ->count();

        $activeCampaigns = Campaign::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('type', 'whatsapp_bulk')
            ->where('status', 'processing')
            ->count();

        return Inertia::render('WhatsAppSender/Index', [
            'business' => $business->only(['id', 'name']),
            'whatsappAccount' => $whatsappAccount ? [
                'status' => $whatsappAccount->status,
                'phone_number' => $whatsappAccount->phone_number,
                'session_id' => $whatsappAccount->session_id,
            ] : null,
            'campaigns' => $campaigns,
            'stats' => [
                'total_campaigns' => $totalCampaigns,
                'active_campaigns' => $activeCampaigns,
                'total_messages_sent' => (int) $totalSent,
            ],
        ]);
    }

    /**
     * Parse and preview uploaded CSV file with contacts.
     */
    public function previewCsv(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB max
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $filename = $file->getClientOriginalName();

        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            // Read first line (header)
            $rawHeader = fgetcsv($handle);
            if (!$rawHeader) {
                fclose($handle);
                return response()->json(['error' => 'ملف CSV فارغ أو غير صالح.'], 422);
            }

            // Clean BOM from first header column if present
            $rawHeader[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $rawHeader[0]);

            // Detect column indices
            $nameIdx = -1;
            $phoneIdx = -1;

            foreach ($rawHeader as $idx => $col) {
                $cleanCol = strtolower(trim($col));
                if (in_array($cleanCol, ['name', 'full_name', 'اسم', 'الاسم', 'عميل', 'customer', 'customer_name'])) {
                    $nameIdx = $idx;
                }
                if (in_array($cleanCol, ['phone', 'mobile', 'tel', 'phone_number', 'رقم', 'الهاتف', 'الجوال', 'موبايل', 'whatsapp'])) {
                    $phoneIdx = $idx;
                }
            }

            // Fallback if not detected by name: first col = name, second col = phone or vice versa
            if ($phoneIdx === -1) {
                if (count($rawHeader) >= 2) {
                    $phoneIdx = 1;
                    $nameIdx = 0;
                } else {
                    $phoneIdx = 0;
                }
            }

            $validCount = 0;
            $invalidCount = 0;
            $previewRows = [];
            $allRows = [];

            while (($data = fgetcsv($handle)) !== false) {
                if (empty(array_filter($data))) {
                    continue; // Skip empty row
                }

                $rawPhone = $data[$phoneIdx] ?? '';
                $rawName = $nameIdx >= 0 ? ($data[$nameIdx] ?? '') : '';

                $cleanPhone = $this->campaignService->cleanPhoneNumber($rawPhone);

                $isValid = strlen($cleanPhone) >= 8;

                if ($isValid) {
                    $validCount++;
                } else {
                    $invalidCount++;
                }

                $rowItem = [
                    'name' => trim($rawName),
                    'phone' => $cleanPhone ?: trim($rawPhone),
                    'raw_phone' => trim($rawPhone),
                    'wa_link' => $isValid ? ('https://wa.me/' . $cleanPhone) : null,
                    'is_valid' => $isValid,
                ];

                $allRows[] = $rowItem;

                if (count($previewRows) < 15) {
                    $previewRows[] = $rowItem;
                }
            }

            fclose($handle);

            $validRecipients = array_values(array_filter($allRows, fn ($r) => $r['is_valid']));
            $validRecipientsCount = count($validRecipients);

            // Check business owner bulk wallet balance
            $currentBusinessId = session('current_business_id');
            $business = Business::find($currentBusinessId);
            $user = $business?->user ?: ($request->user('web') ?: $request->user('team'));
            $wallet = $user ? $user->getOrCreateWallet() : null;
            $currentCredits = $wallet ? (int) $wallet->credits : 0;
            $currentBalance = $wallet ? (float) $wallet->balance : 0.00;
            $canAfford = $currentCredits >= $validRecipientsCount;
            $missingCredits = max(0, $validRecipientsCount - $currentCredits);

            return response()->json([
                'status' => 'success',
                'filename' => $filename,
                'total_rows' => count($allRows),
                'valid_count' => $validCount,
                'invalid_count' => $invalidCount,
                'preview_rows' => $previewRows,
                'recipients' => $validRecipients,
                'wallet' => [
                    'current_credits' => $currentCredits,
                    'current_balance' => $currentBalance,
                    'required_credits' => $validRecipientsCount,
                    'can_afford' => $canAfford,
                    'missing_credits' => $missingCredits,
                ],
            ]);
        }

        return response()->json(['error' => 'تعذر قراءة ملف CSV.'], 422);
    }

    /**
     * Download sample CSV template.
     */
    public function downloadTemplate(): HttpResponse
    {
        $csvContent = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel Arabic compatibility
        $csvContent .= "name,phone\n";
        $csvContent .= "أحمد علي,+201012345678\n";
        $csvContent .= "سارة محمد,+966512345678\n";
        $csvContent .= "محمود حسن,01234567890\n";

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="whatsapp_contacts_template.csv"',
        ]);
    }

    /**
     * Create and start bulk WhatsApp campaign.
     */
    public function send(Request $request): JsonResponse
    {
        $currentBusinessId = session('current_business_id');
        $business = Business::findOrFail($currentBusinessId);
        $user = $business->user ?: ($request->user('web') ?: $request->user('team'));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*.phone' => ['required', 'string'],
            'recipients.*.name' => ['nullable', 'string'],
            'interval_seconds' => ['nullable', 'integer', 'min:2', 'max:300'],
            'jitter_seconds' => ['nullable', 'integer', 'min:0', 'max:60'],
            'save_to_contacts' => ['nullable', 'boolean'],
            'auto_start' => ['nullable', 'boolean'],
            'filename' => ['nullable', 'string'],
        ]);

        $recipientCount = count($validated['recipients']);

        // HARD BALANCE GATE: Do not start if user balance cannot afford campaign
        if ($user && !$this->walletService->hasSufficientCredits($user, $recipientCount)) {
            $wallet = $user->getOrCreateWallet();
            $missing = $recipientCount - $wallet->credits;
            return response()->json([
                'error' => "رصيد المحفظة غير كافٍ لإرسال الحملة. رصيدك الحالي: {$wallet->credits} رسالة، والمطلوب: {$recipientCount} رسالة (ينقصك {$missing} رسالة). يرجى شحن المحفظة أولاً.",
                'wallet' => [
                    'current_credits' => $wallet->credits,
                    'required_credits' => $recipientCount,
                    'missing_credits' => $missing,
                ],
            ], 402);
        }

        $campaign = $this->campaignService->createWhatsAppBulkCampaign($business, $validated);

        if (!empty($validated['auto_start']) && $campaign->total_recipients > 0) {
            $this->campaignService->launchCampaign($campaign);
        }

        return response()->json([
            'status' => 'success',
            'campaign' => $campaign->load('recipients'),
            'message' => 'تم إنشاء حملة الواتساب الجماعية بنجاح.',
        ]);
    }

    /**
     * Live campaign status and logs.
     */
    public function status(Campaign $campaign): JsonResponse
    {
        $this->authorizeCampaign($campaign);

        $recipients = $campaign->recipients()
            ->latest('updated_at')
            ->limit(20)
            ->get();

        $progressPercent = $campaign->total_recipients > 0
            ? round((($campaign->sent_count + $campaign->failed_count) / $campaign->total_recipients) * 100)
            : 0;

        return response()->json([
            'campaign' => $campaign,
            'progress_percent' => $progressPercent,
            'recent_recipients' => $recipients,
        ]);
    }

    /**
     * Process single step.
     */
    public function processStep(Campaign $campaign): JsonResponse
    {
        $this->authorizeCampaign($campaign);

        $recipient = $this->campaignService->processNextRecipient($campaign);

        return response()->json([
            'campaign' => $campaign->fresh(),
            'processed_recipient' => $recipient,
            'completed' => $campaign->fresh()->status === 'completed',
        ]);
    }

    /**
     * Start / resume campaign.
     */
    public function start(Campaign $campaign): JsonResponse
    {
        $this->authorizeCampaign($campaign);

        $this->campaignService->launchCampaign($campaign);

        return response()->json([
            'status' => 'success',
            'campaign' => $campaign->fresh(),
        ]);
    }

    /**
     * Pause campaign.
     */
    public function pause(Campaign $campaign): JsonResponse
    {
        $this->authorizeCampaign($campaign);

        $campaign->update(['status' => 'paused']);

        return response()->json([
            'status' => 'success',
            'campaign' => $campaign->fresh(),
        ]);
    }

    /**
     * Delete campaign.
     */
    public function destroy(Campaign $campaign): JsonResponse|RedirectResponse
    {
        $this->authorizeCampaign($campaign);

        $campaign->delete();

        if (request()->wantsJson()) {
            return response()->json(['status' => 'success']);
        }

        return redirect()->route('whatsapp-sender.index')->with('success', 'تم حذف الحملة بنجاح.');
    }

    protected function authorizeCampaign(Campaign $campaign): void
    {
        if (auth('web')->user()?->is_super_admin) {
            return;
        }

        $currentBusinessId = session('current_business_id');
        if ($campaign->business_id !== $currentBusinessId) {
            abort(403, 'غير مصرح بالوصول إلى هذه الحملة.');
        }
    }
}
