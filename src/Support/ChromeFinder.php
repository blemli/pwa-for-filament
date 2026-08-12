<?php

namespace Blemli\Pwa\Support;

use Symfony\Component\Process\ExecutableFinder;

class ChromeFinder
{
    public static function find(): ?string
    {
        $configured = env('PWA_CHROME_BINARY');

        if (filled($configured) && is_executable($configured)) {
            return $configured;
        }

        $finder = new ExecutableFinder;

        foreach (['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser', 'chrome', 'msedge'] as $binary) {
            if ($path = $finder->find($binary)) {
                return $path;
            }
        }

        foreach ([
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            '/Applications/Chromium.app/Contents/MacOS/Chromium',
            '/Applications/Microsoft Edge.app/Contents/MacOS/Microsoft Edge',
        ] as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        return null;
    }
}
