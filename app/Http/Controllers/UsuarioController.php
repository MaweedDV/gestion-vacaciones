<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Funcionario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UsuarioController extends Controller
{
    public function index()
    {
        return view('usuarios.index', ['usuarios' => User::with(['funcionario', 'departamentoGestion'])->orderBy('name')->paginate(20)]);
    }

    public function create()
    {
        return $this->form(new User());
    }

    public function edit(User $usuario)
    {
        return $this->form($usuario);
    }

    private function form(User $usuario)
    {
        return view('usuarios.form', [
            'usuario' => $usuario,
            'departamentos' => Departamento::where('activo', true)->orderBy('nombre')->get(),
            'funcionarios' => Funcionario::where('activo', true)
                ->where(function ($query) use ($usuario) {
                    $query->whereNull('user_id')->orWhere('user_id', $usuario->id ?: 0);
                })->orderBy('apellidos')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);
        DB::transaction(function () use ($data) {
            $funcionarioId = $data['funcionario_id'] ?? null;
            unset($data['funcionario_id']);
            $user = User::create($data);
            if ($funcionarioId) {
                $funcionario = Funcionario::whereKey($funcionarioId)->lockForUpdate()->firstOrFail();
                if ($funcionario->user_id) {
                    throw ValidationException::withMessages(['funcionario_id' => 'Este funcionario ya tiene usuario.']);
                }
                $funcionario->update(['user_id' => $user->id]);
            }
        });
        return redirect()->route('usuarios.index')->with('ok', 'Cuenta creada.');
    }

    public function update(Request $request, User $usuario)
    {
        $data = $this->validar($request, $usuario);
        if ($usuario->is($request->user()) && (!$data['activo'] || $data['rol'] !== 'administrador')) {
            throw ValidationException::withMessages(['rol' => 'No puedes quitarte tu propio acceso administrativo.']);
        }
        DB::transaction(function () use ($data, $usuario) {
            $funcionarioId = $data['funcionario_id'] ?? null;
            unset($data['funcionario_id']);
            if (empty($data['password'])) unset($data['password']);
            $usuario->update($data);
            Funcionario::where('user_id', $usuario->id)
                ->when($funcionarioId, fn ($q) => $q->where('id', '!=', $funcionarioId))
                ->update(['user_id' => null]);
            if ($funcionarioId) {
                $funcionario = Funcionario::whereKey($funcionarioId)->lockForUpdate()->firstOrFail();
                if ($funcionario->user_id && $funcionario->user_id !== $usuario->id) {
                    throw ValidationException::withMessages(['funcionario_id' => 'Este funcionario ya tiene usuario.']);
                }
                $funcionario->update(['user_id' => $usuario->id]);
            }
        });
        return redirect()->route('usuarios.index')->with('ok', 'Cuenta actualizada.');
    }

    private function validar(Request $request, ?User $usuario = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'password' => [$usuario ? 'nullable' : 'required', 'string', 'min:12', 'confirmed'],
            'rol' => ['required', Rule::in(['administrador', 'jefatura', 'funcionario', 'aprobador_general'])],
            'activo' => ['required', 'boolean'],
            'departamento_gestion_id' => ['nullable', 'exists:departamentos,id'],
            'funcionario_id' => ['nullable', 'exists:funcionarios,id'],
        ]);
        if ($data['rol'] === 'jefatura' && !$data['departamento_gestion_id']) {
            throw ValidationException::withMessages(['departamento_gestion_id' => 'La jefatura debe tener una unidad asignada.']);
        }
        if ($data['rol'] === 'funcionario' && !$data['funcionario_id']) {
            throw ValidationException::withMessages(['funcionario_id' => 'Vincula el usuario a su ficha de funcionario.']);
        }
        if (!in_array($data['rol'], ['jefatura'], true)) $data['departamento_gestion_id'] = null;
        $data['is_admin'] = $data['rol'] === 'administrador';
        return $data;
    }
}
