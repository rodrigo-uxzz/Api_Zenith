<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificarEmailMail;
use App\Models\EmailVerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailVerificationController extends Controller
{
    // Envia ou reenvia o código
    public function enviar(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
        ]);

        // Deleta código anterior se existir
        EmailVerificationCode::where('email', $request->email)->delete();

        // Gera código de 6 dígitos
        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationCode::create([
            'email'     => $request->email,
            'codigo'    => $codigo,
            'expira_em' => now()->addMinutes(15),
        ]);

        Mail::to($request->email)->send(new VerificarEmailMail($request->email, $codigo));

        return response()->json(['message' => 'Código enviado para o email!'], 200);
    }

    // Valida o código
    public function verificar(Request $request)
    {
        $request->validate([
            'email'  => 'required|email',
            'codigo' => 'required|string|size:6',
        ]);

        $verificacao = EmailVerificationCode::where('email', $request->email)
            ->where('codigo', $request->codigo)
            ->first();

        if (!$verificacao) {
            return response()->json(['message' => 'Código inválido.'], 422);
        }

        if (now()->isAfter($verificacao->expira_em)) {
            $verificacao->delete();
            return response()->json(['message' => 'Código expirado. Solicite um novo.'], 422);
        }

        return response()->json(['message' => 'Email verificado! Prossiga com o cadastro.'], 200);
    }
}
