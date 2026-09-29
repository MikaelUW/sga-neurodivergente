<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\CriptografiaService;
use Illuminate\Database\Eloquent\Casts\Attribute;

class InfoOperacional extends Model
{
    use HasFactory;

    protected $table = 'info_operacional';

    protected $fillable = [
        'aluno_id',
        'gatilho_comportamental',
        'acao_recomendada',
        'prioridade',
    ];

    protected function gatilhoComportamental(): Attribute
    {
        $service = new CriptografiaService();
        return Attribute::make(
            get: fn ($value) => $service->decifrarDados($value),
            set: fn ($value) => $service->cifrarDados($value),
        );
    }

    protected function acaoRecomendada(): Attribute
    {
        $service = new CriptografiaService();
        return Attribute::make(
            get: fn ($value) => $service->decifrarDados($value),
            set: fn ($value) => $service->cifrarDados($value),
        );
    }

    public function aluno()
    {
        return $this->belongsTo(Aluno::class);
    }
}
