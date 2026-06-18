<?php

namespace App\Http\Controllers;

use App\Models\NotificationLog;
use Illuminate\Http\Request;

class NotificationLogController extends Controller
{
    /**
     * GET /api/notifications
     * Retorna as notificações do usuário autenticado, mais recentes primeiro.
     */
    public function index(Request $request)
    {
        $notifications = NotificationLog::where('id_usuario', auth()->user()->id_usuario)
            ->orderBy('sent_at', 'desc')
            ->get()
            ->map(fn($n) => [
                'id'       => $n->id,
                'title'    => $n->title,
                'body'     => $n->body,
                'data'     => $n->data,
                'sent_at'  => $n->sent_at->toISOString(),
            ]);

        return response()->json($notifications);
    }
}