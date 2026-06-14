<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RecuperarSenhaMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class RecuperarSenhaController extends Controller
{
    // PASSO 1 - Envia o código pro email
    public function enviarCodigo(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Deleta código anterior se existir
        EmailVerificationCode::where('email', $request->email)->delete();

        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationCode::create([
            'email' => $request->email,
            'codigo' => $codigo,
            'expira_em' => now()->addMinutes(15),
        ]);

        Mail::to($user->email)->send(new RecuperarSenhaMail($user->nome, $codigo));

        return response()->json([
            'message' => 'Código de recuperação enviado para o email!',
        ], 200);
    }

    // PASSO 2 - Valida o código
    public function verificarCodigo(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'codigo' => 'required|string|size:6',
        ]);

        $verificacao = EmailVerificationCode::where('email', $request->email)
            ->where('codigo', $request->codigo)
            ->first();

        if (! $verificacao) {
            return response()->json([
                'error' => 'Código inválido',
                'message' => 'Verifique o código e tente novamente.',
            ], 422);
        }

        if (now()->isAfter($verificacao->expira_em)) {
            $verificacao->delete();

            return response()->json([
                'error' => 'Código expirado',
                'message' => 'Solicite um novo código.',
            ], 422);
        }

        return response()->json([
            'message' => 'Código válido! Prossiga para redefinir a senha.',
        ], 200);
    }

    // PASSO 3 - Redefine a senha
    public function redefinirSenha(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'codigo' => 'required|string|size:6',
            'senha' => 'required|string|min:6',
            'confirmar_senha' => 'required|same:senha',
        ]);

        $verificacao = EmailVerificationCode::where('email', $request->email)
            ->where('codigo', $request->codigo)
            ->first();

        if (! $verificacao) {
            return response()->json([
                'error' => 'Código inválido',
                'message' => 'Verifique o código e tente novamente.',
            ], 422);
        }

        if (now()->isAfter($verificacao->expira_em)) {
            $verificacao->delete();

            return response()->json([
                'error' => 'Código expirado',
                'message' => 'Solicite um novo código.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $user = User::where('email', $request->email)->first();

            $user->senha_hash = Hash::make($request->senha);
            $user->save();

            $verificacao->delete();

            DB::commit();

            return response()->json([
                'message' => 'Senha redefinida com sucesso!',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao redefinir senha',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
