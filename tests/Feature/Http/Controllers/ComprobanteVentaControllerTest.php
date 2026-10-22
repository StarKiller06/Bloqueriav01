<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\Rol;
use App\Models\DetalleVenta;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprobanteVentaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_of_the_empresa_downloads_the_pdf(): void
    {
        $user = User::factory()->create();
        $venta = DetalleVenta::factory()->create()->venta;
        $venta->empresa->agregarUsuario($user, Rol::Encargado);

        $this->actingAs($user)
            ->get(route('ventas.comprobante', $venta))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_forbids_the_comprobante_of_another_empresa(): void
    {
        $user = User::factory()->create();
        Empresa::factory()->conUsuario($user)->create();
        $ajena = DetalleVenta::factory()->create()->venta;

        $this->actingAs($user)
            ->get(route('ventas.comprobante', $ajena))
            ->assertForbidden();
    }

    public function test_redirects_guests_to_the_login(): void
    {
        $venta = Venta::factory()->create();

        $this->get(route('ventas.comprobante', $venta))->assertRedirect('/admin/login');
    }
}
