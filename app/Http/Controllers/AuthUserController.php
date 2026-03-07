<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthUserController extends Controller
{
    public function login(Request $request)
    {

        try {

            $credenciais = $request->validate([
                'email' => 'required|email',
                'senha' => 'required',
            ]);

            $user = User::where('email', $credenciais['email'])->first();

            if (! $user || ! Hash::check($credenciais['senha'], $user->senha_hash)) {
                return response()->json(['error' => 'Credenciais inválidas'], 401);
            }

            if ($user->tipo_usuario === 'psicologo' && $user->psicologo->status_psicologo !== 'aprovado') {
                return response()->json(['error' => 'Aguarde verificação da conta'], 403);
            }
       
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'message' => 'Login realizado com sucesso',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 200);

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

    public function perfil(Request $request)
    {
        try {
            $user = $request->user();

            return response()->json([
                'user' => $user,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao obter perfil',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function updatePerfil(Request $request)
    {
        try {

            $user = $request->user();

            $dados = $request->validate([
                'nome' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255|unique:users,email,'.$user->id_usuario.',id_usuario',
                'telefone' => 'sometimes|string|max:20',
                'senha' => 'sometimes|min:6',
               
            ]);

            if (isset($dados['senha'])) {
                $dados['senha_hash'] = Hash::make($dados['senha']);
                unset($dados['senha']);
            }

            $user->update($dados);

            if ($user->tipo_usuario === "psicologo"){
                $user->psicologo()->update([
                    'biografia' => 'sometimes|string|max:255',
                ]);
            }
            


            return response()->json([
                'message' => 'Perfil atualizado com sucesso',
                'user' => $user,
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Erro ao atualizar perfil',
                'details' => $e->getMessage(),
            ], 500);

        }
    }

    public function excluirPerfil(Request $request)
    {
        try {
            $user = $request->user();
            $user->tokens()->delete();
            $user->delete();

            if ($user->tipo_usuario === "psicologo"){
                $user->psicologo()->delete();
            }
            if ($user->tipo_usuario === "paciente"){
                $user->paciente()->delete();
            }

            return response()->json([
                'message' => 'Perfil excluído com sucesso',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao excluir perfil',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
