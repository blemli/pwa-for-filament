<?php

namespace Blemli\Pwa;

use Blemli\Pwa\Commands\InstallCommand;
use Blemli\Pwa\Commands\UninstallCommand;
use Blemli\Pwa\Livewire\SharePrefillHook;
use Livewire\LivewireManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PwaServiceProvider extends PackageServiceProvider
{
    public static string $name = 'pwa-for-filament';

    public static string $viewNamespace = 'pwa-for-filament';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews(static::$viewNamespace)
            ->hasTranslations()
            ->hasCommands([
                InstallCommand::class,
                UninstallCommand::class,
            ]);
    }

    public function packageBooted(): void
    {
        app(LivewireManager::class)->componentHook(SharePrefillHook::class);
    }
}
