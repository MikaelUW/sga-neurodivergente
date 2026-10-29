<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\CriptografiaService;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Aluno extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alunos';

    protected $fillable = [
        'nome',
        'data_nascimento',
        'necessidades_especiais',
        'observacoes_gerais',
        'responsavel_id',
        'consentimento_lgpd_assinado',
        'data_consentimento',
        'versao_termo_lgpd',
    ];

    protected $casts = [
        'consentimento_lgpd_assinado' => 'boolean',
        'data_consentimento' => 'datetime',
    ];

    /**
     * Criptografia automatica dos atributos sensíveis.
     */
    protected function nome(): Attribute
    {
        $service = new CriptografiaService();
        return Attribute::make(
            get: fn ($value) => $service->decifrarDados($value),
            set: fn ($value) => [
                'nome' => $service->cifrarDados($value),
                // Mantém o blind index sincronizado com o nome.
                'nome_hash' => $service->blindIndex($value),
            ],
        );
    }

    /**
     * Busca alunos por nome usando o blind index (igualdade exata,
     * case-insensitive). Não expõe o texto claro no SQL.
     */
    public function scopeBuscarPorNome($query, ?string $nome)
    {
        if (is_null($nome) || trim($nome) === '') {
            return $query;
        }
        $hash = (new CriptografiaService())->blindIndex($nome);
        return $query->where('nome_hash', $hash);
    }

    protected function necessidadesEspeciais(): Attribute
    {
        $service = new CriptografiaService();
        return Attribute::make(
            get: fn ($value) => $service->decifrarDados($value),
            set: fn ($value) => $service->cifrarDados($value),
        );
    }

    protected function observacoesGerais(): Attribute
    {
        $service = new CriptografiaService();
        return Attribute::make(
            get: fn ($value) => $service->decifrarDados($value),
            set: fn ($value) => $service->cifrarDados($value),
        );
    }

    public function responsavel()
    {
        return $this->belongsTo(Responsavel::class, 'responsavel_id');
    }

    public function infoOperacionais()
    {
        return $this->hasMany(InfoOperacional::class);
    }

    public function laudosMedicos()
    {
        return $this->hasMany(LaudoMedico::class);
    }

    public function ocorrencias()
    {
        return $this->hasMany(Ocorrencia::class);
    }

    public function acessoLogs()
    {
        return $this->hasMany(AcessoLog::class);
    }

    public function monitoras()
    {
        return $this->belongsToMany(Monitora::class, 'aluno_monitora');
    }
}
