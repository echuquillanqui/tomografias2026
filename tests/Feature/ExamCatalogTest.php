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

    public function test_all_consumable_blocks_are_saved_from_the_form_snapshot(): void
    {
        $user = User::create(['username' => 'tester', 'email' => 'tester@example.com', 'password' => 'password']);
        $shared = Reagent::create(['nombre' => 'Guantes', 'unidad' => 'par', 'stock_actual' => 10, 'stock_minimo' => 1, 'activo' => true]);
        $without = Reagent::create(['nombre' => 'Placa', 'unidad' => 'unidad', 'stock_actual' => 10, 'stock_minimo' => 1, 'activo' => true]);
        $with = Reagent::create(['nombre' => 'Jeringa', 'unidad' => 'unidad', 'stock_actual' => 10, 'stock_minimo' => 1, 'activo' => true]);

        $payload = json_encode([
            ['reagent_id' => (string) $shared->id, 'nombre' => '', 'cantidad_estimada' => '1', 'tipo_contraste' => 'Ambos'],
            ['reagent_id' => (string) $without->id, 'nombre' => '', 'cantidad_estimada' => '2', 'tipo_contraste' => 'Sin contraste'],
            ['reagent_id' => (string) $with->id, 'nombre' => '', 'cantidad_estimada' => '3', 'tipo_contraste' => 'Con contraste'],
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($user)->post(route('exams.store'), [
            'nombre_examen' => 'TEM Tórax',
            'tipo_contraste' => 'Ambos',
            'activo' => '1',
            'reagents' => [], // Dynamic controls may be omitted by the browser.
            'reagents_payload' => $payload,
        ])->assertRedirect(route('exams.index'));

        $exam = Exam::where('nombre_examen', 'TEM Tórax')->firstOrFail();
        $this->assertDatabaseHas('exam_reagent', ['exam_id' => $exam->id, 'reagent_id' => $shared->id, 'tipo_contraste' => 'Ambos', 'cantidad_estimada' => 1]);
        $this->assertDatabaseHas('exam_reagent', ['exam_id' => $exam->id, 'reagent_id' => $without->id, 'tipo_contraste' => 'Sin contraste', 'cantidad_estimada' => 2]);
        $this->assertDatabaseHas('exam_reagent', ['exam_id' => $exam->id, 'reagent_id' => $with->id, 'tipo_contraste' => 'Con contraste', 'cantidad_estimada' => 3]);
        $this->assertDatabaseCount('exam_reagent', 3);
    }

    public function test_updating_an_exam_keeps_consumables_in_their_selected_contrast_blocks(): void
    {
        $user = User::create(['username' => 'tester', 'email' => 'tester@example.com', 'password' => 'password']);
        $without = Reagent::create(['nombre' => 'Placa', 'unidad' => 'unidad', 'stock_actual' => 10, 'stock_minimo' => 1, 'activo' => true]);
        $with = Reagent::create(['nombre' => 'Contraste', 'unidad' => 'frasco', 'stock_actual' => 10, 'stock_minimo' => 1, 'activo' => true]);
        $exam = Exam::create(['nombre_examen' => 'TEM Abdomen', 'tipo_contraste' => 'Ambos', 'activo' => true]);

        $payload = json_encode([
            'Sin contraste' => [
                ['reagent_id' => (string) $without->id, 'nombre' => '', 'cantidad_estimada' => '1'],
            ],
            'Con contraste' => [
                ['reagent_id' => (string) $with->id, 'nombre' => '', 'cantidad_estimada' => '2'],
            ],
            'Ambos' => [],
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($user)->put(route('exams.update', $exam), [
            'nombre_examen' => $exam->nombre_examen,
            'tipo_contraste' => 'Ambos',
            'activo' => '1',
            'reagents_payload' => $payload,
        ])->assertRedirect(route('exams.index'));

        $this->assertDatabaseHas('exam_reagent', [
            'exam_id' => $exam->id,
            'reagent_id' => $without->id,
            'tipo_contraste' => 'Sin contraste',
            'cantidad_estimada' => 1,
        ]);
        $this->assertDatabaseHas('exam_reagent', [
            'exam_id' => $exam->id,
            'reagent_id' => $with->id,
            'tipo_contraste' => 'Con contraste',
            'cantidad_estimada' => 2,
        ]);
    }

    public function test_updating_an_exam_changes_its_contrast_mode_from_both(): void
    {
        $user = User::create(['username' => 'tester', 'email' => 'tester@example.com', 'password' => 'password']);
        $reagent = Reagent::create(['nombre' => 'Placa', 'unidad' => 'unidad', 'stock_actual' => 10, 'stock_minimo' => 1, 'activo' => true]);
        $exam = Exam::create(['nombre_examen' => 'TEM Cerebral', 'tipo_contraste' => 'Ambos', 'activo' => true]);

        $payload = json_encode([
            'Sin contraste' => [
                ['reagent_id' => (string) $reagent->id, 'nombre' => '', 'cantidad_estimada' => '1'],
            ],
            'Con contraste' => [],
            'Ambos' => [],
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($user)->put(route('exams.update', $exam), [
            'nombre_examen' => $exam->nombre_examen,
            'tipo_contraste' => 'Sin contraste',
            'activo' => '1',
            'reagents_payload' => $payload,
        ])->assertRedirect(route('exams.index'));

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'tipo_contraste' => 'Sin contraste',
        ]);
        $this->assertDatabaseHas('exam_reagent', [
            'exam_id' => $exam->id,
            'reagent_id' => $reagent->id,
            'tipo_contraste' => 'Sin contraste',
        ]);
    }

    public function test_saving_without_contrast_does_not_replace_its_quantity_with_the_contrast_configuration(): void
    {
        $user = User::create(['username' => 'tester', 'email' => 'tester@example.com', 'password' => 'password']);
        $reagent = Reagent::create(['nombre' => 'Placa', 'unidad' => 'unidad', 'stock_actual' => 10, 'stock_minimo' => 1, 'activo' => true]);
        $exam = Exam::create(['nombre_examen' => 'TEM Cerebral', 'tipo_contraste' => 'Ambos', 'activo' => true]);

        $payload = json_encode([
            'Sin contraste' => [
                ['reagent_id' => (string) $reagent->id, 'nombre' => '', 'cantidad_estimada' => '1'],
            ],
            'Con contraste' => [
                ['reagent_id' => (string) $reagent->id, 'nombre' => '', 'cantidad_estimada' => '5'],
            ],
            'Ambos' => [],
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($user)->put(route('exams.update', $exam), [
            'nombre_examen' => $exam->nombre_examen,
            'tipo_contraste' => 'Sin contraste',
            'activo' => '1',
            'reagents_payload' => $payload,
        ])->assertRedirect(route('exams.index'));

        $this->assertDatabaseHas('exam_reagent', [
            'exam_id' => $exam->id,
            'reagent_id' => $reagent->id,
            'tipo_contraste' => 'Sin contraste',
            'cantidad_estimada' => 1,
        ]);
        $this->assertDatabaseMissing('exam_reagent', [
            'exam_id' => $exam->id,
            'cantidad_estimada' => 5,
        ]);
    }
}
