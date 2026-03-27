<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $table = 'users';
    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'nome',
        'username',
        'email',
        'telefone',
        'genero',
        'senha_hash',
        'data_nascimento',
        'cpf',
        'tipo_usuario',
        'status_usuario',
        'termos_aceitos',
        'foto_perfil'
    ];


    public function psicologo()
    {
        return $this->hasOne(Psicologo::class, 'id_usuario', 'id_usuario');

    }

    public function paciente ()
    {
        return $this->hasOne(Paciente::class, 'id_usuario', 'id_usuario');

    }


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'senha_hash',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
