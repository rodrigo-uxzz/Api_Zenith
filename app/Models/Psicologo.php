<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Models\Abordagem;
use App\Models\Especialidade;
use App\Models\Atendimento;

class Psicologo extends Authenticatable
{
    protected $table = 'psicologo';
    protected $primaryKey = 'id_psicologo';

    protected $fillable = [
        'id_usuario',
        'crp',
        'cadastro_epsi',
        'grau_formacao',
        'biografia',
        'status_psicologo',
        'avaliacao',
    ];

    // App/Models/Psicologo.php

    public function abordagens()
    {
        return $this->belongsToMany(
            Abordagem::class,
            'psicologo_abordagem',
            'id_psicologo',
            'id_abordagem'
        );
    }

    public function especialidades()
    {
        return $this->belongsToMany(
            Especialidade::class,
            'psicologo_especialidade',
            'id_psicologo',
            'id_especialidade'
        );
    }

    public function atendimentos()
    {
        return $this->belongsToMany(
            Atendimento::class,
            'psicologo_atendimento',
            'id_psicologo',
            'id_atendimento'
        );
    }

    public function user() {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
