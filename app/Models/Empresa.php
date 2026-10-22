<?php

namespace App\Models;

use App\Enums\ConceptoSistema;
use App\Enums\ItemInventario;
use App\Enums\Rol;
use App\Enums\TipoMovimiento;
use Carbon\CarbonInterface;
use Database\Factories\EmpresaFactory;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * La empresa es el "inquilino" del SaaS: cada bloquería cliente es una empresa
 * y todo lo demás (sucursales, inventario, caja) le pertenece.
 */
#[Fillable(['nombre', 'nit', 'bloques_por_bulto', 'bloques_por_viaje', 'tarifa_por_bulto'])]
class Empresa extends Model implements HasName
{
    /** @use HasFactory<EmpresaFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bloques_por_bulto' => 'integer',
            'bloques_por_viaje' => 'integer',
            'tarifa_por_bulto' => 'decimal:2',
        ];
    }

    /**
     * Nombre que muestra el panel en el selector de bloquerías.
     */
    public function getFilamentName(): string
    {
        return $this->nombre;
    }

    /**
     * @return HasMany<Sucursal, $this>
     */
    public function sucursales(): HasMany
    {
        return $this->hasMany(Sucursal::class);
    }

    /**
     * @return HasMany<Producto, $this>
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    /**
     * @return HasMany<Trabajador, $this>
     */
    public function trabajadores(): HasMany
    {
        return $this->hasMany(Trabajador::class);
    }

    /**
     * @return HasMany<Cliente, $this>
     */
    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    /**
     * @return HasMany<ConceptoCaja, $this>
     */
    public function conceptosCaja(): HasMany
    {
        return $this->hasMany(ConceptoCaja::class);
    }

    /**
     * @return HasMany<MovimientoCaja, $this>
     */
    public function movimientosCaja(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class);
    }

    /**
     * Si el concepto aún no existe (porque se agregó en una versión nueva del sistema), se crea.
     */
    public function conceptoSistema(ConceptoSistema $clave): ConceptoCaja
    {
        return $this->conceptosCaja()->firstOrCreate(
            ['clave' => $clave],
            ['nombre' => $clave->nombre(), 'tipo' => $clave->tipo()],
        );
    }

    /**
     * Saldo de la caja única: entradas menos gastos.
     */
    public function saldoCaja(): float
    {
        $totales = $this->movimientosCaja()
            ->selectRaw('tipo, sum(monto) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo');

        return (float) ($totales[TipoMovimiento::Entrada->value] ?? 0)
            - (float) ($totales[TipoMovimiento::Gasto->value] ?? 0);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('rol')
            ->withTimestamps();
    }

    /**
     * Toda bloquería empieza con su bloque (precio en cero hasta que el administrador lo ajuste)
     * y con los conceptos de caja que necesitan los módulos, más unos manuales de uso común.
     */
    protected static function booted(): void
    {
        static::created(function (Empresa $empresa): void {
            $empresa->productos()->create(['nombre' => 'Bloque', 'precio_vigente' => 0]);

            foreach (ConceptoSistema::cases() as $clave) {
                $empresa->conceptosCaja()->create([
                    'nombre' => $clave->nombre(),
                    'tipo' => $clave->tipo(),
                    'clave' => $clave,
                ]);
            }

            foreach (ConceptoCaja::MANUALES_INICIALES as $nombre => $tipo) {
                $empresa->conceptosCaja()->create(['nombre' => $nombre, 'tipo' => $tipo]);
            }
        });
    }

    public function agregarUsuario(User $user, Rol $rol): void
    {
        $this->users()->syncWithoutDetaching([$user->getKey() => ['rol' => $rol->value]]);
    }

    /**
     * @return HasMany<MovimientoInventario, $this>
     */
    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    /**
     * @return HasMany<CompraCemento, $this>
     */
    public function comprasCemento(): HasMany
    {
        return $this->hasMany(CompraCemento::class);
    }

    /**
     * @return HasMany<ViajeArena, $this>
     */
    public function viajesArena(): HasMany
    {
        return $this->hasMany(ViajeArena::class);
    }

    /**
     * @return HasMany<Produccion, $this>
     */
    public function producciones(): HasMany
    {
        return $this->hasMany(Produccion::class);
    }

    /**
     * @return HasMany<PagoTrabajador, $this>
     */
    public function pagosTrabajador(): HasMany
    {
        return $this->hasMany(PagoTrabajador::class);
    }

    /**
     * @return HasMany<TrasladoBloques, $this>
     */
    public function trasladosBloques(): HasMany
    {
        return $this->hasMany(TrasladoBloques::class);
    }

    /**
     * @return HasMany<Venta, $this>
     */
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    /**
     * @return HasOne<CargaInicial, $this>
     */
    public function cargaInicial(): HasOne
    {
        return $this->hasOne(CargaInicial::class);
    }

    /**
     * @return HasMany<CierreMensual, $this>
     */
    public function cierresMensuales(): HasMany
    {
        return $this->hasMany(CierreMensual::class);
    }

    /**
     * Un mes cerrado no acepta movimientos con fecha dentro de él.
     */
    public function mesCerrado(CarbonInterface $fecha): bool
    {
        return $this->cierresMensuales()->where('anio', $fecha->year)->where('mes', $fecha->month)->exists();
    }

    /**
     * Inventario general: la suma de todas las sucursales.
     */
    public function stockGeneral(ItemInventario $item): int
    {
        return (int) $this->movimientosInventario()->where('item', $item)->sum('cantidad');
    }
}
