<?php

use Blemli\Pwa\Support\ColorConverter;
use Filament\Support\Facades\FilamentColor;

it('passes hex through and expands shorthand', function () {
    expect(ColorConverter::toHex('#20605A'))->toBe('#20605a')
        ->and(ColorConverter::toHex('#fff'))->toBe('#ffffff')
        ->and(ColorConverter::toHex(null))->toBeNull()
        ->and(ColorConverter::toHex('not-a-color'))->toBeNull();
});

it('converts oklch and rgb to hex', function () {
    expect(ColorConverter::toHex('rgb(32, 96, 90)'))->toBe('#20605a')
        ->and(ColorConverter::toHex('oklch(1 0 0)'))->toBe('#ffffff')
        ->and(ColorConverter::toHex('oklch(0 0 0)'))->toBe('#000000');
});

it('resolves integer shade aliases and the literal zero', function () {
    $palette = [50 => 'oklch(0.985 0 0)', 500 => 'oklch(0.5 0.1 200)', 600 => 500, 900 => 0];

    expect(ColorConverter::resolveShade($palette, 600))->toBe('oklch(0.5 0.1 200)')
        ->and(ColorConverter::resolveShade($palette, 900))->toBe('oklch(1 0 0)')
        ->and(ColorConverter::resolveShade($palette, 50))->toBe('oklch(0.985 0 0)')
        ->and(ColorConverter::resolveShade($palette, 123))->toBeNull();
});

it('resolves booted panel colors to hex', function () {
    bootPanel();

    expect(ColorConverter::panelColorToHex('primary', 600))->toMatch('/^#[0-9a-f]{6}$/')
        ->and(ColorConverter::panelColorToHex('aliased', 600))->toMatch('/^#[0-9a-f]{6}$/')
        ->and(ColorConverter::panelColorToHex('aliased', 900))->toBe('#ffffff')
        ->and(ColorConverter::panelColorToHex('missing-name', 600))->toBeNull();

    expect(FilamentColor::getColor('primary'))->toBeArray();
});
