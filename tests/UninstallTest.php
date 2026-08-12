<?php

use Illuminate\Support\Facades\File;

function fakePublishedArtifacts(): array
{
    $paths = [
        config_path('pwa-for-filament.php'),
        lang_path('vendor/pwa-for-filament/de/pwa.php'),
        resource_path('views/vendor/pwa-for-filament/banner.blade.php'),
        public_path('js/blemli/pwa-for-filament/pwa.js'),
        public_path('css/blemli/pwa-for-filament/pwa.css'),
        public_path('pwa/admin/icon-192.png'),
        public_path('pwa/admin/install.json'),
        public_path('pwa/admin/screenshots/wide-light.png'),
    ];

    foreach ($paths as $path) {
        File::ensureDirectoryExists(dirname($path));
        // The testbench skeleton loads every config file at boot, so the fake
        // config must be a valid, empty one.
        File::put($path, str_ends_with($path, '.php') ? "<?php\n\nreturn [];\n" : 'fake');
    }

    return $paths;
}

it('removes every published and generated artifact', function () {
    $paths = fakePublishedArtifacts();

    $this->artisan('pwa:uninstall', ['--force' => true])->assertSuccessful();

    foreach ($paths as $path) {
        expect(File::exists($path))->toBeFalse($path . ' should be gone');
    }

    expect(File::isDirectory(public_path('js/blemli')))->toBeFalse()
        ->and(File::isDirectory(public_path('css/blemli')))->toBeFalse()
        ->and(File::isDirectory(public_path('pwa')))->toBeFalse();
});

it('keeps console output English regardless of the app locale', function () {
    app()->setLocale('de');

    fakePublishedArtifacts();

    $this->artisan('pwa:uninstall', ['--force' => true])
        ->expectsOutputToContain('The following will be removed:')
        ->assertSuccessful();
});
