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
        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->foreignId('concepto_caja_id')->constrained('conceptos_caja')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tipo', 10);
            $table->decimal('monto', 14, 2);
            $table->date('fecha');
            $table->string('nota')->nullable();
            $table->nullableMorphs('origen');
            $table->timestamps();

            $table->index(['empresa_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_caja');
    }
};
