<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('departamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->unsignedInteger('dotacion_minima')->default(1);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('funcionarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->foreignId('departamento_id')->constrained('departamentos')->restrictOnDelete();
            $table->string('codigo')->unique();
            $table->string('rut', 12)->nullable()->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('cargo');
            $table->date('fecha_ingreso')->nullable();
            $table->string('regimen')->default('por_definir');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('competencias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        Schema::create('competencia_funcionario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funcionario_id')->constrained('funcionarios')->cascadeOnDelete();
            $table->foreignId('competencia_id')->constrained('competencias')->cascadeOnDelete();
            $table->unsignedTinyInteger('nivel')->default(1); // Escala interna 1 a 5; validar en aplicación
            $table->unique(['funcionario_id', 'competencia_id']);
        });

        Schema::create('feriados', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->string('nombre');
            $table->timestamps();
        });

        Schema::create('saldos_vacaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funcionario_id')->constrained('funcionarios')->restrictOnDelete();
            $table->unsignedSmallInteger('periodo');
            $table->decimal('dias_asignados', 7, 2)->default(0);
            $table->decimal('dias_arrastrados', 7, 2)->default(0);
            $table->decimal('dias_ajuste', 7, 2)->default(0);
            $table->decimal('dias_utilizados', 7, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->unique(['funcionario_id', 'periodo']);
            $table->timestamps();
        });

        Schema::create('solicitudes_vacaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funcionario_id')->constrained('funcionarios')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('dias_habiles', 7, 2);
            $table->string('estado')->default('pendiente'); // pendiente, aprobada, rechazada, cancelada
            $table->text('observaciones')->nullable();
            $table->timestamp('resuelta_en')->nullable();
            $table->timestamps();
            $table->index(['estado', 'fecha_inicio', 'fecha_fin'], 'solicitudes_estado_fechas_idx');
        });

        Schema::create('aprobaciones_vacaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_vacacion_id')->constrained('solicitudes_vacaciones')->cascadeOnDelete();
            $table->foreignId('aprobador_user_id')->constrained('users')->restrictOnDelete();
            $table->string('decision'); // aprobada, rechazada, devuelta
            $table->text('comentario')->nullable();
            $table->timestamps();
        });

        Schema::create('indisponibilidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funcionario_id')->constrained('funcionarios')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('tipo'); // permiso, licencia, capacitacion, otro
            $table->text('detalle')->nullable();
            $table->timestamps();
            $table->index(['funcionario_id', 'fecha_inicio', 'fecha_fin'], 'indisp_func_fechas_idx');
        });

        Schema::create('reemplazos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_vacacion_id')->constrained('solicitudes_vacaciones')->restrictOnDelete();
            $table->foreignId('reemplazante_id')->constrained('funcionarios')->restrictOnDelete();
            $table->foreignId('asignado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('estado')->default('propuesto'); // propuesto, confirmado, cancelado
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->index(['reemplazante_id', 'fecha_inicio', 'fecha_fin'], 'reempl_func_fechas_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reemplazos');
        Schema::dropIfExists('indisponibilidades');
        Schema::dropIfExists('aprobaciones_vacaciones');
        Schema::dropIfExists('solicitudes_vacaciones');
        Schema::dropIfExists('saldos_vacaciones');
        Schema::dropIfExists('feriados');
        Schema::dropIfExists('competencia_funcionario');
        Schema::dropIfExists('competencias');
        Schema::dropIfExists('funcionarios');
        Schema::dropIfExists('departamentos');
    }
};
