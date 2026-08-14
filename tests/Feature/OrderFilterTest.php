<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Exam;
use App\Models\Order;
use App\Models\OrderExam;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFilterTest extends TestCase
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

    public function test_index_defaults_to_today_and_renders_interactive_filters(): void
    {
        Carbon::setTestNow('2026-08-07 10:00:00');
        [$user, $agreement] = $this->baseRecords();
        $todayPatient = Patient::create(['dni' => '12345678', 'nombres' => 'Ana', 'apellidos' => 'Torres']);
        $oldPatient = Patient::create(['dni' => '87654321', 'nombres' => 'Luis', 'apellidos' => 'Ramos']);

        $this->order($todayPatient, $agreement, 'ORD-HOY', '2026-08-07 09:00:00');
        $this->order($oldPatient, $agreement, 'ORD-AYER', '2026-08-06 09:00:00');

        $this->actingAs($user)->get(route('orders.index'))
            ->assertOk()
            ->assertSee('value="2026-08-07"', false)
            ->assertSee('ORD-HOY')
            ->assertDontSee('ORD-AYER')
            ->assertSee('x-on:input="submitFilters()"', false)
            ->assertSee('x-on:change="loading = true; $root.requestSubmit()"', false);
    }

    public function test_index_filters_by_date_full_name_and_dni(): void
    {
        [$user, $agreement] = $this->baseRecords();
        $matched = Patient::create(['dni' => '44556677', 'nombres' => 'María Elena', 'apellidos' => 'Pérez Salazar']);
        $other = Patient::create(['dni' => '11223344', 'nombres' => 'Carlos', 'apellidos' => 'Quispe']);

        $this->order($matched, $agreement, 'ORD-001', '2026-08-05 08:00:00');
        $this->order($other, $agreement, 'ORD-002', '2026-08-05 09:00:00');

        $this->actingAs($user)->get(route('orders.index', ['date' => '2026-08-05', 'search' => 'María Pérez']))
            ->assertOk()
            ->assertSee('ORD-001')
            ->assertDontSee('ORD-002');

        $this->actingAs($user)->get(route('orders.index', ['date' => '2026-08-05', 'search' => '44556677']))
            ->assertOk()
            ->assertSee('ORD-001')
            ->assertDontSee('ORD-002');
    }

    public function test_global_search_finds_patient_without_restricting_the_date(): void
    {
        Carbon::setTestNow('2026-08-07 10:00:00');
        [$user, $agreement] = $this->baseRecords();
        $matched = Patient::create(['dni' => '44556677', 'nombres' => 'María Elena', 'apellidos' => 'Pérez Salazar']);
        $other = Patient::create(['dni' => '11223344', 'nombres' => 'Carlos', 'apellidos' => 'Quispe']);

        $this->order($matched, $agreement, 'ORD-ANTIGUA', '2025-01-10 08:00:00');
        $this->order($other, $agreement, 'ORD-HOY', '2026-08-07 09:00:00');

        $this->actingAs($user)->get(route('orders.index', ['search' => 'María Pérez', 'all_dates' => 1]))
            ->assertOk()
            ->assertSee('ORD-ANTIGUA')
            ->assertDontSee('ORD-HOY')
            ->assertSee('Buscar en todas las fechas')
            ->assertSee('name="all_dates"', false)
            ->assertSee('checked', false);
    }

    public function test_index_shows_study_contrast_and_order_file_state(): void
    {
        [$user, $agreement] = $this->baseRecords();
        $patient = Patient::create(['dni' => '44556677', 'nombres' => 'Ana', 'apellidos' => 'Torres']);
        $order = $this->order($patient, $agreement, 'ORD-ESTUDIO', now()->format('Y-m-d H:i:s'));
        $exam = Exam::create(['nombre_examen' => 'TEM CEREBRAL', 'tipo_contraste' => 'Ambos', 'activo' => true]);
        OrderExam::create([
            'order_id' => $order->id,
            'exam_id' => $exam->id,
            'tipo_contraste' => 'Con contraste',
            'precio' => 100,
            'estado' => 'Pendiente',
        ]);

        $this->actingAs($user)->get(route('orders.index'))
            ->assertOk()
            ->assertSee('TEM CEREBRAL')
            ->assertSee('(Con contraste)')
            ->assertSee('Sin Orden');

        $order->update(['archivo_orden_path' => 'ordenes/orden.pdf']);

        $this->actingAs($user)->get(route('orders.index'))
            ->assertOk()
            ->assertSee('order-file-uploaded')
            ->assertSee('Con Orden');
    }

    public function test_order_statuses_match_tomographic_report_statuses(): void
    {
        [$user, $agreement] = $this->baseRecords();
        $patient = Patient::create(['dni' => '44556677', 'nombres' => 'Ana', 'apellidos' => 'Torres']);
        $order = $this->order($patient, $agreement, 'ORD-ESTADO', now()->format('Y-m-d H:i:s'));

        $this->actingAs($user)->get(route('orders.index'))
            ->assertOk()
            ->assertSee('order-status order-status-pending', false)
            ->assertSee('value="Pendiente"', false)
            ->assertSee('value="En proceso"', false)
            ->assertSee('value="Informado"', false)
            ->assertDontSee('value="Entregado"', false)
            ->assertDontSee('value="Anulado"', false);

        $order->update(['estado' => 'En proceso']);
        $this->actingAs($user)->get(route('orders.index'))
            ->assertOk()
            ->assertSee('order-status order-status-progress', false);

        $order->update(['estado' => 'Informado']);
        $this->actingAs($user)->get(route('orders.index'))
            ->assertOk()
            ->assertSee('order-status order-status-complete', false);

        $this->actingAs($user)->patch(route('orders.update-status', $order), ['estado' => 'Anulado'])
            ->assertSessionHasErrors('estado');
    }

    private function baseRecords(): array
    {
        $user = User::create([
            'username' => 'tester',
            'email' => 'tester@example.com',
            'password' => 'password',
        ]);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);

        return [$user, $agreement];
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
