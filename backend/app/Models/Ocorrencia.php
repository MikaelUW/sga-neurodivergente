<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\CriptografiaService;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Ocorrencia extends Model
{
    use HasFactory;

    protected $table = 'ocorrencias';

    protected $fillable = [
        'aluno_id',
        'monitora_id',
        'data_hora',
        'descricao_fatos',
        'tipo_intervencao',
    ];

    protected $casts = [
        'data_hora' => 'datetime',
    ];

    protected function descricaoFatos(): Attribute
    {
        $service = new CriptografiaService();
        return Attribute::make(
            get: fn ($value) => $service->decifrarDados($value),
            set: fn ($value) => $service->cifrarDados($value),
        );
    }

    protected function tipoIntervencao(): Attribute
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

    public function monitora()
    {
        return $this->belongsTo(Monitora::class);
    }
}
