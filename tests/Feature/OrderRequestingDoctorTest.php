<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Order;
use App\Models\Patient;
use App\Models\RequestingDoctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderRequestingDoctorTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_requesting_doctor_can_be_changed_without_editing_the_rest_of_the_order(): void
    {
        $user = User::create(['username' => 'orders', 'email' => 'orders@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '12345678', 'nombres' => 'Ana', 'apellidos' => 'Torres']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $previousDoctor = RequestingDoctor::create(['nombre' => 'Dra. Anterior', 'activo' => true]);
        $newDoctor = RequestingDoctor::create(['nombre' => 'Dr. Nuevo', 'activo' => true]);
        $order = Order::create([
            'codigo_orden' => 'ORD-100',
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'medico_solicitante_id' => $previousDoctor->id,
            'fecha_orden' => now(),
            'estado' => 'Pendiente',
            'total' => 150,
        ]);

        $this->actingAs($user)->patch(route('orders.update-requesting-doctor', $order), [
            'medico_solicitante_id' => $newDoctor->id,
        ])->assertRedirect(route('orders.index'));

        $order->refresh();
        $this->assertSame($newDoctor->id, $order->medico_solicitante_id);
        $this->assertSame('ORD-100', $order->codigo_orden);
        $this->assertSame('150.00', $order->total);
        $this->assertSame('Dr. Nuevo', $order->admissionForm->data['requested_by']);
    }

    public function test_index_highlights_whether_an_order_has_a_requesting_doctor(): void
    {
        $user = User::create(['username' => 'orders', 'email' => 'orders@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '12345678', 'nombres' => 'Ana', 'apellidos' => 'Torres']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $doctor = RequestingDoctor::create(['nombre' => 'Dra. Solicitante', 'activo' => true]);

        foreach ([
            ['code' => 'ORD-CON-MEDICO', 'doctor_id' => $doctor->id],
            ['code' => 'ORD-SIN-MEDICO', 'doctor_id' => null],
        ] as $data) {
            Order::create([
                'codigo_orden' => $data['code'],
                'patient_id' => $patient->id,
                'agreement_id' => $agreement->id,
                'medico_solicitante_id' => $data['doctor_id'],
                'fecha_orden' => now(),
                'estado' => 'Pendiente',
            ]);
        }

        $response = $this->actingAs($user)->get(route('orders.index'));

        $response->assertOk()
            ->assertSee('requesting-doctor-button requesting-doctor-assigned', false)
            ->assertSeeText('Médico solicitante')
            ->assertSee('requesting-doctor-button requesting-doctor-missing', false)
            ->assertSeeText('No médico solicitante');
    }
}
