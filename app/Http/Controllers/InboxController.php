<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\QuickReply;
use App\Models\TeamMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InboxController extends Controller
{
    /**
     * Check if user/agent is authorized to access conversation.
     */
    protected function authorizeConversation(Request $request, Conversation $conversation): void
    {
        $currentBusinessId = session('current_business_id');
        $user = $request->user('web');
        $teamMember = $request->user('team');

        $convBusiness = Business::find($conversation->business_id);
        if (!$convBusiness) {
            abort(404, 'المتجر غير موجود.');
        }

        $isOwner = $user && $convBusiness->user_id === $user->id;
        $isTeam = $teamMember && $convBusiness->teamMembers()->where('team_members.id', $teamMember->id)->exists();

        if (!$isOwner && !$isTeam) {
            abort(403, 'غير مصرح لك بالوصول لمحادثات هذا المتجر.');
        }

        // مزامنة معرف المتجر النشط في الجلسة إذا كان المالك يتنقل بين متاجره
        if ((int) $currentBusinessId !== $conversation->business_id && $isOwner) {
            session(['current_business_id' => $conversation->business_id]);
        }

        if ($teamMember && $teamMember->role === 'agent') {
            if ($conversation->assigned_to !== $teamMember->id) {
                abort(403, 'هذه المحادثة غير مخصصة لك.');
            }
        }
    }

    /**
     * Show Unified Omnichannel Inbox.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $currentBusinessId = session('current_business_id');

        if (!$currentBusinessId) {
            return redirect()->route('businesses.index');
        }

        $business = Business::findOrFail($currentBusinessId);
        $user = $request->user('web') ?: $request->user('team');
        $isAgent = $request->user('team') && $user->role === 'agent';

        // جلب المحادثات الخاصة بالبيزنس الحالي مع مراعاة عزل الوكلاء
        $conversationsQuery = Conversation::where('business_id', $business->id)
            ->with(['contact', 'latestMessage', 'assignedAgent:id,name,role'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at');

        if ($isAgent) {
            // الوكيل يرى فقط المحادثات المخصصة له
            $conversationsQuery->where('assigned_to', $user->id);
        }

        $conversations = $conversationsQuery->get();

        // جلب أعضاء فريق المتجر لإمكانية إسناد المحادثات (للمسؤول والمشرف والمالك)
        $teamMembers = $business->teamMembers()
            ->where('is_active', true)
            ->select(['team_members.id', 'team_members.name', 'team_members.role'])
            ->get();

        // جلب الردود السريعة الخاصة بالبيزنس
        $quickReplies = QuickReply::where('business_id', $business->id)->get();

        // جلب المتاجر الأخرى التابعة لنفس التاجر للتمكن من تحويل المحادثات بينها
        $otherBusinesses = Business::where('user_id', $business->user_id)
            ->where('id', '!=', $business->id)
            ->select(['id', 'name'])
            ->get();

        return Inertia::render('Inbox/Index', [
            'business' => $business->only(['id', 'name']),
            'otherBusinesses' => $otherBusinesses,
            'conversations' => $conversations,
            'teamMembers' => $teamMembers,
            'quickReplies' => $quickReplies,
            'activeConversationId' => $request->query('conversation_id'),
        ]);
    }

    /**
     * Fetch messages for a specific conversation.
     */
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);

        // تصفير عداد غير المقروء
        $conversation->update(['unread_count' => 0]);

        $messages = $conversation->messages()
            ->with(['senderAgent:id,name', 'attachments'])
            ->oldest()
            ->get();

        return response()->json([
            'conversation' => $conversation->load(['contact', 'assignedAgent:id,name,role']),
            'messages' => $messages,
        ]);
    }

    /**
     * Send a reply message from the agent to the customer.
     */
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);

        $validated = $request->validate([
            'body' => ['required', 'string'],
        ]);

        $user = $request->user('web') ?: $request->user('team');
        $senderId = $request->user('team') ? $user->id : null;

        $message = $conversation->messages()->create([
            'sender_type' => 'agent',
            'sender_id' => $senderId,
            'body' => $validated['body'],
            'type' => 'text',
            'status' => 'sent',
        ]);

        $conversation->update([
            'status' => 'open',
            'last_message_at' => now(),
        ]);
        // إرسال الرسالة إلى المنصة الخارجية المناسبة (تيليجرام، واتساب، فيسبوك، إنستجرام)
        try {
            app(\App\Services\Channels\ChannelManager::class)->sendMessage($conversation, $message);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Outbound message dispatch error: ' . $e->getMessage());
        }
        return response()->json([
            'status' => 'success',
            'message' => $message->load('senderAgent:id,name'),
        ]);
    }

    /**
     * Update conversation status (open / closed / pending).
     */
    public function updateStatus(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);

        $validated = $request->validate([
            'status' => ['required', 'in:open,closed,pending'],
        ]);

        $conversation->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'status' => 'success',
            'conversation' => $conversation->load(['contact', 'assignedAgent:id,name,role']),
        ]);
    }

    /**
     * Assign conversation to a team member.
     */
    public function assignAgent(Request $request, Conversation $conversation): JsonResponse
    {
        $currentBusinessId = session('current_business_id');

        if ($conversation->business_id !== (int) $currentBusinessId) {
            abort(403, 'غير مصرح.');
        }

        $user = $request->user('web') ?: $request->user('team');
        $isAgent = $request->user('team') && $user->role === 'agent';

        // الوكيل لا يستطيع تغيير إسناد المحادثة
        if ($isAgent) {
            abort(403, 'لا يملك الوكيل صلاحية تغيير إسناد المحادثات.');
        }

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer'],
        ]);

        if (!empty($validated['assigned_to'])) {
            // التحقق من أن العضو ينتمي لنفس المتجر (عزل المستأجرين)
            $business = Business::findOrFail($currentBusinessId);
            $belongsToBusiness = $business->teamMembers()
                ->where('team_members.id', $validated['assigned_to'])
                ->exists();

            if (!$belongsToBusiness) {
                abort(422, 'العضو المحدد لا ينتمي لهذا المتجر.');
            }
        }

        $conversation->update([
            'assigned_to' => $validated['assigned_to'],
        ]);

        return response()->json([
            'status' => 'success',
            'conversation' => $conversation->load(['contact', 'assignedAgent:id,name,role']),
        ]);
    }

    /**
     * Transfer conversation to another business owned by the same merchant.
     */
    public function transfer(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);

        $validated = $request->validate([
            'target_business_id' => ['required', 'integer', 'exists:businesses,id'],
        ]);

        $targetBusiness = Business::findOrFail($validated['target_business_id']);
        $currentBusiness = Business::findOrFail(session('current_business_id'));

        // Ensure the target business belongs to the same owner
        if ($targetBusiness->user_id !== $currentBusiness->user_id) {
            abort(403, 'غير مصرح بتحويل المحادثة لمتجر غير تابع لك.');
        }

        // 1. Find or create Contact in target business
        $contact = $conversation->contact;
        $targetContact = Contact::withoutGlobalScopes()->firstOrCreate([
            'business_id' => $targetBusiness->id,
            'platform' => $conversation->platform,
            'platform_sender_id' => $contact->platform_sender_id,
        ], [
            'name' => $contact->name,
            'phone' => $contact->phone,
            'email' => $contact->email,
            'avatar' => $contact->avatar,
        ]);

        // 2. Check if target business already has a conversation for this contact
        $existingTargetConversation = Conversation::withoutGlobalScopes()
            ->where('business_id', $targetBusiness->id)
            ->where('contact_id', $targetContact->id)
            ->where('platform', $conversation->platform)
            ->first();

        if ($existingTargetConversation) {
            $existingTargetConversation->update([
                'status' => 'open',
                'last_message_at' => now(),
            ]);

            $conversation->update([
                'status' => 'closed',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'تم نقل وتفعيل المحادثة في متجر ' . $targetBusiness->name . ' بنجاح.',
                'target_conversation_id' => $existingTargetConversation->id,
            ]);
        }

        // 3. Move the current conversation if not already in target business
        $conversation->update([
            'business_id' => $targetBusiness->id,
            'contact_id' => $targetContact->id,
            'assigned_to' => null,
            'status' => 'open',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحويل المحادثة إلى متجر ' . $targetBusiness->name . ' بنجاح.',
            'target_conversation_id' => $conversation->id,
        ]);
    }
}
