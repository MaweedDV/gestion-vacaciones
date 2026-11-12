@extends('layouts.panel')
@section('title', $usuario->exists ? 'Editar usuario' : 'Crear usuario')
@section('content')
<div class="mb-3"><h1 class="h3">{{ $usuario->exists ? 'Editar usuario' : 'Crear usuario' }}</h1><p class="text-muted">El aprobador general puede resolver vacaciones de todos los departamentos, sin administrar usuarios. Las jefaturas solo resuelven las de su unidad.</p></div>
<form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}" class="card card-body">
@csrf @if($usuario->exists) @method('PUT') @endif
<div class="row g-3">
<div class="col-md-6"><label class="form-label" for="name">Nombre</label><input id="name" name="name" class="form-control" value="{{ old('name',$usuario->name) }}" required maxlength="255"></div>
<div class="col-md-6"><label class="form-label" for="email">Correo</label><input id="email" type="email" name="email" class="form-control" value="{{ old('email',$usuario->email) }}" required></div>
<div class="col-md-6"><label class="form-label" for="password">Contraseña {{ $usuario->exists ? '(vacío para conservar)' : '' }}</label><input id="password" type="password" name="password" class="form-control" {{ $usuario->exists ? '' : 'required' }} minlength="12" autocomplete="new-password"></div>
<div class="col-md-6"><label class="form-label" for="password_confirmation">Confirmar contraseña</label><input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password"></div>
<div class="col-md-6"><label class="form-label" for="rol">Rol</label><select name="rol" id="rol" class="form-select" required>
@foreach(['administrador'=>'Administrador','jefatura'=>'Jefatura de unidad','aprobador_general'=>'Aprobador general (todas las unidades)','funcionario'=>'Funcionario'] as $valor=>$etiqueta)<option value="{{ $valor }}" @selected(old('rol',$usuario->rol ?: 'funcionario') === $valor)>{{ $etiqueta }}</option>@endforeach
</select></div>
<div class="col-md-6"><label class="form-label" for="activo">Estado</label><select class="form-select" name="activo" id="activo"><option value="1" @selected(old('activo', $usuario->exists ? (int)$usuario->activo : 1) == 1)>Activo</option><option value="0" @selected(old('activo', $usuario->exists ? (int)$usuario->activo : 1) == 0)>Inactivo</option></select></div>
<div class="col-md-6"><label class="form-label" for="departamento_gestion_id">Departamento a cargo (solo jefatura)</label><select id="departamento_gestion_id" name="departamento_gestion_id" class="form-select"><option value="">Sin asignar</option>@foreach($departamentos as $d)<option value="{{ $d->id }}" @selected(old('departamento_gestion_id',$usuario->departamento_gestion_id)==$d->id)>{{ $d->nombre }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label" for="funcionario_id">Vincular ficha de funcionario</label><select id="funcionario_id" name="funcionario_id" class="form-select"><option value="">Sin vincular</option>@foreach($funcionarios as $f)<option value="{{ $f->id }}" @selected(old('funcionario_id',$usuario->funcionario?->id)==$f->id)>{{ $f->apellidos }}, {{ $f->nombres }} ({{ $f->codigo }})</option>@endforeach</select></div>
</div>
<div class="d-flex gap-2 mt-4"><button class="btn btn-primary" type="submit">Guardar</button><a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Volver</a></div>
</form>
@endsection
