@extends('layouts.panel')
@section('title', 'Saldos de vacaciones')
@section('content')
<h1 class="h3 mb-3">Saldos de vacaciones</h1>
<div class="alert alert-info">Los días deben ingresarse según el régimen legal que corresponda. No se asignan automáticamente por antigüedad.</div>
<form action="{{ route('saldos.store') }}" method="POST" class="card card-body mb-4">@csrf
<h2 class="h5">Crear o actualizar saldo</h2>
<div class="row g-2">
<div class="col-md-4"><label class="form-label">Funcionario</label><select class="form-select" name="funcionario_id" required><option value="">Seleccionar</option>@foreach($funcionarios as $f)<option value="{{ $f->id }}" @selected(old('funcionario_id') == $f->id)>{{ $f->apellidos }}, {{ $f->nombres }} ({{ $f->codigo }})</option>@endforeach</select></div>
<div class="col-md-2"><label class="form-label">Año</label><input type="number" name="periodo" class="form-control" value="{{ old('periodo', date('Y')) }}" required></div>
<div class="col-md-2"><label class="form-label">Asignados</label><input type="number" min="0" max="365" step="0.01" name="dias_asignados" class="form-control" value="{{ old('dias_asignados', 0) }}" required></div>
<div class="col-md-2"><label class="form-label">Arrastrados</label><input type="number" min="0" max="365" step="0.01" name="dias_arrastrados" class="form-control" value="{{ old('dias_arrastrados', 0) }}" required></div>
<div class="col-md-2"><label class="form-label">Ajuste (+/-)</label><input type="number" min="-365" max="365" step="0.01" name="dias_ajuste" class="form-control" value="{{ old('dias_ajuste', 0) }}" required></div>
<div class="col-12"><label class="form-label">Motivo / observaciones</label><textarea class="form-control" name="observaciones">{{ old('observaciones') }}</textarea></div>
</div><div class="mt-3"><button class="btn btn-primary">Guardar saldo</button></div>
</form>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>Funcionario</th><th>Año</th><th>Asignados</th><th>Arrastrados</th><th>Ajuste</th><th>Utilizados</th><th>Disponibles</th></tr></thead><tbody>
@foreach($saldos as $s)<tr><td>{{ $s->funcionario->nombres }} {{ $s->funcionario->apellidos }}</td><td>{{ $s->periodo }}</td><td>{{ $s->dias_asignados }}</td><td>{{ $s->dias_arrastrados }}</td><td>{{ $s->dias_ajuste }}</td><td>{{ $s->dias_utilizados }}</td><td>{{ number_format($s->dias_asignados + $s->dias_arrastrados + $s->dias_ajuste - $s->dias_utilizados, 2, ',', '.') }}</td></tr>@endforeach
</tbody></table></div>{{ $saldos->links() }}
@endsection
