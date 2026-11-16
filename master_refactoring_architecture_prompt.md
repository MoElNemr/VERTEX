# VERTEX v3.1 — Complete Code Review, Anti-Ban Purge & Final Remediation Blueprint

> **SYSTEM DIRECTIVE FOR AI ENGINEER:**
> You are a Principal Full-Stack Architect auditing and finalizing **Vertex v3.1** (`Laravel 13` + `PHP 8.4` + `Inertia.js v2` + `Vue 3` + `Tailwind CSS v4` + `Node.js Baileys`).
> The product is strictly a **100% Safe Inbound/Conversational Omnichannel & Auto-Reply Platform (5 Channels: WhatsApp, Telegram, Facebook, Instagram, TikTok)**.
> **CRITICAL ANTI-BAN POLICY:** Every feature, job, model, UI tab, or API payload that can get a merchant's account banned—specifically **Bulk Messaging, Campaigns, Cold Outbound Blasts, Bulk Wallets, Unsolicited Comment DMs, and Meta `CONFIRMED_EVENT_UPDATE` Tag Abuse**—MUST be completely purged from the codebase.

---

## PART 1: Comprehensive Code Review Findings (Across All 12 Repomix Files)

### A. Critical Ban-Risk & Bulk Messaging Remnants (HIGH BAN RISK 🔴)

1. **Two Conflicting & Broken `wa-server` Implementations (`repomix-output.12.xml`):**
   * **`vertex/wa-server/server.js`**:
     * Has the lightweight `useDebouncedAuthState` (single JSON file per session flushed every 5s) and low-RAM socket flags.
     * **CRITICAL BUG 1:** `autoBootSessions()` calls `createSession(sessionId, null)` without saving or restoring `businessId`. Because `forwardWebhook(businessId, ...)` starts with `if (!businessId) return;`, **every single WhatsApp session stops delivering webhooks to Laravel as soon as the Node server restarts!**
     * **CRITICAL BUG 2:** Parameter mismatch (`camelCase` vs `snake_case`). Laravel's `WhatsAppChannelService.php` sends `session_id` and `business_id`, whereas `vertex/wa-server/server.js` reads `const { sessionId, businessId } = req.body`, returning `400 sessionId is required`!
     * **CRITICAL BUG 3:** Missing `POST /api/sessions/pairing-code` (8-digit phone linking) and `GET /api/sessions` (used by `SuperAdminController`), and returns `qr` instead of `qr_image` / `qr_code`.
   * **`[wa-server]/server.js` (Root folder)**:
     * Has `meta.json` persistence, `pairing-code`, and `qr_image` Data URL generation.
     * **CRITICAL BUG:** Still uses Baileys' heavy `useMultiFileAuthState` (thousands of tiny files on disk), boots all sessions simultaneously via `forEach` without `await` or stagger delay (causing CPU/RAM spikes), and lacks low-RAM flags (`shouldSyncHistoryMessage: () => false`, `getMessage: async () => undefined`).
   * **Missing Anti-Ban Typing Presence:** Both servers call `sock.sendMessage(targetJid, { text })` at 0ms speed without sending `sendPresenceUpdate('composing', targetJid)`, which triggers WhatsApp's automated bot detection.

2. **Dangerous Meta `CONFIRMED_EVENT_UPDATE` Tag Abuse (`MetaChannelService.php` Lines 52–70):**
   * In `sendFacebookMessage()`, if a message is flagged as a campaign or fails with error code `10` / subcode `2018278` (outside the 24-hour messaging window), the code automatically retries with `'messaging_type' => 'MESSAGE_TAG'` and `'tag' => 'CONFIRMED_EVENT_UPDATE'`.
   * **Why this gets Facebook Pages banned:** Meta strictly prohibits `CONFIRMED_EVENT_UPDATE` for retail/e-commerce stores and customer support. Automated abuse of this tag leads to permanent Page messaging restrictions.

3. **Unsolicited Cold DMs on Comment Replies (`MetaChannelService.php` Lines 369–376 & 504–510):**
   * In `processFacebookCommentToDm()` and `processInstagramCommentToDm()`, when a Private Reply to a comment (`recipient: ['comment_id' => $commentId]`) fails, the code falls back to sending a direct message to `recipient: ['id' => $senderId]`.
   * Sending cold DMs to users who only commented (or whose comment is older than 7 days) violates Meta's policy and triggers spam blocks.

4. **Leftover Bulk Messaging, Campaign & Wallet Code (`repomix-output.3_2.xml` – `repomix-output.12.xml`):**
   * Backend files still present:
     * `app/Services/CampaignService.php`
     * `app/Services/WalletService.php`
     * `app/Jobs/ProcessCampaignRecipientJob.php`
     * `app/Models/Campaign.php` & `app/Models/CampaignRecipient.php`
     * `app/Models/BulkMessageWallet.php` & `app/Models/BulkMessageTransaction.php`
   * Frontend & Controller remnants still present:
     * `SuperAdminController.php` & `SuperAdmin/Index.vue` still query and render the **"أحدث الحملات (`campaigns`)"** tab, the **"محافظ رصيد الرسائل المجمعة (`Bulk Message Wallets`)"** table, and the `walletModalOpen` modal (`POST /admin/users/{user}/wallet-adjust`).
     * `SuperAdmin/Index.vue` (Lines 656, 695, 928) uses `<Wallet />` and `<Coins />` icons without importing them from `lucide-vue-next`!
     * `DashboardController.php` & `Dashboard.vue` still query `Campaign::withoutGlobalScopes()` and display `marketing.delivery_rate`.
     * `Welcome.vue`, `Auth/Register.vue`, and `Subscription/Index.vue` still advertise *"حملات إعادة الاستهداف (Retargeting)"*, *"مرسل واتساب CSV"*, and *"الإرسال الجماعي: دفع حسب الرسالة"*.

---

### B. Functional Bugs & Broken Features in the Current Code

5. **WhatsApp, Telegram, Facebook & Instagram Completely Bypass `AutoReplyService` (`repomix-output.4_2.xml`):**
   * `TikTokChannelService.php` properly calls `$this->autoReplyService->handleInboundDirectMessage($conversation, $inboundMessage)` and `$this->autoReplyService->handleInboundComment(...)`.
   * **However**, `MetaChannelService.php` (Line 251), `TelegramChannelService.php` (Line 171), and `WhatsAppChannelService.php` (Line 227) still call the legacy trait `$this->handleAiAutoReply($conversation, $text)` directly!
   * **Result:** All static Auto-Reply Rules (`AutoReplyRule`: Welcome messages, Keyword triggers, Fallback replies, and Comment rules) **never execute** on WhatsApp, Telegram, Facebook, or Instagram!

6. **Accidental Unlimited Message Quota Bug on All Subscriptions (`repomix-output.3_2.xml` & `4_2.xml`):**
   * In `RegisteredUserController.php` (Line 53), `SocialAuthController.php` (Line 89), `FawryService.php` (Line 166), `SuperAdminController.php` (Lines 165, 198, 229), and `UserFactory.php` (Line 34), subscriptions are created **without** setting `'messages_limit' => $plan->monthly_message_quota` and `'quota_resets_at' => now()->addMonth()`.
   * Because `MessageQuotaService::canSend()` treats `$sub->messages_limit === null` as **Unlimited**, every user who registers or pays via Fawry gets **Unlimited messages (`∞`)** even on limited plans!

7. **Broken Quota Counter for Team Members (`HandleInertiaRequests.php` Line 75):**
   * Line 75 calls `$request->user('team')?->user`.
   * In `App\Models\TeamMember`, the relationship to the store owner is `owner()`, not `user()`. Consequently, team members always receive `null` and see `0 / 0` for `message_quota`.

8. **TikTok Comment Settings Validation Always Fails with 422 (`ChannelController.php` Line 186):**
   * `updateCommentSettings()` validates `'platform' => ['required', 'in:facebook,instagram']`, rejecting `'tiktok'` even though Lines 226–238 contain `if ($validated['platform'] === 'tiktok')`!

9. **SuperAdmin Cannot Configure `monthly_message_quota` on Plans (`SuperAdminController.php` & `SuperAdmin/Index.vue`):**
   * `SuperAdminController::storePlan()` and `updatePlan()` do not validate or save `monthly_message_quota`.
   * `SuperAdmin/Index.vue` (`planForm` at Line 268) has no input field for `monthly_message_quota` and displays `$` instead of `ج.م` (`EGP`).

10. **Incomplete TikTok UI Integration Across 5 Views:**
    * `BusinessController::index()` does not eager-load `tiktokAccount`, and `Businesses/Index.vue` only displays 4 channel cards (omitting TikTok).
    * `DashboardController.php` (`$platformMeta` at Line 56) and `Dashboard.vue` (`platformsList` at Line 113) only include 4 platforms and omit `tiktok`.
    * `Contacts/Index.vue` is missing the TikTok filter pill button in the top bar (Lines 383–428) and is missing `case 'tiktok'` in `getPlatformBadge()` (Line 232).
    * `SuperAdminController.php` & `SuperAdmin/Index.vue` omit `tiktokConnected` from `metrics.channels` and omit the `TT` badge from the stores table.
    * `ChannelGuideModal.vue` has no guide tab for TikTok and still shows legacy manual token instructions for Facebook/Instagram instead of 1-Click Meta OAuth.

---

## PART 2: Step-by-Step Execution Plan

### STEP 1: Unify & Harden `wa-server/server.js` (Single-File Debounced Auth + Full Laravel API + Anti-Ban Presence)

1. **Delete Duplicate Server Folder:**
   * Keep a single authoritative `wa-server/server.js` (and if `vertex/wa-server/server.js` exists in the repo, make it identical or remove the duplicate directory).

2. **Implement Complete `wa-server/server.js` Architecture:**
   * **Single-File Debounced Auth + Metadata Store (`useDebouncedAuthState`):**
     * Store `{ creds, keys, meta: { sessionId, businessId, webhookUrl, phoneNumber } }` in a single JSON file per session (`auth_info/session_{id}.json`).
     * Keep state in memory and flush to disk at most once every `5000ms` using atomic write (`.tmp` + `fs.renameSync`), plus an immediate flush on `SIGINT`/`SIGTERM` or `connection === 'open'`.
   * **Low-RAM & Anti-Crash Socket Configuration:**
     ```javascript
     const sock = makeWASocket({
         version,
         auth: state,
         logger: pino({ level: 'silent' }),
         printQRInTerminal: false,
         browser: Browsers.ubuntu('Chrome'),
         connectTimeoutMs: 45000,
         keepAliveIntervalMs: 30000,
         syncFullHistory: false,
         markOnlineOnConnect: false,
         fireInitQueries: false,
         shouldSyncHistoryMessage: () => false,
         shouldIgnoreJid: (jid) => !jid || jid === 'status@broadcast' || jid.endsWith('@broadcast') || jid.endsWith('@newsletter') || jid.endsWith('@g.us'),
         getMessage: async () => undefined,
     });
     ```
   * **Staggered Auto-Boot With Restored `businessId` & `webhookUrl`:**
     * On startup, read all `session_*_auth.json` files, extract `meta.businessId` and `meta.webhookUrl`, and boot each session sequentially with `await new Promise(r => setTimeout(r, 2000))` between sessions.
   * **Human-Like Typing Presence Before Sending (`POST /api/sessions/:sessionId/messages`):**
     * Before calling `session.sock.sendMessage(targetJid, { text })`, simulate natural human typing to protect the merchant's number from WhatsApp anti-spam bans:
       ```javascript
       try {
           await session.sock.sendPresenceUpdate('composing', targetJid);
           const typingDelay = Math.min(1200, Math.max(400, String(text).length * 18));
           await new Promise((r) => setTimeout(r, typingDelay));
           await session.sock.sendPresenceUpdate('paused', targetJid);
       } catch (e) {}
       const sent = await session.sock.sendMessage(targetJid, { text });
       ```
   * **Full Endpoint Compatibility with Laravel (`WhatsAppChannelService` & `SuperAdminController`):**
     * Accept both `session_id` and `sessionId`, `business_id` and `businessId`, `webhook_url` and `webhookUrl`.
     * `GET /health` & `GET /api/sessions` (returns `{ sessions: [...], active_instances: [...] }`).
     * `POST /api/sessions/start` (waits up to 6s for QR generation, converts QR to `qr_image` Data URL via `qrcode.toDataURL`, and returns `{ status, session_id, qr_code, qr_image }`).
     * `POST /api/sessions/pairing-code` (waits for socket handshake, calls `sock.requestPairingCode(cleanPhone)`, and returns `{ status: 'pairing_code_ready', pairing_code, raw_code }`).
     * `GET /api/sessions/:sessionId` & `GET /api/sessions/:sessionId/status`.
     * `POST /api/sessions/:sessionId/messages`.
     * `DELETE /api/sessions/:sessionId` (logs out socket and deletes `session_{id}_auth.json`).

---

### STEP 2: Purge All Bulk Messaging, Campaigns, Wallets & Meta Ban-Risk Code

1. **Delete Bulk & Campaign Files:**
   * Delete `app/Services/CampaignService.php`
   * Delete `app/Services/WalletService.php`
   * Delete `app/Jobs/ProcessCampaignRecipientJob.php`
   * Delete `app/Models/Campaign.php`
   * Delete `app/Models/CampaignRecipient.php`
   * Delete `app/Models/BulkMessageWallet.php`
   * Delete `app/Models/BulkMessageTransaction.php`
   * Delete migrations:
     * `database/migrations/2026_10_12_170000_create_campaigns_table.php`
     * `database/migrations/2026_10_12_170001_create_campaign_recipients_table.php`
     * `database/migrations/2026_10_17_161000_create_bulk_message_wallets_table.php`
     * `database/migrations/2026_10_17_161001_create_bulk_message_transactions_table.php`

2. **Remove Meta Policy Violations in `app/Services/Channels/MetaChannelService.php`:**
   * **In `sendFacebookMessage()`:**
     * Always send with `'messaging_type' => 'RESPONSE'`.
     * Remove `$isCampaign`, `'MESSAGE_TAG'`, and `'CONFIRMED_EVENT_UPDATE'` fallback logic completely.
     * If Meta returns error code `10` or subcode `2018278` (outside 24h window), return:
       `['status' => 'failed', 'error' => 'انتهت نافذة الـ 24 ساعة المسموحة من ميتا للرد على هذا العميل.']`.
   * **In `processFacebookCommentToDm()` & `processInstagramCommentToDm()`:**
     * Send Private Replies **only** using `'recipient' => ['comment_id' => $commentId]`.
     * Delete the fallback block that retries with `'recipient' => ['id' => $senderId]`.

3. **Clean Up Models, Middleware, Controllers & Routes:**
   * **`app/Models/Business.php`**: Remove `campaigns()` relationship.
   * **`app/Models/User.php`**: Remove `bulkWallet()`, `bulkTransactions()`, and `getOrCreateWallet()`.
   * **`app/Services/FawryService.php`**: Remove `createWalletTopupCharge()` and the `if ($transaction->type === 'bulk_credit')` block in `handleSuccessfulPayment()`.
   * **`app/Http/Middleware/HandleInertiaRequests.php`**: Remove `'bulk_wallet'` from the shared array.
   * **`routes/web.php`**: Remove `Route::post('/users/{user}/wallet-adjust', ...)`.

---

### STEP 3: Fix Plan-Based Message Quota (`MessageQuotaService` & `Subscription`)

1. **Simplify `app/Services/MessageQuotaService.php` (Plan Quota Only):**
   * Remove `WalletService` constructor injection.
   * **`canSend(User $owner, int $count = 1): bool`**:
     * If `$owner->isSuperAdmin()`, return `true`.
     * Fetch `$sub = $owner->activeSubscription`. If `! $sub`, return `false`.
     * If `$sub->quota_resets_at && $sub->quota_resets_at->isPast()`, reset `messages_used = 0` and `quota_resets_at = now()->addMonth()`.
     * If `$sub->messages_limit === null`, return `true` (Unlimited plan).
     * Return `max(0, $sub->messages_limit - $sub->messages_used) >= $count`.
   * **`consumeQuota(User $owner, int $businessId, string $platform, int $count = 1, ?string $description = null): bool`**:
     * Inside `DB::transaction`, lock the active `Subscription` row with `lockForUpdate()`.
     * Reset `messages_used = 0` if `quota_resets_at` is past.
     * Increment `messages_used` by `$count` and save.
   * **`getQuotaSummary(?User $owner): array`**:
     * Return `['limit' => ..., 'used' => ..., 'remaining' => ..., 'resets_at' => ..., 'is_unlimited' => ...]`.

2. **Enforce Automatic `messages_limit` & `quota_resets_at` Initialization in `app/Models/Subscription.php`:**
   * Add a `booted()` lifecycle hook in `Subscription.php`:
     ```php
     protected static function booted(): void
     {
         static::saving(function (Subscription $sub) {
             if ($sub->isDirty('plan_id') || (! $sub->exists && $sub->messages_limit === null)) {
                 $plan = $sub->relationLoaded('plan') ? $sub->plan : Plan::find($sub->plan_id);
                 if ($plan) {
                     $sub->messages_limit = $plan->monthly_message_quota;
                 }
             }
             if (empty($sub->quota_resets_at)) {
                 $sub->quota_resets_at = now()->addMonth();
             }
         });
     }
     ```
   * Also pass `'messages_limit' => $plan->monthly_message_quota` and `'quota_resets_at' => now()->addMonth()` in `RegisteredUserController`, `SocialAuthController`, `FawryService`, `SuperAdminController`, and `UserFactory`.

3. **Fix Team Member Quota Lookup in `app/Http/Middleware/HandleInertiaRequests.php`:**
   * Change Line 75 to:
     ```php
     'message_quota' => app(MessageQuotaService::class)->getQuotaSummary(
         $request->user('web') ?: $request->user('team')?->owner
     ),
     ```

4. **Add `monthly_message_quota` to `SuperAdminController` (`storePlan` & `updatePlan`):**
   * Validate `'monthly_message_quota' => ['nullable', 'integer', 'min:1']` and persist `'monthly_message_quota' => $validated['monthly_message_quota'] ?: null`.

---

### STEP 4: Unify Auto-Reply & Comment-to-DM Engine Across All 5 Channels

1. **Route All Inbound DMs Through `AutoReplyService::handleInboundDirectMessage()`:**
   * Update `app/Services/Channels/Concerns/HandlesAiAutoReply.php` so `handleAiAutoReply(Conversation $conversation, string $messageText, ?Message $inboundMessage = null)` resolves the latest contact `Message` (if not passed) and calls:
     ```php
     app(\App\Services\AutoReplyService::class)->handleInboundDirectMessage($conversation, $inboundMessage);
     ```
   * This immediately activates **Welcome Rules, Keyword Rules (Exact / Contains / Starts With), AI Auto-Reply (with working hours & platform filter), and 24h Cached Fallback Rules** across **WhatsApp, Telegram, Facebook, Instagram, and TikTok**, while deducting 1 message from the plan quota via `ChannelManager::sendMessage()`.

2. **Route Facebook & Instagram Comments Through `AutoReplyService` & Quota Check:**
   * In `MetaChannelService::processFacebookCommentToDm()` and `processInstagramCommentToDm()`:
     * First invoke `app(\App\Services\AutoReplyService::class)->handleInboundComment(...)` so custom `AutoReplyRule` records with `trigger_type === 'comment'` are evaluated by priority.
     * If no `AutoReplyRule` matched and `$account->comment_reply_enabled` is enabled on the channel account:
       * Check `app(\App\Services\MessageQuotaService::class)->canSend($business->user, 1)`.
       * Send the public comment reply and/or private comment reply (`recipient: ['comment_id' => $commentId]`), and call `consumeQuota()` when sent.

3. **Fix `ChannelController::updateCommentSettings()` Validation:**
   * Change Line 186 in `app/Http/Controllers/ChannelController.php` to:
     `'platform' => ['required', 'in:facebook,instagram,tiktok'],`

---

### STEP 5: Update Frontend Views (`SuperAdmin`, `Dashboard`, `Subscription`, `Register`, `Welcome`, `Businesses`, `Contacts`, `ChannelGuideModal`)

1. **`resources/js/Pages/SuperAdmin/Index.vue` & `SuperAdminController.php`:**
   * Remove the `campaigns` tab (`أحدث الحملات`), the `userWallets` table (`محافظ رصيد الرسائل المجمعة`), and `walletModalOpen`.
   * Add `monthly_message_quota` input field (`كوتة الرسائل الشهرية - اتركه فارغاً لغير المحدود`) to `planForm` and display each plan's monthly quota on the plan cards.
   * Add `tiktokConnected` to `metrics.channels` and display `TT` badge in the stores table alongside `WA`, `TG`, `FB`, `IG`.

2. **`resources/js/Pages/Dashboard.vue` & `DashboardController.php`:**
   * Add `'tiktok' => ['name' => 'TikTok', 'color' => '#06B6D4']` to `$platformMeta` in `DashboardController.php` and add the 5th platform entry (`tiktok`) to `platformsList` in `Dashboard.vue` (change header label from `4 قنوات` to `5 قنوات`).
   * Replace `marketing.delivery_rate` with `autoRepliesCount` (`قواعد الرد النشطة`).

3. **`resources/js/Pages/Businesses/Index.vue` & `BusinessController.php`:**
   * Eager-load `'tiktokAccount:business_id,username,display_name,status'` in `BusinessController::index()`.
   * Add the 5th channel status box (**تيك توك**) to each business card in `Businesses/Index.vue`.

4. **`resources/js/Pages/Contacts/Index.vue`:**
   * Add the **تيك توك** filter pill button (`filterByPlatform('tiktok')`) with `platformCounts?.tiktok || 0` in the filter bar.
   * Add `case 'tiktok': return { name: 'تيك توك', color: 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20' };` to `getPlatformBadge()`.

5. **`resources/js/Pages/Subscription/Index.vue`, `resources/js/Pages/Auth/Register.vue`, & `resources/js/Pages/Welcome.vue`:**
   * Replace all mentions of *"حملات إعادة الاستهداف (Retargeting)"*, *"مرسل واتساب CSV"*, and *"الإرسال الجماعي: دفع حسب الرسالة"* with:
     * **سعة الرسائل الشهرية للباقة** (`plan.monthly_message_quota ? plan.monthly_message_quota.toLocaleString() + ' رسالة شهرياً' : 'رسائل شهرية غير محدودة'`)
     * **ربط 5 منصات رسمية** (واتساب، فيسبوك، إنستجرام، تيليجرام، تيك توك للأعمال)
     * **الرد الآلي الذكي على الرسائل والتعليقات** (قواعد الكلمات المفتاحية + مساعد الذكاء الاصطناعي Groq)

6. **`resources/js/Components/ChannelGuideModal.vue`:**
   * Add a 5th tab for **تيك توك (TikTok)** and update Facebook & Instagram tabs to explain the 1-Click OAuth connection flow.