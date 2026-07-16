<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasTable('requesting_doctors')) {
            return;
        }

        $currentSolicitantes = DB::table('orders')
            ->join('users', 'orders.medico_solicitante_id', '=', 'users.id')
            ->whereNotNull('orders.medico_solicitante_id')
            ->select('orders.id as order_id', 'users.nombre_completo')
            ->get();

        foreach ($currentSolicitantes as $solicitante) {
            $name = trim(preg_replace('/\s+/', ' ', (string) $solicitante->nombre_completo));
            if ($name === '') {
                DB::table('orders')->where('id', $solicitante->order_id)->update(['medico_solicitante_id' => null]);
                continue;
            }

            $doctor = DB::table('requesting_doctors')->where('nombre', $name)->first();
            $doctorId = $doctor?->id ?? DB::table('requesting_doctors')->insertGetId([
                'nombre' => $name,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('orders')->where('id', $solicitante->order_id)->update(['medico_solicitante_id' => $doctorId]);
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['medico_solicitante_id']);
            $table->foreign('medico_solicitante_id')->references('id')->on('requesting_doctors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        DB::table('orders')->whereNotNull('medico_solicitante_id')->update(['medico_solicitante_id' => null]);

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['medico_solicitante_id']);
            $table->foreign('medico_solicitante_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
