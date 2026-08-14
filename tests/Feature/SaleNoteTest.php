<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Exam;
use App\Models\Order;
use App\Models\Patient;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleNoteTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_new_attentions_receive_consecutive_sale_note_numbers(): void
    {
        [$user, $patient, $agreement, $exam] = $this->dependencies();
        SystemSetting::current()->update([
            'sale_note_series' => '004',
            'next_receipt_number' => 210,
        ]);

        foreach ([210, 211] as $expectedNumber) {
            $this->actingAs($user)->post(route('orders.store'), $this->orderPayload($patient, $agreement, $exam))
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('orders', [
                'sale_note_series' => '004',
                'receipt_number' => $expectedNumber,
            ]);
        }

        $this->assertSame(212, SystemSetting::current()->next_receipt_number);
    }

    public function test_changing_the_series_does_not_change_numbers_already_assigned(): void
    {
        [$user, $patient, $agreement, $exam] = $this->dependencies();
        $setting = SystemSetting::current();
        $setting->update(['sale_note_series' => '004', 'next_receipt_number' => 210]);

        $this->actingAs($user)->post(route('orders.store'), $this->orderPayload($patient, $agreement, $exam));
        $firstOrder = Order::firstOrFail();

        $this->actingAs($user)->put(route('system-settings.update'), [
            'razon_social' => $setting->razon_social,
            'sale_note_series' => '005',
            'next_receipt_number' => 211,
        ])->assertSessionHasNoErrors();

        $this->assertSame('004-210', $firstOrder->fresh()->sale_note_number);
    }

    public function test_company_description_is_saved_and_rendered_in_sale_note(): void
    {
        [$user, $patient, $agreement, $exam] = $this->dependencies();
        $setting = SystemSetting::current();

        $this->actingAs($user)->put(route('system-settings.update'), [
            'razon_social' => 'Diagnóstico Médico SAC',
            'descripcion_empresa' => 'Centro de diagnóstico médico',
            'sale_note_series' => '004',
            'next_receipt_number' => 210,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('system_settings', [
            'id' => $setting->id,
            'descripcion_empresa' => 'Centro de diagnóstico médico',
        ]);

        $this->actingAs($user)->post(route('orders.store'), $this->orderPayload($patient, $agreement, $exam));

        $order = Order::firstOrFail()->load(['patient', 'orderExams.exam', 'payments']);
        $html = view('orders.pdfs.sale-note', ['order' => $order, 'setting' => $setting->fresh()])->render();

        $this->assertStringContainsString('CENTRO DE DIAGNÓSTICO MÉDICO', $html);
    }

    public function test_fiscal_notice_is_emphasized_in_sale_note(): void
    {
        [, $patient, $agreement, $exam] = $this->dependencies();
        $order = Order::create([
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'fecha_orden' => now(),
            'estado' => 'Pendiente',
        ]);
        $order->orderExams()->create([
            'exam_id' => $exam->id,
            'tipo_contraste' => 'Sin contraste',
            'precio' => 330,
            'estado' => 'Pendiente',
        ]);

        $html = view('orders.pdfs.sale-note', [
            'order' => $order->load(['patient', 'orderExams.exam', 'payments']),
            'setting' => SystemSetting::current(),
        ])->render();

        $this->assertStringContainsString('class="fiscal-notice"', $html);
        $this->assertStringContainsString('background: #ffe59b', $html);
        $this->assertStringContainsString('font-size: 10px', $html);
    }

    private function dependencies(): array
    {
        $user = User::factory()->create();
        $patient = Patient::create(['dni' => '12345678', 'nombres' => 'Ana', 'apellidos' => 'Torres', 'telefono' => '987654321']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $exam = Exam::create(['nombre_examen' => 'TEM Cerebral', 'tipo_contraste' => 'Sin contraste', 'activo' => true]);

        return [$user, $patient, $agreement, $exam];
    }

    private function orderPayload(Patient $patient, Agreement $agreement, Exam $exam): array
    {
        return [
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'fecha_orden' => '14/08/2026 10:00',
            'estado' => 'Pendiente',
            'tipo_pago' => 'Efectivo',
            'descuento' => 0,
            'exams' => [[
                'exam_id' => $exam->id,
                'tipo_contraste' => 'Sin contraste',
                'precio' => 330,
                'estado' => 'Pendiente',
            ]],
        ];
    }
}
