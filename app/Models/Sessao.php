<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sessao extends Model
{
    protected $table = 'sessao';
    protected $primaryKey = 'id_sessao';

    protected $fillable = [
        'id_paciente',
        'id_psicologo',
        'data_sessao',
        'hora_inicio',
        'hora_fim',
        'valor',
        'status_sessao',
    ];
}
