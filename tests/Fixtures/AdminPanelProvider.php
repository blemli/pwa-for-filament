<?php

namespace Blemli\Pwa\Tests\Fixtures;

use Blemli\Pwa\PwaPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
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
            ->login()
            ->brandName('Test App')
            ->colors([
                'primary' => '#20605a',
                // Exercises integer shade aliases and the literal 0.
                'aliased' => ['50' => 'oklch(0.985 0 0)', '500' => 'oklch(0.5 0.1 200)', '600' => 500, '900' => 0],
            ])
            ->darkMode()
            ->databaseNotifications()
            ->resources([TestResource::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                ValidateCsrfToken::class,
                SubstituteBindings::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugin(PwaPlugin::make());
    }
}
