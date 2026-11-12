<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reemplazo extends Model
{
    protected $table = 'reemplazos';
    protected $fillable = ['solicitud_vacacion_id', 'reemplazante_id', 'asignado_por_user_id', 'fecha_inicio', 'fecha_fin', 'estado', 'observaciones'];
    protected function casts(): array
    {
        return ['fecha_inicio' => 'date', 'fecha_fin' => 'date'];
    }

    public function solicitudVacacion(): BelongsTo { return $this->belongsTo(SolicitudVacacion::class); }
    public function reemplazante(): BelongsTo { return $this->belongsTo(Funcionario::class, 'reemplazante_id'); }
    public function asignadoPor(): BelongsTo { return $this->belongsTo(User::class, 'asignado_por_user_id'); }
}
