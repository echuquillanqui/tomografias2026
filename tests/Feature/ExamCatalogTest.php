<?php

namespace Tests\Feature;

use App\Models\Exam;
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
}
