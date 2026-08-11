<?php

namespace Tests\Unit;

use App\Models\Agreement;
use App\Models\Order;
use App\Models\Patient;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CashClosingExcelExportTest extends TestCase
{
    public function test_daily_sheet_exports_manual_order_and_receipt_details(): void
    {
        $order = new Order([
            'codigo_orden' => 'MANUAL-00125',
            'fecha_orden' => '2026-08-06 10:30:00',
            'tipo_pago' => 'Efectivo',
            'tipo_comprobante' => 'Boleta',
            'numero_comprobante' => 'B001-000045',
            'total' => 180,
        ]);
        $order->id = 999;
        $order->setRelation('patient', new Patient(['dni' => '12345678', 'nombres' => 'Ana', 'apellidos' => 'Pérez']));
        $order->setRelation('agreement', new Agreement(['nombre_institucion' => 'Particular']));
        $order->setRelation('orderExams', new Collection());
        $order->setRelation('admissionForm', null);
        $order->setRelation('consumables', collect());
        $order->setRelation('medicoSolicitante', null);
        $order->setRelation('medicoInforme', null);

        $orders = collect([$order]);
        $stockSummary = ['initial' => 10, 'delivered' => 0, 'final' => 10];
        $view = view('cash-closings.exports.excel', [
            'periods' => ['day' => 'Día'],
            'period' => 'day',
            'from' => '2026-08-06',
            'to' => '2026-08-06',
            'cashIncome' => 180,
            'expenseTotal' => 0,
            'cashBalance' => 180,
            'yapePlinIncome' => 0,
            'transferIncome' => 0,
            'incomeTotal' => 180,
            'plateSummary' => $stockSummary,
            'iopamidolSummary' => ['initial' => 5, 'delivered' => 0, 'final' => 5],
            'orders' => $orders,
            'expenses' => collect(),
            'cashOrders' => $orders,
            'yapePlinOrders' => collect(),
            'transferOrders' => collect(),
        ])->render();

        $dailySheet = str($view)->between('<Worksheet ss:Name="Cuadre diario">', '</Worksheet>')->toString();

        $this->assertStringContainsString('Orden de servicio', $dailySheet);
        $this->assertStringContainsString('Tipo de comprobante', $dailySheet);
        $this->assertStringContainsString('N° comprobante', $dailySheet);
        $this->assertStringContainsString('Placas usadas', $dailySheet);
        $this->assertStringContainsString('Iopamidol usado', $dailySheet);
        $this->assertStringContainsString('MANUAL-00125', $dailySheet);
        $this->assertStringContainsString('Boleta', $dailySheet);
        $this->assertStringContainsString('B001-000045', $dailySheet);
        $this->assertStringNotContainsString('#999', $dailySheet);
    }
}
