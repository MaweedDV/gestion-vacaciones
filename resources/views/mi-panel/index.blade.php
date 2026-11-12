@extends('layouts.panel')
@section('title', 'Mi panel personal')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Mi panel personal</h1>
        <p class="page-subtitle mb-0">Consulta tus vacaciones, el saldo disponible y el estado de tus solicitudes.</p>
    </div>
    @if($funcionario && $funcionario->activo)
        <a href="{{ route('vacaciones.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> Solicitar vacaciones</a>
    @endif
</div>

@if(! $funcionario)
    <div class="alert alert-warning" role="alert">
        <strong>Tu cuenta aún no está vinculada a una ficha de funcionario.</strong>
        Solicita al administrador que asocie tu usuario a tu ficha para ver tu saldo y tus solicitudes.
    </div>
@else
    <section class="card mb-4" aria-label="Mi información laboral">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="text-muted mb-1">Información del funcionario</div>
                <h2 class="h5 mb-1">{{ $funcionario->nombres }} {{ $funcionario->apellidos }}</h2>
                <div class="text-muted">{{ $funcionario->cargo }} · {{ $funcionario->departamento?->nombre ?? 'Sin departamento asignado' }}</div>
            </div>
            <span class="badge {{ $funcionario->activo ? 'bg-success' : 'bg-secondary' }}">{{ $funcionario->activo ? 'Activo' : 'Inactivo' }}</span>
        </div>
    </section>

    @if(! $saldo)
        <div class="alert alert-warning" role="alert">
            <strong>Saldo no registrado para {{ $periodo }}.</strong>
            Los indicadores de días aparecerán en cero hasta que administración registre tu saldo anual. No significa que no tengas derecho a vacaciones.
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><section class="metric-card h-100">
            <div class="metric-icon metric-blue"><i class="bi bi-wallet2"></i></div>
            <div class="metric-label mt-2">Saldo sin reservas · {{ $periodo }}</div>
            <div class="metric-value">{{ number_format($saldoContable, 2, ',', '.') }}</div>
            <div class="metric-foot">Días asignados + arrastres + ajustes − utilizados</div>
        </section></div>
        <div class="col-sm-6 col-xl-3"><section class="metric-card h-100">
            <div class="metric-icon metric-green"><i class="bi bi-calendar-check"></i></div>
            <div class="metric-label mt-2">Días disponibles para solicitar</div>
            <div class="metric-value">{{ number_format($disponibleSolicitable, 2, ',', '.') }}</div>
            <div class="metric-foot">Descuenta los días reservados en solicitudes pendientes</div>
        </section></div>
        <div class="col-sm-6 col-xl-3"><section class="metric-card h-100">
            <div class="metric-icon metric-purple"><i class="bi bi-check2-circle"></i></div>
            <div class="metric-label mt-2">Días utilizados · {{ $periodo }}</div>
            <div class="metric-value">{{ number_format($utilizados, 2, ',', '.') }}</div>
            <div class="metric-foot">Días descontados al aprobar vacaciones</div>
        </section></div>
        <div class="col-sm-6 col-xl-3"><section class="metric-card h-100">
            <div class="metric-icon metric-amber"><i class="bi bi-hourglass-split"></i></div>
            <div class="metric-label mt-2">Días pendientes de resolución</div>
            <div class="metric-value">{{ number_format($pendientesReservados, 2, ',', '.') }}</div>
            <div class="metric-foot">Solicitudes de {{ $periodo }} en revisión</div>
        </section></div>
    </div>

    <section class="card mb-4">
        <div class="card-header"><h2 class="section-heading">Resumen de mis solicitudes</h2><span class="section-caption">Histórico de todos los períodos</span></div>
        <div class="card-body">
            <div class="row g-3">
                @foreach([['label'=>'Pendientes','estado'=>'pendiente','icon'=>'clock-history','clase'=>'text-warning'],['label'=>'Aprobadas','estado'=>'aprobada','icon'=>'check-circle','clase'=>'text-success'],['label'=>'Rechazadas','estado'=>'rechazada','icon'=>'x-circle','clase'=>'text-danger'],['label'=>'Canceladas','estado'=>'cancelada','icon'=>'slash-circle','clase'=>'text-secondary']] as $item)
                    <div class="col-6 col-lg-3"><div class="employee-status">
                        <i class="bi bi-{{ $item['icon'] }} {{ $item['clase'] }}" aria-hidden="true"></i>
                        <strong>{{ (int)($resumen[$item['estado']] ?? 0) }}</strong>
                        <span>{{ $item['label'] }}</span>
                    </div></div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h2 class="section-heading">Mis solicitudes recientes</h2><span class="section-caption">Últimas 8 solicitudes registradas</span></div>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('vacaciones.index') }}">Ver todo mi historial</a>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Fecha de solicitud</th><th>Período</th><th>Días hábiles</th><th>Estado</th></tr></thead>
                <tbody>
                @forelse($recientes as $solicitud)
                    <tr>
                        <td>{{ $solicitud->created_at?->format('d/m/Y') }}</td>
                        <td>{{ $solicitud->fecha_inicio->format('d/m/Y') }} – {{ $solicitud->fecha_fin->format('d/m/Y') }}</td>
                        <td>{{ number_format((float)$solicitud->dias_habiles, 2, ',', '.') }}</td>
                        <td>
                            @php($color = ['pendiente'=>'warning text-dark', 'aprobada'=>'success', 'rechazada'=>'danger', 'cancelada'=>'secondary'][$solicitud->estado] ?? 'secondary')
                            <span class="badge bg-{{ $color }}">{{ ucfirst($solicitud->estado) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty-state">Todavía no has registrado solicitudes de vacaciones.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <p class="text-muted small mt-3">El saldo corresponde al período {{ $periodo }}. Las solicitudes pendientes se reservan para evitar solicitar más días de los disponibles; el descuento definitivo ocurre al aprobarse.</p>
@endif
@endsection
