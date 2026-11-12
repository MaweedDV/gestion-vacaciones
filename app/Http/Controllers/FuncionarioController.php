<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Funcionario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FuncionarioController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $funcionarios = Funcionario::with('departamento')
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('codigo', 'like', "%{$buscar}%")
                        ->orWhere('nombres', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('cargo', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('apellidos')->orderBy('nombres')->paginate(15)->withQueryString();
        return view('funcionarios.index', compact('funcionarios', 'buscar'));
    }

    public function create()
    {
        return view('funcionarios.form', [
            'funcionario' => new Funcionario(),
            'departamentos' => Departamento::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Funcionario::create($this->validated($request));
        return redirect()->route('funcionarios.index')->with('ok', 'Funcionario registrado.');
    }

    public function edit(Funcionario $funcionario)
    {
        return view('funcionarios.form', [
            'funcionario' => $funcionario,
            'departamentos' => Departamento::where('activo', true)
                ->orWhere('id', $funcionario->departamento_id)->orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Funcionario $funcionario)
    {
        $funcionario->update($this->validated($request, $funcionario));
        return redirect()->route('funcionarios.index')->with('ok', 'Funcionario actualizado.');
    }

    public function destroy(Funcionario $funcionario)
    {
        $funcionario->update(['activo' => false]);
        return redirect()->route('funcionarios.index')->with('ok', 'Funcionario desactivado; se conserva su historial.');
    }

    private function validated(Request $request, ?Funcionario $funcionario = null): array
    {
        $data = $request->validate([
            'departamento_id' => ['required', Rule::exists('departamentos', 'id')->where(function ($query) use ($funcionario) {
                $query->where('activo', true);
                if ($funcionario) { $query->orWhere('id', $funcionario->departamento_id); }
            })],
            'codigo' => ['required', 'string', 'max:255', Rule::unique('funcionarios', 'codigo')->ignore($funcionario?->id)],
            'rut' => ['nullable', 'string', 'max:12', 'regex:/^\d{1,8}-[0-9Kk]$/', Rule::unique('funcionarios', 'rut')->ignore($funcionario?->id)],
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'cargo' => ['required', 'string', 'max:255'],
            'fecha_ingreso' => ['nullable', 'date'],
            'regimen' => ['required', Rule::in(['por_definir', 'municipal_18883', 'administrativo_18834', 'otro'])],
            'activo' => ['required', 'boolean'],
        ]);
        return $data;
    }
}
