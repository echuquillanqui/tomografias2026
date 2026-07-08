<?php

namespace Database\Seeders;

use App\Models\Agreement;
use Illuminate\Database\Seeder;

class AgreementsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['EsSalud', 'Clínica Pinos', 'Particular'] as $nombre) {
            Agreement::firstOrCreate(['nombre_institucion' => $nombre], ['activo' => true]);
        }
    }
}
