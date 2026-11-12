<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MiPanelController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\FeriadoController;
use App\Http\Controllers\FuncionarioController;
use App\Http\Controllers\ReemplazoController;
use App\Http\Controllers\SaldoVacacionController;
use App\Http\Controllers\SolicitudVacacionController;
use App\Http\Controllers\UsuarioController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) return redirect()->route('login');
    return redirect()->route(auth()->user()->tieneRol('administrador') ? 'dashboard.index' : (auth()->user()->tieneRol('funcionario') ? 'mi-panel.index' : 'vacaciones.index'));
});
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store')->middleware('throttle:5,1');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::post('notificaciones/{id}/leer', [NotificacionController::class, 'leer'])->name('notificaciones.leer');
    Route::post('notificaciones/leer-todas', [NotificacionController::class, 'leerTodas'])->name('notificaciones.leer-todas');
});
Route::middleware(['auth', EnsureRole::class.':funcionario'])->group(function () {
    Route::get('mi-panel', [MiPanelController::class, 'index'])->name('mi-panel.index');
});
Route::middleware(['auth', EnsureRole::class.':administrador,jefatura,funcionario,aprobador_general'])->group(function () {
    Route::get('vacaciones', [SolicitudVacacionController::class, 'index'])->name('vacaciones.index');
    Route::get('vacaciones/crear', [SolicitudVacacionController::class, 'create'])->name('vacaciones.create');
    Route::post('vacaciones', [SolicitudVacacionController::class, 'store'])->name('vacaciones.store');
});
Route::middleware(['auth', EnsureRole::class.':jefatura,aprobador_general'])->group(function () {
    Route::post('vacaciones/{solicitud}/aprobar', [SolicitudVacacionController::class, 'aprobar'])->name('vacaciones.aprobar');
    Route::post('vacaciones/{solicitud}/rechazar', [SolicitudVacacionController::class, 'rechazar'])->name('vacaciones.rechazar');
});
Route::middleware(['auth', EnsureRole::class.':administrador'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::resource('departamentos', DepartamentoController::class)->except('show');
    Route::resource('funcionarios', FuncionarioController::class)->except('show');
    Route::get('saldos', [SaldoVacacionController::class, 'index'])->name('saldos.index');
    Route::post('saldos', [SaldoVacacionController::class, 'store'])->name('saldos.store');
    Route::get('feriados', [FeriadoController::class, 'index'])->name('feriados.index');
    Route::post('feriados', [FeriadoController::class, 'store'])->name('feriados.store');
    Route::delete('feriados/{feriado}', [FeriadoController::class, 'destroy'])->name('feriados.destroy');
    Route::get('calendario', [CalendarioController::class, 'index'])->name('calendario.index');
    Route::get('calendario/eventos', [CalendarioController::class, 'eventos'])->name('calendario.eventos');
    Route::get('reemplazos', [ReemplazoController::class, 'index'])->name('reemplazos.index');
    Route::get('reemplazos/solicitud/{solicitud}', [ReemplazoController::class, 'create'])->name('reemplazos.create');
    Route::post('reemplazos/solicitud/{solicitud}', [ReemplazoController::class, 'store'])->name('reemplazos.store');
    Route::post('reemplazos/{reemplazo}/confirmar', [ReemplazoController::class, 'confirmar'])->name('reemplazos.confirmar');
    Route::post('reemplazos/{reemplazo}/cancelar', [ReemplazoController::class, 'cancelar'])->name('reemplazos.cancelar');
    Route::post('vacaciones/{solicitud}/cancelar', [SolicitudVacacionController::class, 'cancelar'])->name('vacaciones.cancelar');
});
Route::middleware(['auth', EnsureRole::class.':administrador'])->group(function () {
    Route::get('auditorias', [AuditoriaController::class, 'index'])->name('auditorias.index');
    Route::resource('usuarios', UsuarioController::class)->only(['index', 'create', 'store', 'edit', 'update']);
});
