<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Paciente extends Authenticatable
{
    protected $table = 'paciente';

    protected $primaryKey = 'id_paciente';

    protected $fillable = [
        'id_usuario',
        'observacoes',
        'status_paciente',

    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
