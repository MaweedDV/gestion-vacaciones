<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('evento', 100)->index();
            $table->string('entidad', 120);
            $table->unsignedBigInteger('entidad_id');
            $table->json('detalle')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entidad','entidad_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('auditorias'); }
};
