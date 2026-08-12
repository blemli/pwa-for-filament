<?php

use Blemli\Pwa\Support\BrandLogoResolver;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;

beforeEach(function () {
    $this->panel = Filament::getPanel('admin');

    File::put(public_path('logo.png'), 'fake-png');
    File::put(public_path('logo.svg'), '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
});

afterEach(function () {
    File::delete([public_path('logo.png'), public_path('logo.svg')]);
});

it('prefers an explicit override', function () {
    $resolved = BrandLogoResolver::resolve($this->panel, public_path('logo.svg'));

    expect($resolved['path'])->toBe(public_path('logo.svg'))
        ->and($resolved['svg'])->toBeTrue();
});

it('resolves a string brand logo URL to the public file', function () {
    $this->panel->brandLogo('https://example.com/logo.png?v=3');

    $resolved = BrandLogoResolver::resolve($this->panel);

    expect($resolved['path'])->toBe(public_path('logo.png'))
        ->and($resolved['svg'])->toBeFalse();
});

it('parses an img tag out of an Htmlable brand logo', function () {
    $this->panel->brandLogo(fn (): HtmlString => new HtmlString('<div><img src="/logo.png" alt="Logo"></div>'));

    expect(BrandLogoResolver::resolve($this->panel)['path'])->toBe(public_path('logo.png'));
});

it('extracts an inline svg from an Htmlable brand logo', function () {
    $this->panel->brandLogo(fn (): HtmlString => new HtmlString('<span><svg viewBox="0 0 10 10"><rect/></svg></span>'));

    $resolved = BrandLogoResolver::resolve($this->panel);

    expect($resolved['svg'])->toBeTrue()
        ->and(File::get($resolved['path']))->toContain('<svg viewBox="0 0 10 10">');
});

it('falls back to the favicon', function () {
    $this->panel->brandLogo(null)->favicon('/logo.svg');

    expect(BrandLogoResolver::resolve($this->panel)['path'])->toBe(public_path('logo.svg'));
});

it('returns null when nothing resolves', function () {
    $this->panel->brandLogo(null)->favicon(null);

    expect(BrandLogoResolver::resolve($this->panel))->toBeNull();
});
