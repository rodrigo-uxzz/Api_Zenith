<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Database\Eloquent\Model;

class Psicologo extends Authenticatable
{
    protected $table = 'psicologo';
    protected $primaryKey = 'id_psicologo';

    protected $fillable = [
        'id_usuario',
        'crp',
        'cadastro_e-psi',
        'grau_formacao',
        'biografia',
        'status_psicologo',
    ];
}
