<?php

namespace Tests\Feature\Seeders;

use App\Enums\Rol;
use App\Models\Empresa;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UsuariosInicialesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class UsuariosInicialesSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'bloqueria.empresa' => ['nombre' => 'Bloquería Real', 'nit' => '901000111-2'],
            'bloqueria.usuarios_iniciales' => [
                ['nombre' => 'Administrador', 'email' => 'admin@real.test', 'password' => 'clave-admin'],
                ['nombre' => 'Programador', 'email' => 'dev@real.test', 'password' => 'clave-dev'],
            ],
        ]);
    }

    public function test_creates_the_empresa_with_both_users_as_administrators(): void
    {
        $this->seed(UsuariosInicialesSeeder::class);

        $empresa = Empresa::query()->sole();
        $this->assertSame('Bloquería Real', $empresa->nombre);

        foreach (['admin@real.test' => 'clave-admin', 'dev@real.test' => 'clave-dev'] as $email => $clave) {
            $user = User::firstWhere('email', $email);
            $this->assertTrue(Hash::check($clave, $user->password));
            $this->assertSame(Rol::Administrador, $user->rolEn($empresa));
        }
    }

    public function test_running_it_again_duplicates_nothing_and_keeps_changed_passwords(): void
    {
        $this->seed(UsuariosInicialesSeeder::class);
        User::firstWhere('email', 'admin@real.test')->update(['password' => 'la-cambio-desde-el-panel']);

        $this->seed(UsuariosInicialesSeeder::class);

        $this->assertSame(1, Empresa::count());
        $this->assertSame(2, User::count());
        $this->assertTrue(Hash::check('la-cambio-desde-el-panel', User::firstWhere('email', 'admin@real.test')->password));
    }

    public function test_refuses_to_create_a_user_without_a_password(): void
    {
        config(['bloqueria.usuarios_iniciales.0.password' => null]);

        $this->expectExceptionMessage('Falta la contraseña de admin@real.test');

        $this->seed(UsuariosInicialesSeeder::class);
    }

    public function test_the_demo_data_is_never_loaded_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(RuntimeException::class);

        $this->app->make(DatabaseSeeder::class)->run();
    }
}
