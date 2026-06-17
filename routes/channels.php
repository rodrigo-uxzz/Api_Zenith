<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Chat;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{id_chat}', function ($user, $id_chat) {
    return Chat::where('id_chat', $id_chat)
        ->where(function ($q) use ($user) {
            $q->where('id_paciente', $user->id)
              ->orWhere('id_psicologo', $user->id);
        })
        ->exists();
});
