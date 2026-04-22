<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agenda extends Model
{
    protected $table = 'agenda';

    protected $primaryKey = 'id_agenda';

    protected $fillable = [
        'id_psicologo',
        'dia_semana',
        'hora_inicio',
        'hora_fim',
        'status_agenda',
    ];
}
