<?php

namespace Tests\Feature;

use App\Models\CashExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CashExpenseFileTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_user_can_add_a_supporting_file_to_an_existing_expense(): void
    {
        Storage::fake('public');
        $user = User::create(['username' => 'caja', 'email' => 'caja@example.com', 'password' => 'password']);
        $expense = CashExpense::create([
            'fecha_egreso' => '2026-10-01',
            'descripcion' => 'Compra de útiles',
            'monto' => 100,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->patch(route('cash-closings.expenses.file.update', $expense), [
            'archivo' => UploadedFile::fake()->create('sustento.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('cash-closings.index'));
        $expense->refresh();
        $this->assertNotNull($expense->archivo_path);
        Storage::disk('public')->assertExists($expense->archivo_path);
    }

    public function test_replacing_a_supporting_file_removes_the_previous_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('egresos-caja/anterior.pdf', 'previous');
        $user = User::create(['username' => 'caja', 'email' => 'caja@example.com', 'password' => 'password']);
        $expense = CashExpense::create([
            'fecha_egreso' => '2026-10-01',
            'descripcion' => 'Compra de útiles',
            'monto' => 100,
            'archivo_path' => 'egresos-caja/anterior.pdf',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->patch(route('cash-closings.expenses.file.update', $expense), [
            'archivo' => UploadedFile::fake()->image('nuevo.jpg'),
        ])->assertSessionHasNoErrors();

        $expense->refresh();
        Storage::disk('public')->assertMissing('egresos-caja/anterior.pdf');
        Storage::disk('public')->assertExists($expense->archivo_path);
    }
}
