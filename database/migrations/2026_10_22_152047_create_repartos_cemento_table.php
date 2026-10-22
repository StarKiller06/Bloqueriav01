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
        Schema::create('repartos_cemento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_cemento_id')->constrained('compras_cemento')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->unsignedInteger('bultos');
            $table->timestamps();

            $table->unique(['compra_cemento_id', 'sucursal_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repartos_cemento');
    }
};
