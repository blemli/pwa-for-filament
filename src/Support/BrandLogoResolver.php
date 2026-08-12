<?php

namespace Blemli\Pwa\Support;

use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\File;

/**
 * Finds the image file behind a panel's branding, however it was configured:
 * an explicit override, a ->brandLogo() given as URL/path or as a rendered
 * view (first <img src> or inline <svg>), or the ->favicon().
 */
class BrandLogoResolver
{
    /** @return array{path: string, svg: bool} | null */
    public static function resolve(Panel $panel, ?string $override = null): ?array
    {
        foreach (static::candidates($panel, $override) as $candidate) {
            if (blank($candidate)) {
                continue;
            }

            $path = static::localize($candidate);

            if ($path !== null) {
                return ['path' => $path, 'svg' => static::isSvg($path)];
            }
        }

        return null;
    }

    /** @return iterable<?string> */
    protected static function candidates(Panel $panel, ?string $override): iterable
    {
        yield $override;

        $logo = rescue(fn (): string | Htmlable | null => $panel->getBrandLogo(), report: false);

        if (is_string($logo)) {
            yield $logo;
        } elseif ($logo instanceof Htmlable) {
            $html = rescue(fn (): string => $logo->toHtml(), report: false);

            if (filled($html)) {
                yield static::imageSourceFromHtml($html);
                yield static::inlineSvgFromHtml($html);
            }
        }

        yield rescue(fn (): ?string => $panel->getFavicon(), report: false);
    }

    protected static function imageSourceFromHtml(string $html): ?string
    {
        return preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches) ? html_entity_decode($matches[1]) : null;
    }

    /**
     * Extracts an inline <svg> into a temp file so the icon generator can
     * treat it like any other SVG source.
     */
    protected static function inlineSvgFromHtml(string $html): ?string
    {
        if (! preg_match('/<svg\b.*?<\/svg>/is', $html, $matches)) {
            return null;
        }

        $path = tempnam(sys_get_temp_dir(), 'pwa-logo-') . '.svg';

        File::put($path, $matches[0]);

        return $path;
    }

    /** Map a URL or path onto an existing local file. */
    protected static function localize(string $source): ?string
    {
        $source = html_entity_decode(trim($source));

        if (preg_match('#^(https?:)?//#i', $source)) {
            $source = parse_url($source, PHP_URL_PATH) ?? '';
        }

        $source = explode('?', $source)[0];

        if ($source === '') {
            return null;
        }

        foreach ([$source, public_path(ltrim($source, '/'))] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    protected static function isSvg(string $path): bool
    {
        if (str_ends_with(strtolower($path), '.svg')) {
            return true;
        }

        return str_contains(strtolower((string) File::get($path)), '<svg');
    }
}
