<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('producciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->foreignId('trabajador_id')->constrained('trabajadores')->restrictOnDelete();
            $table->foreignId('producto_id')->constrained()->restrictOnDelete();
            $table->date('fecha');
            $table->unsignedInteger('bultos_usados');
            $table->unsignedInteger('bloques_sacados');
            $table->unsignedInteger('bloques_esperados');
            $table->integer('diferencia');
            $table->foreignId('pago_trabajador_id')->nullable()->constrained('pagos_trabajador')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['trabajador_id', 'pago_trabajador_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producciones');
    }
};
