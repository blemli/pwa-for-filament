<?php

namespace Blemli\Pwa\Http\Controllers;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lets pwa:install capture authenticated dashboard screenshots: a signed,
 * short-lived URL logs the headless browser in and forwards it to the panel.
 * Debug-only, so the route is inert in production.
 */
class ScreenshotLoginController
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(app()->hasDebugModeEnabled(), 404);

        $panel = Filament::getCurrentPanel();

        abort_unless($panel !== null, 404);

        abort_unless(Filament::auth()->loginUsingId($request->query('user')) !== false, 404);

        $request->session()->regenerate();

        $query = array_filter([
            'pwa-screenshot' => '1',
            'pwa-theme' => $request->query('pwa-theme'),
        ]);

        return redirect()->to($panel->getUrl() . '?' . http_build_query($query));
    }
}
