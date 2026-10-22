<?php

namespace Tests\Feature\Filament;

use App\Enums\Rol;
use App\Filament\Pages\Tenancy\EditarEmpresa;
use App\Filament\Pages\Tenancy\RegistrarEmpresa;
use App\Filament\Resources\Clientes\ClienteResource;
use App\Filament\Resources\ComprasCemento\CompraCementoResource;
use App\Filament\Resources\ConceptosCaja\ConceptoCajaResource;
use App\Filament\Resources\MovimientosCaja\MovimientoCajaResource;
use App\Filament\Resources\MovimientosInventario\MovimientoInventarioResource;
use App\Filament\Resources\PagosTrabajador\PagoTrabajadorResource;
use App\Filament\Resources\Producciones\ProduccionResource;
use App\Filament\Resources\Productos\ProductoResource;
use App\Filament\Resources\Sucursales\SucursalResource;
use App\Filament\Resources\Trabajadores\TrabajadorResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\ViajesArena\ViajeArenaResource;
use App\Models\Empresa;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{class-string}>
     */
    public static function paginas(): array
    {
        return [
            'inicio' => [Dashboard::class],
            'caja' => [MovimientoCajaResource::class],
            'producción' => [ProduccionResource::class],
            'pagos a trabajadores' => [PagoTrabajadorResource::class],
            'inventario' => [MovimientoInventarioResource::class],
            'compras de cemento' => [CompraCementoResource::class],
            'viajes de arena' => [ViajeArenaResource::class],
            'productos' => [ProductoResource::class],
            'trabajadores' => [TrabajadorResource::class],
            'clientes' => [ClienteResource::class],
            'sucursales' => [SucursalResource::class],
            'usuarios' => [UserResource::class],
            'conceptos de caja' => [ConceptoCajaResource::class],
        ];
    }

    /**
     * @param  class-string  $pagina
     */
    #[DataProvider('paginas')]
    public function test_administrator_opens_every_page_of_the_panel(string $pagina): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();

        $this->actingAs($admin)
            ->get($pagina::getUrl(tenant: $empresa))
            ->assertOk();
    }

    public function test_administrator_opens_the_empresa_profile_and_a_product(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();

        $this->actingAs($admin);

        $this->get(EditarEmpresa::getUrl(tenant: $empresa))->assertOk();
        $this->get(ProductoResource::getUrl('view', ['record' => $empresa->productos()->sole()], tenant: $empresa))->assertOk();
    }

    public function test_a_new_user_opens_the_registration_of_a_bloqueria(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(Filament::getPanel('admin')->getTenantRegistrationUrl())
            ->assertOk()
            ->assertSeeText(RegistrarEmpresa::getLabel());
    }

    public function test_encargado_opens_the_dashboard_but_not_the_empresa_profile(): void
    {
        $encargado = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($encargado, Rol::Encargado)->create();

        $this->actingAs($encargado);

        $this->get(Dashboard::getUrl(tenant: $empresa))->assertOk();
        $this->get(EditarEmpresa::getUrl(tenant: $empresa))->assertNotFound();
    }
}
