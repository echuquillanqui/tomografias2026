<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_reports', function (Blueprint $table) {
            $table->longText('tecnica')->nullable()->after('titulo');
            $table->longText('informe')->nullable()->after('tecnica');
            $table->longText('impresion')->nullable()->after('informe');
            $table->longText('recomendaciones')->nullable()->after('impresion');
        });
    }

    public function down(): void
    {
        Schema::table('order_reports', function (Blueprint $table) {
            $table->dropColumn(['tecnica', 'informe', 'impresion', 'recomendaciones']);
        });
    }
};
