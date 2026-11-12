<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Funcionario;
use App\Models\Indisponibilidad;
use App\Models\Reemplazo;
use App\Models\SolicitudVacacion;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarioController extends Controller
{
    public function index(): View
    {
        return view('calendario.index', [
            'departamentos' => Departamento::orderBy('nombre')->get(),
        ]);
    }

    /** Endpoint privado utilizado por FullCalendar. El fin del rango es exclusivo. */
    public function eventos(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
            'departamento_id' => ['nullable', 'integer', 'exists:departamentos,id'],
            'tipo' => ['nullable', 'in:todos,vacaciones,reemplazos,alertas'],
        ]);

        $inicio = CarbonImmutable::parse($data['start'])->startOfDay();
        $finExclusivo = CarbonImmutable::parse($data['end'])->startOfDay();
        if ($inicio->diffInDays($finExclusivo) > 62) {
            return response()->json(['message' => 'La consulta no puede superar 62 días.'], 422);
        }
        $desde = $inicio->toDateString();
        $hasta = $finExclusivo->subDay()->toDateString();
        $departamento = isset($data['departamento_id']) ? (int) $data['departamento_id'] : null;
        $tipo = $data['tipo'] ?? 'todos';
        $eventos = [];

        if (in_array($tipo, ['todos', 'vacaciones'], true)) {
            $solicitudes = SolicitudVacacion::with('funcionario.departamento')
                ->whereIn('estado', ['pendiente', 'aprobada'])
                ->whereDate('fecha_inicio', '<=', $hasta)
                ->whereDate('fecha_fin', '>=', $desde)
                ->when($departamento, fn ($q) => $q->whereHas('funcionario', fn ($f) => $f->where('departamento_id', $departamento)))
                ->get();
            foreach ($solicitudes as $solicitud) {
                $nombre = trim($solicitud->funcionario->nombres.' '.$solicitud->funcionario->apellidos);
                $aprobada = $solicitud->estado === 'aprobada';
                $eventos[] = [
                    'id' => 'v-'.$solicitud->id,
                    'title' => ($aprobada ? 'Vacaciones: ' : 'Pendiente: ').$nombre,
                    'start' => $solicitud->fecha_inicio->toDateString(),
                    'end' => $solicitud->fecha_fin->addDay()->toDateString(),
                    'allDay' => true,
                    'color' => $aprobada ? '#198754' : '#e0a800',
                    'url' => route('vacaciones.index'),
                    'extendedProps' => [
                        'tipo' => 'vacaciones', 'estado' => $solicitud->estado,
                        'funcionario' => $nombre,
                        'departamento' => $solicitud->funcionario->departamento?->nombre,
                    ],
                ];
            }
        }

        if (in_array($tipo, ['todos', 'reemplazos'], true)) {
            $reemplazos = Reemplazo::with(['reemplazante.departamento', 'solicitudVacacion.funcionario.departamento'])
                ->whereIn('estado', ['propuesto', 'confirmado'])
                ->whereDate('fecha_inicio', '<=', $hasta)
                ->whereDate('fecha_fin', '>=', $desde)
                ->when($departamento, fn ($q) => $q->whereHas('solicitudVacacion.funcionario', fn ($f) => $f->where('departamento_id', $departamento)))
                ->get();
            foreach ($reemplazos as $reemplazo) {
                $titular = $reemplazo->solicitudVacacion->funcionario;
                $reemplazante = $reemplazo->reemplazante;
                $confirmado = $reemplazo->estado === 'confirmado';
                $eventos[] = [
                    'id' => 'r-'.$reemplazo->id,
                    'title' => ($confirmado ? 'Reemplazo: ' : 'Propuesta: ').$reemplazante->nombres.' '.$reemplazante->apellidos,
                    'start' => $reemplazo->fecha_inicio->toDateString(),
                    'end' => $reemplazo->fecha_fin->addDay()->toDateString(),
                    'allDay' => true,
                    'color' => $confirmado ? '#0d6efd' : '#6c757d',
                    'url' => route('reemplazos.index'),
                    'extendedProps' => [
                        'tipo' => 'reemplazo', 'estado' => $reemplazo->estado,
                        'funcionario' => $reemplazante->nombres.' '.$reemplazante->apellidos,
                        'titular' => $titular->nombres.' '.$titular->apellidos,
                        'departamento' => $titular->departamento?->nombre,
                    ],
                ];
            }
        }

        if (in_array($tipo, ['todos', 'alertas'], true)) {
            array_push($eventos, ...$this->alertasDotacion($inicio, $finExclusivo, $departamento));
        }

        return response()->json($eventos);
    }

    /** Alertas orientativas de dotación administrativa para días hábiles. */
    private function alertasDotacion(CarbonImmutable $inicio, CarbonImmutable $finExclusivo, ?int $departamentoId): array
    {
        $departamentos = Departamento::where('activo', true)
            ->when($departamentoId, fn ($q) => $q->whereKey($departamentoId))->get();
        if ($departamentos->isEmpty()) return [];

        $desde = $inicio->toDateString();
        $hasta = $finExclusivo->subDay()->toDateString();
        $personas = Funcionario::where('activo', true)->get(['id', 'departamento_id']);
        $vacaciones = SolicitudVacacion::where('estado', 'aprobada')
            ->whereDate('fecha_inicio', '<=', $hasta)->whereDate('fecha_fin', '>=', $desde)
            ->get(['funcionario_id', 'fecha_inicio', 'fecha_fin']);
        $ausencias = Indisponibilidad::whereDate('fecha_inicio', '<=', $hasta)
            ->whereDate('fecha_fin', '>=', $desde)
            ->get(['funcionario_id', 'fecha_inicio', 'fecha_fin']);
        $reemplazos = Reemplazo::with(['reemplazante', 'solicitudVacacion.funcionario'])
            ->where('estado', 'confirmado')
            ->whereDate('fecha_inicio', '<=', $hasta)->whereDate('fecha_fin', '>=', $desde)->get();
        $feriados = \App\Models\Feriado::whereBetween('fecha', [$desde, $hasta])->pluck('fecha')
            ->map(fn ($fecha) => substr((string) $fecha, 0, 10))->flip();

        $personalPorUnidad = $personas->groupBy('departamento_id');
        $vacacionesPorPersona = $vacaciones->groupBy('funcionario_id');
        $ausenciasPorPersona = $ausencias->groupBy('funcionario_id');
        $eventos = [];
        for ($dia = $inicio; $dia->lessThan($finExclusivo); $dia = $dia->addDay()) {
            $fecha = $dia->toDateString();
            if ($dia->isWeekend() || $feriados->has($fecha)) continue;
            foreach ($departamentos as $unidad) {
                $presentes = 0;
                $idsPresentes = [];
                foreach ($personalPorUnidad->get($unidad->id, collect()) as $persona) {
                    $ocupado = $vacacionesPorPersona->get($persona->id, collect())->contains(fn ($v) => $v->fecha_inicio->toDateString() <= $fecha && $v->fecha_fin->toDateString() >= $fecha)
                        || $ausenciasPorPersona->get($persona->id, collect())->contains(fn ($a) => $a->fecha_inicio->toDateString() <= $fecha && $a->fecha_fin->toDateString() >= $fecha);
                    if (!$ocupado) {
                        $idsPresentes[$persona->id] = true;
                    }
                }
                // Un reemplazo interno no agrega personal; uno externo traslada capacidad entre unidades.
                foreach ($reemplazos as $reemplazo) {
                    if ($reemplazo->fecha_inicio->toDateString() > $fecha || $reemplazo->fecha_fin->toDateString() < $fecha) continue;
                    $origen = $reemplazo->reemplazante->departamento_id;
                    $destino = $reemplazo->solicitudVacacion->funcionario->departamento_id;
                    if ($origen === $destino) continue;
                    if ($origen === $unidad->id) unset($idsPresentes[$reemplazo->reemplazante_id]);
                    if ($destino === $unidad->id) {
                        $p = $reemplazo->reemplazante;
                        $ausente = $vacacionesPorPersona->get($p->id, collect())->contains(fn ($v) => $v->fecha_inicio->toDateString() <= $fecha && $v->fecha_fin->toDateString() >= $fecha)
                            || $ausenciasPorPersona->get($p->id, collect())->contains(fn ($a) => $a->fecha_inicio->toDateString() <= $fecha && $a->fecha_fin->toDateString() >= $fecha);
                        if ($p->activo && !$ausente) $idsPresentes[$p->id] = true;
                    }
                }
                $presentes = count($idsPresentes);
                if ($presentes < $unidad->dotacion_minima) {
                    $eventos[] = [
                        'id' => 'a-'.$unidad->id.'-'.$fecha,
                        'title' => '⚠ '.$unidad->nombre.': '.$presentes.'/'.$unidad->dotacion_minima,
                        'start' => $fecha,
                        'allDay' => true,
                        'color' => '#dc3545',
                        'extendedProps' => [
                            'tipo' => 'alerta', 'estado' => 'dotacion_insuficiente',
                            'departamento' => $unidad->nombre, 'presentes' => $presentes,
                            'minimo' => $unidad->dotacion_minima,
                        ],
                    ];
                }
            }
        }
        return $eventos;
    }
}
