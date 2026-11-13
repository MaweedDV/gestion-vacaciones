<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Funcionario;
use App\Models\Reemplazo;
use App\Models\SolicitudVacacion;
use App\Services\PlanificadorReemplazos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReemplazoController extends Controller
{
    /** Administrador: todas las unidades. Jefatura: únicamente su propia unidad. */
    private function autorizarUnidad(SolicitudVacacion $solicitud): void
    {
        $usuario = auth()->user();
        abort_unless($usuario && $usuario->tieneRol('administrador', 'jefatura'), 403);
        if ($usuario->tieneRol('jefatura')) {
            abort_unless($usuario->departamento_gestion_id
                && (int) $solicitud->funcionario->departamento_id === (int) $usuario->departamento_gestion_id,
                403, 'Solo puedes administrar reemplazos de tu departamento.');
        }
    }

    private function autorizarCandidato(Funcionario $persona, SolicitudVacacion $solicitud): void
    {
        // Una jefatura no puede disponer unilateralmente del personal de otra unidad.
        if (auth()->user()->tieneRol('jefatura')) {
            abort_unless((int) $persona->departamento_id === (int) $solicitud->funcionario->departamento_id,
                403, 'La jefatura solo puede designar reemplazantes de su propia unidad.');
        }
    }

    public function index()
    {
        $usuario = auth()->user();
        $consulta = SolicitudVacacion::with(['funcionario.departamento', 'reemplazos.reemplazante'])
            ->whereIn('estado', ['pendiente', 'aprobada']);
        if ($usuario->tieneRol('jefatura')) {
            abort_unless($usuario->departamento_gestion_id, 403, 'Jefatura sin departamento asignado.');
            $consulta->whereHas('funcionario', fn ($q) => $q->where('departamento_id', $usuario->departamento_gestion_id));
        }
        return view('reemplazos.index', [
            'solicitudes' => $consulta->orderBy('fecha_inicio')->paginate(20),
        ]);
    }

    public function create(SolicitudVacacion $solicitud, PlanificadorReemplazos $planificador)
    {
        $this->autorizarUnidad($solicitud);
        abort_unless(in_array($solicitud->estado, ['pendiente', 'aprobada'], true), 404);
        $solicitud->load('funcionario.departamento');
        $desde = request()->query('desde', $solicitud->fecha_inicio->toDateString());
        $hasta = request()->query('hasta', $solicitud->fecha_fin->toDateString());
        if (!is_string($desde) || !is_string($hasta)
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)
            || $desde < $solicitud->fecha_inicio->toDateString()
            || $hasta > $solicitud->fecha_fin->toDateString() || $hasta < $desde) {
            return redirect()->route('reemplazos.create', $solicitud)
                ->withErrors(['fecha_inicio' => 'Rango de fechas inválido.']);
        }

        $sugerencias = $planificador->sugerencias($solicitud, $desde, $hasta);
        if (auth()->user()->tieneRol('jefatura')) {
            $sugerencias = array_values(array_filter($sugerencias,
                fn ($item) => (int) $item['funcionario']->departamento_id === (int) $solicitud->funcionario->departamento_id));
        }

        return view('reemplazos.create', [
            'solicitud' => $solicitud, 'desde' => $desde, 'hasta' => $hasta,
            'sugerencias' => $sugerencias,
            'alerta' => $planificador->alertaDotacion($solicitud, $desde, $hasta),
        ]);
    }

    public function store(Request $request, SolicitudVacacion $solicitud, PlanificadorReemplazos $planificador)
    {
        $this->autorizarUnidad($solicitud);
        $data = $request->validate([
            'reemplazante_id' => ['required', 'integer', 'exists:funcionarios,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);
        DB::transaction(function () use ($solicitud, $data, $planificador) {
            // Bloqueo por persona para evitar asignaciones simultáneas incompatibles.
            $persona = Funcionario::whereKey($data['reemplazante_id'])->lockForUpdate()->firstOrFail();
            $solicitud = SolicitudVacacion::whereKey($solicitud->id)->lockForUpdate()->firstOrFail();
            $this->autorizarUnidad($solicitud);
            $this->autorizarCandidato($persona, $solicitud);
            if (!in_array($solicitud->estado, ['pendiente', 'aprobada'], true)) {
                throw ValidationException::withMessages(['estado' => 'La solicitud ya no admite reemplazos.']);
            }
            $planificador->validar($solicitud, $persona, $data['fecha_inicio'], $data['fecha_fin']);
            $reemplazo = Reemplazo::create([
                'solicitud_vacacion_id' => $solicitud->id,
                'reemplazante_id' => $persona->id,
                'fecha_inicio' => $data['fecha_inicio'], 'fecha_fin' => $data['fecha_fin'],
                'asignado_por_user_id' => auth()->id(), 'estado' => 'propuesto',
                'observaciones' => $data['observaciones'] ?? null,
            ]);
            Auditoria::registrar('reemplazo.propuesto', $reemplazo, ['solicitud_id' => $solicitud->id]);
        });
        return redirect()->route('reemplazos.index')->with('ok', 'Reemplazo propuesto. Puedes confirmarlo cuando se aprueben las vacaciones.');
    }

    public function confirmar(Reemplazo $reemplazo, PlanificadorReemplazos $planificador)
    {
        $this->autorizarUnidad($reemplazo->solicitudVacacion);
        DB::transaction(function () use ($reemplazo, $planificador) {
            $persona = Funcionario::whereKey($reemplazo->reemplazante_id)->lockForUpdate()->firstOrFail();
            $reemplazo = Reemplazo::whereKey($reemplazo->id)->lockForUpdate()->firstOrFail();
            $solicitud = SolicitudVacacion::whereKey($reemplazo->solicitud_vacacion_id)->lockForUpdate()->firstOrFail();
            $this->autorizarUnidad($solicitud);
            $this->autorizarCandidato($persona, $solicitud);
            if ($reemplazo->estado !== 'propuesto') {
                throw ValidationException::withMessages(['estado' => 'El reemplazo no está propuesto.']);
            }
            if ($solicitud->estado !== 'aprobada') {
                throw ValidationException::withMessages(['estado' => 'Primero se deben aprobar las vacaciones.']);
            }
            // Evita que el mismo reemplazo se detecte a sí mismo como conflicto.
            // Si falla la validación, la transacción revierte esta modificación temporal.
            $reemplazo->update(['estado' => 'cancelado']);
            $planificador->validar($solicitud, $persona,
                $reemplazo->fecha_inicio->toDateString(), $reemplazo->fecha_fin->toDateString());
            $reemplazo->update(['estado' => 'confirmado']);
            Auditoria::registrar('reemplazo.confirmado', $reemplazo);
        });
        return back()->with('ok', 'Reemplazo confirmado por la jefatura o administración.');
    }

    public function rechazar(Request $request, Reemplazo $reemplazo)
    {
        $this->autorizarUnidad($reemplazo->solicitudVacacion);
        $data = $request->validate(['motivo' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($reemplazo, $data) {
            $reemplazo = Reemplazo::whereKey($reemplazo->id)->lockForUpdate()->firstOrFail();
            $this->autorizarUnidad($reemplazo->solicitudVacacion);
            if ($reemplazo->estado !== 'propuesto') {
                throw ValidationException::withMessages(['estado' => 'Solo se pueden rechazar reemplazos propuestos.']);
            }
            $reemplazo->update([
                'estado' => 'rechazado',
                'observaciones' => trim(($reemplazo->observaciones ? $reemplazo->observaciones."\n" : '').'Motivo de rechazo: '.$data['motivo']),
            ]);
            Auditoria::registrar('reemplazo.rechazado', $reemplazo, ['motivo' => $data['motivo']]);
        });
        return back()->with('ok', 'Propuesta de reemplazo rechazada.');
    }

    public function cancelar(Reemplazo $reemplazo)
    {
        $this->autorizarUnidad($reemplazo->solicitudVacacion);
        DB::transaction(function () use ($reemplazo) {
            $reemplazo = Reemplazo::whereKey($reemplazo->id)->lockForUpdate()->firstOrFail();
            $this->autorizarUnidad($reemplazo->solicitudVacacion);
            if (!in_array($reemplazo->estado, ['propuesto', 'confirmado'], true)) {
                throw ValidationException::withMessages(['estado' => 'Este reemplazo ya no está activo.']);
            }
            if ($reemplazo->fecha_inicio->isPast()) {
                throw ValidationException::withMessages(['estado' => 'Los reemplazos iniciados requieren regularización administrativa.']);
            }
            $reemplazo->update(['estado' => 'cancelado']);
            Auditoria::registrar('reemplazo.cancelado', $reemplazo);
        });
        return back()->with('ok', 'Reemplazo cancelado.');
    }
}
