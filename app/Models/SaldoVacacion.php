<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaldoVacacion extends Model
{
    protected $table = 'saldos_vacaciones';
    protected $fillable = ['funcionario_id', 'periodo', 'dias_asignados', 'dias_arrastrados', 'dias_ajuste', 'dias_utilizados', 'observaciones'];
    protected function casts(): array
    {
        return ['dias_asignados' => 'decimal:2', 'dias_arrastrados' => 'decimal:2', 'dias_ajuste' => 'decimal:2', 'dias_utilizados' => 'decimal:2'];
    }

    public function funcionario(): BelongsTo { return $this->belongsTo(Funcionario::class); }
}
