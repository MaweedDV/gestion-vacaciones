<?php
namespace App\Http\Controllers;

use App\Models\Funcionario;
use App\Models\Reemplazo;
use App\Models\SolicitudVacacion;
use App\Services\PlanificadorReemplazos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReemplazoController extends Controller
{
    public function index()
    {
        return view('reemplazos.index', [
            'solicitudes' => SolicitudVacacion::with(['funcionario.departamento','reemplazos.reemplazante'])
                ->whereIn('estado',['pendiente','aprobada'])->orderBy('fecha_inicio')->paginate(20),
        ]);
    }

    public function create(SolicitudVacacion $solicitud, PlanificadorReemplazos $planificador)
    {
        abort_unless(in_array($solicitud->estado,['pendiente','aprobada'],true), 404);
        $solicitud->load('funcionario.departamento');
        $desde = request()->query('desde', $solicitud->fecha_inicio->toDateString());
        $hasta = request()->query('hasta', $solicitud->fecha_fin->toDateString());
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$desde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$hasta)
            || $desde < $solicitud->fecha_inicio->toDateString() || $hasta > $solicitud->fecha_fin->toDateString() || $hasta < $desde) {
            return redirect()->route('reemplazos.create',$solicitud)->withErrors(['fecha_inicio'=>'Rango inválido.']);
        }
        return view('reemplazos.create', [
            'solicitud' => $solicitud, 'desde'=>$desde,'hasta'=>$hasta,
            'sugerencias' => $planificador->sugerencias($solicitud,$desde,$hasta),
            'alerta' => $planificador->alertaDotacion($solicitud,$desde,$hasta),
        ]);
    }

    public function store(Request $request, SolicitudVacacion $solicitud, PlanificadorReemplazos $planificador)
    {
        $data = $request->validate([
            'reemplazante_id'=>['required','integer','exists:funcionarios,id'],
            'fecha_inicio'=>['required','date'], 'fecha_fin'=>['required','date','after_or_equal:fecha_inicio'],
            'observaciones'=>['nullable','string','max:2000'],
        ]);
        DB::transaction(function () use($solicitud,$data,$planificador) {
            // Bloquear en orden fijo: evita carreras entre dos asignaciones concurrentes al mismo funcionario.
            $persona = Funcionario::whereKey($data['reemplazante_id'])->lockForUpdate()->firstOrFail();
            $solicitud = SolicitudVacacion::whereKey($solicitud->id)->lockForUpdate()->firstOrFail();
            if (!in_array($solicitud->estado,['pendiente','aprobada'],true))
                throw ValidationException::withMessages(['estado'=>'La solicitud ya no admite reemplazos.']);
            $planificador->validar($solicitud,$persona,$data['fecha_inicio'],$data['fecha_fin']);
            Reemplazo::create([
                'solicitud_vacacion_id'=>$solicitud->id, 'reemplazante_id'=>$persona->id,
                'fecha_inicio'=>$data['fecha_inicio'], 'fecha_fin'=>$data['fecha_fin'],
                'asignado_por_user_id'=>auth()->id(), 'estado'=>'propuesto',
                'observaciones'=>$data['observaciones']??null,
            ]);
        });
        return redirect()->route('reemplazos.index')->with('ok','Reemplazo propuesto. Falta confirmarlo.');
    }

    public function confirmar(Reemplazo $reemplazo, PlanificadorReemplazos $planificador)
    {
        DB::transaction(function () use($reemplazo,$planificador) {
            $persona = Funcionario::whereKey($reemplazo->reemplazante_id)->lockForUpdate()->firstOrFail();
            $reemplazo = Reemplazo::whereKey($reemplazo->id)->lockForUpdate()->firstOrFail();
            $solicitud = SolicitudVacacion::whereKey($reemplazo->solicitud_vacacion_id)->lockForUpdate()->firstOrFail();
            if ($reemplazo->estado !== 'propuesto') throw ValidationException::withMessages(['estado'=>'El reemplazo no está propuesto.']);
            if ($solicitud->estado !== 'aprobada') throw ValidationException::withMessages(['estado'=>'Primero debes aprobar las vacaciones.']);
            // Se excluye el propio registro durante la validación, sin crear una ventana de concurrencia.
            $reemplazo->update(['estado'=>'cancelado']);
            try {
                $planificador->validar($solicitud,$persona,$reemplazo->fecha_inicio->toDateString(),$reemplazo->fecha_fin->toDateString());
            } catch (\Throwable $e) {
                throw $e; // rollback restaura el estado propuesto
            }
            $reemplazo->update(['estado'=>'confirmado']);
        });
        return back()->with('ok','Reemplazo confirmado.');
    }

    public function cancelar(Reemplazo $reemplazo)
    {
        DB::transaction(function () use($reemplazo) {
            $reemplazo = Reemplazo::whereKey($reemplazo->id)->lockForUpdate()->firstOrFail();
            if (!in_array($reemplazo->estado,['propuesto','confirmado'],true))
                throw ValidationException::withMessages(['estado'=>'Ya está cancelado.']);
            if ($reemplazo->fecha_inicio->isPast())
                throw ValidationException::withMessages(['estado'=>'Reemplazos ya iniciados requieren regularización administrativa.']);
            $reemplazo->update(['estado'=>'cancelado']);
        });
        return back()->with('ok','Reemplazo cancelado.');
    }
}
