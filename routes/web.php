<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\AiSettingController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\MetaOAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Controllers\TikTokOAuthController;
use App\Http\Controllers\Webhooks\FawryWebhookController;
use App\Http\Controllers\Webhooks\MetaWebhookController;
use App\Http\Controllers\Webhooks\TelegramWebhookController;
use App\Http\Controllers\Webhooks\TikTokWebhookController;
use App\Http\Controllers\Webhooks\WebhookSimulatorController;
use App\Http\Controllers\Webhooks\WhatsAppWebhookController;
use App\Models\Business;
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
    Route::post('/businesses/{business}/auto-reply-rules', [ChannelController::class, 'storeAutoReplyRule'])->name('channels.auto-reply-rules.store');
    Route::put('/businesses/{business}/auto-reply-rules/{rule}', [ChannelController::class, 'updateAutoReplyRule'])->name('channels.auto-reply-rules.update');
    Route::patch('/businesses/{business}/auto-reply-rules/{rule}/toggle', [ChannelController::class, 'toggleAutoReplyRule'])->name('channels.auto-reply-rules.toggle');
    Route::delete('/businesses/{business}/auto-reply-rules/{rule}', [ChannelController::class, 'deleteAutoReplyRule'])->name('channels.auto-reply-rules.destroy');
    Route::get('/businesses/{business}/auto-replies', function (Business $business) {
        return redirect()->route('channels.index', ['business' => $business->id, 'tab' => 'auto_reply']);
    })->name('auto-replies.index');
    // Meta OAuth 2.0 Integration
    Route::get('/businesses/{business}/channels/meta/redirect', [MetaOAuthController::class, 'redirectToMeta'])->name('meta.redirect');
    Route::get('/channels/meta/callback', [MetaOAuthController::class, 'handleCallback'])->name('meta.callback');
    Route::post('/businesses/{business}/channels/meta/persist', [MetaOAuthController::class, 'persistAsset'])->name('meta.persist-asset');
    Route::delete('/businesses/{business}/channels/meta/{channel}', [MetaOAuthController::class, 'disconnectChannel'])->name('meta.disconnect');

    // TikTok OAuth 2.0 Integration
    Route::get('/businesses/{business}/channels/tiktok/redirect', [TikTokOAuthController::class, 'redirectToTikTok'])->name('tiktok.redirect');
    Route::get('/channels/tiktok/callback', [TikTokOAuthController::class, 'handleCallback'])->name('tiktok.callback');
    Route::delete('/businesses/{business}/channels/tiktok', [TikTokOAuthController::class, 'disconnect'])->name('tiktok.disconnect');

    Route::delete('/businesses/{business}/channels/{channel}', [ChannelController::class, 'disconnect'])->name('channels.disconnect');

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
    Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
    Route::get('/contacts/export', [ContactController::class, 'export'])->name('contacts.export');
    Route::get('/contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
    Route::patch('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');

    // Subscription & Plan Upgrade
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::post('/subscription/simulate', [SubscriptionController::class, 'simulatePayment'])->name('subscription.simulate');

    // System User Guide & Knowledge Base
    Route::get('/guide', [GuideController::class, 'index'])->name('guide.index');
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

    // Super Admin Businesses Management
    Route::post('/businesses', [SuperAdminController::class, 'storeBusiness'])->name('businesses.store');
    Route::put('/businesses/{business}', [SuperAdminController::class, 'updateBusiness'])->name('businesses.update');
    Route::delete('/businesses/{business}', [SuperAdminController::class, 'destroyBusiness'])->name('businesses.destroy');

    // Super Admin Pricing Plans Management
    Route::post('/plans', [SuperAdminController::class, 'storePlan'])->name('plans.store');
    Route::put('/plans/{plan}', [SuperAdminController::class, 'updatePlan'])->name('plans.update');
    Route::delete('/plans/{plan}', [SuperAdminController::class, 'destroyPlan'])->name('plans.destroy');
    Route::patch('/plans/{plan}/toggle-active', [SuperAdminController::class, 'togglePlanActive'])->name('plans.toggle-active');

    // Super Admin Gateway & Apps Settings
    Route::post('/fawry/settings', [SuperAdminController::class, 'updateFawrySettings'])->name('fawry.settings');
    Route::post('/settings/meta', [SuperAdminController::class, 'updateMetaSettings'])->name('settings.meta');
    Route::post('/settings/tiktok', [SuperAdminController::class, 'updateTikTokSettings'])->name('settings.tiktok');
    Route::post('/settings/google', [SuperAdminController::class, 'updateGoogleSettings'])->name('settings.google');
    Route::post('/settings/groq', [SuperAdminController::class, 'updateGroqSettings'])->name('settings.groq');

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
Route::post('/webhooks/tiktok', [TikTokWebhookController::class, 'handle'])->name('webhooks.tiktok');
Route::post('/webhooks/tiktok/{business}', [TikTokWebhookController::class, 'handle'])->name('webhooks.tiktok.business');

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
