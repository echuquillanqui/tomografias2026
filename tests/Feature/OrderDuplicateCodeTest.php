<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\AgreementPrice;
use App\Models\Exam;
use App\Models\Order;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDuplicateCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_two_orders_can_use_the_same_administrative_code(): void
    {
        $user = User::create([
            'username' => 'order-user',
            'email' => 'orders@example.com',
            'password' => 'password',
        ]);
        $patient = Patient::create([
            'dni' => '12345678',
            'nombres' => 'Ana',
            'apellidos' => 'Torres',
        ]);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $exam = Exam::create([
            'nombre_examen' => 'Tórax',
            'tipo_contraste' => 'Sin contraste',
            'activo' => true,
        ]);
        AgreementPrice::create([
            'agreement_id' => $agreement->id,
            'exam_id' => $exam->id,
            'tipo_contraste' => 'Sin contraste',
            'precio_pactado' => 100,
        ]);
        Order::create([
            'codigo_orden' => 'SOLICITUD-001',
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'fecha_orden' => '2026-08-12 09:00:00',
            'estado' => 'Pendiente',
        ]);

        $response = $this->actingAs($user)->post(route('orders.store'), [
            'codigo_orden' => 'SOLICITUD-001',
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'fecha_orden' => '12/08/2026 10:00',
            'estado' => 'Pendiente',
            'tipo_pago' => 'Efectivo',
            'descuento' => 0,
            'exams' => [[
                'exam_id' => $exam->id,
                'tipo_contraste' => 'Sin contraste',
                'precio' => 100,
                'estado' => 'Pendiente',
            ]],
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(2, Order::where('codigo_orden', 'SOLICITUD-001')->count());
    }
}
