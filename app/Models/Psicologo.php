<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Psicologo extends Authenticatable
{
    protected $table = 'psicologo';

    protected $primaryKey = 'id_psicologo';

    protected $fillable = [
        'id_usuario',
        'crp',
        'cadastro_e_psi',
        'grau_formacao',
        'biografia',
        'status_psicologo',
        'preco_sessao',
        'duracao_consulta',
        'intervalo_consulta',
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

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function sessoes()
    {
        return $this->hasMany(Sessao::class, 'id_psicologo');
    }
}
