<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar',
            'pacientes.ver', 'pacientes.crear', 'pacientes.editar', 'pacientes.eliminar',
            'convenios.ver', 'convenios.crear', 'convenios.editar', 'convenios.eliminar',
            'examenes.ver', 'examenes.crear', 'examenes.editar', 'examenes.eliminar',
            'precios.ver', 'precios.crear', 'precios.editar', 'precios.eliminar',
            'ordenes.ver', 'ordenes.crear', 'ordenes.editar', 'ordenes.anular', 'ordenes.entregar',
            'informes.ver', 'informes.crear', 'informes.editar', 'informes.firmar',
            'almacen.ver', 'almacen.crear', 'almacen.editar', 'almacen.ingresos', 'almacen.salidas', 'almacen.ajustes',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $recepcion = Role::firstOrCreate(['name' => 'Recepción', 'guard_name' => 'web']);
        $medico = Role::firstOrCreate(['name' => 'Médico', 'guard_name' => 'web']);
        $almacen = Role::firstOrCreate(['name' => 'Almacén', 'guard_name' => 'web']);

        $admin->syncPermissions($permissions);
        $recepcion->syncPermissions([
            'pacientes.ver', 'pacientes.crear', 'pacientes.editar', 'pacientes.eliminar',
            'convenios.ver', 'examenes.ver', 'precios.ver',
            'ordenes.crear', 'ordenes.ver', 'ordenes.editar', 'ordenes.entregar',
        ]);
        $medico->syncPermissions(['ordenes.ver', 'informes.ver', 'informes.crear', 'informes.editar', 'informes.firmar']);
        $almacen->syncPermissions(['almacen.ver', 'almacen.crear', 'almacen.editar', 'almacen.ingresos', 'almacen.salidas', 'almacen.ajustes', 'ordenes.ver']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
