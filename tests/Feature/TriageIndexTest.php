<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\AgreementPrice;
use App\Models\Exam;
use App\Models\GlobalContrastConsumable;
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

    public function test_index_can_search_a_patient_across_all_dates(): void
    {
        $user = User::create(['username' => 'global-search', 'email' => 'global@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '87654321', 'nombres' => 'Lucía', 'apellidos' => 'Mendoza']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        Order::create([
            'codigo_orden' => 'ORD-HISTORICA',
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'fecha_orden' => '2025-02-03 09:00:00',
            'estado' => 'Pendiente',
        ]);

        $this->actingAs($user)->get(route('triajes.index', [
            'date' => '2026-08-07',
            'search' => 'Lucía',
            'all_dates' => 1,
        ]))
            ->assertOk()
            ->assertSee('ORD-HISTORICA')
            ->assertSee('Buscar en todas las fechas')
            ->assertSee('checked', false);
    }

    public function test_without_contrast_template_only_shows_patient_study_and_plate_field(): void
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
            ->assertSee('Contraste')
            ->assertSee('Sin contraste')
            ->assertSee('Placas')
            ->assertSee('name="plates_count"', false)
            ->assertDontSee('CONSUMIBLES')
            ->assertSee(route('triajes.index'), false)
            ->assertSee('Guardar placas')
            ->assertDontSee('Agregar consumible...')
            ->assertDontSee('ÍNDICE DE TRIAJE')
            ->assertDontSee('Rellenar triaje');
    }

    public function test_with_contrast_template_shows_consumables_form(): void
    {
        $user = User::create(['username' => 'tester-contrast', 'email' => 'contrast@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '12345671', 'nombres' => 'Ana', 'apellidos' => 'Torres']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $exam = Exam::create(['nombre_examen' => 'Abdomen', 'tipo_contraste' => 'Ambos', 'activo' => true]);
        $order = Order::create(['codigo_orden' => 'ORD-CONTRASTE', 'patient_id' => $patient->id, 'agreement_id' => $agreement->id, 'fecha_orden' => '2026-08-07 09:00:00', 'estado' => 'Pendiente']);
        $order->orderExams()->create(['exam_id' => $exam->id, 'tipo_contraste' => 'Con contraste', 'precio' => 100]);

        $this->actingAs($user)->get(route('orders.triaje', $order))
            ->assertOk()
            ->assertSee('CONSUMIBLES')
            ->assertSee('Agregar consumible...')
            ->assertSee('Guardar consumibles');
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

    public function test_admission_form_automatically_uses_the_saved_triage_plate_quantity(): void
    {
        $user = User::create(['username' => 'tester-admission-plates', 'email' => 'admission-plates@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '12345670', 'nombres' => 'Lucía', 'apellidos' => 'Torres']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $order = Order::create(['codigo_orden' => 'ORD-FICHA-PLACAS', 'patient_id' => $patient->id, 'agreement_id' => $agreement->id, 'fecha_orden' => '2026-08-07 09:00:00', 'estado' => 'Pendiente']);
        $plate = Reagent::create(['nombre' => 'Placas', 'unidad' => 'unidad', 'stock_actual' => 10, 'stock_minimo' => 1, 'activo' => true]);
        $order->consumables()->create(['reagent_id' => $plate->id, 'cantidad' => 3]);
        $order->admissionForm()->create(['data' => ['plates_count' => 1, 'delivery_quantities' => ['PLACAS' => 1]]]);

        $this->actingAs($user)->get(route('orders.ficha-ingreso.template', $order))->assertOk();

        $admissionData = $order->fresh()->admissionForm->data;
        $this->assertSame(3, $admissionData['plates_count']);
        $this->assertSame(3, $admissionData['delivery_quantities']['PLACAS']);
    }

    public function test_store_keeps_consumables_for_without_contrast_order(): void
    {
        $user = User::create(['username' => 'tester2', 'email' => 'tester2@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '87654321', 'nombres' => 'Luis', 'apellidos' => 'Rojas']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $exam = Exam::create(['nombre_examen' => 'Tórax', 'tipo_contraste' => 'Sin contraste', 'activo' => true]);
        AgreementPrice::create(['agreement_id' => $agreement->id, 'exam_id' => $exam->id, 'tipo_contraste' => 'Sin contraste', 'precio_pactado' => 100]);
        $reagent = Reagent::create(['nombre' => 'Material descartable', 'unidad' => 'und', 'stock_actual' => 10, 'stock_minimo' => 1, 'activo' => true]);
        GlobalContrastConsumable::create(['tipo_contraste' => 'Sin contraste', 'reagent_id' => $reagent->id, 'cantidad_estimada' => 2]);

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

    public function test_triaje_does_not_preload_consumables_for_without_contrast_exam(): void
    {
        $user = User::create(['username' => 'tester3', 'email' => 'tester3@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '45678912', 'nombres' => 'María', 'apellidos' => 'Vega']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $exam = Exam::create(['nombre_examen' => 'Senos paranasales', 'tipo_contraste' => 'Sin contraste', 'activo' => true]);
        $reagent = Reagent::create(['nombre' => 'Guantes', 'unidad' => 'par', 'stock_actual' => 10, 'stock_minimo' => 1, 'activo' => true]);
        GlobalContrastConsumable::create(['tipo_contraste' => 'Sin contraste', 'reagent_id' => $reagent->id, 'cantidad_estimada' => 3]);
        $order = Order::create(['codigo_orden' => 'ORD-SIN-CONTRASTE', 'patient_id' => $patient->id, 'agreement_id' => $agreement->id, 'fecha_orden' => '2026-08-07 09:00:00', 'estado' => 'Pendiente']);
        $order->orderExams()->create(['exam_id' => $exam->id, 'tipo_contraste' => 'Sin contraste', 'precio' => 100]);

        $this->actingAs($user)->get(route('orders.triaje', $order))
            ->assertOk()
            ->assertDontSee('Guantes')
            ->assertDontSee('Agregar consumible...');
    }

    public function test_triaje_only_preloads_consumables_for_the_selected_contrast(): void
    {
        $user = User::create(['username' => 'tester4', 'email' => 'tester4@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '45678913', 'nombres' => 'Rosa', 'apellidos' => 'Díaz']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $exam = Exam::create(['nombre_examen' => 'TEM Abdomen', 'tipo_contraste' => 'Ambos', 'activo' => true]);
        $reagent = Reagent::create(['nombre' => 'Jeringa', 'unidad' => 'unidad', 'stock_actual' => 10, 'stock_minimo' => 1, 'activo' => true]);
        GlobalContrastConsumable::create(['tipo_contraste' => 'Sin contraste', 'reagent_id' => $reagent->id, 'cantidad_estimada' => 1]);
        GlobalContrastConsumable::create(['tipo_contraste' => 'Con contraste', 'reagent_id' => $reagent->id, 'cantidad_estimada' => 2]);
        $order = Order::create(['codigo_orden' => 'ORD-CON-CONTRASTE', 'patient_id' => $patient->id, 'agreement_id' => $agreement->id, 'fecha_orden' => '2026-08-07 09:00:00', 'estado' => 'Pendiente']);
        $order->orderExams()->create(['exam_id' => $exam->id, 'tipo_contraste' => 'Con contraste', 'precio' => 100]);

        $response = $this->actingAs($user)->get(route('orders.triaje', $order));

        $response->assertOk();
        $this->assertSame(2.0, $response->viewData('triageConsumables')[0]['cantidad']);
    }

    public function test_without_contrast_admission_template_receives_its_global_consumables(): void
    {
        $user = User::create(['username' => 'tester5', 'email' => 'tester5@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '45678914', 'nombres' => 'Julia', 'apellidos' => 'León']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $exam = Exam::create(['nombre_examen' => 'TEM Cerebral', 'tipo_contraste' => 'Sin contraste', 'activo' => true]);
        $reagent = Reagent::create(['nombre' => 'Campo descartable', 'unidad' => 'unidad', 'stock_actual' => 10, 'stock_minimo' => 1, 'activo' => true]);
        GlobalContrastConsumable::create(['tipo_contraste' => 'Sin contraste', 'reagent_id' => $reagent->id, 'cantidad_estimada' => 1]);
        $order = Order::create(['codigo_orden' => 'ORD-FICHA-SIN', 'patient_id' => $patient->id, 'agreement_id' => $agreement->id, 'fecha_orden' => '2026-08-07 09:00:00', 'estado' => 'Pendiente']);
        $order->orderExams()->create(['exam_id' => $exam->id, 'tipo_contraste' => 'Sin contraste', 'precio' => 100]);

        $this->actingAs($user)->get(route('orders.ficha-ingreso.template', $order))
            ->assertOk()
            ->assertDontSee('INSUMOS Y MATERIALES DE USO INTERNO PARA ESTUDIO SIN CONTRASTE')
            ->assertDontSee('Campo descartable');
    }

}
