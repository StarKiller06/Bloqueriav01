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
        Schema::create('conceptos_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->string('tipo', 10);
            $table->string('clave')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'nombre']);
            $table->unique(['empresa_id', 'clave']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conceptos_caja');
    }
};
