<?php

namespace Blemli\Pwa\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\confirm;

class UninstallCommand extends Command
{
    public $signature = 'pwa:uninstall {--force : Skip all confirmation prompts}';

    public $description = 'Uninstall pwa-for-filament: remove published files, generated icons and screenshots';

    public function handle(): int
    {
        $publishedPaths = array_filter([
            config_path('pwa-for-filament.php'),
            lang_path('vendor/pwa-for-filament'),
            resource_path('views/vendor/pwa-for-filament'),
            public_path('css/blemli/pwa-for-filament'),
            public_path('js/blemli/pwa-for-filament'),
            public_path('pwa'),
        ], fn (string $path): bool => File::exists($path));

        $this->info('The following will be removed:');

        foreach ($publishedPaths as $path) {
            $this->line("  - {$path}");
        }

        if ($publishedPaths === []) {
            $this->line('  (nothing published or generated was found)');
        }

        if ($publishedPaths !== [] && ($this->option('force') || confirm('Delete published config, translations, views, assets and all generated icons/screenshots?'))) {
            foreach ($publishedPaths as $path) {
                File::isDirectory($path) ? File::deleteDirectory($path) : File::delete($path);
            }

            foreach ([public_path('css/blemli'), public_path('js/blemli')] as $directory) {
                if (File::isDirectory($directory) && File::files($directory) === [] && File::directories($directory) === []) {
                    File::deleteDirectory($directory);
                }
            }
        }

        $registrations = $this->panelProviderRegistrations();

        if ($registrations !== []) {
            $this->warn('PwaPlugin is still registered in your panel provider(s) — remove the ->plugin(PwaPlugin::make()...) call or the app will crash after composer remove:');

            foreach ($registrations as $location) {
                $this->line("  - {$location}");
            }
        }

        $this->line('Browsers unregister the service worker on their own once sw.js starts returning 404.');

        if (! $this->option('force') && confirm('Run "composer remove blemli/pwa-for-filament" now?', default: $registrations === [])) {
            Process::path(base_path())
                ->forever()
                ->run(['composer', 'remove', 'blemli/pwa-for-filament'], fn (string $type, string $output) => $this->output->write($output));

            $this->info('pwa-for-filament was uninstalled.');
        } else {
            $this->info('pwa-for-filament was uninstalled. Finish with: composer remove blemli/pwa-for-filament');
        }

        return self::SUCCESS;
    }

    /**
     * Find PwaPlugin registrations in the app's providers so the user can
     * remove them before the package code disappears.
     *
     * @return array<string>
     */
    protected function panelProviderRegistrations(): array
    {
        $locations = [];

        if (! File::isDirectory(app_path('Providers'))) {
            return $locations;
        }

        foreach (File::allFiles(app_path('Providers')) as $file) {
            foreach (explode("\n", File::get($file->getPathname())) as $index => $line) {
                if (str_contains($line, 'PwaPlugin')) {
                    $locations[] = $file->getPathname() . ':' . ($index + 1);
                }
            }
        }

        return $locations;
    }
}
