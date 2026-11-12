<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Funcionario;
use App\Models\Feriado;
use App\Models\Indisponibilidad;
use App\Models\Reemplazo;
use App\Models\SolicitudVacacion;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $hoy = CarbonImmutable::today();
        $fecha = $hoy->toDateString();
        $limite = $hoy->addDays(30)->toDateString();

        $funcionariosActivos = Funcionario::where('activo', true)->count();
        $solicitudesPendientes = SolicitudVacacion::where('estado', 'pendiente')->count();
        $vacacionesHoy = SolicitudVacacion::where('estado', 'aprobada')
            ->whereHas('funcionario', fn ($q) => $q->where('activo', true))
            ->whereDate('fecha_inicio', '<=', $fecha)->whereDate('fecha_fin', '>=', $fecha)
            ->distinct()->count('funcionario_id');
        $reemplazosPorConfirmar = Reemplazo::where('estado', 'propuesto')
            ->whereHas('solicitudVacacion', fn ($q) => $q->where('estado', 'aprobada'))
            ->whereDate('fecha_fin', '>=', $fecha)->count();

        $proximas = SolicitudVacacion::with(['funcionario.departamento', 'reemplazos'])
            ->where('estado', 'aprobada')
            ->whereHas('funcionario', fn ($q) => $q->where('activo', true))
            ->whereDate('fecha_inicio', '>=', $fecha)
            ->whereDate('fecha_inicio', '<=', $limite)
            ->orderBy('fecha_inicio')->limit(6)->get();
        $pendientes = SolicitudVacacion::with('funcionario.departamento')
            ->where('estado', 'pendiente')->orderBy('created_at')->limit(6)->get();

        // Resumen de cobertura para hoy; orientativo, no valida competencias o media jornada.
        $alertas = collect();
        $diaNoLaborable = $hoy->isWeekend() || Feriado::whereDate('fecha', $fecha)->exists();
        if (!$diaNoLaborable) {
            $personas = Funcionario::where('activo', true)->get(['id', 'departamento_id']);
            $vacaciones = SolicitudVacacion::where('estado', 'aprobada')
                ->whereDate('fecha_inicio', '<=', $fecha)->whereDate('fecha_fin', '>=', $fecha)
                ->pluck('funcionario_id')->flip();
            $indisponibles = Indisponibilidad::whereDate('fecha_inicio', '<=', $fecha)
                ->whereDate('fecha_fin', '>=', $fecha)->pluck('funcionario_id')->flip();
            $confirmados = Reemplazo::with('solicitudVacacion.funcionario')
                ->where('estado', 'confirmado')
                ->whereDate('fecha_inicio', '<=', $fecha)->whereDate('fecha_fin', '>=', $fecha)
                ->whereHas('solicitudVacacion', fn ($q) => $q->where('estado', 'aprobada'))
                ->get();
            $dotacion = [];
            foreach ($personas as $p) {
                if (!$vacaciones->has($p->id) && !$indisponibles->has($p->id)) {
                    $dotacion[$p->departamento_id][$p->id] = true;
                }
            }
            foreach ($confirmados as $reemplazo) {
                $persona = $personas->firstWhere('id', $reemplazo->reemplazante_id);
                if (!$persona || $vacaciones->has($persona->id) || $indisponibles->has($persona->id)) continue;
                $origen = $persona->departamento_id;
                $destino = $reemplazo->solicitudVacacion->funcionario->departamento_id;
                if ($origen !== $destino) {
                    unset($dotacion[$origen][$persona->id]);
                    $dotacion[$destino][$persona->id] = true;
                }
            }
            $alertas = Departamento::where('activo', true)->orderBy('nombre')->get()
                ->map(function ($d) use ($dotacion) {
                    $d->presentes_estimados = count($dotacion[$d->id] ?? []);
                    return $d;
                })->filter(fn ($d) => $d->presentes_estimados < $d->dotacion_minima)->values();
        }

        return view('dashboard.index', compact(
            'hoy', 'funcionariosActivos', 'solicitudesPendientes', 'vacacionesHoy',
            'reemplazosPorConfirmar', 'proximas', 'pendientes', 'alertas', 'diaNoLaborable'
        ));
    }
}
