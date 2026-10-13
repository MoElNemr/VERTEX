<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\InstagramAccount;
use App\Services\CampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class InstagramSenderController extends Controller
{
    public function __construct(
        protected CampaignService $campaignService
    ) {}

    /**
     * Display Instagram bulk sender view and campaign logs.
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

        $instagramAccount = InstagramAccount::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->first();

        $campaigns = Campaign::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('type', 'instagram_bulk')
            ->latest()
            ->paginate(15);

        $totalSent = Campaign::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('type', 'instagram_bulk')
            ->sum('sent_count');

        $totalCampaigns = Campaign::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('type', 'instagram_bulk')
            ->count();

        $activeCampaigns = Campaign::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('type', 'instagram_bulk')
            ->where('status', 'processing')
            ->count();

        $existingContactsCount = Contact::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('platform', 'instagram')
            ->count();

        return Inertia::render('InstagramSender/Index', [
            'business' => $business->only(['id', 'name']),
            'instagramAccount' => $instagramAccount ? [
                'status' => $instagramAccount->status,
                'username' => $instagramAccount->username,
                'instagram_business_id' => $instagramAccount->instagram_business_id,
            ] : null,
            'campaigns' => $campaigns,
            'existingContactsCount' => $existingContactsCount,
            'stats' => [
                'total_campaigns' => $totalCampaigns,
                'active_campaigns' => $activeCampaigns,
                'total_messages_sent' => (int) $totalSent,
            ],
        ]);
    }

    /**
     * Parse and preview uploaded CSV file for Instagram.
     */
    public function previewCsv(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $filename = $file->getClientOriginalName();

        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $rawHeader = fgetcsv($handle);
            if (!$rawHeader) {
                fclose($handle);
                return response()->json(['error' => 'ملف CSV فارغ أو غير صالح.'], 422);
            }

            $rawHeader[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $rawHeader[0]);

            $nameIdx = -1;
            $idIdx = -1;

            foreach ($rawHeader as $idx => $col) {
                $cleanCol = strtolower(trim($col));
                if (in_array($cleanCol, ['name', 'full_name', 'اسم', 'الاسم', 'customer', 'customer_name'])) {
                    $nameIdx = $idx;
                }
                if (in_array($cleanCol, ['igsid', 'recipient_id', 'id', 'username', 'user_id', 'instagram_id', 'حساب'])) {
                    $idIdx = $idx;
                }
            }

            if ($idIdx === -1) {
                $idIdx = count($rawHeader) >= 2 ? 1 : 0;
                $nameIdx = count($rawHeader) >= 2 ? 0 : -1;
            }

            $validCount = 0;
            $invalidCount = 0;
            $previewRows = [];
            $allRows = [];

            while (($data = fgetcsv($handle)) !== false) {
                if (empty(array_filter($data))) {
                    continue;
                }

                $rawId = trim($data[$idIdx] ?? '');
                $rawName = $nameIdx >= 0 ? trim($data[$nameIdx] ?? '') : '';

                $isValid = strlen($rawId) >= 3;

                if ($isValid) {
                    $validCount++;
                    $rowItem = [
                        'recipient_id' => $rawId,
                        'name' => $rawName ?: ('@' . ltrim($rawId, '@')),
                    ];
                    $allRows[] = $rowItem;
                    if (count($previewRows) < 5) {
                        $previewRows[] = $rowItem;
                    }
                } else {
                    $invalidCount++;
                }
            }
            fclose($handle);

            return response()->json([
                'filename' => $filename,
                'total_rows' => $validCount + $invalidCount,
                'valid_count' => $validCount,
                'invalid_count' => $invalidCount,
                'preview_rows' => $previewRows,
                'recipients' => $allRows,
            ]);
        }

        return response()->json(['error' => 'تعذر قراءة ملف CSV.'], 422);
    }

    /**
     * Download sample CSV template for Instagram.
     */
    public function downloadTemplate(): HttpResponse
    {
        $csvContent = "\xEF\xBB\xBF";
        $csvContent .= "name,igsid\n";
        $csvContent .= "ياسمين صبري,1784140510294\n";
        $csvContent .= "عمر خالد,1784140598765\n";

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="instagram_contacts_template.csv"',
        ]);
    }

    /**
     * Create and start bulk Instagram campaign.
     */
    public function send(Request $request): JsonResponse
    {
        $currentBusinessId = session('current_business_id');
        $business = Business::findOrFail($currentBusinessId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'target_audience' => ['nullable', 'in:csv,existing_contacts'],
            'recipients' => ['nullable', 'array'],
            'recipients.*.recipient_id' => ['nullable', 'string'],
            'recipients.*.name' => ['nullable', 'string'],
            'interval_seconds' => ['nullable', 'integer', 'min:2', 'max:300'],
            'jitter_seconds' => ['nullable', 'integer', 'min:0', 'max:60'],
            'save_to_contacts' => ['nullable', 'boolean'],
            'auto_start' => ['nullable', 'boolean'],
            'filename' => ['nullable', 'string'],
        ]);

        $campaign = $this->campaignService->createChannelBulkCampaign($business, 'instagram', $validated);

        if (!empty($validated['auto_start']) && $campaign->total_recipients > 0) {
            $this->campaignService->launchCampaign($campaign);
        }

        return response()->json([
            'status' => 'success',
            'campaign' => $campaign->load('recipients'),
            'message' => 'تم إنشاء حملة إنستجرام الجماعية بنجاح.',
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
            'id' => $campaign->id,
            'name' => $campaign->name,
            'status' => $campaign->status,
            'total_recipients' => $campaign->total_recipients,
            'sent_count' => $campaign->sent_count,
            'failed_count' => $campaign->failed_count,
            'progress_percent' => min(100, $progressPercent),
            'recent_recipients' => $recipients,
        ]);
    }

    /**
     * Process next recipient.
     */
    public function processStep(Campaign $campaign): JsonResponse
    {
        $this->authorizeCampaign($campaign);

        $recipient = $this->campaignService->processNextRecipient($campaign);

        return response()->json([
            'processed' => (bool) $recipient,
            'recipient' => $recipient,
            'campaign_status' => $campaign->fresh()->status,
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
            'campaign_status' => $campaign->fresh()->status,
            'message' => 'تم تشغيل الحملة بنجاح.',
        ]);
    }

    /**
     * Pause running campaign.
     */
    public function pause(Campaign $campaign): JsonResponse
    {
        $this->authorizeCampaign($campaign);

        $campaign->update(['status' => 'paused']);

        return response()->json([
            'status' => 'success',
            'campaign_status' => 'paused',
            'message' => 'تم إيقاف الحملة مؤقتاً.',
        ]);
    }

    /**
     * Delete campaign.
     */
    public function destroy(Campaign $campaign): JsonResponse|RedirectResponse
    {
        $this->authorizeCampaign($campaign);

        $campaign->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف الحملة بنجاح.',
        ]);
    }

    protected function authorizeCampaign(Campaign $campaign): void
    {
        if (auth('web')->user()?->is_super_admin) {
            return;
        }

        $currentBusinessId = session('current_business_id');
        if ($campaign->business_id !== (int) $currentBusinessId) {
            abort(403, 'غير مصرح لك بإدارة هذه الحملة.');
        }
    }
}
