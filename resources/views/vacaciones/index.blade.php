@extends('layouts.panel')
@section('title', 'Solicitudes de vacaciones')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3">Solicitudes de vacaciones</h1><a class="btn btn-primary" href="{{ route('vacaciones.create') }}">Nueva solicitud</a></div>
@if(auth()->user()->tieneRol('jefatura', 'aprobador_general'))
<div class="alert alert-warning">Antes de aprobar revisa la disponibilidad y cobertura mínima del departamento correspondiente. La asignación de reemplazos se gestiona por separado.</div>
@endif
<div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Funcionario</th><th>Fechas</th><th>Días</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
@forelse($solicitudes as $s)<tr>
<td>{{ $s->funcionario->nombres }} {{ $s->funcionario->apellidos }}<br><small class="text-muted">{{ $s->funcionario->departamento->nombre }}</small></td>
<td>{{ $s->fecha_inicio->format('d/m/Y') }} al {{ $s->fecha_fin->format('d/m/Y') }}</td><td>{{ $s->dias_habiles }}</td>
<td><span class="badge {{ $s->estado==='aprobada'?'bg-success':($s->estado==='pendiente'?'bg-warning text-dark':'bg-secondary') }}">{{ ucfirst($s->estado) }}</span></td>
<td>
@if($s->estado==='pendiente' && auth()->user()->tieneRol('jefatura', 'aprobador_general') && $s->funcionario->user_id !== auth()->id())
<form method="POST" action="{{ route('vacaciones.aprobar', $s) }}" class="d-inline" onsubmit="return confirm('¿Aprobar vacaciones y descontar saldo?')">@csrf<button class="btn btn-sm btn-success">Aprobar</button></form>
<form method="POST" action="{{ route('vacaciones.rechazar', $s) }}" class="mt-1">@csrf<input name="comentario" class="form-control form-control-sm mb-1" placeholder="Motivo de rechazo" required maxlength="2000"><button class="btn btn-sm btn-outline-danger">Rechazar</button></form>
@endif
@if(auth()->user()->tieneRol('administrador') && in_array($s->estado,['pendiente','aprobada']) && $s->fecha_inicio->isFuture())
<form method="POST" action="{{ route('vacaciones.cancelar', $s) }}" class="mt-1" onsubmit="return confirm('¿Cancelar esta solicitud?')">@csrf<button class="btn btn-sm btn-outline-secondary">Cancelar</button></form>
@endif
</td></tr>@empty<tr><td colspan="5">Sin solicitudes registradas.</td></tr>@endforelse
</tbody></table></div>{{ $solicitudes->links() }}
@endsection
