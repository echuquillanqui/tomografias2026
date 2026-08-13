<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Exam;
use App\Models\Order;
use App\Models\Patient;
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
}
