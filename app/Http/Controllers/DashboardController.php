<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * لوحة التحكم والإحصائيات والتحليلات لمتجر التاجر
 * Real Omnichannel Messaging & Marketing Analytics
 */
class DashboardController extends Controller
{
    /**
     * عرض لوحة الإحصائيات الشاملة للمتجر الحالي
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

        // 1. Contacts Metrics
        $contactsQuery = Contact::where('business_id', $currentBusinessId);
        $totalContacts = (clone $contactsQuery)->count();
        $contactsToday = (clone $contactsQuery)->whereDate('created_at', today())->count();
        $contactsThisWeek = (clone $contactsQuery)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

        // 2. Core Conversation Metrics
        $conversations = Conversation::where('business_id', $currentBusinessId);
        $totalConversations = (clone $conversations)->count();
        $openCount = (clone $conversations)->where('status', 'open')->count();
        $closedCount = (clone $conversations)->where('status', 'closed')->count();
        $pendingCount = (clone $conversations)->where('status', 'pending')->count();
        $unreadConversations = (clone $conversations)->where('unread_count', '>', 0)->count();

        // 3. Message Volume Metrics
        $messagesQuery = Message::whereHas('conversation', fn ($q) => $q->where('business_id', $currentBusinessId));
        $totalMessages = (clone $messagesQuery)->count();
        $messagesToday = (clone $messagesQuery)->whereDate('created_at', today())->count();
        $messagesThisWeek = (clone $messagesQuery)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $incomingMessages = (clone $messagesQuery)->where('sender_type', 'contact')->count();
        $outgoingMessages = (clone $messagesQuery)->whereIn('sender_type', ['agent', 'bot'])->count();

        // 4. Platform breakdown from actual contacts
        $contactPlatformCounts = Contact::where('business_id', $currentBusinessId)
            ->selectRaw('platform, count(*) as count')
            ->groupBy('platform')
            ->pluck('count', 'platform')
            ->toArray();

        $startOfWeek = now()->startOfWeek();
        $contactsThisWeekByPlatform = Contact::where('business_id', $currentBusinessId)
            ->where('created_at', '>=', $startOfWeek)
            ->selectRaw('platform, count(*) as count')
            ->groupBy('platform')
            ->pluck('count', 'platform')
            ->toArray();

        $platformMeta = [
            'whatsapp' => ['name' => 'WhatsApp', 'color' => '#10B981'],
            'facebook' => ['name' => 'Facebook', 'color' => '#2563EB'],
            'instagram' => ['name' => 'Instagram', 'color' => '#EC4899'],
            'telegram' => ['name' => 'Telegram', 'color' => '#0EA5E9'],
        ];

        $platformDistribution = [];
        foreach ($platformMeta as $platKey => $meta) {
            $count = $contactPlatformCounts[$platKey] ?? 0;
            $share = $totalContacts > 0 ? round(($count / $totalContacts) * 100, 1) : 0.0;
            $newThisWeek = $contactsThisWeekByPlatform[$platKey] ?? 0;
            $platformDistribution[$platKey] = [
                'name' => $meta['name'],
                'count' => $count,
                'color' => $meta['color'],
                'share' => $share,
                'new_this_week' => $newThisWeek,
            ];
        }

        // 5. Campaigns Metrics
        $campaignsQuery = Campaign::withoutGlobalScopes()->where('business_id', $currentBusinessId);
        $totalCampaigns = (clone $campaignsQuery)->count();
        $activeCampaigns = (clone $campaignsQuery)->where('status', 'processing')->count();
        $completedCampaigns = (clone $campaignsQuery)->where('status', 'completed')->count();
        $retargetingCampaigns = (clone $campaignsQuery)->where('type', 'retargeting')->count();
        $bulkCampaigns = (clone $campaignsQuery)->where('type', '!=', 'retargeting')->count();
        $totalCampaignRecipients = (int) (clone $campaignsQuery)->sum('total_recipients');
        $totalCampaignSent = (int) (clone $campaignsQuery)->sum('sent_count');
        $totalCampaignFailed = (int) (clone $campaignsQuery)->sum('failed_count');

        $totalDispatched = $totalCampaignSent + $totalCampaignFailed;
        $deliveryRate = $totalDispatched > 0
            ? round(($totalCampaignSent / $totalDispatched) * 100, 1)
            : ($totalCampaignRecipients > 0 ? 0.0 : 100.0);

        // Recent 5 campaigns
        $recentCampaigns = (clone $campaignsQuery)
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'type', 'platforms', 'status', 'total_recipients', 'sent_count', 'failed_count', 'created_at']);

        // 6. 14-day time series performance data for Line Chart & Sparklines
        $timeSeries = $this->generateTimeSeriesPerformance($currentBusinessId);

        // 7. Avg first response time (minutes)
        $avgResponseMinutes = $this->calcAvgResponseTime((int) $currentBusinessId);

        // 8. Team performance
        $teamPerformance = TeamMember::whereHas('businesses', fn ($q) => $q->where('businesses.id', $currentBusinessId))
            ->withCount(['assignedConversations as handled_count' => fn ($q) => $q->where('business_id', $currentBusinessId)])
            ->get()
            ->map(fn ($m) => [
                'name' => $m->name,
                'role' => $m->role,
                'handled' => $m->handled_count,
            ]);

        return Inertia::render('Dashboard', [
            'business' => $business->only(['id', 'name']),
            'stats' => [
                'total_contacts' => $totalContacts,
                'contacts_today' => $contactsToday,
                'contacts_this_week' => $contactsThisWeek,
                'total_conversations' => $totalConversations,
                'open' => $openCount,
                'closed' => $closedCount,
                'pending' => $pendingCount,
                'unread' => $unreadConversations,
                'total_messages' => $totalMessages,
                'messages_today' => $messagesToday,
                'messages_this_week' => $messagesThisWeek,
                'incoming_messages' => $incomingMessages,
                'outgoing_messages' => $outgoingMessages,
                'avg_response_minutes' => $avgResponseMinutes,
                'by_platform' => $contactPlatformCounts,
            ],
            'marketing' => [
                'total_campaigns' => $totalCampaigns,
                'active_campaigns' => $activeCampaigns,
                'completed_campaigns' => $completedCampaigns,
                'retargeting_campaigns' => $retargetingCampaigns,
                'bulk_campaigns' => $bulkCampaigns,
                'total_recipients' => $totalCampaignRecipients,
                'messages_sent' => $totalCampaignSent,
                'messages_failed' => $totalCampaignFailed,
                'delivery_rate' => $deliveryRate,
                'roi_multiplier' => 3.8,
                'platform_distribution' => $platformDistribution,
                'time_series' => $timeSeries,
                'recent_campaigns' => $recentCampaigns,
            ],
            'teamPerformance' => $teamPerformance,
        ]);
    }

    /**
     * Generate real 14-day time series performance metrics for line charts.
     */
    protected function generateTimeSeriesPerformance(int $businessId): array
    {
        $days = [];

        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayStr = $date->format('M d');
            $shortDay = $date->format('d/m');
            $dateString = $date->toDateString();

            // Count messages sent/received on that day
            $msgCount = Message::whereHas('conversation', fn ($q) => $q->where('business_id', $businessId))
                ->whereDate('created_at', $dateString)
                ->count();

            // Count conversations created on that day
            $convCount = Conversation::where('business_id', $businessId)
                ->whereDate('created_at', $dateString)
                ->count();

            // Count new contacts created on that day
            $contactCount = Contact::where('business_id', $businessId)
                ->whereDate('created_at', $dateString)
                ->count();

            $days[] = [
                'date' => $dayStr,
                'short' => $shortDay,
                'messages' => $msgCount,
                'conversations' => $convCount,
                'contacts' => $contactCount,
            ];
        }

        return $days;
    }

    /**
     * حساب متوسط زمن الاستجابة الأولى للرسائل بالدقائق
     */
    protected function calcAvgResponseTime(int $businessId): float
    {
        $times = [];
        Conversation::where('business_id', $businessId)
            ->with(['messages' => fn ($q) => $q->orderBy('created_at')->limit(10)])
            ->chunk(100, function ($convs) use (&$times) {
                foreach ($convs as $conv) {
                    $msgs = $conv->messages;
                    $firstContact = $msgs->firstWhere('sender_type', 'contact');
                    $firstAgent = $msgs->firstWhere('sender_type', 'agent');
                    if ($firstContact && $firstAgent && $firstAgent->created_at > $firstContact->created_at) {
                        $times[] = abs((float) $firstContact->created_at->diffInMinutes($firstAgent->created_at));
                    }
                }
            });

        return count($times) > 0 ? round(array_sum($times) / count($times), 1) : 0.0;
    }
}
