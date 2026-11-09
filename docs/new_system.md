# Vertex Social Media Integration - Meta OAuth System Design & Implementation Guidelines

> **DIRECTIVE FOR AI IMPLEMENTER:**
> This document contains functional specifications, behavioral requirements, and system contracts. 
> - Follow all steps in exact numerical order.
> - Adhere to every constraint marked **MUST**, **MUST NOT**, or **CRITICAL**.
> - Do not write or guess missing business logic outside these stated contracts.

---

## 1. System Scope & Core Objective

The objective is to replace the manual entry of credentials (`page_id`, `page_access_token`, `instagram_business_id`, `access_token`) with automated **Meta Login for Business (OAuth 2.0)**.

The system connects two social channels per business tenant:
1. **Facebook Page**
2. **Linked Instagram Business Account**

All messaging engines, inbound webhooks, and bulk dispatchers must continue running without schema breakage or field renaming.

---

## 2. Strict AI Model Guardrails

1. **Environment Separation**:
   - The AI **MUST NOT** hardcode secrets, IDs, tokens, or webhook endpoints into logic or components.
   - All Meta credentials **MUST** be loaded strictly through environment configuration files (`config/services.php` mapped to `.env`).

2. **Database Backward Compatibility**:
   - The AI **MUST NOT** rename, delete, or alter the data types of existing columns in `facebook_accounts` and `instagram_accounts`.
   - Existing columns (`page_id`, `page_access_token`, `instagram_business_id`, `access_token`) **MUST** be populated with the tokens obtained from the OAuth exchange.

3. **CSRF State Validation**:
   - Every OAuth flow **MUST** create a unique, cryptographically random `state` string.
   - The `state` parameter **MUST** be stored in the business owner's active session and matched byte-for-byte upon return from Meta. Mismatches **MUST** terminate the request with an HTTP 403 or session expiry error.

4. **Token Hierarchy**:
   - The AI **MUST NOT** save the initial short-lived user token as the channel access token.
   - The AI **MUST** execute the two-tier token exchange to guarantee non-expiring Page Access Tokens.

---

## 3. Architecture & Token Lifecycle Specification

### 3.1 Token Transformation Pipeline
The implementation must follow a 3-tier token progression:

1. **Authorization Code $\rightarrow$ Short-Lived User Token**:
   - Exchange the incoming authorization `code` via Graph API endpoint `/oauth/access_token`.
   - Lifespan: ~1 to 2 hours.

2. **Short-Lived User Token $\rightarrow$ Long-Lived User Token**:
   - Exchange the short-lived token using the `fb_exchange_token` grant type.
   - Lifespan: 60 days.

3. **Long-Lived User Token $\rightarrow$ Permanent Page Access Token**:
   - Request the user's managed accounts via `/me/accounts` using the Long-Lived User Token.
   - Meta automatically provides a **Permanent Page Access Token** inside each page object.
   - This Page Token does not expire unless permissions are revoked or the user changes their password.
   - This same Page Token is used to access both the Facebook Page and the linked Instagram Business account.

### 3.2 Permission Scopes Contract
The OAuth dialog URL **MUST** declare and request exactly these permission scopes:
- `pages_show_list`: Allows the app to retrieve all Facebook Pages managed by the user.
- `pages_read_engagement`: Grants access to page engagement and comment threads.
- `pages_manage_metadata`: Enables automated webhook subscriptions on the page.
- `pages_messaging`: Enables sending and receiving Facebook Messenger messages.
- `instagram_basic`: Reads basic account info and linked Instagram IDs.
- `instagram_manage_messages`: Enables sending and receiving Instagram Direct Messages.
- `instagram_manage_comments`: Reads and replies to comments on Instagram posts.
- `public_profile`: Basic Meta identity.

---

## 4. Backend System Guidelines

### 4.1 Required Service Layer Responsibilities
Create a dedicated service class (e.g., `MetaOAuthService`) to isolate external HTTP communication:
- **URL Builder**: Generates the Meta OAuth dialog redirect URL with client ID, callback URL, scopes, and session `state`.
- **Token Exchange Methods**: One method to trade `code` for short-lived token, one method to trade short-lived for long-lived token.
- **Account Discovery**: Calls `/me/accounts` with field expansions:
  `id,name,category,access_token,picture{url},tasks,instagram_business_account{id,username,name,profile_picture_url}`.
- **Auto-Subscription Engine**: Automatically fires a POST request to `/v21.0/{page_id}/subscribed_apps` with fields: `messages,messaging_postbacks,message_reads,message_echoes,feed`.

### 4.2 Controller Workflow Rules
Create a dedicated controller (e.g., `MetaOAuthController`) implementing the following endpoints:

1. **Redirect Handler**:
   - Generates random state and saves it to session along with the current `business_id`.
   - Redirects user to the generated Meta OAuth dialog.

2. **Callback Handler**:
   - Verifies the returned `state` against the session.
   - Catches query parameters for errors (`error`, `error_description`) and aborts with user-friendly alerts.
   - Runs the token transformation pipeline.
   - Queries manageable accounts.
   - If multiple accounts exist, stores accounts in temporary session memory and redirects to the channel settings page with a modal activation flag.

3. **Asset Persist Handler**:
   - Receives user's selected `page_id` and an optional boolean flag to connect the linked Instagram account.
   - Runs inside a database transaction (`DB::transaction`).
   - Updates or creates records in `facebook_accounts` and `instagram_accounts`.
   - Fires the webhook auto-subscription.
   - Cleans up temporary OAuth session data.

4. **Channel Disconnect Handler**:
   - Allows targeted deletion of `facebook_accounts` record, `instagram_accounts` record, or both.

5. **Compliance Endpoint (Data Deletion)**:
   - Must be excluded from CSRF middleware.
   - Parses the incoming `signed_request` from Meta using HMAC-SHA256.
   - Removes or anonymizes tokens for the requesting user ID.
   - Returns JSON containing `url` (confirmation check) and `confirmation_code`.

### 4.3 Webhook Security Specification
Inside `MetaWebhookController`:
- Validate all inbound requests via header `X-Hub-Signature-256`.
- Compute HMAC using the raw request body and `services.meta.app_secret`.
- Compare signatures using constant-time string comparison (`hash_equals`).
- If invalid or missing, reject immediately with HTTP status 403.

---

## 5. Database Schema Requirements

Generate a database migration modifying both `facebook_accounts` and `instagram_accounts` tables.

### 5.1 Additions to `facebook_accounts`:
- `meta_user_id` (string, nullable): Stores the Meta user ID of the admin who authorized.
- `token_type` (enum: `'manual'`, `'oauth'`, default `'oauth'`).
- `token_expires_at` (timestamp, nullable): Expiration date of the underlying authorization.
- `avatar_url` (text, nullable): URL for displaying page profile image in the UI.
- `granted_scopes` (json, nullable): List of approved permissions for auditing.

### 5.2 Additions to `instagram_accounts`:
- `meta_user_id` (string, nullable): Linked Facebook User ID.
- `linked_facebook_page_id` (string, nullable): The Facebook Page ID connected to this IG account.
- `token_type` (enum: `'manual'`, `'oauth'`, default `'oauth'`).
- `token_expires_at` (timestamp, nullable).
- `avatar_url` (text, nullable): Instagram profile avatar.
- `granted_scopes` (json, nullable).

---

## 6. Frontend & UI/UX Guidelines

### 6.1 Channels Management Page (`Channels.vue`)
- **Removal**: Remove the legacy manual form fields (Page ID text input and raw Token password field).
- **Primary Integration Card**:
  - Prominent Call to Action button: **"Connect Facebook & Instagram with Meta"** with Meta blue styling.
  - Explanatory copy: "Connect your official Facebook Page and linked Instagram Business account securely with 1-click."
- **Connected Status Card**:
  - When channels are active, display:
    - Facebook Page name, avatar, and linked status indicator.
    - Linked Instagram handle, avatar, and linked status indicator.
    - Token health status badge (e.g., "Active - Permanent Token").
    - Action buttons: "Re-authorize / Change Page" and "Disconnect".

### 6.2 Asset Selection Modal (`MetaAssetSelectorModal.vue`)
- **Trigger**: Automatically displays when redirected back from Meta with multi-page accounts.
- **Selection Components**:
  - A scrollable radio-button list of all discovered Facebook Pages displaying name, category, and page avatar.
  - An inline sub-card indicating whether the currently selected page has an active Instagram Business account attached (showing `@handle` and avatar).
  - A toggle switch or checkbox: "Also connect Instagram Business account".
  - Action buttons: "Cancel" and "Confirm & Link Channels".

---

## 7. Step-by-Step AI Execution Order

When implementing this feature, the AI model must execute tasks in this strict sequence:

1. **Config Verification**: Register Meta config entries in `config/services.php`.
2. **Schema Migration**: Create and run the migration adding OAuth fields to both social media tables.
3. **Service Layer Implementation**: Build the service class handling Meta Graph API communication (OAuth, token exchange, account discovery, and webhook subscriptions).
4. **Controller Implementation**: Build the OAuth controller handling redirect, callback, asset persistence, and data deletion.
5. **Route Configuration**: Register OAuth routes in `routes/web.php` with appropriate session and auth middleware.
6. **Webhook Hardening**: Add HMAC-SHA256 signature validation to the webhook ingestion controller.
7. **Frontend Component Creation**: Build the asset selection modal and update the channel settings page.
8. **End-to-End Validation**: Verify state verification, token persistence, and webhook auto-registration.