@extends('layouts.panel')
@section('title', $departamento->exists ? 'Editar departamento' : 'Nuevo departamento')
@section('content')
<h1 class="h3 mb-3">{{ $departamento->exists ? 'Editar departamento' : 'Nuevo departamento' }}</h1>
<div class="card"><div class="card-body">
<form method="POST" action="{{ $departamento->exists ? route('departamentos.update', $departamento) : route('departamentos.store') }}">
@csrf @if($departamento->exists) @method('PUT') @endif
<div class="mb-3"><label for="nombre" class="form-label">Nombre</label><input class="form-control" id="nombre" name="nombre" required maxlength="255" value="{{ old('nombre', $departamento->nombre) }}"></div>
<div class="mb-3"><label for="dotacion_minima" class="form-label">Dotación mínima</label><input class="form-control" id="dotacion_minima" name="dotacion_minima" type="number" min="0" required value="{{ old('dotacion_minima', $departamento->dotacion_minima ?? 1) }}"></div>
<div class="mb-3"><label for="activo" class="form-label">Estado</label><select class="form-select" id="activo" name="activo"><option value="1" @selected(old('activo', $departamento->activo ?? true) == 1)>Activo</option><option value="0" @selected(old('activo', $departamento->activo ?? true) == 0)>Inactivo</option></select></div>
<button class="btn btn-primary">Guardar</button><a href="{{ route('departamentos.index') }}" class="btn btn-secondary">Volver</a>
</form></div></div>
@endsection
