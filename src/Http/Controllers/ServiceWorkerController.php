<?php

namespace Blemli\Pwa\Http\Controllers;

use Blemli\Pwa\Support\InstallState;
use Filament\Facades\Filament;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class ServiceWorkerController
{
    public function __invoke(): Response
    {
        $panel = Filament::getCurrentPanel();

        abort_unless($panel !== null, 404);

        $panelId = $panel->getId();
        $state = InstallState::read($panelId);
        $directory = InstallState::directory($panelId);

        $precache = [$panel->route('pwa.offline', absolute: false)];

        foreach (['icon-192.png', 'icon-512.png', 'icon.svg'] as $icon) {
            if (is_file("{$directory}/{$icon}")) {
                $precache[] = "/pwa/{$panelId}/{$icon}";
            }
        }

        $config = [
            'version' => $state['version'] ?? 'dev',
            'cachePrefix' => "pwa-{$panelId}-",
            'offlineUrl' => $panel->route('pwa.offline', absolute: false),
            'precache' => $precache,
        ];

        $script = str_replace(
            '__PWA_CONFIG__',
            json_encode($config, JSON_UNESCAPED_SLASHES),
            File::get(__DIR__ . '/../../../resources/js/sw.js'),
        );

        return response($script, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
