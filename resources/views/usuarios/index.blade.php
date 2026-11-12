@extends('layouts.panel')
@section('title', 'Usuarios y permisos')
@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div><h1 class="h3 mb-1">Usuarios y permisos</h1><p class="text-muted mb-0">Controla quién accede y qué puede gestionar.</p></div>
    <a href="{{ route('usuarios.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> Crear usuario</a>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
<thead><tr><th>Usuario</th><th>Rol</th><th>Unidad</th><th>Funcionario vinculado</th><th>Estado</th><th></th></tr></thead><tbody>
@forelse($usuarios as $u)
<tr><td><strong>{{ $u->name }}</strong><br><small class="text-muted">{{ $u->email }}</small></td><td>{{ ucfirst($u->rol) }}</td>
<td>{{ $u->departamentoGestion?->nombre ?? '—' }}</td>
<td>{{ $u->funcionario ? $u->funcionario->nombres.' '.$u->funcionario->apellidos : '—' }}</td>
<td><span class="badge {{ $u->activo ? 'bg-success':'bg-secondary' }}">{{ $u->activo ? 'Activo':'Inactivo' }}</span></td>
<td><a class="btn btn-sm btn-outline-primary" href="{{ route('usuarios.edit', $u) }}">Editar</a></td></tr>
@empty<tr><td colspan="6">Sin usuarios.</td></tr>@endforelse
</tbody></table></div></div><div class="mt-3">{{ $usuarios->links() }}</div>
@endsection
