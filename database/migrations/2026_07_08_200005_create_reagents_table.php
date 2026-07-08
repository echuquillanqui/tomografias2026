<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reagents', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->decimal('stock_actual', 10, 2)->default(0);
            $table->enum('unidad', ['ml', 'unidad', 'frasco', 'caja']);
            $table->decimal('stock_minimo', 10, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reagents');
    }
};
