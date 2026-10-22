<?php

namespace Database\Seeders;

use App\Actions\Caja\RegistrarMovimiento;
use App\Actions\Inventario\RegistrarCompraCemento;
use App\Actions\Inventario\RegistrarViajeArena;
use App\Actions\Inventario\TrasladarBloques;
use App\Actions\Produccion\PagarTrabajador;
use App\Actions\Produccion\RegistrarProduccion;
use App\Actions\Ventas\RegistrarAbono;
use App\Actions\Ventas\RegistrarVenta;
use App\Enums\ConceptoSistema;
use App\Enums\FormaPago;
use App\Enums\Rol;
use App\Enums\TipoDocumento;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos de demostración: una bloquería con dos sucursales, un administrador, un encargado,
     * el bloque con su precio, trabajadores, clientes, caja, cemento, arena, producción, un pago y ventas de contado y a crédito de ejemplo.
     */
    public function run(): void
    {
        $administrador = User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@bloqueria.test',
        ]);

        $encargado = User::factory()->create([
            'name' => 'Encargado',
            'email' => 'encargado@bloqueria.test',
        ]);

        $empresa = Empresa::factory()
            ->conUsuario($administrador, Rol::Administrador)
            ->conUsuario($encargado, Rol::Encargado)
            ->create([
                'nombre' => 'Bloquería Demo',
                'nit' => '900123456-7',
            ]);

        [$sucursal1, $sucursal2] = $empresa->sucursales()->createMany([
            ['nombre' => 'Sucursal 1', 'direccion' => 'Calle 10 # 5-20'],
            ['nombre' => 'Sucursal 2', 'direccion' => 'Carrera 8 # 15-40'],
        ]);

        $empresa->productos()->firstWhere('nombre', 'Bloque')->cambiarPrecio(1800, $administrador);

        $trabajadores = [];

        foreach ([[$sucursal1, 'Trabajador A'], [$sucursal1, 'Trabajador B'], [$sucursal2, 'Trabajador C']] as [$sucursal, $nombre]) {
            $trabajadores[] = $empresa->trabajadores()->create([
                'sucursal_id' => $sucursal->id,
                'nombre' => $nombre,
                'documento' => fake()->unique()->numerify('##########'),
            ]);
        }

        [$clienteEjemplo, $constructora] = $empresa->clientes()->createMany([
            ['tipo_documento' => TipoDocumento::Cedula, 'documento' => '1010101010', 'nombre' => 'Cliente de ejemplo'],
            ['tipo_documento' => TipoDocumento::Nit, 'documento' => '900987654-3', 'nombre' => 'Constructora de ejemplo S.A.S.'],
        ]);

        $registrar = new RegistrarMovimiento;
        $concepto = fn (string $nombre) => $empresa->conceptosCaja()->firstWhere('nombre', $nombre);

        $registrar($empresa->conceptoSistema(ConceptoSistema::SaldoInicial), 4000000, now()->startOfMonth(), $administrador, nota: 'Ejemplo: total del papel');
        $registrar($concepto('Venta de arena'), 120000, now()->startOfMonth()->addDays(2), $encargado, $sucursal1);
        $registrar($concepto('Luz'), 85000, now()->startOfMonth()->addDays(4), $administrador, $sucursal2, 'Ejemplo');
        $registrar($concepto('Transporte'), 40000, now(), $encargado, $sucursal1);

        $inicio = now()->startOfMonth();

        app(RegistrarCompraCemento::class)($empresa, $inicio->copy()->addDay(), 40, 32000, [$sucursal1->id => 25, $sucursal2->id => 15], 'Proveedor de ejemplo', $administrador);
        app(RegistrarViajeArena::class)($sucursal1, $inicio->copy()->addDay(), 700000, user: $administrador);
        app(RegistrarViajeArena::class)($sucursal2, $inicio->copy()->addDay(), 700000, user: $administrador);

        $bloque = $empresa->productos()->firstWhere('nombre', 'Bloque');
        $registrarProduccion = app(RegistrarProduccion::class);

        foreach ([[0, 4, 412], [1, 3, 284], [2, 5, 507], [0, 3, 305]] as $dia => [$indice, $bultos, $sacados]) {
            $registrarProduccion($trabajadores[$indice]->fresh(), $bloque, $bultos, $sacados, $inicio->copy()->addDays(2 + $dia), $encargado);
        }

        app(PagarTrabajador::class)($trabajadores[1]->fresh(), $inicio->copy()->addDays(4), user: $administrador);
        app(TrasladarBloques::class)($sucursal1, $sucursal2, $bloque, 100, $inicio->copy()->addDays(6), $encargado, 'Ejemplo');

        $vender = app(RegistrarVenta::class);
        $lineas = fn (int $cantidad, int $precio = 1800): array => [['producto' => $bloque, 'cantidad' => $cantidad, 'precio' => $precio]];

        $vender($sucursal1, $lineas(150), FormaPago::Contado, $inicio->copy()->addDays(7), user: $encargado);
        $vender($sucursal2, $lineas(200, 1750), FormaPago::Contado, $inicio->copy()->addDays(8), $clienteEjemplo, user: $encargado);
        $credito = $vender($sucursal1, $lineas(300, 1700), FormaPago::Credito, $inicio->copy()->addDays(8), $constructora, $inicio->copy()->addDays(38), $administrador);
        app(RegistrarAbono::class)($credito, 200000, $inicio->copy()->addDays(10), $administrador);
        $vender($sucursal2, $lineas(100), FormaPago::Credito, $inicio->copy()->subDays(40), $clienteEjemplo, $inicio->copy()->subDays(10), $administrador);
    }
}
