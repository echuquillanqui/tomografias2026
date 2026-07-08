<?php

namespace Database\Seeders;

use App\Models\Exam;
use Illuminate\Database\Seeder;

class ExamsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['TEM Cerebral', 'Ambos'], ['TEM Tórax', 'Ambos'], ['TEM Abdomen', 'Ambos'], ['TEM Pelvis', 'Ambos'],
            ['TEM Columna Lumbar', 'Sin contraste'], ['TEM Senos Paranasales', 'Sin contraste'],
        ] as [$nombre, $tipo]) {
            Exam::updateOrCreate(['nombre_examen' => $nombre], ['tipo_contraste' => $tipo, 'activo' => true]);
        }
    }
}
