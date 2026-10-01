<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_superadmin_role_is_available_in_user_forms(): void
    {
        $admin = User::factory()->create(['rol' => 'Admin']);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('<option value="Superadmin"', false);
    }

    public function test_superadmin_role_can_be_assigned_when_creating_a_user(): void
    {
        $admin = User::factory()->create(['rol' => 'Admin']);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'username' => 'nuevo-superadmin',
                'nombre_completo' => 'Nuevo Superadmin',
                'email' => 'superadmin@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'rol' => 'Superadmin',
                'activo' => '1',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $user = User::where('username', 'nuevo-superadmin')->firstOrFail();

        $this->assertSame('Superadmin', $user->rol);
        $this->assertTrue($user->hasRole('Superadmin'));
    }
}
