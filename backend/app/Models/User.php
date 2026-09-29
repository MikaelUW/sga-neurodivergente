<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'login',
        'password',
        'perfil',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function administrador()
    {
        return $this->hasOne(Administrador::class);
    }

    public function monitora()
    {
        return $this->hasOne(Monitora::class);
    }

    public function isAdmin(): bool
    {
        return $this->perfil === 'admin';
    }

    public function isMonitora(): bool
    {
        return $this->perfil === 'monitora';
    }
}
