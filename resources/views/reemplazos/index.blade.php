@extends('layouts.panel')
@section('title','Reemplazos')
@section('content')
<h1 class="h3 mb-3">Planificación de reemplazos</h1>
<p class="text-muted">Puedes indicar reemplazantes, aprobar o rechazar propuestas y organizar la cobertura de tu unidad. Las vacaciones deben estar aprobadas para confirmar un reemplazo.</p>
@if(auth()->user()->tieneRol('jefatura'))
<div class="alert alert-info"><i class="bi bi-info-circle me-1"></i> Como jefatura gestionas únicamente solicitudes y reemplazantes de tu departamento. Para coberturas de otra unidad, contacta a administración.</div>
@endif
<div class="table-responsive"><table class="table table-bordered table-striped align-middle">
<thead><tr><th>Titular</th><th>Unidad</th><th>Vacaciones</th><th>Estado</th><th>Reemplazos</th><th></th></tr></thead>
<tbody>@forelse($solicitudes as $solicitud)
<tr><td>{{ $solicitud->funcionario->nombres }} {{ $solicitud->funcionario->apellidos }}</td>
<td>{{ $solicitud->funcionario->departamento->nombre }}</td>
<td>{{ $solicitud->fecha_inicio->format('d/m/Y') }} al {{ $solicitud->fecha_fin->format('d/m/Y') }}</td>
<td>{{ ucfirst($solicitud->estado) }}</td><td>
@forelse($solicitud->reemplazos as $r)
<div class="border-bottom py-2">{{ $r->reemplazante->nombres }} {{ $r->reemplazante->apellidos }}
<span class="badge {{ $r->estado === 'confirmado' ? 'bg-success' : ($r->estado === 'propuesto' ? 'bg-warning text-dark' : ($r->estado === 'rechazado' ? 'bg-danger' : 'bg-secondary')) }}">{{ $r->estado }}</span>
<small class="d-block">{{ $r->fecha_inicio->format('d/m/Y') }} - {{ $r->fecha_fin->format('d/m/Y') }}</small>
@if($r->estado === 'propuesto' && $solicitud->estado === 'aprobada')
<form method="POST" action="{{ route('reemplazos.confirmar',$r) }}" class="d-inline">@csrf <button class="btn btn-sm btn-success">Aprobar reemplazo</button></form>
@endif
@if($r->estado === 'propuesto')
<form method="POST" action="{{ route('reemplazos.rechazar',$r) }}" class="d-flex gap-1 align-items-center mt-2">@csrf
<input type="text" name="motivo" class="form-control form-control-sm" maxlength="1000" required placeholder="Motivo del rechazo" aria-label="Motivo del rechazo">
<button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Rechazar esta propuesta?')">Rechazar</button></form>
@endif
@if(in_array($r->estado,['propuesto','confirmado']))
<form method="POST" action="{{ route('reemplazos.cancelar',$r) }}" class="d-inline">@csrf <button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Cancelar asignación?')">Cancelar</button></form>
@endif
</div>
@empty <span class="text-muted">Sin reemplazo</span> @endforelse
</td><td><a class="btn btn-primary btn-sm" href="{{ route('reemplazos.create',$solicitud) }}">Planificar</a></td></tr>
@empty<tr><td colspan="6">No hay solicitudes pendientes o aprobadas.</td></tr>@endforelse
</tbody></table></div>{{ $solicitudes->links() }}
@endsection
