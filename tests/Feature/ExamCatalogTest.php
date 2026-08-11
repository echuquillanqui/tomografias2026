<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\AgreementPrice;
use App\Models\Exam;
use App\Models\GlobalContrastConsumable;
use App\Models\Reagent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_exam_registration_only_requires_name_and_active_status(): void
    {
        $user = User::create(['username' => 'tester', 'email' => 'tester@example.com', 'password' => 'password']);

        $this->actingAs($user)->post(route('exams.store'), [
            'nombre_examen' => 'TEM Abdomen',
            'activo' => '1',
        ])->assertRedirect(route('exams.index'));

        $this->assertDatabaseHas('exams', [
            'nombre_examen' => 'TEM Abdomen',
            'tipo_contraste' => 'Ambos',
            'activo' => true,
        ]);
        $this->assertDatabaseCount('exam_reagent', 0);
    }

    public function test_exam_catalog_only_displays_name_and_status_columns(): void
    {
        $user = User::create(['username' => 'tester', 'email' => 'tester@example.com', 'password' => 'password']);
        Exam::create(['nombre_examen' => 'TEM Cerebral', 'tipo_contraste' => 'Ambos', 'activo' => true]);

        $this->actingAs($user)->get(route('exams.index'))
            ->assertOk()
            ->assertSee('TEM Cerebral')
            ->assertSee('Estado')
            ->assertDontSee('<th>Contraste</th>', false)
            ->assertDontSee('<th>Reactivos</th>', false);
    }

    public function test_an_exam_name_remains_unique(): void
    {
        $user = User::create(['username' => 'tester', 'email' => 'tester@example.com', 'password' => 'password']);
        Exam::create(['nombre_examen' => 'TEM Cerebral', 'tipo_contraste' => 'Ambos', 'activo' => true]);

        $this->actingAs($user)->post(route('exams.store'), [
            'nombre_examen' => 'TEM Cerebral',
            'activo' => '1',
        ])->assertSessionHasErrors('nombre_examen');

        $this->assertDatabaseCount('exams', 1);
    }

    public function test_order_form_uses_global_consumables_for_each_contrast(): void
    {
        $user = User::create(['username' => 'tester', 'email' => 'tester@example.com', 'password' => 'password']);
        $exam = Exam::create(['nombre_examen' => 'TEM Pelvis', 'tipo_contraste' => 'Ambos', 'activo' => true]);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        AgreementPrice::create(['agreement_id' => $agreement->id, 'exam_id' => $exam->id, 'tipo_contraste' => 'Sin contraste', 'precio_pactado' => 100]);
        AgreementPrice::create(['agreement_id' => $agreement->id, 'exam_id' => $exam->id, 'tipo_contraste' => 'Con contraste', 'precio_pactado' => 180]);
        $reagent = Reagent::create(['nombre' => 'Iopamidol', 'unidad' => 'frasco', 'stock_actual' => 10, 'stock_minimo' => 1, 'activo' => true]);
        GlobalContrastConsumable::create(['tipo_contraste' => 'Con contraste', 'reagent_id' => $reagent->id, 'cantidad_estimada' => 2]);

        $this->actingAs($user)->get(route('orders.create'))
            ->assertOk()
            ->assertSee('configuración global según el contraste elegido')
            ->assertSee('Iopamidol')
            ->assertSee('globalConsumables', false);
    }
}
