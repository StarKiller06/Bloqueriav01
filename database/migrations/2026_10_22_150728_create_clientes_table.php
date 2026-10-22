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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('tipo_documento', 5);
            $table->string('documento');
            $table->string('nombre');
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->string('direccion')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'tipo_documento', 'documento']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
