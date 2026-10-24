<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Deja lista la bloquería real con sus dos administradores. Es el seeder del servidor:
 *
 *     php artisan db:seed --class=UsuariosInicialesSeeder --force
 *
 * Se puede correr más de una vez: no duplica nada y no cambia contraseñas que ya existan,
 * así que si alguien cambió la suya desde el panel, se respeta.
 */
class UsuariosInicialesSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = $this->empresa();

        foreach (config('bloqueria.usuarios_iniciales') as $datos) {
            if (blank($datos['email'])) {
                throw new RuntimeException("Falta el correo de {$datos['nombre']} en el .env.");
            }

            $user = User::query()->firstWhere('email', $datos['email']);

            if ($user === null) {
                if (blank($datos['password'])) {
                    throw new RuntimeException("Falta la contraseña de {$datos['email']} en el .env.");
                }

                $user = User::query()->create([
                    'name' => $datos['nombre'],
                    'email' => $datos['email'],
                    'password' => $datos['password'],
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $empresa->agregarUsuario($user, Rol::Administrador);
            $this->command?->info("Listo: {$datos['email']} (administrador de {$empresa->nombre})");
        }
    }

    private function empresa(): Empresa
    {
        ['nombre' => $nombre, 'nit' => $nit] = config('bloqueria.empresa');

        if (blank($nombre) || blank($nit)) {
            throw new RuntimeException('Faltan BLOQUERIA_EMPRESA_NOMBRE y BLOQUERIA_EMPRESA_NIT en el .env.');
        }

        return Empresa::query()->firstOrCreate(['nit' => $nit], ['nombre' => $nombre]);
    }
}
