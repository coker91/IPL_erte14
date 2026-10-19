<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use Filament\Actions\Action;
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

class WargaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('warga')
            ->path('warga')
            ->login(Login::class)
            ->brandName('IPL RT 14 — Warga')
            ->userMenuItems(['rekap' => Action::make('rekap')->label('Rekap Keuangan RT')->url('/')])
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->discoverResources(in: app_path('Filament/Warga/Resources'), for: 'App\Filament\Warga\Resources')
            ->discoverPages(in: app_path('Filament/Warga/Pages'), for: 'App\Filament\Warga\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Warga/Widgets'), for: 'App\Filament\Warga\Widgets')
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
