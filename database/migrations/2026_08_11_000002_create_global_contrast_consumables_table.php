<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_contrast_consumables', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_contraste');
            $table->foreignId('reagent_id')->constrained('reagents', indexName: 'gcc_reagent_fk')->restrictOnDelete();
            $table->decimal('cantidad_estimada', 10, 2);
            $table->timestamps();
            $table->unique(['tipo_contraste', 'reagent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_contrast_consumables');
    }
};
