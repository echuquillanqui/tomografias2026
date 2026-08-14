<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->string('sale_note_series', 20)->default('004');
            $table->unsignedBigInteger('next_receipt_number')->default(210);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('sale_note_series', 20)->nullable();
            $table->unsignedBigInteger('receipt_number')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['receipt_number']);
            $table->dropColumn(['sale_note_series', 'receipt_number']);
        });

        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['sale_note_series', 'next_receipt_number']);
        });
    }
};
