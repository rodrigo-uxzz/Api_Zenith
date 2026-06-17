<?php

namespace App\Events;

use App\Models\Mensagem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MensagemEnviada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Mensagem $mensagem) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("chat.{$this->mensagem->id_chat}")
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id_mensagem'    => $this->mensagem->id_mensagem,
            'id_chat'        => $this->mensagem->id_chat,
            'id_remetente'   => $this->mensagem->id_remetente,
            'nome_remetente' => $this->mensagem->remetente->nome,
            'conteudo'       => $this->mensagem->conteudo,
            'data_envio'     => $this->mensagem->data_envio,
            'status_mensagem'=> $this->mensagem->status_mensagem,
        ];
    }

    public function broadcastAs(): string
    {
        return 'MensagemEnviada';
    }
}
