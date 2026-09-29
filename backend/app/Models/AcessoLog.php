<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcessoLog extends Model
{
    use HasFactory;

    protected $table = 'acesso_logs';

    protected $fillable = [
        'user_id',
        'aluno_id',
        'recurso',
        'acao',
        'ip_address',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function aluno()
    {
        return $this->belongsTo(Aluno::class);
    }
}
