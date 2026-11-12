<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol', 20)->default('funcionario')->index();
            $table->boolean('activo')->default(true);
            $table->foreignId('departamento_gestion_id')->nullable()->constrained('departamentos')->nullOnDelete();
        });
        // Mantiene el acceso de los administradores creados en etapas anteriores.
        DB::table('users')->where('is_admin', true)->update(['rol' => 'administrador']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('departamento_gestion_id');
            $table->dropColumn(['rol', 'activo']);
        });
    }
};
