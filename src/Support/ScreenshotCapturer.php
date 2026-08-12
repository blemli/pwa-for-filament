<?php

namespace Blemli\Pwa\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class ScreenshotCapturer
{
    protected bool $warmedUp = false;

    public function __construct(protected string $chrome) {}

    public function capture(string $url, string $outputPath, int $width, int $height): bool
    {
        // Chrome's first launch initializes its profile; racing that has
        // produced captures where page scripts had not run yet.
        if (! $this->warmedUp) {
            Process::timeout(30)->run([$this->chrome, '--headless=new', '--disable-gpu', '--no-first-run', '--dump-dom', 'about:blank']);
            $this->warmedUp = true;
        }

        File::ensureDirectoryExists(dirname($outputPath));

        $result = Process::timeout(90)->run([
            $this->chrome,
            '--headless=new',
            '--disable-gpu',
            '--no-first-run',
            '--hide-scrollbars',
            '--force-device-scale-factor=1',
            "--window-size={$width},{$height}",
            '--virtual-time-budget=10000',
            "--screenshot={$outputPath}",
            $url,
        ]);

        return $result->successful() && is_file($outputPath);
    }
}
