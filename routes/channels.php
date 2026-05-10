<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('ticket.{id}', function ($user, $id) {
    return $user !== null;
});

Broadcast::channel('tickets.new', function ($user) {
    return $user->hasRole('admin') || $user->hasRole('manager');
});
