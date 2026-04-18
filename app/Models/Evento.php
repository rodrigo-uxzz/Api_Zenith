<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    protected $table = 'eventos';
    protected $primaryKey = 'id_evento';

    public $timestamps = false;

    protected $fillable = [
        'id_psicologo',
        'slug',
        'nome',
        'descricao',
        'data_inicio',
        'data_fim',
        'hora_inicio',
        'hora_fim',
        'tipo_evento',
    ];
}
