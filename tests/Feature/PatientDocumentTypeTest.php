<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientDocumentTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_it_registers_a_patient_with_each_supported_document_type(): void
    {
        $user = User::create(['username' => 'admission', 'email' => 'admission@example.com', 'password' => 'password']);

        foreach ([
            ['tipo_documento' => 'DNI', 'dni' => '12345678'],
            ['tipo_documento' => 'PASAPORTE', 'dni' => 'PE-A12345'],
            ['tipo_documento' => 'CARNET DE EXTRANJERIA', 'dni' => 'CE987654'],
        ] as $index => $document) {
            $this->actingAs($user)->postJson(route('patients.store'), $document + [
                'nombres' => 'Paciente '.$index,
                'apellidos' => 'Prueba',
                'sexo' => 'MASCULINO',
            ])->assertCreated()->assertJsonPath('patient.tipo_documento', $document['tipo_documento']);
        }

        $this->assertSame(3, Patient::count());
    }

    public function test_dni_requires_exactly_eight_digits(): void
    {
        $user = User::create(['username' => 'validator', 'email' => 'validator@example.com', 'password' => 'password']);

        $this->actingAs($user)->postJson(route('patients.store'), [
            'tipo_documento' => 'DNI',
            'dni' => 'ABC123',
            'nombres' => 'Paciente',
            'apellidos' => 'Inválido',
            'sexo' => 'FEMENINO',
        ])->assertUnprocessable()->assertJsonValidationErrors('dni');
    }
}
