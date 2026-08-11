<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL may use the original composite unique index to support the
        // exam_id foreign key, so provide a replacement before dropping it.
        Schema::table('exam_reagent', function (Blueprint $table) {
            $table->index('exam_id', 'exam_reagent_exam_id_index');
        });

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

        Schema::table('exam_reagent', function (Blueprint $table) {
            $table->dropIndex('exam_reagent_exam_id_index');
        });
    }
};
