<?php

use Blemli\Pwa\Support\InstallState;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $image = imagecreatetruecolor(64, 64);
    imagefill($image, 0, 0, imagecolorallocate($image, 32, 96, 90));
    imagepng($image, public_path('logo.png'));

    config()->set('pwa-for-filament.icons.source', public_path('logo.png'));
});

afterEach(function () {
    File::delete(public_path('logo.png'));
});

it('installs icons and state non-interactively', function () {
    $this->artisan('pwa:install', ['--force' => true, '--skip-screenshots' => true])
        ->assertSuccessful();

    $directory = InstallState::directory('admin');

    foreach (['icon-192.png', 'icon-512.png', 'icon-maskable-192.png', 'icon-maskable-512.png', 'apple-touch-icon.png'] as $icon) {
        expect(File::exists("{$directory}/{$icon}"))->toBeTrue();
    }

    $state = InstallState::read('admin');

    expect($state['version'])->toMatch('/^\d{14}$/')
        ->and($state['icon_source'])->toBe(public_path('logo.png'));
});

it('re-runs idempotently and bumps the service worker version', function () {
    $this->artisan('pwa:install', ['--force' => true, '--skip-screenshots' => true])->assertSuccessful();

    $firstVersion = InstallState::read('admin')['version'];

    $this->travel(2)->seconds();

    $this->artisan('pwa:install', ['--force' => true, '--skip-screenshots' => true])->assertSuccessful();

    expect(InstallState::read('admin')['version'])->not->toBe($firstVersion);
});

it('fails with a remedy when no icon source resolves', function () {
    config()->set('pwa-for-filament.icons.source', null);

    Filament::getPanel('admin')->brandLogo(null)->favicon(null);

    $this->artisan('pwa:install', ['--force' => true, '--skip-screenshots' => true])
        ->expectsOutputToContain('No usable icon source found')
        ->assertFailed();
});
