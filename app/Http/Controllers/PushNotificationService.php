<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PushToken;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Http;

class PushNotificationService
{
    public function send(
        User $user,
        string $title,
        string $body,
        array $data = []
    ) {
        $tokens = $user->pushTokens()->pluck('token');

        \Log::info('Tokens encontrados:', $tokens->toArray());

        // Salva o log uma única vez por notificação enviada ao usuário
        NotificationLog::create([
            'id_usuario' => $user->id,
            'title'      => $title,
            'body'       => $body,
            'data'       => $data,
            'sent_at'    => now(),
        ]);

        foreach ($tokens as $token) {
            try {
                $response = Http::timeout(10)->post('https://exp.host/--/api/v2/push/send', [
                    'to'    => $token,
                    'title' => $title,
                    'body'  => $body,
                    'data'  => $data,
                ]);

                \Log::info('Resposta Expo:', $response->json());

            } catch (\Exception $e) {
                \Log::error('Erro ao enviar push:', ['erro' => $e->getMessage()]);
            }
        }
    }
}