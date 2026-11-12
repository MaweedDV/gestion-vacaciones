<?php
namespace App\Services;

use App\Models\Funcionario;
use App\Models\Reemplazo;
use App\Models\SolicitudVacacion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlanificadorReemplazos
{
    /** Listado orientativo, nunca asignación automática sin decisión humana. */
    public function sugerencias(SolicitudVacacion $solicitud, string $desde, string $hasta): array
    {
        $titular = $solicitud->funcionario()->with('competencias')->firstOrFail();
        $competencias = $titular->competencias->pluck('id')->all();
        $candidatos = Funcionario::query()->with(['departamento', 'competencias'])
            ->where('activo', true)->whereKeyNot($titular->id)->get();
        $resultado = [];
        foreach ($candidatos as $persona) {
            $motivos = $this->impedimentos($solicitud, $persona, $desde, $hasta);
            $coincidentes = $persona->competencias->whereIn('id', $competencias)->count();
            $mismaUnidad = $persona->departamento_id === $titular->departamento_id;
            $carga = Reemplazo::where('reemplazante_id', $persona->id)
                ->where('estado', 'confirmado')->whereDate('fecha_inicio', '>=', CarbonImmutable::parse($desde)->subYear()->toDateString())
                ->count();
            // Las competencias registradas sirven para priorizar, nunca certifican idoneidad.
            $puntaje = ($mismaUnidad ? 30 : 0) + ($coincidentes * 10) - min($carga, 20) * 3;
            $resultado[] = [
                'funcionario' => $persona, 'disponible' => count($motivos) === 0,
                'motivos' => $motivos, 'coincidentes' => $coincidentes,
                'total_competencias_titular' => count($competencias),
                'misma_unidad' => $mismaUnidad, 'carga' => $carga, 'puntaje' => $puntaje,
            ];
        }
        usort($resultado, fn($a,$b) => ((int)$b['disponible'] <=> (int)$a['disponible'])
            ?: ($b['puntaje'] <=> $a['puntaje']) ?: ($a['funcionario']->id <=> $b['funcionario']->id));
        return $resultado;
    }

    public function validar(SolicitudVacacion $solicitud, Funcionario $persona, string $desde, string $hasta): void
    {
        $motivos = $this->impedimentos($solicitud, $persona, $desde, $hasta);
        if ($motivos) throw ValidationException::withMessages(['reemplazante_id' => implode(' ', $motivos)]);
    }

    public function impedimentos(SolicitudVacacion $solicitud, Funcionario $persona, string $desde, string $hasta): array
    {
        $motivos = [];
        $inicio = CarbonImmutable::parse($desde); $termino = CarbonImmutable::parse($hasta);
        if (!$persona->activo) $motivos[] = 'Funcionario inactivo.';
        if ($persona->id === $solicitud->funcionario_id) $motivos[] = 'El titular no puede reemplazarse a sí mismo.';
        if ($inicio->greaterThan($termino) || $inicio->lessThan($solicitud->fecha_inicio) || $termino->greaterThan($solicitud->fecha_fin))
            $motivos[] = 'Las fechas deben estar dentro de las vacaciones del titular.';
        if (DB::table('solicitudes_vacaciones')->where('funcionario_id', $persona->id)
            ->whereIn('estado', ['pendiente','aprobada'])->whereDate('fecha_inicio','<=',$hasta)
            ->whereDate('fecha_fin','>=',$desde)->exists()) $motivos[] = 'Tiene vacaciones solicitadas o aprobadas.';
        if (DB::table('indisponibilidades')->where('funcionario_id', $persona->id)
            ->whereDate('fecha_inicio','<=',$hasta)->whereDate('fecha_fin','>=',$desde)->exists())
            $motivos[] = 'Tiene una indisponibilidad registrada.';
        if (Reemplazo::where('reemplazante_id',$persona->id)->whereIn('estado',['propuesto','confirmado'])
            ->whereDate('fecha_inicio','<=',$hasta)->whereDate('fecha_fin','>=',$desde)->exists())
            $motivos[] = 'Ya tiene otro reemplazo en el período.';
        // No retirar a alguien de otra unidad si eso incumple su dotación mínima.
        $titular = $solicitud->funcionario;
        if ($persona->departamento_id !== $titular->departamento_id) {
            $minimo = $persona->departamento?->dotacion_minima ?? 0;
            foreach ($this->fechasHabiles($desde, $hasta) as $dia) {
                $presentes = $this->disponiblesUnidad($persona->departamento_id, $dia, $persona->id);
                if ($presentes < $minimo) {
                    $motivos[] = 'Su unidad de origen quedaría bajo la dotación mínima el '.$dia.'.'; break;
                }
            }
        }
        return $motivos;
    }

    /** Dotación neta, sin incluir al funcionario excluido ni ausentes o cedidos a otra unidad. */
    private function disponiblesUnidad(int $departamentoId, string $dia, ?int $excluir = null): int
    {
        return Funcionario::where('departamento_id',$departamentoId)->where('activo',true)
            ->when($excluir, fn($q) => $q->whereKeyNot($excluir))
            ->whereDoesntHave('solicitudesVacaciones',fn($q)=>$q->where('estado','aprobada')
                ->whereDate('fecha_inicio','<=',$dia)->whereDate('fecha_fin','>=',$dia))
            ->whereDoesntHave('indisponibilidades',fn($q)=>$q->whereDate('fecha_inicio','<=',$dia)->whereDate('fecha_fin','>=',$dia))
            ->whereDoesntHave('reemplazosAsignados', fn($q)=>$q->where('estado','confirmado')
                ->whereDate('fecha_inicio','<=',$dia)->whereDate('fecha_fin','>=',$dia)
                ->whereHas('solicitudVacacion.funcionario',fn($q)=>$q->where('departamento_id','!=',$departamentoId)))
            ->count();
    }

    /** Comprueba dotación mínima para la unidad del titular, con reemplazos externos confirmados. */
    public function alertaDotacion(SolicitudVacacion $solicitud, string $desde, string $hasta): ?string
    {
        $departamento = $solicitud->funcionario->departamento;
        if (!$departamento) return 'El titular no tiene departamento.';
        foreach ($this->fechasHabiles($desde, $hasta) as $dia) {
            $presentes = $this->disponiblesUnidad($departamento->id, $dia);
            $entrantes = Reemplazo::where('solicitud_vacacion_id',$solicitud->id)
                ->where('estado','confirmado')->whereDate('fecha_inicio','<=',$dia)->whereDate('fecha_fin','>=',$dia)
                ->whereHas('reemplazante',fn($q)=>$q->where('departamento_id','!=',$departamento->id))->count();
            if ($presentes + $entrantes < $departamento->dotacion_minima) {
                return 'La unidad tendría '.($presentes+$entrantes).' funcionarios disponibles el '.$dia.'; mínimo: '.$departamento->dotacion_minima.'.';
            }
        }
        return null;
    }

    private function fechasHabiles(string $desde, string $hasta): array
    {
        $inicio = CarbonImmutable::parse($desde); $fin = CarbonImmutable::parse($hasta);
        if ($fin->diffInDays($inicio) > 370) return []; // período de vacaciones extraordinariamente largo: no analizar aquí
        $feriados = DB::table('feriados')->whereBetween('fecha',[$desde,$hasta])->pluck('fecha')->all();
        $fechas = [];
        for ($dia=$inicio; $dia->lessThanOrEqualTo($fin); $dia=$dia->addDay()) {
            if (!$dia->isWeekend() && !in_array($dia->toDateString(),$feriados,true)) $fechas[]=$dia->toDateString();
        }
        return $fechas;
    }
}
