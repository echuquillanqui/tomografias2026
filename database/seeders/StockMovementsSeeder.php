<?php

namespace Database\Seeders;

use App\Models\Reagent;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockMovementsSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'almacen@example.com')->firstOrFail();

        foreach ([['Iopamidol', 20], ['Jeringa', 100], ['Catéter', 50], ['Guantes', 10]] as [$name, $quantity]) {
            $reagent = Reagent::where('nombre', $name)->firstOrFail();
            StockMovement::updateOrCreate(
                ['reagent_id' => $reagent->id, 'tipo_movimiento' => 'Ingreso', 'motivo' => 'Stock inicial'],
                ['cantidad' => $quantity, 'user_id' => $user->id, 'fecha_movimiento' => now()]
            );
            $reagent->update(['stock_actual' => $quantity]);
        }
    }
}
