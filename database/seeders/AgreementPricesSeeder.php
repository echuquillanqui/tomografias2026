<?php

namespace Database\Seeders;

use App\Models\Agreement;
use App\Models\AgreementPrice;
use App\Models\Exam;
use Illuminate\Database\Seeder;

class AgreementPricesSeeder extends Seeder
{
    public function run(): void
    {
        $prices = [
            'Particular' => [
                ['TEM Cerebral', 'Sin contraste', 150.00], ['TEM Cerebral', 'Con contraste', 250.00], ['TEM Tórax', 'Sin contraste', 180.00], ['TEM Tórax', 'Con contraste', 280.00],
                ['TEM Abdomen', 'Sin contraste', 200.00], ['TEM Abdomen', 'Con contraste', 320.00], ['TEM Pelvis', 'Sin contraste', 180.00], ['TEM Pelvis', 'Con contraste', 280.00],
                ['TEM Columna Lumbar', 'Sin contraste', 160.00], ['TEM Senos Paranasales', 'Sin contraste', 120.00],
            ],
            'EsSalud' => [
                ['TEM Cerebral', 'Sin contraste', 120.00], ['TEM Cerebral', 'Con contraste', 180.00], ['TEM Tórax', 'Sin contraste', 150.00], ['TEM Tórax', 'Con contraste', 220.00],
                ['TEM Abdomen', 'Sin contraste', 170.00], ['TEM Abdomen', 'Con contraste', 250.00], ['TEM Pelvis', 'Sin contraste', 150.00], ['TEM Pelvis', 'Con contraste', 220.00],
                ['TEM Columna Lumbar', 'Sin contraste', 130.00], ['TEM Senos Paranasales', 'Sin contraste', 100.00],
            ],
            'Clínica Pinos' => [
                ['TEM Cerebral', 'Sin contraste', 130.00], ['TEM Cerebral', 'Con contraste', 210.00], ['TEM Tórax', 'Sin contraste', 160.00], ['TEM Tórax', 'Con contraste', 250.00],
                ['TEM Abdomen', 'Sin contraste', 190.00], ['TEM Abdomen', 'Con contraste', 300.00], ['TEM Pelvis', 'Sin contraste', 170.00], ['TEM Pelvis', 'Con contraste', 260.00],
                ['TEM Columna Lumbar', 'Sin contraste', 140.00], ['TEM Senos Paranasales', 'Sin contraste', 110.00],
            ],
        ];

        foreach ($prices as $agreementName => $items) {
            $agreement = Agreement::where('nombre_institucion', $agreementName)->firstOrFail();
            foreach ($items as [$examName, $tipoContraste, $price]) {
                $exam = Exam::where('nombre_examen', $examName)->firstOrFail();
                AgreementPrice::updateOrCreate(
                    ['agreement_id' => $agreement->id, 'exam_id' => $exam->id, 'tipo_contraste' => $tipoContraste],
                    ['precio_pactado' => $price]
                );
            }
        }
    }
}
