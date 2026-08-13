<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Exam;
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
}
