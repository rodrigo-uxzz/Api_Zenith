<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use App\Models\Chat;
use App\Models\Paciente;
use App\Models\Psicologo;

Broadcast::channel('chat.{id_chat}', function ($user, $id_chat) {
    Log::info('Broadcasting auth tentativa', [
        'id_usuario' => $user->id_usuario ?? 'NULL',
        'id'         => $user->id ?? 'NULL',
        'id_chat'    => $id_chat,
    ]);

    $paciente  = Paciente::where('id_usuario', $user->id_usuario)->first();
    $psicologo = Psicologo::where('id_usuario', $user->id_usuario)->first();

    Log::info('Broadcasting auth resultado', [
        'paciente'  => $paciente?->id_paciente ?? 'NULL',
        'psicologo' => $psicologo?->id_psicologo ?? 'NULL',
    ]);

    return Chat::where('id_chat', $id_chat)
        ->where(function ($q) use ($paciente, $psicologo) {
            if ($paciente) {
                $q->orWhere('id_paciente', $paciente->id_paciente);
            }
            if ($psicologo) {
                $q->orWhere('id_psicologo', $psicologo->id_psicologo);
            }
        })
        ->exists();
});
