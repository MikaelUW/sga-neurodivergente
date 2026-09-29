<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\CriptografiaService;
use Illuminate\Database\Eloquent\Casts\Attribute;

class LaudoMedico extends Model
{
    use HasFactory;

    protected $table = 'laudos_medicos';

    protected $fillable = [
        'aluno_id',
        'path_arquivo',
        'crm_medico',
        'data_upload',
        'especialidade',
    ];

    protected function crmMedico(): Attribute
    {
        $service = new CriptografiaService();
        return Attribute::make(
            get: fn ($value) => $service->decifrarDados($value),
            set: fn ($value) => $service->cifrarDados($value),
        );
    }

    protected function especialidade(): Attribute
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
