@extends('layouts.panel')
@section('title', $funcionario->exists ? 'Editar funcionario' : 'Nuevo funcionario')
@section('content')
<h1 class="h3 mb-3">{{ $funcionario->exists ? 'Editar funcionario' : 'Nuevo funcionario' }}</h1>
<div class="card"><div class="card-body">
<form method="POST" action="{{ $funcionario->exists ? route('funcionarios.update', $funcionario) : route('funcionarios.store') }}">
@csrf @if($funcionario->exists) @method('PUT') @endif
<div class="row g-3">
<div class="col-md-6"><label for="codigo" class="form-label">Código</label><input class="form-control" id="codigo" name="codigo" required maxlength="255" value="{{ old('codigo', $funcionario->codigo) }}"></div>
<div class="col-md-6"><label for="rut" class="form-label">RUT (sin puntos, con guion)</label><input class="form-control" id="rut" name="rut" maxlength="12" value="{{ old('rut', $funcionario->rut) }}" placeholder="12345678-9"></div>
<div class="col-md-6"><label for="nombres" class="form-label">Nombres</label><input class="form-control" id="nombres" name="nombres" required value="{{ old('nombres', $funcionario->nombres) }}"></div>
<div class="col-md-6"><label for="apellidos" class="form-label">Apellidos</label><input class="form-control" id="apellidos" name="apellidos" required value="{{ old('apellidos', $funcionario->apellidos) }}"></div>
<div class="col-md-6"><label for="cargo" class="form-label">Cargo</label><input class="form-control" id="cargo" name="cargo" required value="{{ old('cargo', $funcionario->cargo) }}"></div>
<div class="col-md-6"><label for="departamento_id" class="form-label">Departamento</label><select class="form-select" id="departamento_id" name="departamento_id" required><option value="">Seleccione...</option>@foreach($departamentos as $dep)<option value="{{ $dep->id }}" @selected(old('departamento_id', $funcionario->departamento_id) == $dep->id)>{{ $dep->nombre }}{{ $dep->activo ? '' : ' (inactivo)' }}</option>@endforeach</select></div>
<div class="col-md-6"><label for="fecha_ingreso" class="form-label">Fecha de ingreso</label><input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso" value="{{ old('fecha_ingreso', $funcionario->fecha_ingreso?->format('Y-m-d')) }}"></div>
<div class="col-md-6"><label for="regimen" class="form-label">Régimen</label><select class="form-select" id="regimen" name="regimen">
@foreach(['por_definir'=>'Por definir', 'municipal_18883'=>'Estatuto municipal (18.883)', 'administrativo_18834'=>'Estatuto administrativo (18.834)', 'otro'=>'Otro'] as $value=>$label)
<option value="{{ $value }}" @selected(old('regimen', $funcionario->regimen ?? 'por_definir') === $value)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-6"><label for="activo" class="form-label">Estado</label><select class="form-select" id="activo" name="activo"><option value="1" @selected(old('activo', $funcionario->activo ?? true) == 1)>Activo</option><option value="0" @selected(old('activo', $funcionario->activo ?? true) == 0)>Inactivo</option></select></div>
</div><div class="mt-4"><button class="btn btn-primary">Guardar</button><a class="btn btn-secondary" href="{{ route('funcionarios.index') }}">Volver</a></div>
</form></div></div>
@endsection
