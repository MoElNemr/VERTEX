<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\QuickReply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InboxController extends Controller
{
    /**
     * Show Unified Omnichannel Inbox.
     */
    public function index(Request $request): Response
    {
        $currentBusinessId = session('current_business_id');

        if (!$currentBusinessId) {
            return redirect()->route('businesses.index');
        }

        $business = Business::findOrFail($currentBusinessId);

        // جلب المحادثات الخاصة بالبيزنس الحالي
        $conversations = Conversation::where('business_id', $business->id)
            ->with(['contact', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();

        // جلب الردود السريعة الخاصة بالبيزنس
        $quickReplies = QuickReply::where('business_id', $business->id)->get();

        return Inertia::render('Inbox/Index', [
            'business' => $business->only(['id', 'name']),
            'conversations' => $conversations,
            'quickReplies' => $quickReplies,
            'activeConversationId' => $request->query('conversation_id'),
        ]);
    }

    /**
     * Fetch messages for a specific conversation.
     */
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $currentBusinessId = session('current_business_id');

        if ($conversation->business_id !== (int) $currentBusinessId) {
            abort(403, 'غير مصرح لك بمشاهدة هذه المحادثة.');
        }

        // تصفير عداد غير المقروء
        $conversation->update(['unread_count' => 0]);

        $messages = $conversation->messages()
            ->with(['senderAgent:id,name', 'attachments'])
            ->oldest()
            ->get();

        return response()->json([
            'conversation' => $conversation->load('contact'),
            'messages' => $messages,
        ]);
    }

    /**
     * Send a reply message from the agent to the customer.
     */
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $currentBusinessId = session('current_business_id');

        if ($conversation->business_id !== (int) $currentBusinessId) {
            abort(403, 'غير مصرح لك بإرسال رسالة في هذه المحادثة.');
        }

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
            'last_message_at' => now(),
        ]);

        // هنا سيتم استدعاء خدمة الإرسال إلى المنصة الحقيقية (WhatsApp, Telegram, etc.)
        // $this->dispatchToPlatform($conversation, $message);

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
        $currentBusinessId = session('current_business_id');

        if ($conversation->business_id !== (int) $currentBusinessId) {
            abort(403, 'غير مصرح لك بتحديث هذه المحادثة.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:open,closed,pending'],
        ]);

        $conversation->update([
            'status' => $validated['status'],
        ]);

        return response()->json(['status' => 'success', 'conversation' => $conversation]);
    }
}
