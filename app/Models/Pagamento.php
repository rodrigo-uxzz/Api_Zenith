<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pagamento extends Model
{
    protected $table = 'pagamento';

    protected $primaryKey = 'id_pagamento';

    public $timestamps = true;

    protected $fillable = [
        'id_sessao',
        'id_paciente',
        'id_psicologo',
        'tipo_pagamento',
        'status_pagamento',
        'valor_total',
    ];
    
    public function sessao()
    {
        return $this->belongsTo(Sessao::class, 'id_sessao');
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function psicologo()
    {
        return $this->belongsTo(Psicologo::class, 'id_psicologo');
    }
}
