<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Contact;
use App\Services\CampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RetargetingController extends Controller
{
    public function __construct(
        protected CampaignService $campaignService
    ) {}

    /**
     * Display retargeting campaigns and audience overview.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $currentBusinessId = session('current_business_id');

        if (! $currentBusinessId) {
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

        // Platform audience counts
        $platformAudience = Contact::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->selectRaw('platform, count(*) as count')
            ->groupBy('platform')
            ->pluck('count', 'platform')
            ->toArray();

        // Available contact tags
        $allTags = Contact::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Campaigns list
        $campaigns = Campaign::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('type', 'retargeting')
            ->withCount(['recipients as pending_count' => fn ($q) => $q->where('status', 'pending')])
            ->latest()
            ->paginate(15);

        // Stats summary
        $totalSent = Campaign::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('type', 'retargeting')
            ->sum('sent_count');

        $totalCampaigns = Campaign::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('type', 'retargeting')
            ->count();

        $activeCampaigns = Campaign::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('type', 'retargeting')
            ->where('status', 'processing')
            ->count();

        return Inertia::render('Retargeting/Index', [
            'business' => $business->only(['id', 'name']),
            'campaigns' => $campaigns,
            'audience' => [
                'total' => array_sum($platformAudience),
                'by_platform' => [
                    'whatsapp' => $platformAudience['whatsapp'] ?? 0,
                    'facebook' => $platformAudience['facebook'] ?? 0,
                    'instagram' => $platformAudience['instagram'] ?? 0,
                    'telegram' => $platformAudience['telegram'] ?? 0,
                ],
                'available_tags' => $allTags,
            ],
            'stats' => [
                'total_campaigns' => $totalCampaigns,
                'active_campaigns' => $activeCampaigns,
                'total_messages_sent' => (int) $totalSent,
            ],
        ]);
    }

    /**
     * Store and optionally start a new retargeting campaign.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $currentBusinessId = session('current_business_id');
        $business = Business::findOrFail($currentBusinessId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => ['string', 'in:whatsapp,facebook,instagram,telegram,all'],
            'message' => ['required', 'string'],
            'platform_messages' => ['nullable', 'array'],
            'interval_seconds' => ['nullable', 'integer', 'min:2', 'max:300'],
            'jitter_seconds' => ['nullable', 'integer', 'min:0', 'max:60'],
            'tags' => ['nullable', 'array'],
            'auto_start' => ['nullable', 'boolean'],
        ]);

        $campaign = $this->campaignService->createRetargetingCampaign($business, $validated);

        if (! empty($validated['auto_start']) && $campaign->total_recipients > 0) {
            $this->campaignService->launchCampaign($campaign);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'campaign' => $campaign,
            ]);
        }

        return redirect()->route('retargeting.index')->with('success', 'تم إنشاء حملة إعادة الاستهداف بنجاح.');
    }

    /**
     * Get real-time campaign status and recipients progress.
     */
    public function show(Campaign $campaign): JsonResponse
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
     * Start / resume campaign.
     */
    public function start(Campaign $campaign): JsonResponse
    {
        $this->authorizeCampaign($campaign);

        $this->campaignService->launchCampaign($campaign);

        return response()->json([
            'status' => 'success',
            'message' => 'تم بدء إرسال الحملة بنجاح.',
            'campaign' => $campaign->fresh(),
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
            'message' => 'تم إيقاف الحملة مؤقتاً.',
            'campaign' => $campaign->fresh(),
        ]);
    }

    /**
     * Execute single step / next recipient (client-driven or background step).
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
     * Delete campaign.
     */
    public function destroy(Campaign $campaign): RedirectResponse|JsonResponse
    {
        $this->authorizeCampaign($campaign);

        $campaign->delete();

        if (request()->wantsJson()) {
            return response()->json(['status' => 'success']);
        }

        return redirect()->route('retargeting.index')->with('success', 'تم حذف الحملة بنجاح.');
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
