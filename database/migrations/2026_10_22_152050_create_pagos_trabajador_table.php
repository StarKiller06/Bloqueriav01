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
        Schema::create('pagos_trabajador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trabajador_id')->constrained('trabajadores')->restrictOnDelete();
            $table->date('fecha');
            $table->unsignedInteger('bultos_pagados');
            $table->decimal('tarifa', 12, 2);
            $table->decimal('monto', 12, 2);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_trabajador');
    }
};
