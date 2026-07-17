<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_expenses', function (Blueprint $table) {
            $table->foreignId('cash_fixed_expense_id')->nullable()->after('id')->constrained('cash_fixed_expenses')->nullOnDelete();
            $table->string('fixed_expense_period', 7)->nullable()->after('cash_fixed_expense_id');
            $table->unique(['cash_fixed_expense_id', 'fixed_expense_period'], 'cash_expense_fixed_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cash_expenses', function (Blueprint $table) {
            $table->dropUnique('cash_expense_fixed_period_unique');
            $table->dropConstrainedForeignId('cash_fixed_expense_id');
            $table->dropColumn('fixed_expense_period');
        });
    }
};
