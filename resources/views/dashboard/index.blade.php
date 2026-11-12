@extends('layouts.panel')
@section('title', 'Panel de control')
@section('content')
<div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
    <div class="institutional-heading mb-0"><img src="{{ asset('images/logo-institucion.png') }}" alt="Escudo institucional"><div><h1 class="h3 mb-0">Resumen general</h1><p class="page-subtitle mb-0">Vacaciones, personal y continuidad del servicio.</p></div></div>
    <a href="{{ route('vacaciones.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Nueva solicitud</a>
</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="metric-card"><div class="d-flex justify-content-between align-items-start"><span class="metric-label">Funcionarios activos</span><span class="metric-icon metric-blue"><i class="bi bi-people"></i></span></div><div class="metric-value">{{ number_format($funcionariosActivos,0,',','.') }}</div><div class="metric-foot">Registrados en el sistema</div></div></div>
    <div class="col-6 col-xl-3"><div class="metric-card"><div class="d-flex justify-content-between align-items-start"><span class="metric-label">Solicitudes pendientes</span><span class="metric-icon metric-amber"><i class="bi bi-hourglass-split"></i></span></div><div class="metric-value">{{ $solicitudesPendientes }}</div><div class="metric-foot">Requieren revisión</div></div></div>
    <div class="col-6 col-xl-3"><div class="metric-card"><div class="d-flex justify-content-between align-items-start"><span class="metric-label">De vacaciones hoy</span><span class="metric-icon metric-green"><i class="bi bi-calendar2-check"></i></span></div><div class="metric-value">{{ $vacacionesHoy }}</div><div class="metric-foot">Vacaciones aprobadas vigentes</div></div></div>
    <div class="col-6 col-xl-3"><div class="metric-card"><div class="d-flex justify-content-between align-items-start"><span class="metric-label">Reemplazos por confirmar</span><span class="metric-icon metric-purple"><i class="bi bi-arrow-left-right"></i></span></div><div class="metric-value">{{ $reemplazosPorConfirmar }}</div><div class="metric-foot">Propuestos para vacaciones aprobadas</div></div></div>
</div>
<div class="row g-4">
    <div class="col-12 col-xxl-8 section-stack">
        <section class="card"><div class="card-header d-flex justify-content-between align-items-center"><div><h2 class="section-heading">Próximas vacaciones</h2><span class="section-caption">Comienzan dentro de los próximos 30 días</span></div><a href="{{ route('calendario.index') }}" class="btn btn-sm btn-outline-primary">Ver calendario</a></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Funcionario</th><th>Departamento</th><th>Desde</th><th>Hasta</th><th>Cobertura</th></tr></thead><tbody>
        @forelse($proximas as $s)<tr><td><strong>{{ $s->funcionario->nombres }} {{ $s->funcionario->apellidos }}</strong></td><td>{{ $s->funcionario->departamento?->nombre ?? 'Sin departamento' }}</td><td>{{ $s->fecha_inicio->format('d/m/Y') }}</td><td>{{ $s->fecha_fin->format('d/m/Y') }}</td><td>@if($s->reemplazos->contains('estado','confirmado'))<span class="badge bg-success">Confirmado</span>@elseif($s->reemplazos->contains('estado','propuesto'))<span class="badge bg-warning text-dark">Propuesto</span>@else<span class="badge bg-light text-secondary border">Sin asignar</span>@endif</td></tr>
        @empty<tr><td colspan="5"><div class="empty-state"><i class="bi bi-calendar-check"></i>No hay vacaciones aprobadas que comiencen en los próximos 30 días.</div></td></tr>@endforelse
        </tbody></table></div><div class="card-footer bg-white border-0 px-3 py-3"><small class="text-muted">La etiqueta de cobertura indica si hay alguna asignación; no garantiza cobertura completa del período.</small></div></section>
        <section class="card"><div class="card-header d-flex justify-content-between align-items-center"><div><h2 class="section-heading">Solicitudes por resolver</h2><span class="section-caption">Las más antiguas aparecen primero</span></div><a href="{{ route('vacaciones.index') }}" class="btn btn-sm btn-outline-primary">Ver todas</a></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Funcionario</th><th>Departamento</th><th>Período solicitado</th><th>Estado</th></tr></thead><tbody>
        @forelse($pendientes as $s)<tr><td><strong>{{ $s->funcionario->nombres }} {{ $s->funcionario->apellidos }}</strong></td><td>{{ $s->funcionario->departamento?->nombre ?? 'Sin departamento' }}</td><td>{{ $s->fecha_inicio->format('d/m/Y') }} – {{ $s->fecha_fin->format('d/m/Y') }}</td><td><span class="badge bg-warning text-dark">Pendiente</span></td></tr>
        @empty<tr><td colspan="4"><div class="empty-state"><i class="bi bi-check-circle"></i>No hay solicitudes pendientes.</div></td></tr>@endforelse
        </tbody></table></div></section>
    </div>
    <div class="col-12 col-xxl-4 section-stack">
        <section class="card"><div class="card-header"><h2 class="section-heading">Alertas de cobertura de hoy</h2><span class="section-caption">{{ $hoy->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</span></div><div class="card-body">
        @if($diaNoLaborable)
            <div class="empty-state"><i class="bi bi-calendar-event"></i>Hoy no se evalúa la dotación: es fin de semana o feriado registrado.</div>
        @elseif($alertas->isEmpty())
            <div class="empty-state"><i class="bi bi-shield-check"></i>No se detectaron unidades bajo la dotación mínima registrada para hoy.</div>
        @else
            @foreach($alertas as $d)<div class="coverage-line"><div><strong>{{ $d->nombre }}</strong><div class="text-muted small">Personal estimado: {{ $d->presentes_estimados }} · mínimo: {{ $d->dotacion_minima }}</div></div><span class="badge bg-danger">Revisar</span></div>@endforeach
        @endif
        <div class="small text-muted mt-3">Cálculo orientativo por cantidad de personas. No verifica competencias, horas parciales ni cobertura de todos los turnos.</div>
        </div></section>
        <section class="card"><div class="card-header"><h2 class="section-heading">Accesos rápidos</h2><span class="section-caption">Operaciones frecuentes</span></div><div class="card-body d-grid gap-2">
            <a class="quick-link" href="{{ route('vacaciones.create') }}"><span><i class="bi bi-plus-circle me-2"></i>Registrar vacaciones</span><i class="bi bi-chevron-right"></i></a>
            <a class="quick-link" href="{{ route('reemplazos.index') }}"><span><i class="bi bi-person-check me-2"></i>Gestionar reemplazos</span><i class="bi bi-chevron-right"></i></a>
            <a class="quick-link" href="{{ route('funcionarios.index') }}"><span><i class="bi bi-people me-2"></i>Administrar funcionarios</span><i class="bi bi-chevron-right"></i></a>
            <a class="quick-link" href="{{ route('saldos.index') }}"><span><i class="bi bi-wallet2 me-2"></i>Consultar saldos</span><i class="bi bi-chevron-right"></i></a>
        </div></section>
    </div>
</div>
@endsection
