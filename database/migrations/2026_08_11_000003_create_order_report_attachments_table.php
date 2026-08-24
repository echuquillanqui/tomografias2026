<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_report_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_report_id')->constrained('order_reports', indexName: 'ora_report_fk')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('original_size');
            $table->unsignedBigInteger('stored_size');
            $table->boolean('compressed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_report_attachments');
    }
};
