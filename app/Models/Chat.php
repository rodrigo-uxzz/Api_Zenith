<?php

namespace App\Models;

use App\Models\Paciente;
use App\Models\Psicologo;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $table = 'chat';

    protected $primaryKey = 'id_chat';

    protected $fillable = [
        'id_paciente',
        'id_psicologo',
        'status_chat',
    ];

    public function mensagens()
    {
        return $this->hasMany(Mensagem::class, 'id_chat');
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    public function psicologo()
    {
        return $this->belongsTo(Psicologo::class, 'id_psicologo', 'id_psicologo');
    }
}
