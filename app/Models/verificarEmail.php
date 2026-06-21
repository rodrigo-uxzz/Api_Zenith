<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class verificarEmail extends Model
{
    protected $table = 'verificacao_email';

    protected $fillable = [
        'email',
        'codigo',
        'expiracao',
    ];
}
