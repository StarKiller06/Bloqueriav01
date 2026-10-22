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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('numero');
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('fecha');
            $table->string('forma_pago', 10);
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('total', 14, 2);
            $table->decimal('saldo_pendiente', 14, 2)->default(0);
            $table->timestamp('anulada_at')->nullable();
            $table->string('motivo_anulacion')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['empresa_id', 'numero']);
            $table->index(['empresa_id', 'saldo_pendiente']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
