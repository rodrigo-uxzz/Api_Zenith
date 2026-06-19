<?php

namespace App\Http\Controllers;

use App\Events\MensagemEnviada;
use App\Events\MensagemLida;
use App\Models\Chat;
use App\Models\Mensagem;
use App\Models\Paciente;
use App\Models\Psicologo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function iniciarChat(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'id_psicologo' => 'required|exists:psicologo,id_psicologo',
            ]);

            // ✅ pega o paciente do usuário autenticado
            $id_paciente = auth()->user()->paciente->id_paciente;

            $chat = Chat::firstOrCreate(
                [
                    'id_paciente' => $id_paciente,
                    'id_psicologo' => $request->id_psicologo,
                ],
                [
                    'status_chat' => 'ativo',
                ]
            );

            DB::commit();

            return response()->json([
                'message' => 'Chat iniciado com sucesso',
                'chat' => $chat,
            ], 200);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Erro ao iniciar chat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function enviar(Request $request)
    {
        DB::beginTransaction();
        try {

            $request->validate([
                'id_chat' => 'required|exists:chat,id_chat',
                'conteudo' => 'required|string|max:1000',
            ]);

            $mensagem = Mensagem::create([
                'id_chat' => $request->id_chat,
                'id_remetente' => auth()->user()->id_usuario,
                'conteudo' => $request->conteudo,
                'data_envio' => now(),
                'status_mensagem' => 'enviada',
            ]);

            broadcast(new MensagemEnviada($mensagem));

            DB::commit();

            return response()->json([
                'message' => 'Mensagem enviada com sucesso',
                'mensagem' => $mensagem,
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Erro ao enviar mensagem',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function historico($id_chat)
    {
        try {
            $idUsuario = auth()->user()->id_usuario;

            $paciente = Paciente::where('id_usuario', $idUsuario)->first();
            $psicologo = Psicologo::where('id_usuario', $idUsuario)->first();

            $chat = Chat::where('id_chat', $id_chat)
                ->where(function ($q) use ($paciente, $psicologo) {
                    if ($paciente) {
                        $q->orWhere('id_paciente', $paciente->id_paciente);
                    }
                    if ($psicologo) {
                        $q->orWhere('id_psicologo', $psicologo->id_psicologo);
                    }
                })
                ->firstOrFail();

            $mensagens = Mensagem::where('id_chat', $chat->id_chat)
                ->orderBy('data_envio')
                ->get();

            return response()->json($mensagens, 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Chat não encontrado ou acesso negado.',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar histórico.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function visualizar($id_chat)
    {
        DB::beginTransaction();
        try {
            $idUsuario = auth()->user()->id_usuario;

            Mensagem::where('id_chat', $id_chat)
                ->where('id_remetente', '!=', $idUsuario)
                ->where('status_mensagem', '!=', 'lida')
                ->update(['status_mensagem' => 'lida']);

            broadcast(new MensagemLida($id_chat, $idUsuario))->toOthers();

            DB::commit();

            return response()->json(['message' => 'Mensagens marcadas como lidas'], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erro ao marcar mensagens como lidas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
