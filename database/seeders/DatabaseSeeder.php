<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            UsersSeeder::class,
            RequestingDoctorsSeeder::class,
            PatientsSeeder::class,
            AgreementsSeeder::class,
            ExamsSeeder::class,
            ReagentsSeeder::class,
            AgreementPricesSeeder::class,
            ExamReagentSeeder::class,
            OrdersSeeder::class,
            StockMovementsSeeder::class,
        ]);
    }
}
