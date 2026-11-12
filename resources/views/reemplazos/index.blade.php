@extends('layouts.panel')
@section('title','Reemplazos')
@section('content')
<h1 class="h3 mb-3">Planificación de reemplazos</h1>
<p class="text-muted">Asigna y confirma personal para cubrir vacaciones. Las propuestas no son designaciones oficiales.</p>
<div class="table-responsive"><table class="table table-bordered table-striped align-middle">
<thead><tr><th>Titular</th><th>Unidad</th><th>Vacaciones</th><th>Estado</th><th>Reemplazos</th><th></th></tr></thead>
<tbody>@forelse($solicitudes as $solicitud)
<tr><td>{{ $solicitud->funcionario->nombres }} {{ $solicitud->funcionario->apellidos }}</td>
<td>{{ $solicitud->funcionario->departamento->nombre }}</td>
<td>{{ $solicitud->fecha_inicio->format('d/m/Y') }} al {{ $solicitud->fecha_fin->format('d/m/Y') }}</td>
<td>{{ ucfirst($solicitud->estado) }}</td><td>
@forelse($solicitud->reemplazos as $r)
<div class="border-bottom py-2">{{ $r->reemplazante->nombres }} {{ $r->reemplazante->apellidos }}
<span class="badge {{ $r->estado === 'confirmado' ? 'bg-success' : ($r->estado === 'propuesto' ? 'bg-warning text-dark' : 'bg-secondary') }}">{{ $r->estado }}</span>
<small class="d-block">{{ $r->fecha_inicio->format('d/m/Y') }} - {{ $r->fecha_fin->format('d/m/Y') }}</small>
@if($r->estado === 'propuesto' && $solicitud->estado === 'aprobada')
<form method="POST" action="{{ route('reemplazos.confirmar',$r) }}" class="d-inline">@csrf <button class="btn btn-sm btn-success">Confirmar</button></form>
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
