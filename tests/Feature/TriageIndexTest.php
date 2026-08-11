<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\AgreementPrice;
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

    public function test_updating_plate_consumable_updates_admission_form_plate_fields(): void
    {
        $user = User::create(['username' => 'tester-plates', 'email' => 'plates@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '12345679', 'nombres' => 'Elena', 'apellidos' => 'Paredes']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $order = Order::create(['codigo_orden' => 'ORD-PLACAS', 'patient_id' => $patient->id, 'agreement_id' => $agreement->id, 'fecha_orden' => '2026-08-07 09:00:00', 'estado' => 'Pendiente']);
        $plate = Reagent::create(['nombre' => 'Placas', 'unidad' => 'unidad', 'stock_actual' => 10, 'stock_minimo' => 1, 'activo' => true]);
        $order->admissionForm()->create(['data' => [
            'patient_name' => 'Dato conservado',
            'plates_count' => 1,
            'delivery_quantities' => ['PLACAS' => 1, 'CD' => 2],
        ]]);

        $this->actingAs($user)->put(route('triajes.consumables.update', $order), [
            'consumables' => [['reagent_id' => $plate->id, 'cantidad' => 4]],
        ])->assertRedirect(route('triajes.index'));

        $admissionData = $order->fresh()->admissionForm->data;
        $this->assertSame(4, $admissionData['plates_count']);
        $this->assertSame(4, $admissionData['delivery_quantities']['PLACAS']);
        $this->assertSame(2, $admissionData['delivery_quantities']['CD']);
        $this->assertSame('Dato conservado', $admissionData['patient_name']);
    }

    public function test_store_keeps_consumables_for_without_contrast_order(): void
    {
        $user = User::create(['username' => 'tester2', 'email' => 'tester2@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '87654321', 'nombres' => 'Luis', 'apellidos' => 'Rojas']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $exam = Exam::create(['nombre_examen' => 'Tórax', 'tipo_contraste' => 'Sin contraste', 'activo' => true]);
        AgreementPrice::create(['agreement_id' => $agreement->id, 'exam_id' => $exam->id, 'tipo_contraste' => 'Sin contraste', 'precio_pactado' => 100]);
        $reagent = Reagent::create(['nombre' => 'Material descartable', 'unidad' => 'und', 'stock_actual' => 10, 'stock_minimo' => 1, 'activo' => true]);

        $response = $this->actingAs($user)->post(route('orders.store'), [
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'fecha_orden' => '07/08/2026 09:00',
            'estado' => 'Pendiente',
            'tipo_pago' => 'Efectivo',
            'descuento' => 0,
            'exams' => [[
                'exam_id' => $exam->id,
                'tipo_contraste' => 'Sin contraste',
                'precio' => 100,
                'estado' => 'Pendiente',
            ]],
            'consumables' => [[
                'reagent_id' => $reagent->id,
                'cantidad' => 2,
            ]],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('order_consumables', [
            'reagent_id' => $reagent->id,
            'cantidad' => 2,
        ]);
    }

    public function test_triaje_preloads_configured_consumables_for_without_contrast_exam(): void
    {
        $user = User::create(['username' => 'tester3', 'email' => 'tester3@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '45678912', 'nombres' => 'María', 'apellidos' => 'Vega']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $exam = Exam::create(['nombre_examen' => 'Senos paranasales', 'tipo_contraste' => 'Sin contraste', 'activo' => true]);
        $reagent = Reagent::create(['nombre' => 'Guantes', 'unidad' => 'par', 'stock_actual' => 10, 'stock_minimo' => 1, 'activo' => true]);
        $exam->reagents()->attach($reagent->id, ['cantidad_estimada' => 3]);
        $order = Order::create(['codigo_orden' => 'ORD-SIN-CONTRASTE', 'patient_id' => $patient->id, 'agreement_id' => $agreement->id, 'fecha_orden' => '2026-08-07 09:00:00', 'estado' => 'Pendiente']);
        $order->orderExams()->create(['exam_id' => $exam->id, 'tipo_contraste' => 'Sin contraste', 'precio' => 100]);

        $this->actingAs($user)->get(route('orders.triaje', $order))
            ->assertOk()
            ->assertSee('Guantes')
            ->assertSee('3');
    }

    public function test_triaje_only_preloads_consumables_for_the_selected_contrast(): void
    {
        $user = User::create(['username' => 'tester4', 'email' => 'tester4@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '45678913', 'nombres' => 'Rosa', 'apellidos' => 'Díaz']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $exam = Exam::create(['nombre_examen' => 'TEM Abdomen', 'tipo_contraste' => 'Ambos', 'activo' => true]);
        $reagent = Reagent::create(['nombre' => 'Jeringa', 'unidad' => 'unidad', 'stock_actual' => 10, 'stock_minimo' => 1, 'activo' => true]);
        $exam->reagents()->attach($reagent->id, ['cantidad_estimada' => 1, 'tipo_contraste' => 'Sin contraste']);
        $exam->reagents()->attach($reagent->id, ['cantidad_estimada' => 2, 'tipo_contraste' => 'Con contraste']);
        $order = Order::create(['codigo_orden' => 'ORD-CON-CONTRASTE', 'patient_id' => $patient->id, 'agreement_id' => $agreement->id, 'fecha_orden' => '2026-08-07 09:00:00', 'estado' => 'Pendiente']);
        $order->orderExams()->create(['exam_id' => $exam->id, 'tipo_contraste' => 'Con contraste', 'precio' => 100]);

        $response = $this->actingAs($user)->get(route('orders.triaje', $order));

        $response->assertOk();
        $this->assertSame(2.0, $response->viewData('triageConsumables')[0]['cantidad']);
    }

}
