<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Funcionario extends Model
{
    protected $table = 'funcionarios';
    protected $fillable = ['user_id', 'departamento_id', 'codigo', 'rut', 'nombres', 'apellidos', 'cargo', 'fecha_ingreso', 'regimen', 'activo'];
    protected function casts(): array
    {
        return ['fecha_ingreso' => 'date', 'activo' => 'boolean'];
    }

    public function departamento(): BelongsTo { return $this->belongsTo(Departamento::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function competencias(): BelongsToMany { return $this->belongsToMany(Competencia::class, 'competencia_funcionario')->withPivot('nivel'); }
    public function solicitudesVacaciones(): HasMany { return $this->hasMany(SolicitudVacacion::class); }
    public function saldosVacaciones(): HasMany { return $this->hasMany(SaldoVacacion::class); }
    public function indisponibilidades(): HasMany { return $this->hasMany(Indisponibilidad::class); }
    public function reemplazosAsignados(): HasMany { return $this->hasMany(Reemplazo::class, 'reemplazante_id'); }
}
