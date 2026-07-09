<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_reports', function (Blueprint $table) {
            $table->foreignId('medico_firmante_id')->nullable()->after('order_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('medico_firmante_id');
        });
    }
};
