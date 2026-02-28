<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Register all of the broadcast channels that this application supports.
| Private channels require authentication via Sanctum.
|
*/

// Default user notification channel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Sync channel - each user gets a private sync channel for real-time updates
Broadcast::channel('user.{userId}.sync', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
