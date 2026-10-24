<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Tenancy\EditarEmpresa;
use App\Filament\Pages\Tenancy\RegistrarEmpresa;
use App\Filament\Support\PaletaMarca;
use App\Models\Empresa;
use Filament\Auth\Pages\Register;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName(config('bloqueria.marca.nombre'))
            ->brandLogo(fn () => view('filament.marca'))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('img/favicon.png'))
            ->login()
            ->registration(config('bloqueria.registro_abierto') ? Register::class : null)
            ->passwordReset()
            ->profile()
            ->tenant(Empresa::class)
            ->tenantRegistration(RegistrarEmpresa::class)
            ->tenantProfile(EditarEmpresa::class)
            ->colors([
                'primary' => PaletaMarca::primaria(),
                'gray' => PaletaMarca::gris(),
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Red,
                'info' => Color::Sky,
            ])
            ->navigationGroups(['Ventas', 'Producción', 'Inventario', 'Finanzas', 'Catálogos', 'Configuración'])
            ->databaseNotifications()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
