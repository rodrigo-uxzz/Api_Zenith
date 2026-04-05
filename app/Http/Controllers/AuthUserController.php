<?php

namespace App\Http\Controllers;

use App\Models\Psicologo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
// A Função de verificarUserCPF
use Illuminate\Support\Facades\Storage;

class AuthUserController extends Controller
{
    public function login(Request $request)
    {

        try {

            $credenciais = $request->validate([
                'login' => 'required|string',
                'senha' => 'required',
            ]);

            $user = User::where('email', $credenciais['login'])->first();

            if ($user->status_usuario !== 'ativo') {
                return response()->json([
                    'error' => 'usuario desativado',
                ], 403);
            } else {

                if (! $user) {
                    $psicologo = Psicologo::where('crp', $credenciais['login'])->first();

                    if ($psicologo) {
                        $user = User::find($psicologo->id_usuario);
                    }
                }

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

    public function perfil(Request $request)
    {
        try {

            $user = $request->user();
            $psicologo = Psicologo::where('id_usuario', $user->id_usuario)->first();

            return response()->json([
                'user' => $user,
                'psicologo' => $psicologo,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao obter perfil',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function verificarUserCPF(Request $request) // *O certo é "verificar Disponibilidade" mas eu prefiro assim!
    {
        try {

            $dados = $request->validate([
                'username' => 'string|required_without:cpf',
                'cpf' => 'string|required_without:username',
            ]);

            $usernameExiste = false;
            $cpfExiste = false;

            if (! empty($dados['username'])) {
                $usernameExiste = User::where('username', $dados['username'])->exists();
            }

            if (! empty($dados['cpf'])) {

                // remove máscara do CPF
                $cpf = preg_replace('/\D/', '', $dados['cpf']);

                $cpfExiste = User::where('cpf', $cpf)->exists();
            }

            return response()->json([
                'username_disponivel' => ! $usernameExiste,
                'cpf_disponivel' => ! $cpfExiste,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao verificar disponibilidade',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function redefinirSenha(Request $request)
    {
        $dados = $request->validate([
            'token_verificacao' => 'required',
            'nova_senha' => 'required|min:8',
        ]);

        $userId = Cache::get('verificacao_'.$dados['token_verificacao']);

        if (! $userId) {
            return response()->json(['error' => 'Token inválido ou expirado'], 401);
        }

        $user = User::find($userId);

        $user->update([
            'senha_hash' => Hash::make($dados['nova_senha']),
        ]);

        Cache::forget('verificacao_'.$dados['token_verificacao']);

        return response()->json(['message' => 'Senha atualizada com sucesso']);
    }

    public function updatePerfil(Request $request)
    {
        try {

            $user = $request->user();

            $dados = $request->validate([
                'nome' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255|unique:users,email,'.$user->id_usuario.',id_usuario',
                'telefone' => 'sometimes|string|max:20',
                'senha' => 'sometimes|min:8',
                'biografia' => 'sometimes|string|max:255',
                'foto_perfil' => 'sometimes|image|mimes:jpg,jpeg,png|max:5120',

            ]);

            if ($request->hasFile('foto_perfil')) {

                if ($user->foto_perfil) {
                    Storage::disk('public')->delete($user->foto_perfil);
                }

                $fotoPerfil = $request->file('foto_perfil')->store('fotos', 'public');
                $dados['foto_perfil'] = $fotoPerfil;
            }

            if (isset($dados['senha'])) {
                $dados['senha_hash'] = Hash::make($dados['senha']);
                unset($dados['senha']);
            }

            $user->update($dados);

            if ($user->tipo_usuario === 'psicologo' && isset($dados['biografia'])) {
                $user->psicologo()->update([
                    'biografia' => $dados['biografia'],
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
            DB::transaction();

            $user = $request->user();
            $user->status_usuario = 'excluido';

            $user->save();

            DB::commit();

            return response()->json([
                'message' => 'Perfil excluído com sucesso',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao excluir perfil',
                'details' => $e->getMessage(),
            ], 500);

        }
    }
}
