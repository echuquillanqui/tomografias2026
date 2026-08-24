<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_orden')->nullable()->unique();
            $table->foreignId('patient_id')->constrained('patients', indexName: 'orders_patient_fk')->restrictOnDelete();
            $table->foreignId('agreement_id')->constrained('agreements', indexName: 'orders_agreement_fk')->restrictOnDelete();
            $table->foreignId('medico_solicitante_id')->nullable()->constrained('requesting_doctors', indexName: 'orders_requesting_doctor_fk')->nullOnDelete();
            $table->foreignId('medico_informe_id')->nullable()->constrained('users', indexName: 'orders_reporting_doctor_fk')->nullOnDelete();
            $table->string('archivo_orden_path')->nullable();
            $table->date('fecha_orden');
            $table->enum('estado', ['Pendiente', 'En proceso', 'Informado', 'Entregado', 'Anulado'])->default('Pendiente');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users', indexName: 'orders_creator_fk')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
