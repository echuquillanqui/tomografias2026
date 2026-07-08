<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Reagent;
use Illuminate\Database\Seeder;

class ExamReagentSeeder extends Seeder
{
    public function run(): void
    {
        $examNames = ['TEM Cerebral', 'TEM Tórax', 'TEM Abdomen', 'TEM Pelvis'];
        $reagentNames = ['Iopamidol', 'Jeringa', 'Catéter'];

        foreach ($examNames as $examName) {
            $exam = Exam::where('nombre_examen', $examName)->firstOrFail();
            foreach ($reagentNames as $reagentName) {
                $reagent = Reagent::where('nombre', $reagentName)->firstOrFail();
                $exam->reagents()->syncWithoutDetaching([
                    $reagent->id => ['cantidad_estimada' => 1],
                ]);
            }
        }
    }
}
