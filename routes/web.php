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
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
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
    Route::delete('/businesses/{business}/channels/{channel}', [\App\Http\Controllers\ChannelController::class, 'disconnect'])->name('channels.disconnect');

    // Unified Inbox
    Route::get('/inbox', [\App\Http\Controllers\InboxController::class, 'index'])->name('inbox.index');
    Route::get('/inbox/{conversation}/messages', [\App\Http\Controllers\InboxController::class, 'messages'])->name('inbox.messages');
    Route::post('/inbox/{conversation}/messages', [\App\Http\Controllers\InboxController::class, 'sendMessage'])->name('inbox.send');
    Route::patch('/inbox/{conversation}/status', [\App\Http\Controllers\InboxController::class, 'updateStatus'])->name('inbox.status');
});

// Incoming Public Webhooks (Exempt from CSRF)
Route::post('/webhooks/telegram/{business}', function ($business) {
    return response()->json(['status' => 'received']);
})->name('webhooks.telegram');

Route::match(['get', 'post'], '/webhooks/meta/{business}', function ($business) {
    return response('ok');
})->name('webhooks.meta');

require __DIR__.'/auth.php';
