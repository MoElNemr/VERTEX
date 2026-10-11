<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * لوحة التحكم والإحصائيات والتحليلات لمتجر التاجر
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

        // Metrics
        $conversations = Conversation::where('business_id', $currentBusinessId);
        $totalConversations = (clone $conversations)->count();
        $openCount = (clone $conversations)->where('status', 'open')->count();
        $closedCount = (clone $conversations)->where('status', 'closed')->count();
        $pendingCount = (clone $conversations)->where('status', 'pending')->count();

        $messagesToday = Message::whereHas('conversation', fn ($q) => $q->where('business_id', $currentBusinessId))
            ->whereDate('created_at', today())->count();
        $messagesThisWeek = Message::whereHas('conversation', fn ($q) => $q->where('business_id', $currentBusinessId))
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

        // Per platform breakdown
        $byPlatform = Conversation::where('business_id', $currentBusinessId)
            ->selectRaw('platform, count(*) as count')
            ->groupBy('platform')
            ->get()
            ->pluck('count', 'platform');

        // Avg first response time (minutes)
        $avgResponseMinutes = $this->calcAvgResponseTime((int) $currentBusinessId);

        // Team performance
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
                'total_conversations' => $totalConversations,
                'open' => $openCount,
                'closed' => $closedCount,
                'pending' => $pendingCount,
                'messages_today' => $messagesToday,
                'messages_this_week' => $messagesThisWeek,
                'avg_response_minutes' => $avgResponseMinutes,
                'by_platform' => $byPlatform,
            ],
            'teamPerformance' => $teamPerformance,
        ]);
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
                        $times[] = $firstAgent->created_at->diffInMinutes($firstContact->created_at);
                    }
                }
            });

        return count($times) > 0 ? round(array_sum($times) / count($times), 1) : 0.0;
    }
}
