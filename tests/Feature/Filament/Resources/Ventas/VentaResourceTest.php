<?php

namespace Tests\Feature\Filament\Resources\Ventas;

use App\Actions\Ventas\RegistrarVenta;
use App\Enums\EstadoVenta;
use App\Enums\FormaPago;
use App\Enums\ItemInventario;
use App\Enums\Rol;
use App\Filament\Resources\Clientes\Pages\ManageClientes;
use App\Filament\Resources\Ventas\Pages\CreateVenta;
use App\Filament\Resources\Ventas\Pages\ListVentas;
use App\Filament\Resources\Ventas\Pages\ViewVenta;
use App\Mail\ComprobanteVenta;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class VentaResourceTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private Sucursal $sucursal;

    private Cliente $cliente;

    private function escenario(Rol $rol = Rol::Encargado): void
    {
        $user = User::factory()->create();
        $this->empresa = Empresa::factory()->conUsuario($user, $rol)->create();
        $this->empresa->productos()->sole()->update(['precio_vigente' => 1800]);
        $this->sucursal = Sucursal::factory()->for($this->empresa)->create();
        $this->cliente = Cliente::factory()->for($this->empresa)->create(['email' => 'cliente@correo.test']);
        MovimientoInventario::factory()->for($this->sucursal)->bloques(1000)->create();

        $this->entrarComo($user, $this->empresa);
    }

    public function test_the_form_starts_with_the_bloque_at_its_current_price(): void
    {
        $this->escenario();

        Livewire::test(CreateVenta::class)
            ->assertSchemaStateSet(fn (array $state): bool => collect($state['lineas'])->first()['precio'] === 1800.0);
    }

    public function test_encargado_sells_to_consumidor_final_for_cash(): void
    {
        $this->escenario();

        Livewire::test(CreateVenta::class)
            ->fillForm([
                'sucursal_id' => $this->sucursal->id,
                'fecha' => now()->toDateString(),
                'forma_pago' => FormaPago::Contado->value,
                'lineas' => [['producto_id' => $this->empresa->productos()->sole()->id, 'cantidad' => 200, 'precio' => 1750]],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $venta = $this->empresa->ventas()->sole();
        $this->assertSame('350000.00', $venta->total);
        $this->assertSame(800, $this->sucursal->stock(ItemInventario::Bloque));
        $this->assertSame(350000.0, $this->empresa->saldoCaja());
    }

    public function test_a_credit_sale_without_cliente_shows_an_error(): void
    {
        $this->escenario();

        Livewire::test(CreateVenta::class)
            ->fillForm([
                'sucursal_id' => $this->sucursal->id,
                'fecha' => now()->toDateString(),
                'forma_pago' => FormaPago::Credito->value,
                'lineas' => [['producto_id' => $this->empresa->productos()->sole()->id, 'cantidad' => 10, 'precio' => 1800]],
            ])
            ->call('create')
            ->assertHasFormErrors(['cliente_id' => 'required']);

        $this->assertSame(0, Venta::count());
    }

    public function test_selling_more_than_the_stock_explains_why(): void
    {
        $this->escenario();

        Livewire::test(CreateVenta::class)
            ->fillForm([
                'sucursal_id' => $this->sucursal->id,
                'fecha' => now()->toDateString(),
                'forma_pago' => FormaPago::Contado->value,
                'lineas' => [['producto_id' => $this->empresa->productos()->sole()->id, 'cantidad' => 5000, 'precio' => 1800]],
            ])
            ->call('create')
            ->assertNotified('No se pudo guardar');

        $this->assertSame(0, Venta::count());
    }

    public function test_abonar_from_the_list_lowers_the_saldo(): void
    {
        $this->escenario();
        $venta = $this->ventaACredito();

        Livewire::test(ListVentas::class)
            ->callAction(TestAction::make('abonar')->table($venta), data: ['monto' => 80000, 'fecha' => now()->toDateString()])
            ->assertHasNoFormErrors();

        $this->assertSame('100000.00', $venta->fresh()->saldo_pendiente);
        $this->assertSame(80000.0, $this->empresa->saldoCaja());
    }

    public function test_filters_vencidas(): void
    {
        $this->escenario();
        $vencida = app(RegistrarVenta::class)($this->sucursal, $this->lineas(10), FormaPago::Credito, now()->subDays(40), $this->cliente, now()->subDays(10));
        $alDia = $this->ventaACredito();

        Livewire::test(ListVentas::class)
            ->filterTable('estado', EstadoVenta::Vencida->value)
            ->assertCanSeeTableRecords([$vencida])
            ->assertCanNotSeeTableRecords([$alDia]);
    }

    public function test_only_the_administrator_annuls(): void
    {
        $this->escenario(Rol::Administrador);
        $venta = $this->ventaACredito();

        Livewire::test(ViewVenta::class, ['record' => $venta->getRouteKey()])
            ->callAction('anular', data: ['motivo' => 'Error de digitación'])
            ->assertHasNoFormErrors();

        $this->assertSame(EstadoVenta::Anulada, $venta->fresh()->estado());
    }

    public function test_encargado_cannot_annul(): void
    {
        $this->escenario(Rol::Encargado);
        $venta = $this->ventaACredito();

        Livewire::test(ViewVenta::class, ['record' => $venta->getRouteKey()])
            ->assertActionHidden('anular');
    }

    public function test_sends_the_comprobante_to_the_clientes_email(): void
    {
        Mail::fake();
        $this->escenario();
        $venta = $this->ventaACredito();

        Livewire::test(ViewVenta::class, ['record' => $venta->getRouteKey()])
            ->mountAction('enviar')
            ->assertActionDataSet(['email' => 'cliente@correo.test'])
            ->callMountedAction();

        Mail::assertSent(ComprobanteVenta::class, fn (ComprobanteVenta $mail): bool => $mail->hasTo('cliente@correo.test') && $mail->venta->is($venta));
    }

    public function test_the_clientes_list_shows_what_each_one_owes(): void
    {
        $this->escenario();
        $this->ventaACredito();

        Livewire::test(ManageClientes::class)
            ->assertTableColumnStateSet('debe', '180000.00', $this->cliente);
    }

    public function test_lists_only_the_ventas_of_the_open_empresa(): void
    {
        $ajena = Venta::factory()->create();
        $this->escenario();
        $propia = $this->ventaACredito();

        Livewire::test(ListVentas::class)
            ->assertCanSeeTableRecords([$propia])
            ->assertCanNotSeeTableRecords([$ajena]);
    }

    private function ventaACredito(): Venta
    {
        return app(RegistrarVenta::class)($this->sucursal, $this->lineas(100), FormaPago::Credito, now(), $this->cliente, now()->addDays(30));
    }

    /**
     * @return list<array{producto: Producto, cantidad: int, precio: float}>
     */
    private function lineas(int $cantidad): array
    {
        return [['producto' => $this->empresa->productos()->sole(), 'cantidad' => $cantidad, 'precio' => 1800]];
    }
}
