<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{receiver_id}', function ($user, $receiver_id) {
    // dd($user, $receiver_id);
    return (int) $user->id === (int) $receiver_id;
});
