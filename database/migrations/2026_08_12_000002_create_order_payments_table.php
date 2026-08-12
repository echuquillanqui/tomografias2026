<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('payment_method');
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->unique(['order_id', 'payment_method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
