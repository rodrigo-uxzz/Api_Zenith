<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Abordagem extends Model
{
    protected $table = 'abordagens';
    protected $primaryKey = 'id_abordagem';
    protected $fillable = ['nome', 'descricao'];
}