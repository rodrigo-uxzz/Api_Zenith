<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthAdminsController extends Controller
{
    public function login(Request $request)
    {

        try {

            $credenciais = $request->validate([
                'login' => 'required|string',
                'senha' => 'required',
            ]);

            $user = User::where('email', $credenciais['login'])->first();

            if ($user->tipo_usuario !== 'admin') {
                return response()->json([
                    'error' => 'Acesso negado!',
                ], 404);
            }

            if ($user->status_usuario !== 'ativo') {
                return response()->json([
                    'error' => 'usuario desativado',
                ], 403);
            } else {

                if (! $user || ! Hash::check($credenciais['senha'], $user->senha_hash)) {
                    return response()->json(['error' => 'Credenciais inválidas'], 401);
                }

                $token = $user->createToken('auth-token')->plainTextToken;

                return response()->json([
                    'message' => 'Login realizado com sucesso',
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'user' => $user,
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao processar login',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json(['message' => 'Logout realizado com sucesso'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao processar logout',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
