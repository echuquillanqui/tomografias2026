<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientsSeeder extends Seeder
{
    public function run(): void
    {
        Patient::updateOrCreate(['dni' => '12345678'], ['nombres' => 'Juan Carlos', 'apellidos' => 'Pérez Ramos', 'telefono' => '999999999', 'fecha_nacimiento' => '1990-05-10', 'edad' => 34]);
        Patient::updateOrCreate(['dni' => '87654321'], ['nombres' => 'María Elena', 'apellidos' => 'Torres Salazar', 'telefono' => '988888888', 'fecha_nacimiento' => '1985-08-20', 'edad' => 39]);
    }
}
