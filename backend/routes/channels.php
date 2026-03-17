<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// The 'turismo-updates' channel is public, hence no authorization callback is required.
// Anyone can listen to events broadcasted on this channel.
