<?php

namespace App\Services\Channels;

use App\Models\Business;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

interface ChannelServiceInterface
{
    /**
     * Send an outbound message to the external platform.
     */
    public function sendMessage(Conversation $conversation, Message $message): array;

    /**
     * Handle incoming webhook requests from the external platform.
     */
    public function handleWebhook(Business $business, Request $request): array;
}
