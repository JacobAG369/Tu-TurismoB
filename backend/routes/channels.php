<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| Public Broadcast Channels
|--------------------------------------------------------------------------
|
| The map listens on the public `mapa-actualizaciones` channel. Since it is
| public, no authorization callback is required here.
|
*/
