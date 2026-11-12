<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AprobacionVacacion extends Model
{
    protected $table = 'aprobaciones_vacaciones';
    protected $fillable = ['solicitud_vacacion_id', 'aprobador_user_id', 'decision', 'comentario'];

    public function solicitudVacacion(): BelongsTo { return $this->belongsTo(SolicitudVacacion::class); }
    public function aprobador(): BelongsTo { return $this->belongsTo(User::class, 'aprobador_user_id'); }
}
