<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Reagent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TriageIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_index_lists_orders_consumables_and_template_action(): void
    {
        $user = User::create([
            'username' => 'tester',
            'email' => 'tester@example.com',
            'password' => 'password',
        ]);
        $patient = Patient::create(['dni' => '12345678', 'nombres' => 'Ana', 'apellidos' => 'Torres']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $order = Order::create([
            'codigo_orden' => 'ORD-TRIAJE-01',
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'fecha_orden' => '2026-08-07 09:00:00',
            'estado' => 'Pendiente',
        ]);
        $reagent = Reagent::create([
            'nombre' => 'Contraste',
            'unidad' => 'ml',
            'stock_actual' => 10,
            'stock_minimo' => 1,
            'activo' => true,
        ]);
        $order->consumables()->create(['reagent_id' => $reagent->id, 'cantidad' => 2]);

        $this->actingAs($user)->get(route('triajes.index'))
            ->assertOk()
            ->assertSee('ORD-TRIAJE-01')
            ->assertSee('Ana Torres')
            ->assertSee('Contraste')
            ->assertSee('2.00 ml')
            ->assertSee('Rellenar plantilla')
            ->assertSee(route('orders.triaje', $order), false)
            ->assertDontSee('Guardar consumibles');
    }
}
