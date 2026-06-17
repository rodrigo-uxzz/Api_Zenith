<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PushToken;
use Illuminate\Support\Facades\Http;

class PushNotificationService
{
    public function send(
        User $user,
        string $title,
        string $body,
        array $data = []
    )
    {
        $tokens =
            $user->pushTokens()
                 ->pluck('token');

        \Log::info('Tokens encontrados:', $tokens->toArray());

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