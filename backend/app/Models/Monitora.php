<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Monitora extends Model
{
    use HasFactory;

    protected $table = 'monitoras';

    protected $fillable = [
        'user_id',
        'classes',
        'cpf',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ocorrencias()
    {
        return $this->hasMany(Ocorrencia::class);
    }

    public function alunos()
    {
        return $this->belongsToMany(Aluno::class, 'aluno_monitora');
    }
}
