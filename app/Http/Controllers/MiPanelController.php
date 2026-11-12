<?php

namespace App\Http\Controllers;

use App\Models\SaldoVacacion;
use App\Models\SolicitudVacacion;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MiPanelController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $funcionario = $usuario->funcionario()->with('departamento')->first();
        $periodo = CarbonImmutable::today()->year;

        // Nunca se consulta el funcionario por un ID recibido del navegador.
        // La identidad se obtiene exclusivamente de la sesión autenticada.
        if (! $funcionario) {
            return view('mi-panel.index', [
                'funcionario' => null,
                'periodo' => $periodo,
            ]);
        }

        $saldo = SaldoVacacion::query()
            ->where('funcionario_id', $funcionario->id)
            ->where('periodo', $periodo)->first();

        $resumen = SolicitudVacacion::query()
            ->where('funcionario_id', $funcionario->id)
            ->selectRaw('estado, COUNT(*) AS cantidad')
            ->groupBy('estado')->pluck('cantidad', 'estado');

        $pendientesReservados = (float) SolicitudVacacion::query()
            ->where('funcionario_id', $funcionario->id)
            ->where('estado', 'pendiente')
            ->whereYear('fecha_inicio', $periodo)
            ->sum('dias_habiles');

        $asignados = $saldo ? (float) $saldo->dias_asignados : 0.0;
        $arrastrados = $saldo ? (float) $saldo->dias_arrastrados : 0.0;
        $ajuste = $saldo ? (float) $saldo->dias_ajuste : 0.0;
        $utilizados = $saldo ? (float) $saldo->dias_utilizados : 0.0;
        $saldoContable = $asignados + $arrastrados + $ajuste - $utilizados;
        $disponibleSolicitable = $saldoContable - $pendientesReservados;

        $recientes = SolicitudVacacion::query()
            ->where('funcionario_id', $funcionario->id)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('mi-panel.index', compact(
            'funcionario', 'periodo', 'saldo', 'asignados', 'arrastrados', 'ajuste',
            'utilizados', 'saldoContable', 'pendientesReservados', 'disponibleSolicitable',
            'resumen', 'recientes'
        ));
    }
}
