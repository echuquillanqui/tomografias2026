<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_consumables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('reagent_id')->constrained('reagents')->restrictOnDelete();
            $table->decimal('cantidad', 10, 2);
            $table->timestamps();
            $table->unique(['order_id', 'reagent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_consumables');
    }
};
