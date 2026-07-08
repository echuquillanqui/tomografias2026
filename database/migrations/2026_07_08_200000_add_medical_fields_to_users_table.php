<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nombre_completo')->nullable()->after('name');
            $table->enum('rol', ['Admin', 'Recepción', 'Médico', 'Almacén'])->default('Recepción')->after('password');
            $table->enum('tipo_medico', ['Solicitante', 'De Informe', 'Ambos'])->nullable()->after('rol');
            $table->string('cmp')->nullable()->after('tipo_medico');
            $table->string('rne')->nullable()->after('cmp');
            $table->decimal('comision_porcentaje', 5, 2)->nullable()->after('rne');
            $table->string('firma_path')->nullable()->after('comision_porcentaje');
            $table->boolean('activo')->default(true)->after('firma_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nombre_completo',
                'rol',
                'tipo_medico',
                'cmp',
                'rne',
                'comision_porcentaje',
                'firma_path',
                'activo',
            ]);
        });
    }
};
