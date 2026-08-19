<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Exam;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Reagent;
use App\Models\RequestingDoctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderValidationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_invalid_order_identifies_the_field_and_preserves_submitted_values(): void
    {
        $user = User::create(['username' => 'order-user', 'email' => 'order@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '12345678', 'nombres' => 'Ana', 'apellidos' => 'Torres']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $exam = Exam::create(['nombre_examen' => 'Tórax', 'tipo_contraste' => 'Sin contraste', 'activo' => true]);

        $response = $this->from(route('orders.create'))->actingAs($user)->post(route('orders.store'), [
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'fecha_orden' => 'fecha incorrecta',
            'estado' => 'Pendiente',
            'observaciones' => 'Datos que no deben desaparecer',
            'payments' => [['payment_method' => 'Efectivo', 'amount' => 100]],
            'exams' => [[
                'exam_id' => $exam->id,
                'tipo_contraste' => 'Sin contraste',
                'precio' => 100,
                'estado' => 'Pendiente',
            ]],
        ]);

        $response->assertRedirect(route('orders.create'))
            ->assertSessionHasErrors(['fecha_orden' => 'El campo fecha y hora debe contener una fecha y hora válidas.'])
            ->assertSessionHasInput('fecha_orden', 'fecha incorrecta')
            ->assertSessionHasInput('observaciones', 'Datos que no deben desaparecer')
            ->assertSessionHasInput('exams.0.exam_id', $exam->id)
            ->assertSessionHasInput('payments.0.amount', 100);
    }

    public function test_updating_exams_refreshes_automatic_admission_and_declaration_values(): void
    {
        $user = User::create(['username' => 'order-editor', 'email' => 'editor@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '87654321', 'nombres' => 'Luis', 'apellidos' => 'Ramos']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $withoutContrast = Exam::create(['nombre_examen' => 'Tórax', 'tipo_contraste' => 'Ambos', 'activo' => true]);
        $withContrast = Exam::create(['nombre_examen' => 'Abdomen completo', 'tipo_contraste' => 'Ambos', 'activo' => true]);
        $order = Order::create([
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'fecha_orden' => now(),
            'estado' => 'Pendiente',
            'tipo_pago' => 'Efectivo',
            'subtotal' => 100,
            'total' => 100,
            'created_by' => $user->id,
        ]);
        $order->orderExams()->create([
            'exam_id' => $withoutContrast->id,
            'tipo_contraste' => 'Sin contraste',
            'precio' => 100,
            'estado' => 'Pendiente',
        ]);
        $order->admissionForm()->create(['data' => [
            'study' => 'Tórax',
            'contrast_label' => 'SIN CONTRASTE',
            'has_contrast' => false,
            'cause' => 'Dato clínico conservado',
        ]]);
        $order->swornDeclaration()->create(['data' => ['study' => 'Tórax', 'revocation' => 'Dato conservado']]);

        $response = $this->actingAs($user)->put(route('orders.update', $order), [
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'fecha_orden' => now()->format('Y-m-d H:i'),
            'estado' => 'Pendiente',
            'payments' => [['payment_method' => 'Efectivo', 'amount' => 150]],
            'exams' => [[
                'exam_id' => $withContrast->id,
                'tipo_contraste' => 'Con contraste',
                'precio' => 150,
                'estado' => 'Pendiente',
            ]],
        ]);

        $response->assertRedirect(route('orders.show', $order));
        $admissionData = $order->fresh()->admissionForm->data;
        $this->assertSame('Abdomen completo', $admissionData['study']);
        $this->assertSame('CON CONTRASTE', $admissionData['contrast_label']);
        $this->assertTrue($admissionData['has_contrast']);
        $this->assertSame('Dato clínico conservado', $admissionData['cause']);
        $this->assertSame('Abdomen completo', $order->fresh()->swornDeclaration->data['study']);
        $this->assertSame('Dato conservado', $order->fresh()->swornDeclaration->data['revocation']);
    }

    public function test_updating_requesting_doctor_refreshes_the_admission_form(): void
    {
        $user = User::create(['username' => 'doctor-editor', 'email' => 'doctor-editor@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '11223344', 'nombres' => 'Rosa', 'apellidos' => 'Flores']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $exam = Exam::create(['nombre_examen' => 'Tórax', 'tipo_contraste' => 'Sin contraste', 'activo' => true]);
        $previousDoctor = RequestingDoctor::create(['nombre' => 'Dra. Anterior', 'activo' => true]);
        $currentDoctor = RequestingDoctor::create(['nombre' => 'Dr. Actualizado', 'activo' => true]);
        $order = Order::create([
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'medico_solicitante_id' => $previousDoctor->id,
            'fecha_orden' => now(),
            'estado' => 'Pendiente',
            'tipo_pago' => 'Efectivo',
            'subtotal' => 100,
            'total' => 100,
            'created_by' => $user->id,
        ]);
        $order->orderExams()->create(['exam_id' => $exam->id, 'tipo_contraste' => 'Sin contraste', 'precio' => 100, 'estado' => 'Pendiente']);
        $order->admissionForm()->create(['data' => ['requested_by' => 'Dra. Anterior', 'cause' => 'Dato clínico']]);

        $this->actingAs($user)->put(route('orders.update', $order), [
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'medico_solicitante_id' => $currentDoctor->id,
            'fecha_orden' => now()->format('Y-m-d H:i'),
            'estado' => 'Pendiente',
            'payments' => [['payment_method' => 'Efectivo', 'amount' => 100]],
            'exams' => [['exam_id' => $exam->id, 'tipo_contraste' => 'Sin contraste', 'precio' => 100, 'estado' => 'Pendiente']],
        ])->assertRedirect(route('orders.show', $order));

        $admissionData = $order->fresh()->admissionForm->data;
        $this->assertSame('Dr. Actualizado', $admissionData['requested_by']);
        $this->assertSame('Dato clínico', $admissionData['cause']);
        $this->actingAs($user)->get(route('orders.ficha-ingreso.template', $order))
            ->assertOk()
            ->assertSee('Dr. Actualizado');
    }

    public function test_all_orders_show_the_sworn_declaration_action(): void
    {
        $user = User::create(['username' => 'contrast-user', 'email' => 'contrast-user@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '44332211', 'nombres' => 'Elena', 'apellidos' => 'Mamani', 'edad' => 35]);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $exam = Exam::create(['nombre_examen' => 'Abdomen', 'tipo_contraste' => 'Sin contraste', 'activo' => true]);
        $order = Order::create(['patient_id' => $patient->id, 'agreement_id' => $agreement->id, 'fecha_orden' => now(), 'estado' => 'Pendiente']);
        $order->orderExams()->create(['exam_id' => $exam->id, 'tipo_contraste' => 'Sin contraste', 'precio' => 100]);

        $this->actingAs($user)->get(route('orders.index'))
            ->assertOk()
            ->assertSee(route('orders.declaracion-jurada', $order), false);
    }

    public function test_admission_form_and_sworn_declaration_edits_are_persisted(): void
    {
        $user = User::create(['username' => 'document-editor', 'email' => 'document-editor@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '55667788', 'nombres' => 'Julia', 'apellidos' => 'Quispe']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $exam = Exam::create(['nombre_examen' => 'Pelvis', 'tipo_contraste' => 'Ambos', 'activo' => true]);
        $order = Order::create(['patient_id' => $patient->id, 'agreement_id' => $agreement->id, 'fecha_orden' => now(), 'estado' => 'Pendiente']);
        $order->orderExams()->create(['exam_id' => $exam->id, 'tipo_contraste' => 'Con contraste', 'precio' => 120]);

        $this->actingAs($user)->put(route('orders.ficha-ingreso.update', $order), [
            'cause' => 'Dolor abdominal persistente',
            'symptomatology' => 'Náuseas',
            'allergy' => 'Ninguna conocida',
            'fasting' => 'SI',
            'creatinine' => '0.9',
        ])->assertRedirect(route('orders.ficha-ingreso.template', $order));

        $this->actingAs($user)->put(route('orders.declaracion-jurada.update', $order), [
            'legal_representative_dni' => '99887766',
            'hour' => '10:30',
            'revocation' => 'El paciente mantiene su autorización.',
        ])->assertRedirect(route('orders.declaracion-jurada.template', $order));

        $admissionData = $order->fresh()->admissionForm->data;
        $this->assertSame('Dolor abdominal persistente', $admissionData['cause']);
        $this->assertSame('Ninguna conocida', $admissionData['allergy']);
        $this->assertSame('0.9', $admissionData['creatinine']);
        $declarationData = $order->fresh()->swornDeclaration->data;
        $this->assertSame('99887766', $declarationData['legal_representative_dni']);
        $this->assertSame('El paciente mantiene su autorización.', $declarationData['revocation']);
    }

    public function test_admission_fields_are_not_lost_when_consumables_are_synchronized(): void
    {
        $user = User::create(['username' => 'admission-consumables', 'email' => 'admission-consumables@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '55443322', 'nombres' => 'Rosa', 'apellidos' => 'Flores']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $exam = Exam::create(['nombre_examen' => 'Tórax', 'tipo_contraste' => 'Ambos', 'activo' => true]);
        $plate = Reagent::create(['nombre' => 'Placa radiográfica', 'unidad' => 'unidad', 'stock_actual' => 10, 'stock_minimo' => 0]);
        $order = Order::create(['patient_id' => $patient->id, 'agreement_id' => $agreement->id, 'fecha_orden' => now(), 'estado' => 'Pendiente']);
        $order->orderExams()->create(['exam_id' => $exam->id, 'tipo_contraste' => 'Con contraste', 'precio' => 120]);
        $order->admissionForm()->create(['data' => ['cause' => '', 'medication' => '', 'antecedents' => '']]);

        $this->actingAs($user)->put(route('orders.ficha-ingreso.update', $order), [
            'cause' => 'Control por dolor torácico',
            'medication' => 'Metformina',
            'antecedents' => 'Hipertensión arterial',
            'allergy' => 'Alergia al yodo',
            'fasting' => 'SI',
            'creatinine' => '1.1',
            'consumables' => [
                ['reagent_id' => $plate->id, 'cantidad' => 2],
            ],
        ])->assertRedirect(route('orders.ficha-ingreso.template', $order));

        $admissionData = $order->fresh()->admissionForm->data;
        $this->assertSame('Control por dolor torácico', $admissionData['cause']);
        $this->assertSame('Metformina', $admissionData['medication']);
        $this->assertSame('Hipertensión arterial', $admissionData['antecedents']);
        $this->assertSame('Alergia al yodo', $admissionData['allergy']);
        $this->assertSame('SI', $admissionData['fasting']);
        $this->assertSame('1.1', $admissionData['creatinine']);
        $this->assertSame(2, $admissionData['plates_count']);
        $this->assertSame(2, $admissionData['delivery_quantities']['PLACAS']);
    }
}
