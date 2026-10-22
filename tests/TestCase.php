<?php

namespace Tests;

use App\Models\Empresa;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Inicia sesión y abre la empresa en el panel, como si el usuario la hubiera elegido.
     */
    protected function entrarComo(User $user, Empresa $empresa): void
    {
        $this->actingAs($user);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::setTenant($empresa);
        Filament::bootCurrentPanel();
    }
}
