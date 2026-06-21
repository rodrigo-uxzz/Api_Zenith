<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MensagemLida implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $id_chat,
        public int $id_usuario
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("chat.{$this->id_chat}")];
    }

    public function broadcastWith(): array
    {
        return [
            'id_chat'    => $this->id_chat,
            'id_usuario' => $this->id_usuario,
        ];
    }

    public function broadcastAs(): string
    {
        return 'MensagemLida';
    }
}
