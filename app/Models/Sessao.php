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
        'data_solicitada',
        'hora_solicitada',
        'observacoes',
        'anotacoes',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function psicologo()
    {
        return $this->belongsTo(Psicologo::class, 'id_psicologo');
    }
}
