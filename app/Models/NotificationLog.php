<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $table = 'notification_logs';

    protected $fillable = [
        'id_usuario',
        'title',
        'body',
        'data',
        'sent_at',
    ];

    protected $casts = [
        'data'    => 'array',
        'sent_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}