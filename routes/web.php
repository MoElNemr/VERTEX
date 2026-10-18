<?php

use App\Http\Controllers\ProfileController;
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

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth:web,team', 'active_subscription'])->name('dashboard');

Route::middleware(['auth:web,team', 'active_subscription'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Businesses
    Route::get('/businesses', [\App\Http\Controllers\BusinessController::class, 'index'])->name('businesses.index');
    Route::post('/businesses', [\App\Http\Controllers\BusinessController::class, 'store'])->name('businesses.store');
    Route::post('/businesses/{business}/switch', [\App\Http\Controllers\BusinessController::class, 'switch'])->name('businesses.switch');

    // Channels
    Route::get('/businesses/{business}/channels', [\App\Http\Controllers\ChannelController::class, 'index'])->name('channels.index');
    Route::post('/businesses/{business}/channels/telegram', [\App\Http\Controllers\ChannelController::class, 'connectTelegram'])->name('channels.telegram.connect');
    Route::post('/businesses/{business}/channels/facebook', [\App\Http\Controllers\ChannelController::class, 'connectFacebook'])->name('channels.facebook.connect');
    Route::post('/businesses/{business}/channels/instagram', [\App\Http\Controllers\ChannelController::class, 'connectInstagram'])->name('channels.instagram.connect');
    Route::post('/businesses/{business}/channels/whatsapp/pairing-code', [\App\Http\Controllers\ChannelController::class, 'requestWhatsAppPairingCode'])->name('channels.whatsapp.pairing-code');
    Route::get('/businesses/{business}/channels/whatsapp/status', [\App\Http\Controllers\ChannelController::class, 'checkWhatsAppStatus'])->name('channels.whatsapp.status');
    Route::post('/businesses/{business}/channels/comment-settings', [\App\Http\Controllers\ChannelController::class, 'updateCommentSettings'])->name('channels.comment-settings.update');
    Route::delete('/businesses/{business}/channels/{channel}', [\App\Http\Controllers\ChannelController::class, 'disconnect'])->name('channels.disconnect');

    // Unified Inbox
    Route::get('/inbox', [\App\Http\Controllers\InboxController::class, 'index'])->name('inbox.index');
    Route::get('/inbox/{conversation}/messages', [\App\Http\Controllers\InboxController::class, 'messages'])->name('inbox.messages');
    Route::post('/inbox/{conversation}/messages', [\App\Http\Controllers\InboxController::class, 'sendMessage'])->name('inbox.send');
    Route::patch('/inbox/{conversation}/status', [\App\Http\Controllers\InboxController::class, 'updateStatus'])->name('inbox.status');
    Route::patch('/inbox/{conversation}/assign', [\App\Http\Controllers\InboxController::class, 'assignAgent'])->name('inbox.assign');
    Route::post('/inbox/{conversation}/transfer', [\App\Http\Controllers\InboxController::class, 'transfer'])->name('inbox.transfer');
    Route::post('/inbox/{conversation}/ai-suggest', [\App\Http\Controllers\InboxController::class, 'suggestReply'])->name('inbox.ai-suggest');
    Route::post('/inbox/{conversation}/summarize', [\App\Http\Controllers\InboxController::class, 'summarize'])->name('inbox.summarize');

    // AI Settings
    Route::get('/businesses/{business}/ai-settings', [\App\Http\Controllers\AiSettingController::class, 'show'])->name('ai-settings.show');
    Route::post('/businesses/{business}/ai-settings', [\App\Http\Controllers\AiSettingController::class, 'update'])->name('ai-settings.update');

    // Team Management
    Route::get('/team', [\App\Http\Controllers\TeamMemberController::class, 'index'])->name('team.index');
    Route::post('/team', [\App\Http\Controllers\TeamMemberController::class, 'store'])->name('team.store');
    Route::put('/team/{teamMember}', [\App\Http\Controllers\TeamMemberController::class, 'update'])->name('team.update');
    Route::patch('/team/{teamMember}/toggle-status', [\App\Http\Controllers\TeamMemberController::class, 'toggleStatus'])->name('team.toggle-status');
    Route::delete('/team/{teamMember}', [\App\Http\Controllers\TeamMemberController::class, 'destroy'])->name('team.destroy');

    // Contact Management
    Route::get('/contacts', [\App\Http\Controllers\ContactController::class, 'index'])->name('contacts.index');
    Route::get('/contacts/export', [\App\Http\Controllers\ContactController::class, 'export'])->name('contacts.export');
    Route::get('/contacts/{contact}', [\App\Http\Controllers\ContactController::class, 'show'])->name('contacts.show');
    Route::patch('/contacts/{contact}', [\App\Http\Controllers\ContactController::class, 'update'])->name('contacts.update');

    // Retargeting
    Route::get('/retargeting', [\App\Http\Controllers\RetargetingController::class, 'index'])->name('retargeting.index');
    Route::post('/retargeting', [\App\Http\Controllers\RetargetingController::class, 'store'])->name('retargeting.store');
    Route::get('/retargeting/{campaign}', [\App\Http\Controllers\RetargetingController::class, 'show'])->name('retargeting.show');
    Route::post('/retargeting/{campaign}/start', [\App\Http\Controllers\RetargetingController::class, 'start'])->name('retargeting.start');
    Route::post('/retargeting/{campaign}/pause', [\App\Http\Controllers\RetargetingController::class, 'pause'])->name('retargeting.pause');
    Route::post('/retargeting/{campaign}/process-step', [\App\Http\Controllers\RetargetingController::class, 'processStep'])->name('retargeting.step');
    Route::delete('/retargeting/{campaign}', [\App\Http\Controllers\RetargetingController::class, 'destroy'])->name('retargeting.destroy');

    // 1. WhatsApp Bulk Sender
    Route::get('/whatsapp-sender', [\App\Http\Controllers\WhatsAppSenderController::class, 'index'])->name('whatsapp-sender.index');
    Route::post('/whatsapp-sender/preview-csv', [\App\Http\Controllers\WhatsAppSenderController::class, 'previewCsv'])->name('whatsapp-sender.preview-csv');
    Route::get('/whatsapp-sender/template', [\App\Http\Controllers\WhatsAppSenderController::class, 'downloadTemplate'])->name('whatsapp-sender.template');
    Route::post('/whatsapp-sender/send', [\App\Http\Controllers\WhatsAppSenderController::class, 'send'])->name('whatsapp-sender.send');
    Route::get('/whatsapp-sender/{campaign}/status', [\App\Http\Controllers\WhatsAppSenderController::class, 'status'])->name('whatsapp-sender.status');
    Route::post('/whatsapp-sender/{campaign}/process-step', [\App\Http\Controllers\WhatsAppSenderController::class, 'processStep'])->name('whatsapp-sender.step');
    Route::post('/whatsapp-sender/{campaign}/start', [\App\Http\Controllers\WhatsAppSenderController::class, 'start'])->name('whatsapp-sender.start');
    Route::post('/whatsapp-sender/{campaign}/pause', [\App\Http\Controllers\WhatsAppSenderController::class, 'pause'])->name('whatsapp-sender.pause');
    Route::delete('/whatsapp-sender/{campaign}', [\App\Http\Controllers\WhatsAppSenderController::class, 'destroy'])->name('whatsapp-sender.destroy');

    // 2. Telegram Bulk Sender
    Route::get('/telegram-sender', [\App\Http\Controllers\TelegramSenderController::class, 'index'])->name('telegram-sender.index');
    Route::post('/telegram-sender/preview-csv', [\App\Http\Controllers\TelegramSenderController::class, 'previewCsv'])->name('telegram-sender.preview-csv');
    Route::get('/telegram-sender/template', [\App\Http\Controllers\TelegramSenderController::class, 'downloadTemplate'])->name('telegram-sender.template');
    Route::post('/telegram-sender/send', [\App\Http\Controllers\TelegramSenderController::class, 'send'])->name('telegram-sender.send');
    Route::get('/telegram-sender/{campaign}/status', [\App\Http\Controllers\TelegramSenderController::class, 'status'])->name('telegram-sender.status');
    Route::post('/telegram-sender/{campaign}/process-step', [\App\Http\Controllers\TelegramSenderController::class, 'processStep'])->name('telegram-sender.step');
    Route::post('/telegram-sender/{campaign}/start', [\App\Http\Controllers\TelegramSenderController::class, 'start'])->name('telegram-sender.start');
    Route::post('/telegram-sender/{campaign}/pause', [\App\Http\Controllers\TelegramSenderController::class, 'pause'])->name('telegram-sender.pause');
    Route::delete('/telegram-sender/{campaign}', [\App\Http\Controllers\TelegramSenderController::class, 'destroy'])->name('telegram-sender.destroy');

    // 3. Facebook Bulk Sender
    Route::get('/facebook-sender', [\App\Http\Controllers\FacebookSenderController::class, 'index'])->name('facebook-sender.index');
    Route::post('/facebook-sender/preview-csv', [\App\Http\Controllers\FacebookSenderController::class, 'previewCsv'])->name('facebook-sender.preview-csv');
    Route::get('/facebook-sender/template', [\App\Http\Controllers\FacebookSenderController::class, 'downloadTemplate'])->name('facebook-sender.template');
    Route::post('/facebook-sender/send', [\App\Http\Controllers\FacebookSenderController::class, 'send'])->name('facebook-sender.send');
    Route::get('/facebook-sender/{campaign}/status', [\App\Http\Controllers\FacebookSenderController::class, 'status'])->name('facebook-sender.status');
    Route::post('/facebook-sender/{campaign}/process-step', [\App\Http\Controllers\FacebookSenderController::class, 'processStep'])->name('facebook-sender.step');
    Route::post('/facebook-sender/{campaign}/start', [\App\Http\Controllers\FacebookSenderController::class, 'start'])->name('facebook-sender.start');
    Route::post('/facebook-sender/{campaign}/pause', [\App\Http\Controllers\FacebookSenderController::class, 'pause'])->name('facebook-sender.pause');
    Route::delete('/facebook-sender/{campaign}', [\App\Http\Controllers\FacebookSenderController::class, 'destroy'])->name('facebook-sender.destroy');

    // 4. Instagram Bulk Sender
    Route::get('/instagram-sender', [\App\Http\Controllers\InstagramSenderController::class, 'index'])->name('instagram-sender.index');
    Route::post('/instagram-sender/preview-csv', [\App\Http\Controllers\InstagramSenderController::class, 'previewCsv'])->name('instagram-sender.preview-csv');
    Route::get('/instagram-sender/template', [\App\Http\Controllers\InstagramSenderController::class, 'downloadTemplate'])->name('instagram-sender.template');
    Route::post('/instagram-sender/send', [\App\Http\Controllers\InstagramSenderController::class, 'send'])->name('instagram-sender.send');
    Route::get('/instagram-sender/{campaign}/status', [\App\Http\Controllers\InstagramSenderController::class, 'status'])->name('instagram-sender.status');
    Route::post('/instagram-sender/{campaign}/process-step', [\App\Http\Controllers\InstagramSenderController::class, 'processStep'])->name('instagram-sender.step');
    Route::post('/instagram-sender/{campaign}/start', [\App\Http\Controllers\InstagramSenderController::class, 'start'])->name('instagram-sender.start');
    Route::post('/instagram-sender/{campaign}/pause', [\App\Http\Controllers\InstagramSenderController::class, 'pause'])->name('instagram-sender.pause');
    // Subscription & Plan Upgrade
    Route::get('/subscription', [\App\Http\Controllers\SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/subscription/checkout', [\App\Http\Controllers\SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::post('/subscription/simulate', [\App\Http\Controllers\SubscriptionController::class, 'simulatePayment'])->name('subscription.simulate');

    // Bulk Message Wallet
    Route::get('/wallet', [\App\Http\Controllers\WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/topup', [\App\Http\Controllers\WalletController::class, 'topup'])->name('wallet.topup');
    Route::post('/wallet/simulate', [\App\Http\Controllers\WalletController::class, 'simulate'])->name('wallet.simulate');
});

// Super Admin Dedicated Auth (Master Control)
Route::prefix('admin')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Admin\AdminAuthController::class, 'create'])->name('admin.login');
    Route::post('/login', [\App\Http\Controllers\Admin\AdminAuthController::class, 'store'])->name('admin.login.store');
});

// Super Admin Command Center (Platform Owner Only)
Route::middleware(['super_admin'])->prefix('admin')->name('superadmin.')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\Admin\AdminAuthController::class, 'destroy'])->name('logout');
    Route::get('/', [\App\Http\Controllers\SuperAdminController::class, 'index'])->name('index');
    Route::post('/businesses/{business}/impersonate', [\App\Http\Controllers\SuperAdminController::class, 'impersonateBusiness'])->name('businesses.impersonate');
    Route::post('/users/{user}/toggle-super-admin', [\App\Http\Controllers\SuperAdminController::class, 'toggleSuperAdmin'])->name('users.toggle-admin');
    Route::post('/whatsapp/{sessionId}/restart', [\App\Http\Controllers\SuperAdminController::class, 'restartWhatsAppSession'])->name('whatsapp.restart');

    // Super Admin Users Management
    Route::post('/users', [\App\Http\Controllers\SuperAdminController::class, 'storeUser'])->name('users.store');
    Route::put('/users/{user}', [\App\Http\Controllers\SuperAdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [\App\Http\Controllers\SuperAdminController::class, 'destroyUser'])->name('users.destroy');
    Route::post('/users/{user}/assign-plan', [\App\Http\Controllers\SuperAdminController::class, 'assignUserPlan'])->name('users.assign-plan');
    Route::post('/users/{user}/wallet-adjust', [\App\Http\Controllers\SuperAdminController::class, 'adjustUserWallet'])->name('users.wallet-adjust');

    // Super Admin Businesses Management
    Route::post('/businesses', [\App\Http\Controllers\SuperAdminController::class, 'storeBusiness'])->name('businesses.store');
    Route::put('/businesses/{business}', [\App\Http\Controllers\SuperAdminController::class, 'updateBusiness'])->name('businesses.update');
    Route::delete('/businesses/{business}', [\App\Http\Controllers\SuperAdminController::class, 'destroyBusiness'])->name('businesses.destroy');

    // Super Admin Pricing Plans Management
    Route::post('/plans', [\App\Http\Controllers\SuperAdminController::class, 'storePlan'])->name('plans.store');
    Route::put('/plans/{plan}', [\App\Http\Controllers\SuperAdminController::class, 'updatePlan'])->name('plans.update');
    Route::delete('/plans/{plan}', [\App\Http\Controllers\SuperAdminController::class, 'destroyPlan'])->name('plans.destroy');
    Route::patch('/plans/{plan}/toggle-active', [\App\Http\Controllers\SuperAdminController::class, 'togglePlanActive'])->name('plans.toggle-active');

    // Super Admin Fawry Settings
    Route::post('/fawry/settings', [\App\Http\Controllers\SuperAdminController::class, 'updateFawrySettings'])->name('fawry.settings');

    // Super Admin Message Pricing (Bulk & Retargeting)
    Route::post('/pricing/messages', [\App\Http\Controllers\SuperAdminController::class, 'updateMessagePricing'])->name('pricing.messages');

    // System Cache & Optimizations
    Route::post('/system/optimize', [\App\Http\Controllers\SuperAdminController::class, 'clearSystemCache'])->name('system.optimize');
});

// Subscription Public Return Callback
Route::get('/subscription/callback', [\App\Http\Controllers\Webhooks\FawryWebhookController::class, 'callback'])->name('subscription.callback');

// Incoming Public Webhooks (Exempt from CSRF)
Route::post('/webhooks/fawry', [\App\Http\Controllers\Webhooks\FawryWebhookController::class, 'handle'])->name('webhooks.fawry');
Route::post('/webhooks/telegram/{business}', [\App\Http\Controllers\Webhooks\TelegramWebhookController::class, 'handle'])->name('webhooks.telegram');
Route::get('/webhooks/meta/{business}', [\App\Http\Controllers\Webhooks\MetaWebhookController::class, 'verify'])->name('webhooks.meta.verify');
Route::post('/webhooks/meta/{business}', [\App\Http\Controllers\Webhooks\MetaWebhookController::class, 'handle'])->name('webhooks.meta');
Route::post('/webhooks/whatsapp/{business}', [\App\Http\Controllers\Webhooks\WhatsAppWebhookController::class, 'handle'])->name('webhooks.whatsapp');

// Webhook Testing & Simulation (Allows testing full inbound flows)
Route::prefix('webhooks/simulate')->group(function () {
    Route::post('/telegram/{business}', [\App\Http\Controllers\Webhooks\WebhookSimulatorController::class, 'simulateTelegram'])->name('webhooks.simulate.telegram');
    Route::post('/facebook/{business}', [\App\Http\Controllers\Webhooks\WebhookSimulatorController::class, 'simulateFacebook'])->name('webhooks.simulate.facebook');
    Route::post('/facebook-comment/{business}', [\App\Http\Controllers\Webhooks\WebhookSimulatorController::class, 'simulateFacebookComment'])->name('webhooks.simulate.facebook-comment');
    Route::post('/instagram/{business}', [\App\Http\Controllers\Webhooks\WebhookSimulatorController::class, 'simulateInstagram'])->name('webhooks.simulate.instagram');
    Route::post('/instagram-comment/{business}', [\App\Http\Controllers\Webhooks\WebhookSimulatorController::class, 'simulateInstagramComment'])->name('webhooks.simulate.instagram-comment');
    Route::post('/whatsapp/{business}', [\App\Http\Controllers\Webhooks\WebhookSimulatorController::class, 'simulateWhatsApp'])->name('webhooks.simulate.whatsapp');
});

require __DIR__.'/auth.php';
