<?php

namespace App\Http\Controllers;

use App\Models\PushToken;
use Illuminate\Http\Request;


class PushTokenController extends Controller
{
    public function store(Request $request)
    {
        \Log::info('save-push-token chamado', [
            'user'  => auth()->id(),
            'token' => $request->token,
            'all'   => $request->all(),
        ]);

        $request->validate([
            'token' => 'required'
        ]);

        PushToken::updateOrCreate(
            [
                'token' => $request->token
            ],
            [
                'id_usuario' => auth()->id(),
                'platform' => $request->platform ?? 'android'
            ]
        );

        return response()->json([
            'success' => true
        ]);
    }
}