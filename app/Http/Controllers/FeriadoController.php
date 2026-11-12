<?php

namespace App\Http\Controllers;

use App\Models\Feriado;
use Illuminate\Http\Request;

class FeriadoController extends Controller
{
    public function index()
    {
        return view('feriados.index', ['feriados' => Feriado::orderBy('fecha')->paginate(30)]);
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha' => ['required', 'date', 'unique:feriados,fecha'],
            'nombre' => ['required', 'string', 'max:255'],
        ]);
        Feriado::create($data);
        return back()->with('ok', 'Feriado agregado.');
    }
    public function destroy(Feriado $feriado)
    {
        $feriado->delete();
        return back()->with('ok', 'Feriado eliminado. Revisa las solicitudes pendientes afectadas.');
    }
}
