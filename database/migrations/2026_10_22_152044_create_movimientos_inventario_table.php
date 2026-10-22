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
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->string('item', 10);
            $table->foreignId('producto_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('tipo', 20);
            $table->integer('cantidad');
            $table->date('fecha');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nota')->nullable();
            $table->nullableMorphs('origen');
            $table->timestamps();

            $table->index(['sucursal_id', 'item']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
