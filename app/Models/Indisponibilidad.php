<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Indisponibilidad extends Model
{
    protected $table = 'indisponibilidades';
    protected $fillable = ['funcionario_id', 'fecha_inicio', 'fecha_fin', 'tipo', 'detalle'];
    protected function casts(): array
    {
        return ['fecha_inicio' => 'date', 'fecha_fin' => 'date'];
    }

    public function funcionario(): BelongsTo { return $this->belongsTo(Funcionario::class); }
}
