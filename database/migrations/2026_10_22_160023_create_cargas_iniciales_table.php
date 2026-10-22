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
        Schema::create('cargas_iniciales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('fecha_corte');
            $table->decimal('saldo_caja', 14, 2);
            $table->timestamp('cerrada_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cargas_iniciales');
    }
};
