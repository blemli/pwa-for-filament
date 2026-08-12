<?php

namespace Blemli\Pwa\Support;

use GdImage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Imagick;
use ImagickPixel;
use Symfony\Component\Process\ExecutableFinder;
use Throwable;

/**
 * Renders the PWA icon set from a single source image using whatever the
 * host already has: SVG sources are rasterized via librsvg, Inkscape or
 * Imagick (in that order, through captured processes so delegate warnings
 * never leak into console output), then resized with Imagick or GD.
 * No composer dependencies.
 */
class IconGenerator
{
    protected const SIZES = [192, 512];

    protected const MASKABLE_CONTENT_RATIO = 0.7;

    protected const APPLE_TOUCH_SIZE = 180;

    protected const MASTER_SIZE = 1024;

    /** @var array<string> */
    public array $generated = [];

    /** @var array<string> */
    public array $warnings = [];

    public function __construct(
        protected string $source,
        protected bool $svg,
        protected string $outputDirectory,
        protected string $background = '#ffffff',
    ) {}

    public function generate(): static
    {
        File::ensureDirectoryExists($this->outputDirectory);

        if ($this->svg) {
            File::copy($this->source, $target = "{$this->outputDirectory}/icon.svg");
            $this->generated[] = $target;

            $master = $this->rasterizeSvg();

            if ($master === null) {
                $this->warnings[] = 'The brand logo is an SVG but no rasterizer is available, so the PNG icon '
                    . 'sizes (192px and 512px are required for installability) were skipped. Install librsvg '
                    . '(rsvg-convert), Inkscape or ext-imagick and re-run, or point icons.source to a PNG.';

                return $this;
            }

            $this->source = $master;
            $this->svg = false;
        }

        if (! extension_loaded('imagick') && ! extension_loaded('gd')) {
            $this->warnings[] = 'Neither ext-imagick nor ext-gd is available — no PNG icons were generated.';

            return $this;
        }

        extension_loaded('imagick') ? $this->generateWithImagick() : $this->generateWithGd();

        return $this;
    }

    /**
     * SVG -> PNG master, preferring dedicated rasterizers called through
     * captured processes: Imagick's own SVG handling shells out to a
     * delegate whose deprecation warnings leak straight to the console.
     */
    protected function rasterizeSvg(): ?string
    {
        $target = tempnam(sys_get_temp_dir(), 'pwa-icon-') . '.png';
        $finder = new ExecutableFinder;
        $size = (string) self::MASTER_SIZE;

        if ($binary = $finder->find('rsvg-convert')) {
            $result = Process::timeout(60)->run([$binary, '--keep-aspect-ratio', '-w', $size, '-h', $size, '-o', $target, $this->source]);

            if ($result->successful() && is_file($target) && filesize($target) > 0) {
                return $target;
            }
        }

        if ($binary = $finder->find('inkscape')) {
            $result = Process::timeout(60)->run([$binary, '--export-type=png', "--export-filename={$target}", '-w', $size, $this->source]);

            if ($result->successful() && is_file($target) && filesize($target) > 0) {
                return $target;
            }
        }

        if (extension_loaded('imagick')) {
            try {
                $master = $this->imagickMaster();
                $master->writeImage($target);
                $master->destroy();

                return $target;
            } catch (Throwable) {
                // No delegate able to read SVG — fall through to the warning.
            }
        }

        return null;
    }

    protected function generateWithImagick(): void
    {
        $master = $this->imagickMaster();

        foreach (self::SIZES as $size) {
            $this->writeImagick($this->imagickCanvas($master, $size, 1.0, transparent: true), "icon-{$size}.png");
            $this->writeImagick($this->imagickCanvas($master, $size, self::MASKABLE_CONTENT_RATIO, transparent: false), "icon-maskable-{$size}.png");
        }

        $this->writeImagick($this->imagickCanvas($master, self::APPLE_TOUCH_SIZE, 0.8, transparent: false), 'apple-touch-icon.png');

        $master->destroy();
    }

    protected function imagickMaster(): Imagick
    {
        $master = new Imagick;
        $master->setBackgroundColor(new ImagickPixel('transparent'));

        if ($this->svg) {
            $probe = new Imagick;
            $probe->setBackgroundColor(new ImagickPixel('transparent'));
            $probe->readImage($this->source);
            $natural = max($probe->getImageWidth(), $probe->getImageHeight(), 1);
            $probe->destroy();

            $master->setResolution(
                ceil(96 * self::MASTER_SIZE / $natural),
                ceil(96 * self::MASTER_SIZE / $natural),
            );
        }

        $master->readImage($this->source);
        $master->setImageFormat('png32');

        return $master;
    }

    /** Scale the master to $contentRatio of a square canvas, centered. */
    protected function imagickCanvas(Imagick $master, int $size, float $contentRatio, bool $transparent): Imagick
    {
        $canvas = new Imagick;
        $canvas->newImage($size, $size, new ImagickPixel($transparent ? 'transparent' : $this->background));
        $canvas->setImageFormat('png32');

        $content = clone $master;
        $box = max(1, (int) floor($size * $contentRatio));
        $content->resizeImage($box, $box, Imagick::FILTER_LANCZOS, 1, bestfit: true);

        $canvas->compositeImage(
            $content,
            Imagick::COMPOSITE_OVER,
            (int) round(($size - $content->getImageWidth()) / 2),
            (int) round(($size - $content->getImageHeight()) / 2),
        );

        $content->destroy();

        return $canvas;
    }

    protected function writeImagick(Imagick $image, string $filename): void
    {
        $image->writeImage($target = "{$this->outputDirectory}/{$filename}");
        $image->destroy();

        $this->generated[] = $target;
    }

    protected function generateWithGd(): void
    {
        $master = $this->gdMaster();

        if ($master === null) {
            return;
        }

        foreach (self::SIZES as $size) {
            $this->writeGd($this->gdCanvas($master, $size, 1.0, transparent: true), "icon-{$size}.png");
            $this->writeGd($this->gdCanvas($master, $size, self::MASKABLE_CONTENT_RATIO, transparent: false), "icon-maskable-{$size}.png");
        }

        $this->writeGd($this->gdCanvas($master, self::APPLE_TOUCH_SIZE, 0.8, transparent: false), 'apple-touch-icon.png');
    }

    protected function gdMaster(): ?GdImage
    {
        $mime = File::mimeType($this->source) ?: '';

        $image = match (true) {
            str_contains($mime, 'png') => imagecreatefrompng($this->source),
            str_contains($mime, 'jpeg') => imagecreatefromjpeg($this->source),
            str_contains($mime, 'gif') => imagecreatefromgif($this->source),
            str_contains($mime, 'webp') && function_exists('imagecreatefromwebp') => imagecreatefromwebp($this->source),
            default => false,
        };

        if ($image === false) {
            $this->warnings[] = "GD cannot read the logo source ({$mime}). Supported without Imagick: PNG, JPEG, GIF, WebP.";

            return null;
        }

        imagesavealpha($image, true);

        return $image;
    }

    protected function gdCanvas(GdImage $master, int $size, float $contentRatio, bool $transparent): GdImage
    {
        $canvas = imagecreatetruecolor($size, $size);
        imagesavealpha($canvas, true);
        imagealphablending($canvas, false);

        if ($transparent) {
            imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        } else {
            [$red, $green, $blue] = sscanf(ltrim($this->background, '#'), '%02x%02x%02x');
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, (int) $red, (int) $green, (int) $blue));
        }

        imagealphablending($canvas, true);

        $sourceWidth = imagesx($master);
        $sourceHeight = imagesy($master);
        $box = (int) floor($size * $contentRatio);
        $scale = min($box / $sourceWidth, $box / $sourceHeight);
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));

        imagecopyresampled(
            $canvas,
            $master,
            (int) round(($size - $targetWidth) / 2),
            (int) round(($size - $targetHeight) / 2),
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        );

        imagealphablending($canvas, false);

        return $canvas;
    }

    protected function writeGd(GdImage $image, string $filename): void
    {
        imagepng($image, $target = "{$this->outputDirectory}/{$filename}");

        $this->generated[] = $target;
    }
}
