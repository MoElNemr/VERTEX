# VERTEX: Customer Conversation & Retention Platform

VERTEX is a customer conversation, retention, and multichannel messaging platform engineered for growing commerce teams and service businesses. It consolidates customer conversations across WhatsApp, Facebook Messenger, Instagram, and Telegram into a unified team inbox with order context, AI-assisted workflows, permission-based marketing campaigns, and transparent usage billing.

---

## 1. Architectural Overview

VERTEX follows a **Modular Monolith** architecture with a specialized provider adapter boundary:

```text
Browser (Vue 3 + Inertia.js)
        │ HTTPS / Session Auth
        ▼
Laravel 13 Application (System of Record) ───── PostgreSQL / Relational DB
   │     │     │                                       │
   │     │     └─ Redis (Queue, Cache, Locks)          ├─ Webhook Receipts & Deduplication
   │     └─ Laravel Reverb (Realtime Events)           ├─ Immutable Usage Ledger
   ├─ Meta Channel Service (Graph API + Signatures)    ├─ Audit Events Log
   ├─ Telegram Channel Service (Bot API)               ├─ Consents & Suppressions
   ├─ Fawry Payment Service (Signed IPN)               └─ Platform Admins (Dedicated Guard)
   └─ Private Signed API ─ Node.js WhatsApp Adapter (Baileys Service)
```

### Core Tenancy & Identity Design
- **Platform Operators (`admin` guard):** Stored in `platform_admins`. Isolated from customer accounts and tenant metrics. Roles: `owner`, `support`, `finance`, `operations`.
- **Tenant Accounts (`web` guard):** Stored in `users` with multi-business ownership (`businesses`, `team_members`).
- **Domain Guardrails:**
  - `EnsureBusinessAccess`: Enforces tenant-boundary authorization on all channel, AI, and inbox routes.
  - `WebhookReceipt`: Idempotent deduplication for Meta, Telegram, WhatsApp, and Fawry incoming webhooks.
  - `UsageLedger`: Immutable double-entry style ledger tracking all wallet credits, debits, adjustments, and campaign consumption.
  - `Consent` & `SuppressionEntry`: Enforces opt-in verification and Do-Not-Contact lists across marketing campaigns.
  - `AuditEvent`: Append-only security audit log for admin actions, tenant impersonation, and balance adjustments.

---

## 2. Prerequisites & Environment Setup

### Prerequisites
- **PHP:** 8.2 or higher (with `pdo`, `mbstring`, `openssl`, `curl`, `json` extensions)
- **Composer:** 2.x
- **Node.js:** 18.x or 20.x LTS with NPM
- **Database:** PostgreSQL (recommended for production) or MySQL / SQLite (for local dev)
- **Redis:** Recommended for queues and locks

### 1. Backend Setup (Laravel)
```bash
cd vertex

# Install PHP dependencies
composer install

# Copy environment template
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations and seed clean data
php artisan migrate --seed
```

### 2. WhatsApp Adapter Setup (Node.js Service)
```bash
cd ../JS-server

# Install dependencies
npm install

# Configure environment variables
# Set SHARED_SECRET matching vertex .env (WHATSAPP_SERVICE_SECRET)
npm start
```

### 3. Frontend Build
```bash
cd ../vertex
npm install
npm run build   # or npm run dev for local development
```

---

## 3. Seeded Accounts & Testing Credentials

Running `php artisan db:seed` provisions the following test accounts:

| Role | Guard / Context | Email | Password |
|---|---|---|---|
| **Platform Super Admin** | `admin` (`/admin/login`) | `admin@vertex.app` | `Admin@Vertex2026!` |
| **Store Owner** | `web` (`/login`) | `owner@vertex.app` | `Password123!` |
| **Support Agent** | `web` (`/login`) | `agent@vertex.app` | `Password123!` |

*(Store owner account owns "متجر فيرتكس التجريبي" with pre-configured inbox conversations, channel setups, and wallet balance.)*

---

## 4. Security & Webhook Hardening

- **Simulator Route Restriction:** Webhook simulation routes (`/webhooks/simulate/*`) are restricted to `local` and `testing` environments or require `ENABLE_SIMULATOR=true`. Never exposed in production.
- **Signature Verification:**
  - **Meta:** `X-Hub-Signature-256` HMAC validation against raw payload.
  - **Telegram:** Secret token validation (`X-Telegram-Bot-Api-Secret-Token`).
  - **WhatsApp Node Service:** Inter-service HMAC token generation and verification (`WhatsAppTokenService`).
  - **Fawry:** MD5/SHA256 signature validation with server-to-server reconciliation.
- **Node.js Service Boundary:** Fallback static shared secret is strictly prohibited in production (`NODE_ENV=production` fails startup if secret is missing).

---

## 5. Verification & Test Suite

Run the full automated test suite covering authentication, tenant isolation, billing ledger, campaign balance gates, and channel integrity:

```bash
php artisan test
```

All 120+ feature and unit tests run with transactional database isolation (`RefreshDatabase`).
