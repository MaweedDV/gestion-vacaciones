<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CalendarioLaboral
{
    public function diasHabiles(string $inicio, string $fin): int
    {
        $desde = CarbonImmutable::parse($inicio)->startOfDay();
        $hasta = CarbonImmutable::parse($fin)->startOfDay();
        if ($hasta->lessThan($desde)) {
            throw new \InvalidArgumentException('El fin debe ser posterior al inicio.');
        }
        $feriados = DB::table('feriados')->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->pluck('fecha')->map(fn ($fecha) => substr((string) $fecha, 0, 10))->flip();
        $dias = 0;
        for ($fecha = $desde; $fecha->lessThanOrEqualTo($hasta); $fecha = $fecha->addDay()) {
            if (!$fecha->isWeekend() && !$feriados->has($fecha->toDateString())) {
                $dias++;
            }
        }
        return $dias;
    }
}
