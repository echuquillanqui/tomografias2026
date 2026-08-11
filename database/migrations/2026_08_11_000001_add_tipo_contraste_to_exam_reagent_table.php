<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_reagent', function (Blueprint $table) {
            $table->dropUnique(['exam_id', 'reagent_id']);
            $table->string('tipo_contraste')->default('Ambos')->after('reagent_id');
            $table->unique(['exam_id', 'reagent_id', 'tipo_contraste']);
        });
    }

    public function down(): void
    {
        Schema::table('exam_reagent', function (Blueprint $table) {
            $table->dropUnique(['exam_id', 'reagent_id', 'tipo_contraste']);
            $table->dropColumn('tipo_contraste');
            $table->unique(['exam_id', 'reagent_id']);
        });
    }
};
