@extends('layouts.panel')
@section('title', 'Departamentos')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
 <h1 class="h3">Departamentos</h1><a href="{{ route('departamentos.create') }}" class="btn btn-primary">Nuevo departamento</a>
</div>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
<thead><tr><th>Nombre</th><th>Dotación mínima</th><th>Funcionarios</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody>
@forelse($departamentos as $departamento)
<tr><td>{{ $departamento->nombre }}</td><td>{{ $departamento->dotacion_minima }}</td><td>{{ $departamento->funcionarios_count }}</td>
<td><span class="badge {{ $departamento->activo ? 'bg-success' : 'bg-secondary' }}">{{ $departamento->activo ? 'Activo' : 'Inactivo' }}</span></td><td>
<a href="{{ route('departamentos.edit', $departamento) }}" class="btn btn-sm btn-outline-primary">Editar</a>
@if($departamento->activo)
<form method="POST" action="{{ route('departamentos.destroy', $departamento) }}" class="d-inline" onsubmit="return confirm('¿Desactivar departamento?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Desactivar</button></form>
@endif
</td></tr>
@empty<tr><td colspan="5">No hay departamentos registrados.</td></tr>@endforelse
</tbody></table></div></div><div class="mt-3">{{ $departamentos->links('pagination::bootstrap-5') }}</div>
@endsection
