<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Reagent;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderConsumableMovementBackfillTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_it_registers_historical_consumption_and_updates_stock_once(): void
    {
        $user = User::create(['username' => 'stock-user', 'email' => 'stock@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '12345678', 'nombres' => 'Ana', 'apellidos' => 'Torres']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $reagent = Reagent::create([
            'nombre' => 'IOPAMIDOL',
            'stock_actual' => 20,
            'unidad' => 'frasco',
            'stock_minimo' => 2,
            'activo' => true,
        ]);
        $order = Order::create([
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'fecha_orden' => '2026-08-10',
            'estado' => 'Pendiente',
            'created_by' => $user->id,
        ]);
        $order->consumables()->create(['reagent_id' => $reagent->id, 'cantidad' => 3]);

        $migration = require database_path('migrations/2026_08_17_235900_backfill_order_consumable_stock_movements.php');
        $migration->up();
        $migration->up();

        $movement = StockMovement::sole();
        $this->assertSame('Salida', $movement->tipo_movimiento);
        $this->assertSame('3.00', $movement->cantidad);
        $this->assertSame('2026-08-10', $movement->fecha_movimiento->toDateString());
        $this->assertSame($order->id, $movement->order_id);
        $this->assertSame('17.00', $reagent->fresh()->stock_actual);
    }
}
