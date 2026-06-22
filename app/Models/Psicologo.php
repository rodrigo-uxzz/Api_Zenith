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
        'pix_tipo',
        'pix_chave',
        'pix_nome_recebedor',
        'pix_cidade',
        'link_consulta',
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

    public function avaliacoes()
    {
        return $this->hasMany(Avaliacao::class, 'id_psicologo', 'id_psicologo');
    }
}
