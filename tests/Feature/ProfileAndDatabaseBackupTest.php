<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileAndDatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_profile_and_password(): void
    {
        $user = User::factory()->create(['nombre_completo' => 'Nombre anterior', 'rol' => 'Recepción']);

        $this->actingAs($user)->put(route('profile.update'), [
            'nombre_completo' => 'Nombre actualizado',
            'username' => 'usuario.nuevo',
            'email' => 'nuevo@example.com',
        ])->assertRedirect();

        $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'password',
            'password' => 'clave-segura',
            'password_confirmation' => 'clave-segura',
        ])->assertRedirect();

        $this->assertSame('Nombre actualizado', $user->fresh()->nombre_completo);
        $this->assertTrue(Hash::check('clave-segura', $user->fresh()->password));
    }

    public function test_only_superadmin_can_download_database_backup(): void
    {
        $regularUser = User::factory()->create(['rol' => 'Admin']);
        $this->actingAs($regularUser)->get(route('database-backup.download'))->assertForbidden();

        Role::create(['name' => 'Superadmin', 'guard_name' => 'web']);
        $superadmin = User::factory()->create(['rol' => 'Superadmin']);
        $superadmin->assignRole('Superadmin');

        $response = $this->actingAs($superadmin)->get(route('database-backup.download'));

        $response->assertOk();
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.sql', (string) $response->headers->get('content-disposition'));
    }
}
