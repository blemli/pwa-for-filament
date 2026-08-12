<?php

namespace Blemli\Pwa\Http\Controllers;

use Blemli\Pwa\PwaPlugin;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;

class OfflineController
{
    public function __invoke(): View
    {
        $panel = Filament::getCurrentPanel();

        abort_unless($panel !== null, 404);

        /** @var PwaPlugin $plugin */
        $plugin = $panel->getPlugin('pwa-for-filament');

        return view('pwa-for-filament::offline', [
            'panel' => $panel,
            'plugin' => $plugin,
        ]);
    }
}
