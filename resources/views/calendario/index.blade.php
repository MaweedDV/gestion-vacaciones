@extends('layouts.panel')
@section('title', 'Calendario general')
@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div><h1 class="h3 mb-1">Calendario de vacaciones y reemplazos</h1><p class="text-muted mb-0">Planificación de ausencias y cobertura por unidad.</p></div>
  <div class="d-flex gap-2"><a class="btn btn-outline-primary" href="{{ route('vacaciones.index') }}">Solicitudes</a><a class="btn btn-primary" href="{{ route('reemplazos.index') }}">Reemplazos</a></div>
</div>
<div class="card shadow-sm mb-3"><div class="card-body row g-3">
  <div class="col-md-6"><label for="departamento" class="form-label">Departamento</label><select class="form-select" id="departamento"><option value="">Todos los departamentos</option>@foreach($departamentos as $departamento)<option value="{{ $departamento->id }}">{{ $departamento->nombre }}</option>@endforeach</select></div>
  <div class="col-md-6"><label for="tipo" class="form-label">Mostrar</label><select class="form-select" id="tipo"><option value="todos">Todos los eventos</option><option value="vacaciones">Vacaciones</option><option value="reemplazos">Reemplazos</option><option value="alertas">Alertas de dotación</option></select></div>
  <div class="col-12 d-flex flex-wrap gap-3 small"><span><span class="badge bg-success">Verde</span> Aprobada</span><span><span class="badge bg-warning text-dark">Amarillo</span> Pendiente</span><span><span class="badge bg-primary">Azul</span> Reemplazo confirmado</span><span><span class="badge bg-secondary">Gris</span> Reemplazo propuesto</span><span><span class="badge bg-danger">Rojo</span> Bajo dotación mínima</span></div>
</div></div>
<div id="calendarError" class="alert alert-danger d-none" role="alert"></div>
<div class="card shadow-sm"><div class="card-body"><div id="calendar"></div></div></div>
<p class="text-muted small mt-3">Las alertas son estimaciones por número de personas, no validan competencias, funciones ni jornadas parciales. Los feriados deben registrarse previamente. Los reemplazos internos no incrementan la dotación de la unidad.</p>
<div class="modal fade" id="eventoDetalle" tabindex="-1" aria-labelledby="eventoTitulo" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h2 class="modal-title fs-5" id="eventoTitulo">Detalle</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body" id="eventoContenido"></div><div class="modal-footer"><a id="eventoEnlace" class="btn btn-primary d-none" href="#">Ver módulo</a><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button></div></div></div></div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.19/locales/es.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const filtroDepartamento = document.getElementById('departamento');
  const filtroTipo = document.getElementById('tipo');
  const errorBox = document.getElementById('calendarError');
  const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
    locale: 'es', initialView: 'dayGridMonth', firstDay: 1,
    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
    height: 'auto', dayMaxEvents: 3, displayEventTime: false,
    events: function (info, success, failure) {
      const params = new URLSearchParams({ start: info.startStr.substring(0,10), end: info.endStr.substring(0,10), tipo: filtroTipo.value });
      if (filtroDepartamento.value) params.set('departamento_id', filtroDepartamento.value);
      fetch(@json(route('calendario.eventos')) + '?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then(async response => { if (!response.ok) throw new Error('No se pudieron obtener los eventos (' + response.status + ').'); return response.json(); })
        .then(data => { errorBox.classList.add('d-none'); success(data); })
        .catch(error => { errorBox.textContent = error.message; errorBox.classList.remove('d-none'); failure(error); });
    },
    eventClick: function (info) {
      info.jsEvent.preventDefault();
      const e = info.event;
      document.getElementById('eventoTitulo').textContent = e.title;
      const detalles = e.extendedProps;
      const contenido = document.getElementById('eventoContenido');
      contenido.replaceChildren();
      const items = [ ['Tipo', detalles.tipo], ['Estado', detalles.estado], ['Funcionario', detalles.funcionario], ['Titular', detalles.titular], ['Departamento', detalles.departamento], ['Disponibles', detalles.presentes], ['Mínimo requerido', detalles.minimo] ];
      items.forEach(([etiqueta, valor]) => { if (valor !== undefined && valor !== null) { const p = document.createElement('p'); p.className = 'mb-2'; const strong = document.createElement('strong'); strong.textContent = etiqueta + ': '; p.append(strong, document.createTextNode(String(valor))); contenido.appendChild(p); } });
      const enlace = document.getElementById('eventoEnlace');
      if (e.url) { enlace.href = e.url; enlace.classList.remove('d-none'); } else { enlace.removeAttribute('href'); enlace.classList.add('d-none'); }
      bootstrap.Modal.getOrCreateInstance(document.getElementById('eventoDetalle')).show();
    }
  });
  filtroDepartamento.addEventListener('change', () => calendar.refetchEvents());
  filtroTipo.addEventListener('change', () => calendar.refetchEvents());
  calendar.render();
});
</script>
@endpush
