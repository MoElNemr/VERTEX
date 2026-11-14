<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\AiSettingController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacebookSenderController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\InstagramSenderController;
use App\Http\Controllers\MetaOAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RetargetingController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Controllers\TelegramSenderController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\Webhooks\FawryWebhookController;
use App\Http\Controllers\Webhooks\MetaWebhookController;
use App\Http\Controllers\Webhooks\TelegramWebhookController;
use App\Http\Controllers\Webhooks\WebhookSimulatorController;
use App\Http\Controllers\Webhooks\WhatsAppWebhookController;
use App\Http\Controllers\WhatsAppSenderController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth:web,team', 'active_subscription'])->name('dashboard');

Route::middleware(['auth:web,team', 'active_subscription'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Businesses
    Route::get('/businesses', [BusinessController::class, 'index'])->name('businesses.index');
    Route::post('/businesses', [BusinessController::class, 'store'])->name('businesses.store');
    Route::post('/businesses/{business}/switch', [BusinessController::class, 'switch'])->name('businesses.switch');

    // Channels
    Route::get('/businesses/{business}/channels', [ChannelController::class, 'index'])->name('channels.index');
    Route::post('/businesses/{business}/channels/telegram', [ChannelController::class, 'connectTelegram'])->name('channels.telegram.connect');
    Route::post('/businesses/{business}/channels/facebook', [ChannelController::class, 'connectFacebook'])->name('channels.facebook.connect');
    Route::post('/businesses/{business}/channels/instagram', [ChannelController::class, 'connectInstagram'])->name('channels.instagram.connect');
    Route::post('/businesses/{business}/channels/whatsapp/qr', [ChannelController::class, 'generateWhatsAppQr'])->name('channels.whatsapp.qr');
    Route::post('/businesses/{business}/channels/whatsapp/pairing-code', [ChannelController::class, 'requestWhatsAppPairingCode'])->name('channels.whatsapp.pairing-code');
    Route::get('/businesses/{business}/channels/whatsapp/status', [ChannelController::class, 'checkWhatsAppStatus'])->name('channels.whatsapp.status');
    Route::post('/businesses/{business}/channels/comment-settings', [ChannelController::class, 'updateCommentSettings'])->name('channels.comment-settings.update');
    Route::delete('/businesses/{business}/channels/{channel}', [ChannelController::class, 'disconnect'])->name('channels.disconnect');

    // Meta OAuth 2.0 Integration
    Route::get('/businesses/{business}/channels/meta/redirect', [MetaOAuthController::class, 'redirectToMeta'])->name('meta.redirect');
    Route::get('/channels/meta/callback', [MetaOAuthController::class, 'handleCallback'])->name('meta.callback');
    Route::post('/businesses/{business}/channels/meta/persist', [MetaOAuthController::class, 'persistAsset'])->name('meta.persist-asset');
    Route::delete('/businesses/{business}/channels/meta/{channel}', [MetaOAuthController::class, 'disconnectChannel'])->name('meta.disconnect');

    // Unified Inbox
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::get('/inbox/{conversation}/messages', [InboxController::class, 'messages'])->name('inbox.messages');
    Route::post('/inbox/{conversation}/messages', [InboxController::class, 'sendMessage'])->name('inbox.send');
    Route::patch('/inbox/{conversation}/status', [InboxController::class, 'updateStatus'])->name('inbox.status');
    Route::patch('/inbox/{conversation}/assign', [InboxController::class, 'assignAgent'])->name('inbox.assign');
    Route::post('/inbox/{conversation}/transfer', [InboxController::class, 'transfer'])->name('inbox.transfer');
    Route::post('/inbox/{conversation}/ai-suggest', [InboxController::class, 'suggestReply'])->name('inbox.ai-suggest');
    Route::post('/inbox/{conversation}/summarize', [InboxController::class, 'summarize'])->name('inbox.summarize');

    // AI Settings
    Route::get('/businesses/{business}/ai-settings', [AiSettingController::class, 'show'])->name('ai-settings.show');
    Route::post('/businesses/{business}/ai-settings', [AiSettingController::class, 'update'])->name('ai-settings.update');

    // Team Management
    Route::get('/team', [TeamMemberController::class, 'index'])->name('team.index');
    Route::post('/team', [TeamMemberController::class, 'store'])->name('team.store');
    Route::put('/team/{teamMember}', [TeamMemberController::class, 'update'])->name('team.update');
    Route::patch('/team/{teamMember}/toggle-status', [TeamMemberController::class, 'toggleStatus'])->name('team.toggle-status');
    Route::delete('/team/{teamMember}', [TeamMemberController::class, 'destroy'])->name('team.destroy');

    // Contact Management
    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::get('/contacts/export', [ContactController::class, 'export'])->name('contacts.export');
    Route::get('/contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
    Route::patch('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');

    // Retargeting
    Route::get('/retargeting', [RetargetingController::class, 'index'])->name('retargeting.index');
    Route::post('/retargeting', [RetargetingController::class, 'store'])->name('retargeting.store');
    Route::get('/retargeting/{campaign}', [RetargetingController::class, 'show'])->name('retargeting.show');
    Route::post('/retargeting/{campaign}/start', [RetargetingController::class, 'start'])->name('retargeting.start');
    Route::post('/retargeting/{campaign}/pause', [RetargetingController::class, 'pause'])->name('retargeting.pause');
    Route::post('/retargeting/{campaign}/process-step', [RetargetingController::class, 'processStep'])->name('retargeting.step');
    Route::delete('/retargeting/{campaign}', [RetargetingController::class, 'destroy'])->name('retargeting.destroy');

    // 1. WhatsApp Bulk Sender
    Route::get('/whatsapp-sender', [WhatsAppSenderController::class, 'index'])->name('whatsapp-sender.index');
    Route::post('/whatsapp-sender/preview-csv', [WhatsAppSenderController::class, 'previewCsv'])->name('whatsapp-sender.preview-csv');
    Route::get('/whatsapp-sender/template', [WhatsAppSenderController::class, 'downloadTemplate'])->name('whatsapp-sender.template');
    Route::post('/whatsapp-sender/send', [WhatsAppSenderController::class, 'send'])->name('whatsapp-sender.send');
    Route::get('/whatsapp-sender/{campaign}/status', [WhatsAppSenderController::class, 'status'])->name('whatsapp-sender.status');
    Route::post('/whatsapp-sender/{campaign}/process-step', [WhatsAppSenderController::class, 'processStep'])->name('whatsapp-sender.step');
    Route::post('/whatsapp-sender/{campaign}/start', [WhatsAppSenderController::class, 'start'])->name('whatsapp-sender.start');
    Route::post('/whatsapp-sender/{campaign}/pause', [WhatsAppSenderController::class, 'pause'])->name('whatsapp-sender.pause');
    Route::delete('/whatsapp-sender/{campaign}', [WhatsAppSenderController::class, 'destroy'])->name('whatsapp-sender.destroy');

    // 2. Telegram Bulk Sender
    Route::get('/telegram-sender', [TelegramSenderController::class, 'index'])->name('telegram-sender.index');
    Route::post('/telegram-sender/preview-csv', [TelegramSenderController::class, 'previewCsv'])->name('telegram-sender.preview-csv');
    Route::get('/telegram-sender/template', [TelegramSenderController::class, 'downloadTemplate'])->name('telegram-sender.template');
    Route::post('/telegram-sender/send', [TelegramSenderController::class, 'send'])->name('telegram-sender.send');
    Route::get('/telegram-sender/{campaign}/status', [TelegramSenderController::class, 'status'])->name('telegram-sender.status');
    Route::post('/telegram-sender/{campaign}/process-step', [TelegramSenderController::class, 'processStep'])->name('telegram-sender.step');
    Route::post('/telegram-sender/{campaign}/start', [TelegramSenderController::class, 'start'])->name('telegram-sender.start');
    Route::post('/telegram-sender/{campaign}/pause', [TelegramSenderController::class, 'pause'])->name('telegram-sender.pause');
    Route::delete('/telegram-sender/{campaign}', [TelegramSenderController::class, 'destroy'])->name('telegram-sender.destroy');

    // 3. Facebook Bulk Sender
    Route::get('/facebook-sender', [FacebookSenderController::class, 'index'])->name('facebook-sender.index');
    Route::post('/facebook-sender/preview-csv', [FacebookSenderController::class, 'previewCsv'])->name('facebook-sender.preview-csv');
    Route::get('/facebook-sender/template', [FacebookSenderController::class, 'downloadTemplate'])->name('facebook-sender.template');
    Route::post('/facebook-sender/send', [FacebookSenderController::class, 'send'])->name('facebook-sender.send');
    Route::get('/facebook-sender/{campaign}/status', [FacebookSenderController::class, 'status'])->name('facebook-sender.status');
    Route::post('/facebook-sender/{campaign}/process-step', [FacebookSenderController::class, 'processStep'])->name('facebook-sender.step');
    Route::post('/facebook-sender/{campaign}/start', [FacebookSenderController::class, 'start'])->name('facebook-sender.start');
    Route::post('/facebook-sender/{campaign}/pause', [FacebookSenderController::class, 'pause'])->name('facebook-sender.pause');
    Route::delete('/facebook-sender/{campaign}', [FacebookSenderController::class, 'destroy'])->name('facebook-sender.destroy');

    // 4. Instagram Bulk Sender
    Route::get('/instagram-sender', [InstagramSenderController::class, 'index'])->name('instagram-sender.index');
    Route::post('/instagram-sender/preview-csv', [InstagramSenderController::class, 'previewCsv'])->name('instagram-sender.preview-csv');
    Route::get('/instagram-sender/template', [InstagramSenderController::class, 'downloadTemplate'])->name('instagram-sender.template');
    Route::post('/instagram-sender/send', [InstagramSenderController::class, 'send'])->name('instagram-sender.send');
    Route::get('/instagram-sender/{campaign}/status', [InstagramSenderController::class, 'status'])->name('instagram-sender.status');
    Route::post('/instagram-sender/{campaign}/process-step', [InstagramSenderController::class, 'processStep'])->name('instagram-sender.step');
    Route::post('/instagram-sender/{campaign}/start', [InstagramSenderController::class, 'start'])->name('instagram-sender.start');
    Route::post('/instagram-sender/{campaign}/pause', [InstagramSenderController::class, 'pause'])->name('instagram-sender.pause');
    Route::delete('/instagram-sender/{campaign}', [InstagramSenderController::class, 'destroy'])->name('instagram-sender.destroy');
    // Subscription & Plan Upgrade
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::post('/subscription/simulate', [SubscriptionController::class, 'simulatePayment'])->name('subscription.simulate');

    // Bulk Message Wallet
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/topup', [WalletController::class, 'topup'])->name('wallet.topup');
    Route::post('/wallet/simulate', [WalletController::class, 'simulate'])->name('wallet.simulate');
});

// Super Admin Dedicated Auth (Master Control)
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'store'])->name('admin.login.store');
});

// Super Admin Command Center (Platform Owner Only)
Route::middleware(['super_admin'])->prefix('admin')->name('superadmin.')->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
    Route::get('/', [SuperAdminController::class, 'index'])->name('index');
    Route::post('/businesses/{business}/impersonate', [SuperAdminController::class, 'impersonateBusiness'])->name('businesses.impersonate');
    Route::post('/users/{user}/toggle-super-admin', [SuperAdminController::class, 'toggleSuperAdmin'])->name('users.toggle-admin');
    Route::post('/whatsapp/{sessionId}/restart', [SuperAdminController::class, 'restartWhatsAppSession'])->name('whatsapp.restart');

    // Super Admin Users Management
    Route::post('/users', [SuperAdminController::class, 'storeUser'])->name('users.store');
    Route::put('/users/{user}', [SuperAdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [SuperAdminController::class, 'destroyUser'])->name('users.destroy');
    Route::post('/users/{user}/assign-plan', [SuperAdminController::class, 'assignUserPlan'])->name('users.assign-plan');
    Route::post('/users/{user}/wallet-adjust', [SuperAdminController::class, 'adjustUserWallet'])->name('users.wallet-adjust');

    // Super Admin Businesses Management
    Route::post('/businesses', [SuperAdminController::class, 'storeBusiness'])->name('businesses.store');
    Route::put('/businesses/{business}', [SuperAdminController::class, 'updateBusiness'])->name('businesses.update');
    Route::delete('/businesses/{business}', [SuperAdminController::class, 'destroyBusiness'])->name('businesses.destroy');

    // Super Admin Pricing Plans Management
    Route::post('/plans', [SuperAdminController::class, 'storePlan'])->name('plans.store');
    Route::put('/plans/{plan}', [SuperAdminController::class, 'updatePlan'])->name('plans.update');
    Route::delete('/plans/{plan}', [SuperAdminController::class, 'destroyPlan'])->name('plans.destroy');
    Route::patch('/plans/{plan}/toggle-active', [SuperAdminController::class, 'togglePlanActive'])->name('plans.toggle-active');

    // Super Admin Fawry Settings
    Route::post('/fawry/settings', [SuperAdminController::class, 'updateFawrySettings'])->name('fawry.settings');

    // System Cache & Optimizations
    Route::post('/system/optimize', [SuperAdminController::class, 'clearSystemCache'])->name('system.optimize');
});

// Subscription Public Return Callback
Route::get('/subscription/callback', [FawryWebhookController::class, 'callback'])->name('subscription.callback');

// Incoming Public Webhooks (Exempt from CSRF)
Route::post('/webhooks/fawry', [FawryWebhookController::class, 'handle'])->name('webhooks.fawry');
Route::post('/webhooks/telegram/{business}', [TelegramWebhookController::class, 'handle'])->name('webhooks.telegram');
Route::get('/webhooks/meta', [MetaWebhookController::class, 'verifyGlobal'])->name('webhooks.meta.global.verify');
Route::post('/webhooks/meta', [MetaWebhookController::class, 'handleGlobal'])->name('webhooks.meta.global');
Route::get('/webhooks/meta/{business}', [MetaWebhookController::class, 'verify'])->name('webhooks.meta.verify');
Route::post('/webhooks/meta/{business}', [MetaWebhookController::class, 'handle'])->name('webhooks.meta');
Route::post('/webhooks/whatsapp/{business}', [WhatsAppWebhookController::class, 'handle'])->name('webhooks.whatsapp');

// Meta Data Deletion & Compliance (Exempt from CSRF)
Route::post('/meta/data-deletion', [MetaOAuthController::class, 'dataDeletion'])->name('meta.data-deletion');
Route::get('/meta/data-deletion-status/{code}', [MetaOAuthController::class, 'deletionStatus'])->name('meta.deletion-status');

// Webhook Testing & Simulation (Allows testing full inbound flows)
Route::prefix('webhooks/simulate')->group(function () {
    Route::post('/telegram/{business}', [WebhookSimulatorController::class, 'simulateTelegram'])->name('webhooks.simulate.telegram');
    Route::post('/facebook/{business}', [WebhookSimulatorController::class, 'simulateFacebook'])->name('webhooks.simulate.facebook');
    Route::post('/facebook-comment/{business}', [WebhookSimulatorController::class, 'simulateFacebookComment'])->name('webhooks.simulate.facebook-comment');
    Route::post('/instagram/{business}', [WebhookSimulatorController::class, 'simulateInstagram'])->name('webhooks.simulate.instagram');
    Route::post('/instagram-comment/{business}', [WebhookSimulatorController::class, 'simulateInstagramComment'])->name('webhooks.simulate.instagram-comment');
    Route::post('/whatsapp/{business}', [WebhookSimulatorController::class, 'simulateWhatsApp'])->name('webhooks.simulate.whatsapp');
});

require __DIR__.'/auth.php';
