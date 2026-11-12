@extends('layouts.panel')
@section('title', 'Historial de solicitudes')
@section('content')
<div class="mb-4"><h1 class="h3 mb-1">Historial de actividad</h1><p class="page-subtitle">Registro de creación, aprobación, rechazo y cancelación de solicitudes.</p></div>
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Solicitud</th><th>Detalle</th></tr></thead><tbody>
@forelse($eventos as $e)
<tr><td>{{ $e->created_at?->format('d/m/Y H:i') }}</td><td>{{ $e->usuario?->name ?? 'Cuenta eliminada' }}</td><td><span class="badge bg-light text-dark border">{{ str_replace('.', ' · ', $e->evento) }}</span></td><td>#{{ $e->entidad_id }}</td><td class="small">{{ $e->detalle ? json_encode($e->detalle, JSON_UNESCAPED_UNICODE) : '—' }}</td></tr>
@empty<tr><td colspan="5" class="text-muted">No hay eventos registrados.</td></tr>@endforelse
</tbody></table></div></div><div class="mt-3">{{ $eventos->links() }}</div>
@endsection
