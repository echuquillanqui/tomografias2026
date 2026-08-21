<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Order;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_dashboard_filters_orders_by_date_and_selected_product(): void
    {
        $user = User::create(['username' => 'dashboard', 'email' => 'dashboard@example.com', 'password' => 'password']);
        $patient = Patient::create(['dni' => '12345678', 'nombres' => 'Ana', 'apellidos' => 'Torres']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);

        $included = Order::create(['codigo_orden' => 'ORD-IN', 'patient_id' => $patient->id, 'agreement_id' => $agreement->id, 'fecha_orden' => '2026-08-15 10:00:00']);
        $included->admissionForm()->create(['data' => ['delivery_quantities' => ['CD' => 3]]]);
        $excluded = Order::create(['codigo_orden' => 'ORD-OUT', 'patient_id' => $patient->id, 'agreement_id' => $agreement->id, 'fecha_orden' => '2026-07-15 10:00:00']);
        $excluded->admissionForm()->create(['data' => ['delivery_quantities' => ['CD' => 9]]]);

        $this->actingAs($user)->get(route('home', [
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'product' => 'cds',
        ]))->assertOk()
            ->assertSee('ORD-IN')
            ->assertDontSee('ORD-OUT')
            ->assertSee('CD entregados')
            ->assertDontSee('metric-blue', false)
            ->assertSee('value="cds" selected', false);
    }

    public function test_dashboard_falls_back_to_safe_filters_for_invalid_values(): void
    {
        $this->travelTo('2026-08-21');
        $user = User::create(['username' => 'dashboard', 'email' => 'dashboard@example.com', 'password' => 'password']);

        $this->actingAs($user)->get(route('home', [
            'start_date' => 'not-a-date',
            'end_date' => '2026-99-99',
            'product' => 'unknown',
        ]))->assertOk()
            ->assertSee('value="2026-08-01"', false)
            ->assertSee('value="2026-08-21"', false)
            ->assertSee('value="all" selected', false);
    }
}
