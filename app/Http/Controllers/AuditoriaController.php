<?php
namespace App\Http\Controllers;
use App\Models\Auditoria;
use Illuminate\View\View;
class AuditoriaController extends Controller {
    public function index(): View {
        return view('auditorias.index', ['eventos' => Auditoria::with('usuario')->orderByDesc('id')->paginate(30)]);
    }
}
