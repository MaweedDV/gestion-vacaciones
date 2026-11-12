<?php

namespace App\Http\Controllers;

use App\Models\Funcionario;
use App\Models\User;
use App\Notifications\EstadoVacaciones;
use App\Models\Auditoria;
use App\Models\SaldoVacacion;
use App\Models\SolicitudVacacion;
use App\Services\CalendarioLaboral;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class SolicitudVacacionController extends Controller
{
    public function index(Request $request)
    {
        $usuario = $request->user();
        $query = SolicitudVacacion::with(['funcionario.departamento', 'aprobaciones']);
        if ($usuario->tieneRol('jefatura')) {
            abort_unless($usuario->departamento_gestion_id, 403, 'Jefatura sin departamento.');
            $query->whereHas('funcionario', fn ($q) => $q->where('departamento_id', $usuario->departamento_gestion_id));
        } elseif ($usuario->tieneRol('funcionario')) {
            $funcionarioId = $usuario->funcionario?->id;
            $query->where('funcionario_id', $funcionarioId ?? 0);
        }
        return view('vacaciones.index', ['solicitudes' => $query->orderByDesc('created_at')->paginate(20)]);
    }

    public function create(Request $request)
    {
        $query = Funcionario::where('activo', true);
        $usuario = $request->user();
        if ($usuario->tieneRol('jefatura', 'aprobador_general')) {
            abort_unless($usuario->departamento_gestion_id || $usuario->tieneRol('aprobador_general'), 403);
            $query->where('user_id', $usuario->id);
        } elseif ($usuario->tieneRol('funcionario')) {
            $query->where('user_id', $usuario->id);
        }
        return view('vacaciones.create', ['funcionarios' => $query->orderBy('apellidos')->get()]);
    }

    public function store(Request $request, CalendarioLaboral $calendario)
    {
        $data = $request->validate([
            'funcionario_id' => ['required', 'exists:funcionarios,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);
        $this->autorizarSolicitudPropia($request->user(), (int)$data['funcionario_id']);
        $desde = CarbonImmutable::parse($data['fecha_inicio']);
        $hasta = CarbonImmutable::parse($data['fecha_fin']);
        if ($desde->year !== $hasta->year) {
            throw ValidationException::withMessages(['fecha_fin' => 'En esta etapa, la solicitud debe pertenecer a un solo año calendario.']);
        }
        if ($desde->lessThan(CarbonImmutable::today())) {
            throw ValidationException::withMessages(['fecha_inicio' => 'No se permiten nuevas solicitudes con fecha de inicio pasada.']);
        }
        $dias = $calendario->diasHabiles($data['fecha_inicio'], $data['fecha_fin']);
        if ($dias === 0) {
            throw ValidationException::withMessages(['fecha_inicio' => 'El período no tiene días hábiles.']);
        }
        DB::transaction(function () use ($data, $dias, $desde) {
            $funcionario = Funcionario::whereKey($data['funcionario_id'])->lockForUpdate()->firstOrFail();
            if (!$funcionario->activo) {
                throw ValidationException::withMessages(['funcionario_id' => 'Funcionario inactivo.']);
            }
            $saldo = SaldoVacacion::where('funcionario_id', $funcionario->id)
                ->where('periodo', $desde->year)->lockForUpdate()->first();
            if (!$saldo) {
                throw ValidationException::withMessages(['funcionario_id' => 'Primero registra el saldo de vacaciones de este año.']);
            }
            $this->validarSolapamientos($funcionario->id, $data['fecha_inicio'], $data['fecha_fin']);
            $reservados = (float) SolicitudVacacion::where('funcionario_id', $funcionario->id)
                ->where('estado', 'pendiente')->whereYear('fecha_inicio', $desde->year)->sum('dias_habiles');
            $disponible = (float)$saldo->dias_asignados + (float)$saldo->dias_arrastrados
                + (float)$saldo->dias_ajuste - (float)$saldo->dias_utilizados - $reservados;
            if ($dias > $disponible + 0.001) {
                throw ValidationException::withMessages(['fecha_inicio' => "Saldo insuficiente considerando solicitudes pendientes. Disponible: {$disponible} días."]);
            }
            $solicitud = SolicitudVacacion::create($data + ['dias_habiles' => $dias, 'estado' => 'pendiente']);
            Auditoria::registrar('solicitud.creada', $solicitud, ['funcionario_id' => $funcionario->id, 'dias_habiles' => $dias]);
            DB::afterCommit(fn () => $this->notificarNuevaSolicitud($solicitud->id));
        });
        return redirect()->route('vacaciones.index')->with('ok', 'Solicitud registrada y pendiente de aprobación.');
    }

    public function aprobar(SolicitudVacacion $solicitud, CalendarioLaboral $calendario)
    {
        $this->autorizarResolucion(auth()->user(), $solicitud);
        DB::transaction(function () use ($solicitud, $calendario) {
            $funcionario = Funcionario::whereKey($solicitud->funcionario_id)->lockForUpdate()->firstOrFail();
            $solicitud = SolicitudVacacion::whereKey($solicitud->id)->lockForUpdate()->firstOrFail();
            if ($solicitud->estado !== 'pendiente') {
                throw ValidationException::withMessages(['estado' => 'La solicitud ya fue resuelta.']);
            }
            if (!$funcionario->activo) {
                throw ValidationException::withMessages(['estado' => 'El funcionario está inactivo.']);
            }
            $dias = $calendario->diasHabiles($solicitud->fecha_inicio->toDateString(), $solicitud->fecha_fin->toDateString());
            if ($dias < 1) {
                throw ValidationException::withMessages(['estado' => 'No hay días hábiles en el período.']);
            }
            $this->validarSolapamientos($funcionario->id, $solicitud->fecha_inicio->toDateString(), $solicitud->fecha_fin->toDateString(), $solicitud->id);
            $saldo = SaldoVacacion::where('funcionario_id', $funcionario->id)
                ->where('periodo', $solicitud->fecha_inicio->year)->lockForUpdate()->firstOrFail();
            $disponible = (float)$saldo->dias_asignados + (float)$saldo->dias_arrastrados
                + (float)$saldo->dias_ajuste - (float)$saldo->dias_utilizados;
            if ($dias > $disponible + 0.001) {
                throw ValidationException::withMessages(['estado' => 'Saldo insuficiente para aprobar.']);
            }
            $saldo->increment('dias_utilizados', $dias);
            $solicitud->update(['estado' => 'aprobada', 'dias_habiles' => $dias, 'resuelta_en' => now()]);
            $solicitud->aprobaciones()->create(['aprobador_user_id' => auth()->id(), 'decision' => 'aprobada']);
            Auditoria::registrar('solicitud.aprobada', $solicitud);
            DB::afterCommit(fn () => $this->notificarResolucion($solicitud->id, 'aprobada'));
        });
        return back()->with('ok', 'Vacaciones aprobadas. El saldo se actualizó.');
    }

    public function rechazar(Request $request, SolicitudVacacion $solicitud)
    {
        $this->autorizarResolucion($request->user(), $solicitud);
        $data = $request->validate(['comentario' => ['required', 'string', 'max:2000']]);
        DB::transaction(function () use ($solicitud, $data) {
            $solicitud = SolicitudVacacion::whereKey($solicitud->id)->lockForUpdate()->firstOrFail();
            if ($solicitud->estado !== 'pendiente') {
                throw ValidationException::withMessages(['estado' => 'La solicitud ya fue resuelta.']);
            }
            $solicitud->reemplazos()->whereIn('estado', ['propuesto','confirmado'])->update(['estado' => 'cancelado']);
            $solicitud->update(['estado' => 'rechazada', 'resuelta_en' => now()]);
            $solicitud->aprobaciones()->create(['aprobador_user_id' => auth()->id(), 'decision' => 'rechazada', 'comentario' => $data['comentario']]);
            Auditoria::registrar('solicitud.rechazada', $solicitud, ['motivo' => $data['comentario']]);
            DB::afterCommit(fn () => $this->notificarResolucion($solicitud->id, 'rechazada'));
        });
        return back()->with('ok', 'Solicitud rechazada.');
    }

    public function cancelar(SolicitudVacacion $solicitud)
    {
        DB::transaction(function () use ($solicitud) {
            $funcionario = Funcionario::whereKey($solicitud->funcionario_id)->lockForUpdate()->firstOrFail();
            $solicitud = SolicitudVacacion::whereKey($solicitud->id)->lockForUpdate()->firstOrFail();
            if (!in_array($solicitud->estado, ['pendiente', 'aprobada'], true)) {
                throw ValidationException::withMessages(['estado' => 'No se puede cancelar esta solicitud.']);
            }
            if ($solicitud->fecha_inicio->lessThanOrEqualTo(CarbonImmutable::today())) {
                throw ValidationException::withMessages(['estado' => 'La cancelación de períodos ya iniciados requiere un proceso administrativo especial.']);
            }
            if ($solicitud->estado === 'aprobada') {
                $saldo = SaldoVacacion::where('funcionario_id', $funcionario->id)
                    ->where('periodo', $solicitud->fecha_inicio->year)->lockForUpdate()->firstOrFail();
                if ((float)$saldo->dias_utilizados < (float)$solicitud->dias_habiles) {
                    throw ValidationException::withMessages(['estado' => 'El saldo presenta una inconsistencia.']);
                }
                $saldo->decrement('dias_utilizados', $solicitud->dias_habiles);
            }
            $solicitud->reemplazos()->whereIn('estado', ['propuesto','confirmado'])->update(['estado' => 'cancelado']);
            $solicitud->update(['estado' => 'cancelada', 'resuelta_en' => now()]);
            $solicitud->aprobaciones()->create(['aprobador_user_id' => auth()->id(), 'decision' => 'cancelada']);
            Auditoria::registrar('solicitud.cancelada', $solicitud);
        });
        return back()->with('ok', 'Solicitud cancelada.');
    }

    private function notificarNuevaSolicitud(int $solicitudId): void
    {
        try {
            $solicitud = SolicitudVacacion::with('funcionario.departamento')->findOrFail($solicitudId);
            $funcionario = $solicitud->funcionario;
            // Jefaturas de la unidad, aprobadores generales: no incluye administradores observadores.
            $destinatarios = User::query()->where('activo', true)
                ->where(function ($q) use ($funcionario) {
                    $q->where('rol', 'aprobador_general')->orWhere(function ($j) use ($funcionario) {
                        $j->where('rol', 'jefatura')->where('departamento_gestion_id', $funcionario->departamento_id);
                    });
                })->where('id', '!=', $funcionario->user_id ?? 0)->get();
            if ($destinatarios->isNotEmpty()) {
                Notification::send($destinatarios, new EstadoVacaciones(
                    $solicitud->id, 'Nueva solicitud pendiente',
                    $funcionario->nombres.' '.$funcionario->apellidos.' solicita vacaciones del '.
                    $solicitud->fecha_inicio->format('d/m/Y').' al '.$solicitud->fecha_fin->format('d/m/Y').'.', 'pendiente'
                ));
            }
        } catch (\Throwable $e) {
            Log::error('No se pudo generar la notificación de solicitud', ['solicitud_id' => $solicitudId, 'error' => $e->getMessage()]);
        }
    }

    private function notificarResolucion(int $solicitudId, string $estado): void
    {
        try {
            $solicitud = SolicitudVacacion::with('funcionario.user')->findOrFail($solicitudId);
            $usuario = $solicitud->funcionario?->user;
            if ($usuario && $usuario->activo) {
                $usuario->notify(new EstadoVacaciones(
                    $solicitud->id,
                    $estado === 'aprobada' ? 'Vacaciones aprobadas' : 'Vacaciones rechazadas',
                    'Tu solicitud del '.$solicitud->fecha_inicio->format('d/m/Y').' al '.
                    $solicitud->fecha_fin->format('d/m/Y').' fue '.$estado.'.', $estado
                ));
            }
        } catch (\Throwable $e) {
            Log::error('No se pudo generar la notificación de resolución', ['solicitud_id' => $solicitudId, 'error' => $e->getMessage()]);
        }
    }

    private function autorizarSolicitudPropia($usuario, int $funcionarioId): void
    {
        $funcionario = Funcionario::findOrFail($funcionarioId);
        // La jefatura solicita sus propias vacaciones; jamás en nombre de subordinados.
        // Administración puede registrar solicitudes institucionales si se necesita regularización.
        if ($usuario->tieneRol('administrador')) return;
        abort_unless($usuario->tieneRol('funcionario', 'jefatura', 'aprobador_general')
            && $funcionario->user_id === $usuario->id, 403);
    }

    private function autorizarResolucion($usuario, SolicitudVacacion $solicitud): void
    {
        // Aprobador general puede resolver en cualquier unidad; jefatura solo en su departamento.
        // El administrador supervisa y no tiene permiso de aprobación por este flujo.
        abort_unless($usuario && $usuario->tieneRol('jefatura', 'aprobador_general'), 403);
        if ($usuario->tieneRol('jefatura')) {
            abort_unless($usuario->departamento_gestion_id
                && $solicitud->funcionario->departamento_id === $usuario->departamento_gestion_id, 403);
        }
        abort_if($solicitud->funcionario->user_id === $usuario->id, 403, 'No puedes resolver tus propias vacaciones.');
    }

    private function validarSolapamientos(int $funcionarioId, string $desde, string $hasta, ?int $excepto = null): void
    {
        $conflicto = SolicitudVacacion::where('funcionario_id', $funcionarioId)
            ->whereIn('estado', ['pendiente', 'aprobada'])
            ->when($excepto, fn ($q) => $q->where('id', '!=', $excepto))
            ->whereDate('fecha_inicio', '<=', $hasta)->whereDate('fecha_fin', '>=', $desde)->exists();
        $indisponible = DB::table('indisponibilidades')->where('funcionario_id', $funcionarioId)
            ->whereDate('fecha_inicio', '<=', $hasta)->whereDate('fecha_fin', '>=', $desde)->exists();
        if ($conflicto || $indisponible) {
            throw ValidationException::withMessages(['fecha_inicio' => 'Las fechas se superponen con otra solicitud o indisponibilidad.']);
        }
    }
}
