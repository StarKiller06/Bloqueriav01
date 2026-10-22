<?php

namespace App\Enums;

/**
 * Conceptos que crea el sistema y que usan los otros módulos (ventas, compras, pagos).
 * No se pueden editar, borrar ni elegir a mano en el formulario de movimientos.
 */
enum ConceptoSistema: string
{
    case SaldoInicial = 'saldo_inicial';
    case VentaContado = 'venta_contado';
    case AbonoCredito = 'abono_credito';
    case CompraCemento = 'compra_cemento';
    case ViajeArena = 'viaje_arena';
    case PagoTrabajador = 'pago_trabajador';

    public function nombre(): string
    {
        return match ($this) {
            self::SaldoInicial => 'Saldo inicial',
            self::VentaContado => 'Venta de contado',
            self::AbonoCredito => 'Abono de crédito',
            self::CompraCemento => 'Compra de cemento',
            self::ViajeArena => 'Viaje de arena',
            self::PagoTrabajador => 'Pago a trabajador',
        };
    }

    public function tipo(): TipoMovimiento
    {
        return match ($this) {
            self::SaldoInicial, self::VentaContado, self::AbonoCredito => TipoMovimiento::Entrada,
            self::CompraCemento, self::ViajeArena, self::PagoTrabajador => TipoMovimiento::Gasto,
        };
    }
}
