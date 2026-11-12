<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificacionController extends Controller
{
    public function index(Request $request): View
    {
        $notificaciones = $request->user()->notifications()->latest()->paginate(15);
        return view('notificaciones.index', compact('notificaciones'));
    }

    public function leer(Request $request, string $id): RedirectResponse
    {
        // La búsqueda parte de la relación del usuario autenticado: no admite IDOR.
        $notificacion = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notificacion->markAsRead();
        return redirect()->route('vacaciones.index');
    }

    public function leerTodas(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);
        return back()->with('ok', 'Notificaciones marcadas como leídas.');
    }
}
