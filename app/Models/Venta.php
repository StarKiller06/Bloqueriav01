<?php

namespace App\Models;

use App\Enums\EstadoVenta;
use App\Enums\FormaPago;
use Database\Factories\VentaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * El saldo pendiente solo lo cambian RegistrarVenta, RegistrarAbono y AnularVenta.
 */
#[Fillable(['numero', 'sucursal_id', 'cliente_id', 'fecha', 'forma_pago', 'fecha_vencimiento', 'total', 'saldo_pendiente', 'user_id'])]
class Venta extends Model
{
    /** @use HasFactory<VentaFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'fecha' => 'date',
            'forma_pago' => FormaPago::class,
            'fecha_vencimiento' => 'date',
            'total' => 'decimal:2',
            'saldo_pendiente' => 'decimal:2',
            'anulada_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Empresa, $this>
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * @return BelongsTo<Sucursal, $this>
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    /**
     * @return BelongsTo<Cliente, $this>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * @return HasMany<DetalleVenta, $this>
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    /**
     * @return HasMany<Abono, $this>
     */
    public function abonos(): HasMany
    {
        return $this->hasMany(Abono::class)->orderBy('fecha')->orderBy('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Número con ceros a la izquierda, como se imprime en la factura.
     */
    public function numeroFormateado(): string
    {
        return str_pad((string) $this->numero, 5, '0', STR_PAD_LEFT);
    }

    public function nombreCliente(): string
    {
        return $this->cliente->nombre ?? 'Consumidor final';
    }

    public function estaAnulada(): bool
    {
        return $this->anulada_at !== null;
    }

    public function estado(): EstadoVenta
    {
        return match (true) {
            $this->estaAnulada() => EstadoVenta::Anulada,
            (float) $this->saldo_pendiente <= 0 => EstadoVenta::Pagada,
            $this->fecha_vencimiento?->isBefore(today()) ?? false => EstadoVenta::Vencida,
            default => EstadoVenta::Pendiente,
        };
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function vigentes(Builder $query): void
    {
        $query->whereNull('anulada_at');
    }

    /**
     * Ventas a crédito que todavía deben algo.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function porCobrar(Builder $query): void
    {
        $query->whereNull('anulada_at')->where('saldo_pendiente', '>', 0);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function vencidas(Builder $query): void
    {
        $query->porCobrar()->whereDate('fecha_vencimiento', '<', today());
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function enEstado(Builder $query, EstadoVenta $estado): void
    {
        match ($estado) {
            EstadoVenta::Anulada => $query->whereNotNull('anulada_at'),
            EstadoVenta::Pagada => $query->whereNull('anulada_at')->where('saldo_pendiente', '<=', 0),
            EstadoVenta::Vencida => $query->vencidas(),
            EstadoVenta::Pendiente => $query->porCobrar()->where(fn (Builder $query) => $query
                ->whereNull('fecha_vencimiento')
                ->orWhereDate('fecha_vencimiento', '>=', today())),
        };
    }
}
