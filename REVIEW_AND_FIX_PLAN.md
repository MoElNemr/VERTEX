# Vertex — Project Review and Fix Plan

Review date: 2026-11-20 (commit `00ee000`). Scope: backend, frontend, UI/UX, system design, `wa-server`.

## Verdict

Good product idea, prototype-grade engineering. Feature coverage is wide (unified inbox, 5 channels, AI auto-reply, quotas, Fawry billing, super admin, teams). The channel-service layer (`ChannelManager` plus one service per platform) is a sound base, and the RTL dark UI is consistent. Ingestion, authorization and payments are not safe for production yet.

All 171 tests pass (576 assertions), but they do not catch the worst bugs. `tests/Feature/ChannelWebhooksTest.php:208` passes only because of the WhatsApp auth bypass.

---

## Findings

### Critical (security — fix before any production traffic)

1. **Webhook simulator is public.** `routes/web.php:176-183`, `WebhookSimulatorController`. No auth, no environment gate, CSRF-exempt. Anyone can inject messages into any business (IDs are sequential), which triggers AI auto-replies, real outbound sends, and quota usage.
2. **WhatsApp webhook auth skips when the header is missing.** `WhatsAppWebhookController.php:25` — `if ($providedSecret && !hash_equals(...))`. Without the header, anyone can overwrite `qr_code` / `session_id` (merchant scans attacker's QR) or inject messages. `WhatsAppChannelService.php:225-236` also fans messages out to every business matching a phone number taken from the payload.
3. **Production runs Fawry in sandbox by default.** `config/services.php:55` defaults `FAWRY_MODE` to `'sandbox'`, so the "force live" branch in `FawryService.php:27` never runs. In sandbox the IPN signature check is skipped (`FawryWebhookController.php:223`) and `/subscription/simulate` is open to any logged-in user with no ownership check — free premium plan.
4. **wa-server open by default.** `wa-server/server.js:20,38` falls back to `default_wa_secret_key` and then lets every request through. `cors()` open. Client-supplied `webhook_url` (L392) is an SSRF hole and receives the API key plus QR codes.
5. **Meta and Telegram signature checks skip when the secret is empty.** `MetaWebhookController.php:45-64` reads only env config while the admin panel stores the secret in `SystemSetting`. `TelegramWebhookController.php:23,105` skips without a `webhook_secret` or account.

### High

- **No async layer.** `app/Jobs` does not exist despite Horizon. Groq (30s timeout, `AiService.php:152`), outbound sends and `ShouldBroadcastNow` events all run inside the webhook request. Providers time out and retry, causing load storms.
- **No real idempotency.** `messages.platform_message_id` is indexed but not unique; dedup is check-then-insert. Missing ids become `uniqid()`. Concurrent retries create duplicate messages and duplicate AI replies.
- **Quota race.** `ChannelManager.php:53-70` — `canSend()` then send then `consumeQuota()` with no lock; lazy resets in `MessageQuotaService.php:121,263` can overwrite increments.
- **Secrets sent to the browser.**
  - Meta page `access_token`s via Inertia props (`ChannelController.php:72`).
  - Telegram `bot_token` pre-filled in a form (`Channels.vue:154-155`).
  - Agents see the decrypted AI `api_key` through `AiSettingController::show`, which skips the admin-only check; `update` has no role check either.
- **WhatsApp QR sent to a third party.** `Channels.vue:228` uses `api.qrserver.com`.
- **Team members bypass the subscription gate.** `EnsureActiveSubscription.php:17-22`.
- **Team admin privilege escalation.** `TeamMemberController::update` lets an admin assign any of the owner's stores, including to themselves, and reset other admins' passwords.
- **Social login account takeover.** `SocialAuthController.php` links by email without checking `email_verified`; Facebook login does not block super-admin accounts.
- **No login throttling** on `AdminAuthController::store` and `TeamAuthenticatedSessionController::store`.
- **Encrypted Telegram tokens overflow `varchar(255)`.** Works on SQLite, fails on MySQL strict / Postgres.
- **wa-server reliability.**
  - Reconnect never happens (old socket stays in the map, early return at L169).
  - `forwardWebhook` (L141-156) swallows errors, no retry/outbox — messages lost when Laravel is down.
  - Media never downloaded; `append` upserts can resend old messages.
  - Single process with in-memory sessions — no HA; `sessionId` unsanitised in file paths.
  - Baileys is an unofficial protocol — WhatsApp ToS / ban risk.

### Medium (backend)

- Tenancy enforced by hand: `BelongsToBusiness` global scope does nothing without a session; no Policies; about five copy-pasted `authorizeBusiness` variants. Spatie multitenancy configured but unused (`tenant_finder => null`, `queues_are_tenant_aware_by_default => true` will break the first queued job).
- Broadcast channel `business.{id}` (`routes/channels.php`) sends every conversation to agents who should only see assigned ones, and to inactive members.
- AI context uses `oldest()->limit(N)` (`AiService.php:28,56`) — sends the first messages, not the latest. `suggestReply` / `summarize` are not metered.
- Fawry IPN not idempotent (`FawryService.php:187-212`); a later FAILED IPN can overwrite a paid one; paid amount never compared.
- Unbounded queries: inbox loads all conversations and all messages; dashboard runs ~42 count queries in a loop.
- `SystemSetting` stores Groq / Google / Meta / Fawry secrets in plain text; `default_test_key` fallback.
- Hard-coded Meta verify token `vertex_secret_token` (`MetaChannelService.php:204`).
- `SetCurrentBusiness` filters by a non-existent `is_active` column on businesses — query error for team members without a store.
- `BusinessController::store` crashes for team members (`canCreateBusiness` undefined on `TeamMember`).
- `SuperAdminController::updateUser` silently removes super-admin when the field is omitted; no last-admin guard.
- Registration accepts inactive plans (`exists:plans,id` only).
- `env()` called outside config files (returns null with cached config).
- Webhooks run in the `web` middleware group (session, `SetCurrentBusiness`, Inertia).
- Worktree autoloader hack committed in `bootstrap/app.php:13-31`.
- `.env.example` missing most required keys (WhatsApp, Reverb, Telegram, TikTok, Fawry, Google); Horizon targets Redis while `.env.example` uses the database queue; `viewHorizon` gate allows nobody.

### Frontend / UI/UX

- **Inbox (`Pages/Inbox/Index.vue`).**
  - Duplicate messages when the Echo event arrives before the HTTP response (:282-294, :431-436).
  - Polling fallback checks a non-existent socket property and discards the fetched page (:501-509).
  - No stale-response guard when switching conversations quickly (:256-271).
  - Auto-scrolls on every incoming message; no message pagination.
  - No optimistic/pending state; send failures silently restore text.
  - Voice note keeps playing after navigation; notification permission requested without a user gesture; Enter sends during IME composition.
- **Broken page.** `ContactController.php:111` renders `Contacts/Show`, which does not exist.
- **Leaks / polling.** `beforeinstallprompt` listener never removed (`AuthenticatedLayout.vue:54`); WhatsApp status polled every 2s with no cap and a hard-coded URL (`Channels.vue:323-334`); `window.location.reload()` instead of `router.reload()`.
- **Styling.** `slate-750` / `slate-850` (29 uses), `pb-safe`, `dir-ltr` are not real utilities. Tailwind v4 still on a v3-style `tailwind.config.js`; Figtree font never loaded; `autoprefixer` unused. `vite.config.js` hard-codes LAN IP `192.168.1.105`.
- **Design system.** No shared primitives; class strings copy-pasted. Two modal systems and two toast systems. Errors via `alert()`, `console.error` or empty `catch`.
- **Dead code.** Breeze leftovers (`NavLink`, `ResponsiveNavLink`, unused `Dropdown` imports; Profile / VerifyEmail / ConfirmPassword in English and a light theme). Unused forms in `Channels.vue:158-178`. Pinia installed but never registered.
- **Giant files.** `SuperAdmin/Index.vue` (2275 lines), `Businesses/Channels.vue` (2049), `Inbox/Index.vue` (1246). Platform maps and date formatters duplicated per page.
- **Accessibility.** `Modal.vue` has no focus trap / initial focus / focus return; hand-rolled overlays lack Escape and `role="dialog"`. Most text 10–11px in `slate-500` on `slate-950`.
- **RTL.** Mostly correct; a few physical-direction leftovers (`Dropdown.vue:41-43`, `Team/Index.vue:284,295`, `Inbox:994`).

### Docs vs reality

- `README.md` is the Laravel default; `AGENTS.md` is a copy of the Boost guidelines.
- `TASKS.md` marks every phase done, but contact import, purchase history and real webhook security tests are missing.
- `master_refactoring_architecture_prompt.md` step 1 ("harden server.js") is not done.

---

## Fix Plan

### Phase 0 — Security (1–2 days, before anything else)

Each fix gets a regression test; correct `ChannelWebhooksTest.php:208` to expect rejection.

1. Remove the simulator routes, or restrict them to `local` and require auth.
2. Make every webhook signature check fail closed when the secret or header is missing: WhatsApp, Meta (single secret source shared with OAuth), Telegram, Fawry.
3. Default Fawry to live in production; make `simulatePayment` local-only and owner-checked.
4. wa-server: refuse to start without an API key, restrict CORS, stop accepting `webhook_url` from clients, sanitise `sessionId`.
5. Stop sending tokens to the browser (Meta session accounts, Telegram token, AI key); render the QR on the server or client-side, never via a third party.
6. Throttle admin and team logins; require `email_verified` for Google login; block super-admin linking via Facebook; fix the team subscription bypass and the team-admin escalation.

### Phase 1 — Reliable ingestion (3–5 days)

1. Unique index on `(conversation_id, platform_message_id)` (or provider + id) and `insertOrIgnore`; never generate fake ids.
2. Webhooks persist the raw event and return 200 immediately; a `ProcessInboundMessage` job does the rest. AI replies and outbound sends become queued jobs with retries, backoff and `failed_jobs`. Switch broadcasts from `ShouldBroadcastNow` to queued. Move webhook routes out of the `web` middleware group.
3. Atomic quota: `UPDATE ... WHERE used < limit` or `lockForUpdate`; meter AI suggest/summarize.
4. Fawry IPN idempotent (atomic status transition, never downgrade a paid transaction), and verify the paid amount.
5. wa-server: fix reconnect, add a durable outbox with retries and timeouts, download media, ignore `append` upserts.

### Phase 2 — Authorization and tenancy (2–3 days)

1. Policies for `Business`, `Conversation`, `Channel`, `TeamMember`, `AiSetting`, replacing the ad-hoc `authorizeBusiness` variants.
2. Broadcast authorization: agents receive only assigned conversations; inactive members receive nothing.
3. Encrypt `SystemSetting` secrets; widen Telegram token columns to `text`; remove Spatie multitenancy scaffolding.
4. Fix the `is_active` query in `SetCurrentBusiness`, `BusinessController::store` for team members, `updateUser` super-admin flag, and inactive plans at registration.

### Phase 3 — Frontend (3–4 days)

1. Inbox: dedupe by message id, stale-request guard, correct socket-state check (`connector.pusher.connection.state`), message pagination, scroll only when already at the bottom, pending/failed message states, pause audio on unmount.
2. Shared `platforms.js` / `formatters.js`; `BaseButton`, `BaseInput`, `BaseModal` (focus trap, Escape, `role="dialog"`); one toast system.
3. Split SuperAdmin and Channels into tab components.
4. Replace non-existent utilities, move Tailwind config into `@theme`, create `Contacts/Show` or remove the route, remove the hard-coded IP from `vite.config.js`.
5. Translate and restyle the Breeze pages; remove dead components and Pinia.

### Phase 4 — Cleanup and scaling

1. Paginate inbox and dashboard; cache counts.
2. Fix AI history to use the most recent messages.
3. Remove the autoloader hack from `bootstrap/app.php`.
4. Complete `.env.example`; write a real README.
5. Long term: move WhatsApp to the official Cloud API; run wa-server with shared session storage if kept.
