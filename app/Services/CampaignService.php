<?php

namespace App\Services;

use App\Jobs\ProcessCampaignRecipientJob;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Channels\ChannelManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignService
{
    public function __construct(
        protected ChannelManager $channelManager,
        protected WalletService $walletService
    ) {}

    /**
     * Create a retargeting campaign for existing customers.
     */
    public function createRetargetingCampaign(Business $business, array $data): Campaign
    {
        return DB::transaction(function () use ($business, $data) {
            $platforms = $data['platforms'] ?? ['whatsapp', 'facebook', 'instagram', 'telegram'];
            if (in_array('all', $platforms)) {
                $platforms = ['whatsapp', 'facebook', 'instagram', 'telegram'];
            }

            $campaign = Campaign::create([
                'business_id' => $business->id,
                'name' => $data['name'] ?? ('حملة إعادة استهداف - '.now()->format('Y/m/d H:i')),
                'type' => 'retargeting',
                'platforms' => $platforms,
                'message' => $data['message'] ?? '',
                'platform_messages' => $data['platform_messages'] ?? [],
                'status' => 'draft',
                'interval_seconds' => max(2, (int) ($data['interval_seconds'] ?? 10)),
                'jitter_seconds' => max(0, (int) ($data['jitter_seconds'] ?? 2)),
                'total_recipients' => 0,
                'metadata' => [
                    'tags_filter' => $data['tags'] ?? [],
                    'audience_type' => $data['audience_type'] ?? 'all',
                ],
            ]);

            // Query matching contacts
            $contactsQuery = Contact::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->whereIn('platform', $platforms);

            if (! empty($data['tags']) && is_array($data['tags'])) {
                $contactsQuery->where(function ($q) use ($data) {
                    foreach ($data['tags'] as $tag) {
                        $q->orWhereJsonContains('tags', $tag);
                    }
                });
            }

            $contacts = $contactsQuery->get();

            $recipientsData = [];
            $now = now();

            foreach ($contacts as $contact) {
                // Determine message body for this contact
                $body = $data['platform_messages'][$contact->platform] ?? $data['message'] ?? '';
                $body = $this->interpolateMessage($body, [
                    'name' => $contact->name ?: 'عميلنا العزيز',
                    'phone' => $contact->phone ?: '',
                    'platform' => $this->getPlatformLabel($contact->platform),
                ]);

                $recipientsData[] = [
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id,
                    'platform' => $contact->platform,
                    'recipient_id' => $contact->platform_sender_id ?: ($contact->phone ?: (string) $contact->id),
                    'recipient_name' => $contact->name,
                    'message_body' => $body,
                    'custom_data' => json_encode(['email' => $contact->email]),
                    'status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($recipientsData)) {
                // Bulk insert in chunks of 500
                foreach (array_chunk($recipientsData, 500) as $chunk) {
                    CampaignRecipient::insert($chunk);
                }
            }

            $campaign->update([
                'total_recipients' => count($recipientsData),
            ]);

            return $campaign;
        });
    }

    /**
     * Create a WhatsApp bulk campaign from uploaded numbers/names.
     */
    public function createWhatsAppBulkCampaign(Business $business, array $data): Campaign
    {
        return DB::transaction(function () use ($business, $data) {
            $rawRecipients = $data['recipients'] ?? [];
            $saveToContacts = ! empty($data['save_to_contacts']);

            $campaign = Campaign::create([
                'business_id' => $business->id,
                'name' => $data['name'] ?? ('إرسال واتساب جماعي - '.now()->format('Y/m/d H:i')),
                'type' => 'whatsapp_bulk',
                'platforms' => ['whatsapp'],
                'message' => $data['message'] ?? '',
                'platform_messages' => ['whatsapp' => $data['message'] ?? ''],
                'status' => 'draft',
                'interval_seconds' => max(2, (int) ($data['interval_seconds'] ?? 10)),
                'jitter_seconds' => max(0, (int) ($data['jitter_seconds'] ?? 2)),
                'total_recipients' => 0,
                'metadata' => [
                    'csv_filename' => $data['filename'] ?? 'uploaded_contacts.csv',
                    'save_to_contacts' => $saveToContacts,
                ],
            ]);

            $recipientsData = [];
            $now = now();

            foreach ($rawRecipients as $row) {
                $phone = $this->cleanPhoneNumber($row['phone'] ?? $row['number'] ?? '');
                if (! $phone) {
                    continue;
                }

                $name = trim($row['name'] ?? $row['customer_name'] ?? '');

                // Interpolate message
                $body = $this->interpolateMessage($data['message'] ?? '', [
                    'name' => $name ?: 'عميلنا العزيز',
                    'phone' => $phone,
                    'platform' => 'واتساب',
                ]);

                $contactId = null;
                if ($saveToContacts) {
                    $contact = Contact::withoutGlobalScopes()->updateOrCreate(
                        [
                            'business_id' => $business->id,
                            'platform' => 'whatsapp',
                            'platform_sender_id' => $phone,
                        ],
                        [
                            'name' => $name ?: null,
                            'phone' => $phone,
                        ]
                    );
                    $contactId = $contact->id;
                }

                $recipientsData[] = [
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contactId,
                    'platform' => 'whatsapp',
                    'recipient_id' => $phone,
                    'recipient_name' => $name,
                    'message_body' => $body,
                    'custom_data' => json_encode($row),
                    'status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($recipientsData)) {
                foreach (array_chunk($recipientsData, 500) as $chunk) {
                    CampaignRecipient::insert($chunk);
                }
            }

            $campaign->update([
                'total_recipients' => count($recipientsData),
            ]);

            return $campaign;
        });
    }

    /**
     * Create a bulk campaign for any channel (Telegram, Facebook, Instagram, or WhatsApp).
     */
    public function createChannelBulkCampaign(Business $business, string $platform, array $data): Campaign
    {
        return DB::transaction(function () use ($business, $platform, $data) {
            $rawRecipients = $data['recipients'] ?? [];
            $targetAudience = $data['target_audience'] ?? 'csv';
            $saveToContacts = ! empty($data['save_to_contacts']);
            $platformLabel = $this->getPlatformLabel($platform);

            $campaign = Campaign::create([
                'business_id' => $business->id,
                'name' => $data['name'] ?? ("إرسال {$platformLabel} جماعي - ".now()->format('Y/m/d H:i')),
                'type' => "{$platform}_bulk",
                'platforms' => [$platform],
                'message' => $data['message'] ?? '',
                'platform_messages' => [$platform => $data['message'] ?? ''],
                'status' => 'draft',
                'interval_seconds' => max(2, (int) ($data['interval_seconds'] ?? 10)),
                'jitter_seconds' => max(0, (int) ($data['jitter_seconds'] ?? 2)),
                'total_recipients' => 0,
                'metadata' => [
                    'csv_filename' => $data['filename'] ?? null,
                    'target_audience' => $targetAudience,
                    'save_to_contacts' => $saveToContacts,
                ],
            ]);

            $recipientsData = [];
            $now = now();

            if ($targetAudience === 'existing_contacts') {
                $contacts = Contact::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->where('platform', $platform)
                    ->get();

                foreach ($contacts as $contact) {
                    $body = $this->interpolateMessage($data['message'] ?? '', [
                        'name' => $contact->name ?: 'عميلنا العزيز',
                        'platform' => $platformLabel,
                    ]);

                    $recipientsData[] = [
                        'campaign_id' => $campaign->id,
                        'contact_id' => $contact->id,
                        'platform' => $platform,
                        'recipient_id' => $contact->platform_sender_id ?: (string) $contact->id,
                        'recipient_name' => $contact->name,
                        'message_body' => $body,
                        'custom_data' => null,
                        'status' => 'pending',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            } else {
                foreach ($rawRecipients as $row) {
                    $rawId = $row['recipient_id'] ?? $row['id'] ?? $row['phone'] ?? $row['username'] ?? $row['chat_id'] ?? '';
                    if ($platform === 'whatsapp') {
                        $rawId = $this->cleanPhoneNumber($rawId);
                    } else {
                        $rawId = trim((string) $rawId);
                    }

                    if (! $rawId) {
                        continue;
                    }

                    $name = trim($row['name'] ?? $row['customer_name'] ?? '');

                    $body = $this->interpolateMessage($data['message'] ?? '', [
                        'name' => $name ?: 'عميلنا العزيز',
                        'platform' => $platformLabel,
                    ]);

                    $contactId = null;
                    if ($saveToContacts) {
                        $contact = Contact::withoutGlobalScopes()->updateOrCreate(
                            [
                                'business_id' => $business->id,
                                'platform' => $platform,
                                'platform_sender_id' => $rawId,
                            ],
                            [
                                'name' => $name ?: null,
                                'phone' => $platform === 'whatsapp' ? $rawId : null,
                            ]
                        );
                        $contactId = $contact->id;
                    }

                    $recipientsData[] = [
                        'campaign_id' => $campaign->id,
                        'contact_id' => $contactId,
                        'platform' => $platform,
                        'recipient_id' => $rawId,
                        'recipient_name' => $name,
                        'message_body' => $body,
                        'custom_data' => json_encode($row),
                        'status' => 'pending',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (! empty($recipientsData)) {
                foreach (array_chunk($recipientsData, 500) as $chunk) {
                    CampaignRecipient::insert($chunk);
                }
            }

            $campaign->update([
                'total_recipients' => count($recipientsData),
            ]);

            return $campaign;
        });
    }

    /**
     * Start/Launch campaign and schedule queued jobs.
     */
    public function launchCampaign(Campaign $campaign): void
    {
        // For bulk campaigns (WhatsApp, Telegram, Facebook, Instagram), verify wallet credits
        if ($campaign->type !== 'retargeting') {
            $user = $campaign->business?->user;
            if ($user) {
                $pendingCount = $campaign->recipients()->where('status', 'pending')->count();
                if (! $this->walletService->hasSufficientCredits($user, $pendingCount)) {
                    $wallet = $user->getOrCreateWallet();
                    throw new \RuntimeException("رصيد الرسائل غير كافٍ لإطلاق الحملة. رصيدك الحالي: {$wallet->credits} رسالة، والمطلوب: {$pendingCount} رسالة.");
                }
            }
        }

        $campaign->update([
            'status' => 'processing',
            'started_at' => $campaign->started_at ?: now(),
        ]);

        $pendingRecipients = $campaign->recipients()->where('status', 'pending')->orderBy('id')->get();

        $interval = $campaign->interval_seconds;
        $jitter = $campaign->jitter_seconds;
        $accumulatedDelay = 0;

        foreach ($pendingRecipients as $index => $recipient) {
            // Apply jitter variation: e.g. interval 10s with jitter 2s => between 8s and 12s
            $stepDelay = $index === 0 ? 0 : max(1, $interval + ($jitter > 0 ? rand(-$jitter, $jitter) : 0));
            $accumulatedDelay += $stepDelay;

            ProcessCampaignRecipientJob::dispatch($recipient->id)
                ->delay(now()->addSeconds($accumulatedDelay));
        }
    }

    /**
     * Process next pending recipient immediately (step execution).
     */
    public function processNextRecipient(Campaign $campaign): ?CampaignRecipient
    {
        if ($campaign->status === 'paused' || $campaign->status === 'cancelled') {
            return null;
        }

        // For bulk sender campaigns, ensure user has at least 1 credit remaining
        if ($campaign->type !== 'retargeting') {
            $user = $campaign->business?->user;
            if ($user && ! $this->walletService->hasSufficientCredits($user, 1)) {
                $campaign->update(['status' => 'paused']);
                throw new \RuntimeException('رصيد الرسائل نفد. تم إيقاف الحملة مؤقتاً حتى إعادة شحن المحفظة.');
            }
        }

        $recipient = DB::transaction(function () use ($campaign) {
            return CampaignRecipient::where('campaign_id', $campaign->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();
        });

        if (! $recipient) {
            $campaign->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return null;
        }

        $this->sendSingleRecipient($recipient);

        // Check if more are pending
        $remaining = CampaignRecipient::where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->count();

        if ($remaining === 0) {
            $campaign->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        return $recipient->fresh();
    }

    /**
     * Send message to a single campaign recipient.
     */
    public function sendSingleRecipient(CampaignRecipient $recipient): bool
    {
        $campaign = $recipient->campaign;

        if ($campaign->status === 'paused' || $campaign->status === 'cancelled') {
            return false;
        }

        $businessId = $campaign->business_id;
        $user = $campaign->business?->user;

        // If not retargeting, pre-check that user has sufficient credits (BUG-05)
        if ($campaign->type !== 'retargeting' && $user) {
            if (! $this->walletService->hasSufficientCredits($user, 1)) {
                $campaign->update(['status' => 'paused']);
                $recipient->update([
                    'status' => 'failed',
                    'error_message' => 'رصيد الرسائل غير كافٍ. تم إيقاف الحملة مؤقتاً.',
                    'sent_at' => now(),
                ]);
                $campaign->increment('failed_count');
                Log::warning("Campaign {$campaign->id} paused due to empty wallet balance.");

                return false;
            }
        }

        try {
            // Find or create Contact if not linked
            $contact = $recipient->contact;
            if (! $contact) {
                $contact = Contact::withoutGlobalScopes()->firstOrCreate(
                    [
                        'business_id' => $businessId,
                        'platform' => $recipient->platform,
                        'platform_sender_id' => $recipient->recipient_id,
                    ],
                    [
                        'name' => $recipient->recipient_name,
                        'phone' => $recipient->platform === 'whatsapp' ? $recipient->recipient_id : null,
                    ]
                );

                $recipient->update(['contact_id' => $contact->id]);
            }

            // Find or create Conversation
            $conversation = Conversation::withoutGlobalScopes()->firstOrCreate(
                [
                    'business_id' => $businessId,
                    'contact_id' => $contact->id,
                ],
                [
                    'platform' => $recipient->platform,
                    'status' => 'open',
                    'last_message_at' => now(),
                ]
            );

            // Create Message
            $body = $recipient->message_body ?: $campaign->message;
            $message = $conversation->messages()->create([
                'sender_type' => 'agent',
                'body' => $body,
                'type' => 'text',
                'status' => 'sent',
                'metadata' => [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'campaign_type' => $campaign->type,
                ],
            ]);

            // Dispatch outbound message through platform channel service
            $dispatchResult = $this->channelManager->sendMessage($conversation, $message);

            $platformMsgId = $dispatchResult['platform_message_id'] ?? null;
            $status = ($dispatchResult['status'] ?? '') === 'failed' ? 'failed' : 'sent';
            $error = $status === 'failed' ? ($dispatchResult['error'] ?? 'Unknown dispatch error') : null;

            $recipient->update([
                'status' => $status,
                'platform_message_id' => $platformMsgId,
                'error_message' => $error,
                'sent_at' => now(),
            ]);

            if ($status === 'sent') {
                $campaign->increment('sent_count');

                // Deduct credit only on confirmed dispatch success (BUG-05)
                if ($campaign->type !== 'retargeting' && $user) {
                    try {
                        $this->walletService->deductCredit(
                            $user,
                            $businessId,
                            $recipient->platform,
                            "إرسال رسالة حملة {$campaign->name} إلى {$recipient->recipient_id}",
                            [
                                'campaign_id' => $campaign->id,
                                'recipient_id' => $recipient->id,
                                'platform' => $recipient->platform,
                            ]
                        );
                    } catch (\Throwable $e) {
                        Log::error("Failed to deduct credit after successful send: {$e->getMessage()}");
                    }
                }
            } else {
                $campaign->increment('failed_count');
            }

            return $status === 'sent';
        } catch (\Throwable $e) {
            Log::error("Campaign {$campaign->id} Recipient {$recipient->id} Send Failed: ".$e->getMessage());

            $recipient->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'sent_at' => now(),
            ]);

            $campaign->increment('failed_count');

            return false;
        }
    }

    /**
     * Interpolate message placeholders like {name}, {phone}, {platform}.
     */
    public function interpolateMessage(string $template, array $variables): string
    {
        $search = [];
        $replace = [];

        foreach ($variables as $key => $val) {
            $search[] = '{'.$key.'}';
            $replace[] = (string) $val;

            $search[] = '{{'.$key.'}}';
            $replace[] = (string) $val;
        }

        return str_replace($search, $replace, $template);
    }

    /**
     * Clean phone number format for WhatsApp.
     */
    public function cleanPhoneNumber(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);

        // If local Egyptian number e.g. 010... -> 2010...
        if (str_starts_with($clean, '01') && strlen($clean) === 11) {
            $clean = '2'.$clean;
        }
        // If local Saudi number e.g. 05... -> 9665...
        elseif (str_starts_with($clean, '05') && strlen($clean) === 10) {
            $clean = '966'.substr($clean, 1);
        }

        return $clean;
    }

    protected function getPlatformLabel(string $platform): string
    {
        return match ($platform) {
            'whatsapp' => 'واتساب',
            'facebook' => 'فيسبوك',
            'instagram' => 'إنستجرام',
            'telegram' => 'تيليجرام',
            default => $platform,
        };
    }
}
