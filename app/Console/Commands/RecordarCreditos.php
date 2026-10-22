<?php

namespace App\Console\Commands;

use App\Enums\EstadoVenta;
use App\Enums\Rol;
use App\Filament\Resources\Ventas\VentaResource;
use App\Models\Empresa;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Number;

/**
 * Avisa a los administradores de cada bloquería qué créditos siguen pendientes y cuáles vencieron.
 * Se programa para el último día de cada mes en routes/console.php.
 */
#[Signature('creditos:recordar')]
#[Description('Avisa en el panel a los administradores los créditos pendientes y vencidos')]
class RecordarCreditos extends Command
{
    public function handle(): int
    {
        $avisadas = 0;

        Empresa::query()
            ->whereHas('ventas', fn ($query) => $query->porCobrar())
            ->each(function (Empresa $empresa) use (&$avisadas): void {
                $pendiente = (float) $empresa->ventas()->porCobrar()->sum('saldo_pendiente');
                $cantidad = $empresa->ventas()->porCobrar()->count();
                $vencidas = $empresa->ventas()->vencidas()->count();

                $administradores = $empresa->users()->wherePivot('rol', Rol::Administrador->value)->get();

                Notification::make()
                    ->title('Créditos por cobrar a fin de mes')
                    ->body(sprintf(
                        '%d ventas a crédito suman %s por cobrar%s.',
                        $cantidad,
                        Number::currency($pendiente, 'COP', 'es_CO', 0),
                        $vencidas > 0 ? ", y {$vencidas} ya pasaron su fecha límite" : '',
                    ))
                    ->icon('heroicon-o-clock')
                    ->color($vencidas > 0 ? 'danger' : 'warning')
                    ->actions([
                        Action::make('ver')
                            ->label('Ver créditos')
                            ->url(VentaResource::getUrl('index', [
                                'filters' => ['estado' => ['value' => $vencidas > 0 ? EstadoVenta::Vencida->value : EstadoVenta::Pendiente->value]],
                            ], panel: 'admin', tenant: $empresa)),
                    ])
                    ->sendToDatabase($administradores);

                $avisadas++;
            });

        $this->info("Se avisó a {$avisadas} bloquerías.");

        return self::SUCCESS;
    }
}
