<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Especialidade extends Model
{
    protected $table = 'especialidades';
    protected $primaryKey = 'id_especialidade';
    protected $fillable = ['nome', 'descricao'];
}