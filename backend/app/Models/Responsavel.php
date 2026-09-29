<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Responsavel extends Model
{
    use HasFactory;

    protected $table = 'responsaveis';

    protected $fillable = [
        'nome',
        'cpf',
        'email',
        'telefone',
    ];

    public function alunos()
    {
        return $this->hasMany(Aluno::class, 'responsavel_id');
    }
}
