<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        // No escalar automáticamente cuentas RRHH a administrador.
        // Reasignar manualmente a alguno de los tres roles desde cuenta administradora.
        DB::table('users')->where('rol', 'rrhh')->update(['activo' => false]);
    }
    public function down(): void { /* No reactivar cuentas automáticamente por seguridad. */ }
};
