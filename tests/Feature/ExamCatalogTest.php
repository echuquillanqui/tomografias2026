<?php

namespace Tests\Feature;

use App\Models\Exam;
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

    public function test_an_exam_name_cannot_be_registered_twice_for_different_contrasts(): void
    {
        $user = User::create(['username' => 'tester', 'email' => 'tester@example.com', 'password' => 'password']);
        Exam::create(['nombre_examen' => 'TEM Cerebral', 'tipo_contraste' => 'Sin contraste', 'activo' => true]);

        $response = $this->actingAs($user)->post(route('exams.store'), [
            'nombre_examen' => 'TEM Cerebral',
            'tipo_contraste' => 'Con contraste',
            'activo' => '1',
        ]);

        $response->assertSessionHasErrors('nombre_examen');
        $this->assertDatabaseCount('exams', 1);
    }

    public function test_an_exam_can_be_registered_once_for_both_contrasts(): void
    {
        $user = User::create(['username' => 'tester', 'email' => 'tester@example.com', 'password' => 'password']);

        $response = $this->actingAs($user)->post(route('exams.store'), [
            'nombre_examen' => 'TEM Abdomen',
            'tipo_contraste' => 'Ambos',
            'activo' => '1',
        ]);

        $response->assertRedirect(route('exams.index'));
        $this->assertDatabaseHas('exams', [
            'nombre_examen' => 'TEM Abdomen',
            'tipo_contraste' => 'Ambos',
        ]);
    }

    public function test_consumables_can_be_configured_separately_for_each_contrast(): void
    {
        $user = User::create(['username' => 'tester', 'email' => 'tester@example.com', 'password' => 'password']);
        $reagent = Reagent::create(['nombre' => 'Jeringa', 'unidad' => 'unidad', 'stock_actual' => 10, 'stock_minimo' => 1, 'activo' => true]);

        $this->actingAs($user)->post(route('exams.store'), [
            'nombre_examen' => 'TEM Abdomen',
            'tipo_contraste' => 'Ambos',
            'activo' => '1',
            'reagents' => [
                ['reagent_id' => $reagent->id, 'cantidad_estimada' => 1, 'tipo_contraste' => 'Sin contraste'],
                ['reagent_id' => $reagent->id, 'cantidad_estimada' => 2, 'tipo_contraste' => 'Con contraste'],
            ],
        ])->assertRedirect(route('exams.index'));

        $exam = Exam::where('nombre_examen', 'TEM Abdomen')->firstOrFail();
        $this->assertDatabaseHas('exam_reagent', ['exam_id' => $exam->id, 'reagent_id' => $reagent->id, 'tipo_contraste' => 'Sin contraste', 'cantidad_estimada' => 1]);
        $this->assertDatabaseHas('exam_reagent', ['exam_id' => $exam->id, 'reagent_id' => $reagent->id, 'tipo_contraste' => 'Con contraste', 'cantidad_estimada' => 2]);
    }
}
