<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('business.{businessId}', function ($user, $businessId) {
    $authUser = Auth::guard('web')->user() ?? Auth::guard('team')->user() ?? $user;

    if ($authUser instanceof \App\Models\User) {
        return $authUser->businesses()->where('businesses.id', $businessId)->exists();
    }

    if ($authUser instanceof \App\Models\TeamMember) {
        return $authUser->businesses()->where('businesses.id', $businessId)->exists();
    }

    return false;
}, ['guards' => ['web', 'team']]);
