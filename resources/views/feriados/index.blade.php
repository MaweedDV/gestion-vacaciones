@extends('layouts.panel')
@section('title', 'Feriados')
@section('content')
<h1 class="h3">Feriados</h1>
<div class="alert alert-warning">Carga manual: incorpora feriados nacionales y los regionales/comunales aplicables, previa verificación oficial. Cambiar feriados puede afectar solicitudes pendientes.</div>
<form action="{{ route('feriados.store') }}" method="POST" class="card card-body mb-3">@csrf<div class="row g-2 align-items-end"><div class="col-md-3"><label class="form-label">Fecha</label><input type="date" name="fecha" class="form-control" required></div><div class="col-md-6"><label class="form-label">Nombre</label><input name="nombre" class="form-control" required maxlength="255"></div><div class="col-md-3"><button class="btn btn-primary">Agregar feriado</button></div></div></form>
<table class="table table-striped"><thead><tr><th>Fecha</th><th>Nombre</th><th></th></tr></thead><tbody>@foreach($feriados as $f)<tr><td>{{ $f->fecha->format('d-m-Y') }}</td><td>{{ $f->nombre }}</td><td><form action="{{ route('feriados.destroy', $f) }}" method="POST" onsubmit="return confirm('¿Eliminar el feriado?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Eliminar</button></form></td></tr>@endforeach</tbody></table>{{ $feriados->links() }}
@endsection
