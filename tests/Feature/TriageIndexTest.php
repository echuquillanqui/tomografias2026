<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Exam;
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

    public function test_index_lists_only_orders_from_selected_date_without_consumables_column(): void
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
        Order::create([
            'codigo_orden' => 'ORD-AYER',
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'fecha_orden' => '2026-08-06 09:00:00',
            'estado' => 'Pendiente',
        ]);

        $this->actingAs($user)->get(route('triajes.index', ['date' => '2026-08-07']))
            ->assertOk()
            ->assertSee('ORD-TRIAJE-01')
            ->assertSee('Ana Torres')
            ->assertSee('value="2026-08-07"', false)
            ->assertDontSee('ORD-AYER')
            ->assertDontSee('<th>Consumibles</th>', false)
            ->assertDontSee('2.00 ml')
            ->assertSee('Rellenar plantilla')
            ->assertSee(route('orders.triaje', $order), false)
            ->assertDontSee('Guardar consumibles');
    }

    public function test_consumables_template_only_shows_patient_study_and_consumables_form(): void
    {
        $user = User::create(['username' => 'tester', 'email' => 'tester@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '12345678', 'nombres' => 'Ana', 'apellidos' => 'Torres']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $exam = Exam::create(['nombre_examen' => 'Tórax', 'tipo_contraste' => 'Sin contraste', 'activo' => true]);
        $order = Order::create(['codigo_orden' => 'ORD-01', 'patient_id' => $patient->id, 'agreement_id' => $agreement->id, 'fecha_orden' => '2026-08-07 09:00:00', 'estado' => 'Pendiente']);
        $order->orderExams()->create(['exam_id' => $exam->id, 'tipo_contraste' => 'Sin contraste', 'precio' => 100]);

        $this->actingAs($user)->get(route('orders.triaje', $order))
            ->assertOk()
            ->assertSee('DATOS DEL PACIENTE Y ESTUDIO')
            ->assertSee('Ana Torres')
            ->assertSee('Tórax')
            ->assertSee('CONSUMIBLES')
            ->assertSee(route('triajes.index'), false)
            ->assertSee('Guardar consumibles')
            ->assertDontSee('ÍNDICE DE TRIAJE')
            ->assertDontSee('Rellenar triaje');
    }
}
