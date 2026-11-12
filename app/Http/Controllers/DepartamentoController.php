<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartamentoController extends Controller
{
    public function index()
    {
        $departamentos = Departamento::withCount('funcionarios')->orderBy('nombre')->paginate(15);
        return view('departamentos.index', compact('departamentos'));
    }

    public function create()
    {
        return view('departamentos.form', ['departamento' => new Departamento()]);
    }

    public function store(Request $request)
    {
        Departamento::create($this->validated($request));
        return redirect()->route('departamentos.index')->with('ok', 'Departamento registrado.');
    }

    public function edit(Departamento $departamento)
    {
        return view('departamentos.form', compact('departamento'));
    }

    public function update(Request $request, Departamento $departamento)
    {
        $departamento->update($this->validated($request, $departamento));
        return redirect()->route('departamentos.index')->with('ok', 'Departamento actualizado.');
    }

    public function destroy(Departamento $departamento)
    {
        $departamento->update(['activo' => false]);
        return redirect()->route('departamentos.index')->with('ok', 'Departamento desactivado; no se han eliminado registros.');
    }

    private function validated(Request $request, ?Departamento $departamento = null): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('departamentos', 'nombre')->ignore($departamento?->id)],
            'dotacion_minima' => ['required', 'integer', 'min:0', 'max:99999'],
            'activo' => ['required', 'boolean'],
        ]);
        return $data;
    }
}
