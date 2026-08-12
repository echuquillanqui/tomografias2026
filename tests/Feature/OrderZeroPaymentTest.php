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

class OrderZeroPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_a_zero_total_agreement_order_accepts_a_zero_convenio_payment(): void
    {
        [$user, $patient, $agreement, $exam] = $this->orderDependencies();

        $response = $this->actingAs($user)->post(route('orders.store'), [
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'fecha_orden' => '12/08/2026 10:00',
            'estado' => 'Pendiente',
            'payments' => [[
                'payment_method' => 'Convenio',
                'amount' => 0,
            ]],
            'descuento' => 0,
            'exams' => [[
                'exam_id' => $exam->id,
                'tipo_contraste' => 'Sin contraste',
                'precio' => 0,
                'estado' => 'Pendiente',
            ]],
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('orders', ['tipo_pago' => 'Convenio', 'total' => 0]);
        $this->assertDatabaseHas('order_payments', ['payment_method' => 'Convenio', 'amount' => 0]);
    }

    public function test_an_existing_zero_total_order_accepts_a_zero_convenio_payment(): void
    {
        [$user, $patient, $agreement] = $this->orderDependencies();
        $order = Order::create([
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'fecha_orden' => '2026-08-12 10:00:00',
            'estado' => 'Pendiente',
            'total' => 0,
        ]);

        $response = $this->actingAs($user)->patch(route('orders.update-payment', $order), [
            'payments' => [[
                'payment_method' => 'Convenio',
                'amount' => 0,
            ]],
        ]);

        $response->assertRedirect(route('orders.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('order_payments', [
            'order_id' => $order->id,
            'payment_method' => 'Convenio',
            'amount' => 0,
        ]);
    }

    private function orderDependencies(): array
    {
        $user = User::create([
            'username' => 'agreement-user',
            'email' => 'agreement@example.com',
            'password' => 'password',
        ]);
        $patient = Patient::create([
            'dni' => '12345678',
            'nombres' => 'Ana',
            'apellidos' => 'Torres',
        ]);
        $agreement = Agreement::create([
            'nombre_institucion' => 'Convenio gratuito',
            'activo' => true,
        ]);
        $exam = Exam::create([
            'nombre_examen' => 'Tórax',
            'tipo_contraste' => 'Sin contraste',
            'activo' => true,
        ]);
        AgreementPrice::create([
            'agreement_id' => $agreement->id,
            'exam_id' => $exam->id,
            'tipo_contraste' => 'Sin contraste',
            'precio_pactado' => 0,
        ]);

        return [$user, $patient, $agreement, $exam];
    }
}
