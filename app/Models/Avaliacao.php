<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Avaliacao extends Model
{
    protected $table = 'avaliacao';
    protected $primaryKey = 'id_avaliacao';

    protected $fillable = [
        'id_paciente',
        'id_psicologo',
        'nota',
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
