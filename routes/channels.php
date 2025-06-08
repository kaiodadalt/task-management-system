<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Domain.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
