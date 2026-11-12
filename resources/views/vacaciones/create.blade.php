@extends('layouts.panel')
@section('title', 'Nueva solicitud')
@section('content')
<h1 class="h3 mb-3">Nueva solicitud de vacaciones</h1>
<div class="alert alert-info">La solicitud se registra pendiente. Los días hábiles se calculan en el servidor descontando sábados, domingos y feriados cargados. Esta versión acepta períodos dentro de un mismo año.</div>
<form action="{{ route('vacaciones.store') }}" method="POST" class="card card-body">@csrf
<div class="mb-3"><label class="form-label">Funcionario</label><select name="funcionario_id" class="form-select" required><option value="">Seleccionar funcionario</option>@foreach($funcionarios as $f)<option value="{{ $f->id }}" @selected(old('funcionario_id')==$f->id)>{{ $f->apellidos }}, {{ $f->nombres }} — {{ $f->cargo }}</option>@endforeach</select></div>
<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Inicio</label><input type="date" name="fecha_inicio" class="form-control" value="{{ old('fecha_inicio') }}" required></div><div class="col-md-6 mb-3"><label class="form-label">Término</label><input type="date" name="fecha_fin" class="form-control" value="{{ old('fecha_fin') }}" required></div></div>
<div class="mb-3"><label class="form-label">Observaciones (opcional)</label><textarea name="observaciones" class="form-control">{{ old('observaciones') }}</textarea></div>
<div><button class="btn btn-primary">Registrar solicitud</button> <a class="btn btn-outline-secondary" href="{{ route('vacaciones.index') }}">Volver</a></div>
</form>
@endsection
