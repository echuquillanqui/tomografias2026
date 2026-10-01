<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY rol ENUM('Superadmin', 'Admin', 'Recepción', 'Médico', 'Almacén') NOT NULL DEFAULT 'Recepción'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('users')->where('rol', 'Superadmin')->update(['rol' => 'Admin']);
            DB::statement("ALTER TABLE users MODIFY rol ENUM('Admin', 'Recepción', 'Médico', 'Almacén') NOT NULL DEFAULT 'Recepción'");
        }
    }
};
