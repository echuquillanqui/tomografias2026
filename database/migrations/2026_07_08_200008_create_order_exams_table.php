<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->restrictOnDelete();
            $table->enum('tipo_contraste', ['Con contraste', 'Sin contraste']);
            $table->decimal('precio', 10, 2);
            $table->enum('estado', ['Pendiente', 'Realizado', 'Informado', 'Anulado'])->default('Pendiente');
            $table->decimal('comision_porcentaje', 5, 2)->nullable();
            $table->decimal('comision_monto', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_exams');
    }
};
