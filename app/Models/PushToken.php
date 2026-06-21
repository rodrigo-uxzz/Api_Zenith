<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushToken extends Model
{
    protected $table = 'push_tokens';

    protected $fillable = [
        'id_usuario',
        'token',
        'platform'
    ];
}