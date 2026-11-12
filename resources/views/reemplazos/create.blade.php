@extends('layouts.panel')
@section('title','Planificar reemplazo')
@section('content')
<a href="{{ route('reemplazos.index') }}">&larr; Volver a reemplazos</a>
<h1 class="h3 mt-3">Planificar cobertura</h1>
<p>Funcionario: <strong>{{ $solicitud->funcionario->nombres }} {{ $solicitud->funcionario->apellidos }}</strong> ·
{{ $solicitud->funcionario->departamento->nombre }} · Vacaciones: {{ $solicitud->fecha_inicio->format('d/m/Y') }} al {{ $solicitud->fecha_fin->format('d/m/Y') }}</p>
@if($alerta)<div class="alert alert-warning"><strong>Advertencia de dotación:</strong> {{ $alerta }}. La designación debe ser evaluada por la jefatura.</div>@endif
<form method="GET" action="{{ route('reemplazos.create',$solicitud) }}" class="row g-2 mb-4">
<div class="col-md-4"><label class="form-label">Desde</label><input type="date" class="form-control" name="desde" value="{{ $desde }}" min="{{ $solicitud->fecha_inicio->toDateString() }}" max="{{ $solicitud->fecha_fin->toDateString() }}"></div>
<div class="col-md-4"><label class="form-label">Hasta</label><input type="date" class="form-control" name="hasta" value="{{ $hasta }}" min="{{ $solicitud->fecha_inicio->toDateString() }}" max="{{ $solicitud->fecha_fin->toDateString() }}"></div>
<div class="col-md-4 d-flex align-items-end"><button class="btn btn-secondary">Actualizar sugerencias</button></div>
</form>
<p class="text-muted">La puntuación solo ordena candidatos: misma unidad +30, competencia coincidente +10 y carga reciente -3 por reemplazo. La jefatura valida las competencias y pertinencia.</p>
<div class="table-responsive"><table class="table table-bordered align-middle">
<thead><tr><th>Candidato</th><th>Unidad</th><th>Competencias coincidentes</th><th>Carga</th><th>Puntaje</th><th>Evaluación</th><th>Asignación manual</th></tr></thead>
<tbody>@forelse($sugerencias as $item)
<tr><td>{{ $item['funcionario']->nombres }} {{ $item['funcionario']->apellidos }}</td>
<td>{{ $item['funcionario']->departamento->nombre }}</td><td>{{ $item['coincidentes'] }} / {{ $item['total_competencias_titular'] }}</td>
<td>{{ $item['carga'] }}</td><td>{{ $item['puntaje'] }}</td>
<td>@if($item['disponible'])<span class="badge bg-success">Disponible</span>@else<span class="badge bg-danger">No disponible</span> <small>{{ implode(' ', $item['motivos']) }}</small>@endif</td>
<td>@if($item['disponible'])<form method="POST" action="{{ route('reemplazos.store',$solicitud) }}">@csrf
<input type="hidden" name="reemplazante_id" value="{{ $item['funcionario']->id }}"><input type="hidden" name="fecha_inicio" value="{{ $desde }}"><input type="hidden" name="fecha_fin" value="{{ $hasta }}">
<button class="btn btn-sm btn-primary" onclick="return confirm('¿Proponer a este funcionario?')">Proponer</button></form>@else — @endif</td></tr>
@empty<tr><td colspan="7">No hay candidatos.</td></tr>@endforelse
</tbody></table></div>
<p class="small text-muted">Se pueden asignar tramos sucesivos a diferentes funcionarios. La cobertura completa del período aún debe verificarse administrativamente.</p>
@endsection
