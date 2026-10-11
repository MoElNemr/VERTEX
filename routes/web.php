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

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth:web,team'])->name('dashboard');

Route::middleware('auth:web,team')->group(function () {
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
    Route::post('/businesses/{business}/channels/whatsapp/qr', [\App\Http\Controllers\ChannelController::class, 'generateWhatsAppQr'])->name('channels.whatsapp.qr');
    Route::post('/businesses/{business}/channels/whatsapp/pairing-code', [\App\Http\Controllers\ChannelController::class, 'requestWhatsAppPairingCode'])->name('channels.whatsapp.pairing-code');
    Route::get('/businesses/{business}/channels/whatsapp/status', [\App\Http\Controllers\ChannelController::class, 'checkWhatsAppStatus'])->name('channels.whatsapp.status');
    Route::delete('/businesses/{business}/channels/{channel}', [\App\Http\Controllers\ChannelController::class, 'disconnect'])->name('channels.disconnect');

    // Unified Inbox
    Route::get('/inbox', [\App\Http\Controllers\InboxController::class, 'index'])->name('inbox.index');
    Route::get('/inbox/{conversation}/messages', [\App\Http\Controllers\InboxController::class, 'messages'])->name('inbox.messages');
    Route::post('/inbox/{conversation}/messages', [\App\Http\Controllers\InboxController::class, 'sendMessage'])->name('inbox.send');
    Route::patch('/inbox/{conversation}/status', [\App\Http\Controllers\InboxController::class, 'updateStatus'])->name('inbox.status');
    Route::patch('/inbox/{conversation}/assign', [\App\Http\Controllers\InboxController::class, 'assignAgent'])->name('inbox.assign');

    // Team Management
    Route::get('/team', [\App\Http\Controllers\TeamMemberController::class, 'index'])->name('team.index');
    Route::post('/team', [\App\Http\Controllers\TeamMemberController::class, 'store'])->name('team.store');
    Route::put('/team/{teamMember}', [\App\Http\Controllers\TeamMemberController::class, 'update'])->name('team.update');
    Route::patch('/team/{teamMember}/toggle-status', [\App\Http\Controllers\TeamMemberController::class, 'toggleStatus'])->name('team.toggle-status');
    Route::delete('/team/{teamMember}', [\App\Http\Controllers\TeamMemberController::class, 'destroy'])->name('team.destroy');
});

// Incoming Public Webhooks (Exempt from CSRF)
Route::post('/webhooks/telegram/{business}', [\App\Http\Controllers\Webhooks\TelegramWebhookController::class, 'handle'])->name('webhooks.telegram');
Route::get('/webhooks/meta/{business}', [\App\Http\Controllers\Webhooks\MetaWebhookController::class, 'verify'])->name('webhooks.meta.verify');
Route::post('/webhooks/meta/{business}', [\App\Http\Controllers\Webhooks\MetaWebhookController::class, 'handle'])->name('webhooks.meta');
Route::post('/webhooks/whatsapp/{business}', [\App\Http\Controllers\Webhooks\WhatsAppWebhookController::class, 'handle'])->name('webhooks.whatsapp');

// Webhook Testing & Simulation (Allows testing full inbound flows)
Route::prefix('webhooks/simulate')->group(function () {
    Route::post('/telegram/{business}', [\App\Http\Controllers\Webhooks\WebhookSimulatorController::class, 'simulateTelegram'])->name('webhooks.simulate.telegram');
    Route::post('/facebook/{business}', [\App\Http\Controllers\Webhooks\WebhookSimulatorController::class, 'simulateFacebook'])->name('webhooks.simulate.facebook');
    Route::post('/instagram/{business}', [\App\Http\Controllers\Webhooks\WebhookSimulatorController::class, 'simulateInstagram'])->name('webhooks.simulate.instagram');
    Route::post('/whatsapp/{business}', [\App\Http\Controllers\Webhooks\WebhookSimulatorController::class, 'simulateWhatsApp'])->name('webhooks.simulate.whatsapp');
});

require __DIR__.'/auth.php';
