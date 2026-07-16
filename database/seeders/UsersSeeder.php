<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['email' => 'admin@example.com', 'role' => 'Admin', 'data' => ['nombre_completo' => 'Administrador Principal', 'username' => 'echuquillanquiy', 'password' => Hash::make('password'), 'rol' => 'Admin', 'activo' => true]],
            ['email' => 'recepcion@example.com', 'role' => 'Recepción', 'data' => ['nombre_completo' => 'Usuario Recepción', 'username' => 'recepcion', 'password' => Hash::make('password'), 'rol' => 'Recepción', 'activo' => true]],
            ['email' => 'informante@example.com', 'role' => 'Médico', 'data' => ['nombre_completo' => 'Dr. Médico Informante', 'username' => 'informante', 'password' => Hash::make('password'), 'rol' => 'Médico', 'tipo_medico' => 'De Informe', 'cmp' => 'CMP67890', 'rne' => 'RNE67890', 'comision_porcentaje' => 10, 'firma_path' => null, 'activo' => true]],
            ['email' => 'almacen@example.com', 'role' => 'Almacén', 'data' => ['nombre_completo' => 'Usuario Almacén', 'username' => 'almacen', 'password' => Hash::make('password'), 'rol' => 'Almacén', 'activo' => true]],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(['email' => $userData['email']], $userData['data'] + ['email' => $userData['email']]);
            $user->assignRole($userData['role']);
        }
    }
}
