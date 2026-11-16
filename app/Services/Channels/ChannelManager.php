<?php

namespace App\Services\Channels;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\MessageQuotaService;
use InvalidArgumentException;

class ChannelManager
{
    protected array $drivers = [];

    public function __construct(
        protected TelegramChannelService $telegramService,
        protected MetaChannelService $metaService,
        protected WhatsAppChannelService $whatsappService,
        protected TikTokChannelService $tiktokService,
        protected MessageQuotaService $quotaService
    ) {
        $this->drivers = [
            'telegram' => $this->telegramService,
            'facebook' => $this->metaService,
            'instagram' => $this->metaService,
            'whatsapp' => $this->whatsappService,
            'tiktok' => $this->tiktokService,
        ];
    }

    /**
     * Get the driver instance for a given platform.
     */
    public function driver(string $platform): ChannelServiceInterface
    {
        if (! isset($this->drivers[$platform])) {
            throw new InvalidArgumentException("Unsupported channel driver: {$platform}");
        }

        return $this->drivers[$platform];
    }

    /**
     * Dispatch an outbound message to its platform provider and handle quota consumption.
     */
    public function sendMessage(Conversation $conversation, Message $message): array
    {
        $business = $conversation->business;
        $owner = $business?->user;
        $isCampaign = ! empty($message->metadata['campaign_id']);

        // For non-campaign outbound messages (inbox manual reply or auto-reply), verify quota upfront
        if (! $isCampaign && $owner && ! $this->quotaService->canSend($owner, 1)) {
            $message->update(['status' => 'failed']);
            throw new \RuntimeException('كوتة الرسائل ورصيد المحفظة نفد. يرجى ترقية الباقة أو شحن المحفظة.');
        }

        $result = $this->driver($conversation->platform)->sendMessage($conversation, $message);

        // Deduct quota strictly AFTER confirmed delivery/sent status for non-campaign messages
        $status = $result['status'] ?? 'sent';
        if (! $isCampaign && $owner && in_array($status, ['sent', 'delivered'], true)) {
            $this->quotaService->consumeQuota(
                $owner,
                $business->id,
                $conversation->platform,
                1,
                "إرسال رسالة مباشرة إلى {$conversation->contact?->name}"
            );
        }

        return $result;
    }

    public function telegram(): TelegramChannelService
    {
        return $this->telegramService;
    }

    public function meta(): MetaChannelService
    {
        return $this->metaService;
    }

    public function whatsapp(): WhatsAppChannelService
    {
        return $this->whatsappService;
    }

    public function tiktok(): TikTokChannelService
    {
        return $this->tiktokService;
    }
}
