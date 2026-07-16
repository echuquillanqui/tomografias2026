<?php

namespace Database\Seeders;

use App\Models\Agreement;
use App\Models\AgreementPrice;
use App\Models\Exam;
use App\Models\Order;
use App\Models\OrderExam;
use App\Models\Patient;
use App\Models\RequestingDoctor;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrdersSeeder extends Seeder
{
    public function run(): void
    {
        $patient = Patient::where('dni', '12345678')->firstOrFail();
        $agreement = Agreement::where('nombre_institucion', 'Particular')->firstOrFail();
        $solicitante = RequestingDoctor::firstOrCreate(['nombre' => 'Dr. Médico Solicitante'], ['activo' => true]);
        $informante = User::where('email', 'informante@example.com')->firstOrFail();
        $creator = User::where('email', 'recepcion@example.com')->firstOrFail();
        $exam = Exam::where('nombre_examen', 'TEM Cerebral')->firstOrFail();
        $price = AgreementPrice::where('agreement_id', $agreement->id)->where('exam_id', $exam->id)->where('tipo_contraste', 'Con contraste')->firstOrFail();
        $commissionPercentage = 10;
        $commissionAmount = round(((float) $price->precio_pactado * $commissionPercentage) / 100, 2);

        $order = Order::updateOrCreate(
            ['codigo_orden' => 'ORD-000001'],
            [
                'patient_id' => $patient->id,
                'agreement_id' => $agreement->id,
                'medico_solicitante_id' => $solicitante->id,
                'medico_informe_id' => $informante->id,
                'archivo_orden_path' => null,
                'fecha_orden' => now()->toDateString(),
                'estado' => 'Pendiente',
                'subtotal' => $price->precio_pactado,
                'descuento' => 0,
                'total' => $price->precio_pactado,
                'observaciones' => null,
                'created_by' => $creator->id,
            ]
        );

        OrderExam::updateOrCreate(
            ['order_id' => $order->id, 'exam_id' => $exam->id, 'tipo_contraste' => 'Con contraste'],
            ['precio' => $price->precio_pactado, 'estado' => 'Pendiente', 'comision_porcentaje' => $commissionPercentage, 'comision_monto' => $commissionAmount]
        );
    }
}
