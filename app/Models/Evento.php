<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    protected $table = 'eventos';
    protected $primaryKey = 'id_evento';

    protected $fillable = [
        'id_psicologo',
        'data_inicio',
        'data_fim',
        'hora_inicio',
        'hora_fim',
        'tipo_evento',
    ];
}
