<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitudVacacion extends Model
{
    protected $table = 'solicitudes_vacaciones';
    protected $fillable = ['funcionario_id', 'fecha_inicio', 'fecha_fin', 'dias_habiles', 'estado', 'observaciones', 'resuelta_en'];
    protected function casts(): array
    {
        return ['fecha_inicio' => 'date', 'fecha_fin' => 'date', 'dias_habiles' => 'decimal:2', 'resuelta_en' => 'datetime'];
    }

    public function funcionario(): BelongsTo { return $this->belongsTo(Funcionario::class); }
    public function aprobaciones(): HasMany { return $this->hasMany(AprobacionVacacion::class); }
    public function reemplazos(): HasMany { return $this->hasMany(Reemplazo::class); }
}
