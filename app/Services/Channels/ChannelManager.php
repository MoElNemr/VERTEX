<?php

namespace App\Services\Channels;

use App\Models\Conversation;
use App\Models\Message;
use InvalidArgumentException;

class ChannelManager
{
    protected array $drivers = [];

    public function __construct(
        protected TelegramChannelService $telegramService,
        protected MetaChannelService $metaService,
        protected WhatsAppChannelService $whatsappService
    ) {
        $this->drivers = [
            'telegram' => $this->telegramService,
            'facebook' => $this->metaService,
            'instagram' => $this->metaService,
            'whatsapp' => $this->whatsappService,
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
     * Dispatch an outbound message to its platform provider.
     */
    public function sendMessage(Conversation $conversation, Message $message): array
    {
        return $this->driver($conversation->platform)->sendMessage($conversation, $message);
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
}
