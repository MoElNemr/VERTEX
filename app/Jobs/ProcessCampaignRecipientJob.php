<?php

namespace App\Jobs;

use App\Models\CampaignRecipient;
use App\Services\CampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Multitenancy\Jobs\NotTenantAware;

class ProcessCampaignRecipientJob implements NotTenantAware, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(
        public int $recipientId
    ) {}

    public function handle(CampaignService $campaignService): void
    {
        $recipient = CampaignRecipient::find($this->recipientId);

        if (! $recipient || $recipient->status !== 'pending') {
            return;
        }

        $campaignService->sendSingleRecipient($recipient);
    }
}
