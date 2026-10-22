<?php

namespace App\Filament\Support;

use App\Exceptions\ReglaDeNegocio;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Corre una operación desde un botón del panel. Si rompe una regla del negocio,
 * muestra el motivo al usuario y deja el formulario abierto para que lo corrija.
 */
final class Reglas
{
    /**
     * @template TResultado
     *
     * @param  Closure(): TResultado  $operacion
     * @return TResultado
     */
    public static function aplicar(Action $action, Closure $operacion): mixed
    {
        try {
            return $operacion();
        } catch (ReglaDeNegocio $e) {
            Notification::make()
                ->danger()
                ->title('No se pudo guardar')
                ->body($e->getMessage())
                ->persistent()
                ->send();

            $action->halt();
        }
    }
}
