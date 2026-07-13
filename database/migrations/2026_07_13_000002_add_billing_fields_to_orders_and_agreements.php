<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('tipo_comprobante')->nullable()->after('tipo_pago');
            $table->string('numero_comprobante')->nullable()->after('tipo_comprobante');
        });

        Schema::table('agreements', function (Blueprint $table) {
            $table->boolean('mostrar_precio_orden')->default(true)->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['tipo_comprobante', 'numero_comprobante']);
        });

        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn('mostrar_precio_orden');
        });
    }
};
