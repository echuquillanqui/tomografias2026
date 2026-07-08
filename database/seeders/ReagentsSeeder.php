<?php

namespace Database\Seeders;

use App\Models\Reagent;
use Illuminate\Database\Seeder;

class ReagentsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Iopamidol', 20, 'frasco', 5], ['Jeringa', 100, 'unidad', 20], ['Catéter', 50, 'unidad', 10], ['Guantes', 10, 'caja', 2],
        ] as [$nombre, $stock, $unidad, $minimo]) {
            Reagent::updateOrCreate(['nombre' => $nombre], ['stock_actual' => $stock, 'unidad' => $unidad, 'stock_minimo' => $minimo, 'activo' => true]);
        }
    }
}
