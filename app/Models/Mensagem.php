<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mensagem extends Model
{
    protected $table = 'mensagem';

    protected $primaryKey = 'id_mensagem';

    protected $fillable = [
        'id_chat',
        'id_remetente',
        'conteudo',
        'data_envio',
        'status_mensagem',
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class, 'id_chat');
    }

    public function remetente()
    {
        return $this->belongsTo(User::class, 'id_remetente', 'id_usuario');
    }

    protected $casts = [
        'data_envio' => 'datetime',
    ];
}
