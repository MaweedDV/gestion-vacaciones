<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'rol', 'activo', 'departamento_gestion_id', 'is_admin'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_admin' => 'boolean', 'activo' => 'boolean'];
    }

    public function funcionario(): HasOne { return $this->hasOne(Funcionario::class); }
    public function departamentoGestion(): BelongsTo { return $this->belongsTo(Departamento::class, 'departamento_gestion_id'); }

    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->rol, $roles, true);
    }
}
