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
        Schema::create('detalles_carga_inicial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carga_inicial_id')->constrained('cargas_iniciales')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->unsignedInteger('bloques');
            $table->unsignedInteger('cemento');
            $table->unsignedInteger('arena_bloques');
            $table->timestamps();

            $table->unique(['carga_inicial_id', 'sucursal_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_carga_inicial');
    }
};
