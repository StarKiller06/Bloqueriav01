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
        Schema::create('trabajadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->string('nombre');
            $table->string('documento');
            $table->string('telefono')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'documento']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trabajadores');
    }
};
