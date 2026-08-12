<?php

use Blemli\Pwa\Support\IconGenerator;
use Illuminate\Support\Facades\File;

function makePngFixture(string $path, int $width = 100, int $height = 60): void
{
    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, imagecolorallocate($image, 200, 40, 40));
    imagepng($image, $path);
}

beforeEach(function () {
    $this->output = public_path('pwa/testing');
});

it('generates the full png icon set from a raster source', function () {
    makePngFixture($source = sys_get_temp_dir() . '/pwa-test-logo.png');

    $generator = (new IconGenerator($source, false, $this->output, '#112233'))->generate();

    expect($generator->warnings)->toBeEmpty();

    foreach ([192, 512] as $size) {
        expect(getimagesize("{$this->output}/icon-{$size}.png"))->toMatchArray([$size, $size])
            ->and(getimagesize("{$this->output}/icon-maskable-{$size}.png"))->toMatchArray([$size, $size]);
    }

    expect(getimagesize("{$this->output}/apple-touch-icon.png"))->toMatchArray([180, 180]);
});

it('keeps maskable icons opaque', function () {
    makePngFixture($source = sys_get_temp_dir() . '/pwa-test-logo.png');

    (new IconGenerator($source, false, $this->output, '#112233'))->generate();

    $image = imagecreatefrompng("{$this->output}/icon-maskable-192.png");
    $corner = imagecolorsforindex($image, imagecolorat($image, 2, 2));

    expect($corner['alpha'])->toBe(0)
        ->and([$corner['red'], $corner['green'], $corner['blue']])->toBe([0x11, 0x22, 0x33]);
});

it('rasterizes svg sources and keeps the svg for the manifest', function () {
    File::put($source = sys_get_temp_dir() . '/pwa-test-logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="#cd7051"/></svg>');

    $generator = (new IconGenerator($source, true, $this->output))->generate();

    expect(File::exists("{$this->output}/icon.svg"))->toBeTrue()
        ->and($generator->warnings)->toBeEmpty()
        ->and(getimagesize("{$this->output}/icon-512.png"))->toMatchArray([512, 512]);
})->skip(! extension_loaded('imagick'), 'ext-imagick not available');
