<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE orders MODIFY fecha_orden DATETIME NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE orders ALTER COLUMN fecha_orden TYPE TIMESTAMP(0) WITHOUT TIME ZONE USING fecha_orden::timestamp');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE orders MODIFY fecha_orden DATE NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE orders ALTER COLUMN fecha_orden TYPE DATE USING fecha_orden::date');
        }
    }
};
