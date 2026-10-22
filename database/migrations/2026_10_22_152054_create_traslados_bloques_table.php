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
        Schema::create('traslados_bloques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained()->restrictOnDelete();
            $table->foreignId('sucursal_origen_id')->constrained('sucursales')->restrictOnDelete();
            $table->foreignId('sucursal_destino_id')->constrained('sucursales')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->date('fecha');
            $table->string('nota')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traslados_bloques');
    }
};
