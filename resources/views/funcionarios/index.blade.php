@extends('layouts.panel')
@section('title', 'Funcionarios')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3">Funcionarios</h1><a href="{{ route('funcionarios.create') }}" class="btn btn-primary">Nuevo funcionario</a></div>
<form class="mb-3 d-flex gap-2" method="GET" action="{{ route('funcionarios.index') }}">
<input type="search" name="buscar" class="form-control" placeholder="Código, nombre, apellido o cargo" value="{{ $buscar }}">
<button class="btn btn-outline-primary">Buscar</button><a class="btn btn-outline-secondary" href="{{ route('funcionarios.index') }}">Limpiar</a></form>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
<thead><tr><th>Código</th><th>Nombre</th><th>Cargo</th><th>Departamento</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
@forelse($funcionarios as $funcionario)
<tr><td>{{ $funcionario->codigo }}</td><td>{{ $funcionario->nombres }} {{ $funcionario->apellidos }}</td><td>{{ $funcionario->cargo }}</td><td>{{ $funcionario->departamento?->nombre }}</td><td><span class="badge {{ $funcionario->activo ? 'bg-success' : 'bg-secondary' }}">{{ $funcionario->activo ? 'Activo' : 'Inactivo' }}</span></td><td>
<a href="{{ route('funcionarios.edit', $funcionario) }}" class="btn btn-sm btn-outline-primary">Editar</a>
@if($funcionario->activo)
<form method="POST" action="{{ route('funcionarios.destroy', $funcionario) }}" class="d-inline" onsubmit="return confirm('¿Desactivar funcionario?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Desactivar</button></form>
@endif
</td></tr>
@empty<tr><td colspan="6">No hay funcionarios registrados.</td></tr>@endforelse
</tbody></table></div></div><div class="mt-3">{{ $funcionarios->links('pagination::bootstrap-5') }}</div>
@endsection
