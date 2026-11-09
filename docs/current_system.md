# Vertex Social Media Integration - Current System Analysis

## 1. Executive Summary

Vertex currently connects Facebook Pages and Instagram Business accounts via **manual credential entry**. End users (business owners) must manually obtain:
- Facebook: `page_id` and a `page_access_token`
- Instagram: `instagram_business_id` and a Graph API `access_token`

This document details how the current architecture is built across the database, backend controllers, service layer, webhooks, bulk senders, and frontend UI.

---

## 2. Architecture & Component Diagram

```
+-------------------------------------------------------------+
|                     Client / Browser                        |
|  (Resources/js/Pages/Businesses/Channels.vue + Guide Modal) |
+-------------------------------------------------------------+
                               |
               Manual Input (IDs + Access Tokens)
                               |
                               v
+-------------------------------------------------------------+
|              Laravel Backend (ChannelController)            |
|  - Validates IDs & tokens via Graph API v21.0               |
|  - Auto-subscribes app to page webhooks (subscribed_apps)   |
|  - Stores credentials in MySQL database                     |
+-------------------------------------------------------------+
            |                                    |
            v                                    v
+-----------------------+              +-----------------------+
|   facebook_accounts   |              |  instagram_accounts   |
|   (table in DB)       |              |  (table in DB)        |
+-----------------------+              +-----------------------+
            ^                                    ^
            |                                    |
+-------------------------------------------------------------+
|             MetaChannelService (Runtime Engine)             |
|  - Inbound: MetaWebhookController receives events           |
|  - Outbound: Sends DMs via graph.facebook.com/v21.0         |
|  - Automation: AI auto-replies, keyword triggers            |
|  - Broadcasts: FacebookSender / InstagramSender controllers |
+-------------------------------------------------------------+
```

---

## 3. Database Schema

Social media accounts are stored in two dedicated tables with a 1-to-1 relationship per `business_id`.

### 3.1 `facebook_accounts` Table
- `id` (bigint, PK)
- `business_id` (foreign key to `businesses.id`, unique, cascade on delete)
- `page_id` (varchar, unique)
- `page_name` (varchar)
- `page_access_token` (text, plaintext token)
- `status` (`enum('connected', 'disconnected')`, default `'connected'`)
- `comment_reply_enabled` (boolean, default false)
- `comment_reply_text` (text, nullable)
- `dm_message_text` (text, nullable)
- `comment_keywords` (json, nullable)
- `timestamps`

### 3.2 `instagram_accounts` Table
- `id` (bigint, PK)
- `business_id` (foreign key to `businesses.id`, unique, cascade on delete)
- `instagram_business_id` (varchar, unique)
- `username` (varchar, nullable)
- `access_token` (text, plaintext token)
- `status` (`enum('connected', 'disconnected')`, default `'connected'`)
- `comment_reply_enabled` (boolean, default false)
- `comment_reply_text` (text, nullable)
- `dm_message_text` (text, nullable)
- `comment_keywords` (json, nullable)
- `timestamps`

---

## 4. Backend Implementation Breakdown

### 4.1 Connection Flow (`App\Http\Controllers\ChannelController`)

#### Facebook Connection (`connectFacebook`)
1. **Validation**: Requires `page_id` and `page_access_token`.
2. **Token Verification**: Calls Meta Graph API endpoint:
   `GET https://graph.facebook.com/v21.0/{page_id}?fields=name,id&access_token={token}`
   If HTTP request fails, returns error to user.
3. **Webhook Subscription**: Calls Meta Graph API to subscribe to webhooks:
   `POST https://graph.facebook.com/v21.0/{page_id}/subscribed_apps`
   Fields: `messages,messaging_postbacks,message_reads,feed`
4. **Persistence**: Saves or updates record in `facebook_accounts` table.

#### Instagram Connection (`connectInstagram`)
1. **Validation**: Requires `instagram_business_id` and `access_token`.
2. **Token Verification**: Calls Meta Graph API endpoint:
   `GET https://graph.facebook.com/v21.0/{instagram_business_id}?fields=username,name,id&access_token={token}`
3. **Persistence**: Saves or updates record in `instagram_accounts` table.

#### Disconnection (`disconnect`)
- Deletes the respective channel record (`$business->facebookAccount()->delete()` or `$business->instagramAccount()->delete()`).

---

### 4.2 Inbound Webhooks (`MetaWebhookController` & `MetaChannelService`)

1. **Verification Challenge (`GET /webhooks/meta/{business}`)**:
   - Meta sends query parameters: `hub_mode`, `hub_verify_token`, `hub_challenge`.
   - Backend compares token against `config('services.meta.verify_token')` or `'vertex_meta_token_' . $business->id` or fallback `'vertex_secret_token'`.
   - Returns challenge string with 200 HTTP response.

2. **Incoming Event Handling (`POST /webhooks/meta/{business}`)**:
   - Handles `page` (Facebook) and `instagram` events.
   - Parses message objects:
     - Direct Messages (DMs): Creates `Contact`, `Conversation`, and `Message` in database.
     - Comments on feed/posts: Matches configured keywords (`comment_keywords`), posts auto-replies under the comment, and sends private DMs if configured.
     - Triggers AI auto-reply pipeline if enabled (`HandlesAiAutoReply` trait).

---

### 4.3 Outbound Messaging & Bulk Campaigns

1. **Direct Replies (`MetaChannelService::sendMessage`)**:
   - Uses stored `page_access_token` or `access_token`.
   - Sends payload to `https://graph.facebook.com/v21.0/me/messages?access_token={token}`.
   - Stores `platform_message_id` and updates message status to `delivered`.

2. **Bulk Campaign Senders**:
   - `FacebookSenderController` & `InstagramSenderController` read uploaded CSV lists or target lists and sequentially dispatch messages using the connected business token.

---

## 5. Frontend Implementation (`Channels.vue` & `ChannelGuideModal.vue`)

- **View**: `resources/js/Pages/Businesses/Channels.vue`
- Users see a form with inputs:
  - For Facebook: Text input for `Page ID`, password input for `Page Access Token`.
  - For Instagram: Text input for `Instagram Business ID`, password input for `Access Token`.
- A guide modal (`ChannelGuideModal.vue`) provides manual instructions:
  - Instructs users to go to Meta Business Suite.
  - Explains how to create a "System User" in Meta Business Settings.
  - Explains how to add assets, assign full control, and generate a system user token with specific permission scopes.
  - Copies required permission strings and webhook callback URLs.

---

## 6. Pain Points & Limitations of Current System

1. **Extreme User Friction**: Regular business owners cannot navigate Meta Business Settings or understand "System Users", "Access Tokens", or "Permissions".
2. **Token Expiration**: User tokens generated via Graph API Explorer expire in 1-2 hours or 60 days. System user tokens require complex manual configuration.
3. **Security Vulnerability**: Users paste raw administrative tokens into web forms.
4. **Manual Instagram Linkage**: Users must find their Instagram Business ID manually via Graph API or page metadata.
5. **No Automated Revocation / Refresh**: If a token becomes invalid (e.g. user changes Facebook password), the system fails silently until an API call fails.
