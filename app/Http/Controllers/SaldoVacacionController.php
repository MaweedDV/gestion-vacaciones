<?php

namespace App\Http\Controllers;

use App\Models\Funcionario;
use App\Models\SaldoVacacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaldoVacacionController extends Controller
{
    public function index()
    {
        return view('saldos.index', [
            'saldos' => SaldoVacacion::with('funcionario')->orderByDesc('periodo')->paginate(20),
            'funcionarios' => Funcionario::where('activo', true)->orderBy('apellidos')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'funcionario_id' => ['required', 'exists:funcionarios,id'],
            'periodo' => ['required', 'integer', 'between:2020,2100'],
            'dias_asignados' => ['required', 'numeric', 'between:0,365'],
            'dias_arrastrados' => ['required', 'numeric', 'between:0,365'],
            'dias_ajuste' => ['required', 'numeric', 'between:-365,365'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);
        DB::transaction(function () use ($data) {
            $saldo = SaldoVacacion::where('funcionario_id', $data['funcionario_id'])
                ->where('periodo', $data['periodo'])->lockForUpdate()->first();
            $total = (float)$data['dias_asignados'] + (float)$data['dias_arrastrados'] + (float)$data['dias_ajuste'];
            if ($total < (float)($saldo?->dias_utilizados ?? 0)) {
                throw ValidationException::withMessages(['dias_asignados' => 'El total no puede ser inferior a los días ya utilizados.']);
            }
            if ($saldo) {
                $saldo->update($data);
            } else {
                SaldoVacacion::create($data);
            }
        });
        return back()->with('ok', 'Saldo guardado.');
    }
}
