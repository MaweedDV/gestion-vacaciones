@extends('layouts.panel')
@section('title', 'Mis notificaciones')
@section('content')
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div><h1 class="h3 mb-1">Mis notificaciones</h1><p class="page-subtitle mb-0">Solicitudes recibidas y resultados de las vacaciones.</p></div>
    <form method="POST" action="{{ route('notificaciones.leer-todas') }}">@csrf<button type="submit" class="btn btn-outline-primary"><i class="bi bi-check2-all me-1"></i> Marcar todas como leídas</button></form>
</div>
<div class="card"><div class="list-group list-group-flush">
@forelse($notificaciones as $notificacion)
    <div class="list-group-item notice-item {{ $notificacion->read_at ? '' : 'notice-unread' }}">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="notice-icon"><i class="bi bi-{{ ($notificacion->data['tipo'] ?? '') === 'aprobada' ? 'check-circle' : (($notificacion->data['tipo'] ?? '') === 'rechazada' ? 'x-circle' : 'bell') }}"></i></div>
            <div class="flex-grow-1">
                <strong>{{ $notificacion->data['titulo'] ?? 'Notificación' }}</strong>
                @unless($notificacion->read_at)<span class="badge bg-primary ms-2">Nueva</span>@endunless
                <div class="notice-message">{{ $notificacion->data['mensaje'] ?? '' }}</div>
                <small class="text-muted">{{ $notificacion->created_at?->format('d/m/Y H:i') }}</small>
            </div>
            <form method="POST" action="{{ route('notificaciones.leer', $notificacion->id) }}">@csrf<button class="btn btn-outline-primary btn-sm">{{ $notificacion->read_at ? 'Ver solicitudes' : 'Leer y ver' }}</button></form>
        </div>
    </div>
@empty
    <div class="empty-state"><i class="bi bi-bell-slash"></i>No hay notificaciones por ahora.</div>
@endforelse
</div></div>
<div class="mt-3">{{ $notificaciones->links() }}</div>
@endsection
