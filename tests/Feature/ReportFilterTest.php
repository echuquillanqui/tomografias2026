<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Order;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_reports_default_to_today_and_can_search_all_dates(): void
    {
        Carbon::setTestNow('2026-08-12 10:00:00');
        $user = User::create(['username' => 'tester', 'email' => 'tester@example.com', 'password' => 'password']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $todayPatient = Patient::create(['dni' => '12345678', 'nombres' => 'Ana', 'apellidos' => 'Torres']);
        $oldPatient = Patient::create(['dni' => '87654321', 'nombres' => 'Lucía', 'apellidos' => 'Mendoza']);
        $this->order($todayPatient, $agreement, 'ORD-HOY', '2026-08-12 09:00:00');
        $this->order($oldPatient, $agreement, 'ORD-HISTORICA', '2025-02-03 09:00:00');

        $this->actingAs($user)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('value="2026-08-12"', false)
            ->assertSee('ORD-HOY')
            ->assertDontSee('ORD-HISTORICA')
            ->assertSee('Buscar en todas las fechas');

        $this->actingAs($user)->get(route('reports.index', [
            'date' => '2026-08-12',
            'search' => 'Lucía',
            'all_dates' => 1,
        ]))
            ->assertOk()
            ->assertSee('ORD-HISTORICA')
            ->assertDontSee('ORD-HOY')
            ->assertSee('checked', false);
    }

    public function test_reports_filter_by_selected_date(): void
    {
        $user = User::create(['username' => 'tester', 'email' => 'tester@example.com', 'password' => 'password']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $patient = Patient::create(['dni' => '12345678', 'nombres' => 'Ana', 'apellidos' => 'Torres']);
        $this->order($patient, $agreement, 'ORD-SELECCIONADA', '2026-08-10 09:00:00');
        $this->order($patient, $agreement, 'ORD-OTRA', '2026-08-11 09:00:00');

        $this->actingAs($user)->get(route('reports.index', ['date' => '2026-08-10']))
            ->assertOk()
            ->assertSee('ORD-SELECCIONADA')
            ->assertDontSee('ORD-OTRA');
    }

    private function order(Patient $patient, Agreement $agreement, string $code, string $date): Order
    {
        return Order::create([
            'codigo_orden' => $code,
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'fecha_orden' => $date,
            'estado' => 'Pendiente',
        ]);
    }
}
