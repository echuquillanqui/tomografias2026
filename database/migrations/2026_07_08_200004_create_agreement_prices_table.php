<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreement_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained('agreements')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->enum('tipo_contraste', ['Con contraste', 'Sin contraste']);
            $table->decimal('precio_pactado', 10, 2);
            $table->timestamps();
            $table->unique(['agreement_id', 'exam_id', 'tipo_contraste']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_prices');
    }
};
