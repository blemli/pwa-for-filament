<?php

namespace Blemli\Pwa\Support;

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\View\Components\ButtonComponent;
use Throwable;

class ColorConverter
{
    /**
     * The exact colors Filament's own solid-button resolver picks for a
     * registered palette — including the WCAG-contrast text shade and the
     * pale-palette background fallbacks (e.g. yellow buttons get dark text).
     * Only meaningful once the panel has booted.
     *
     * @return array{bg: string, text: string, hoverBg: string, hoverText: string, darkBg: string, darkText: string, darkHoverBg: string, darkHoverText: string} | null
     */
    public static function buttonColors(string $name): ?array
    {
        $palette = rescue(fn (): ?array => FilamentColor::getColor($name), report: false);

        if (blank($palette)) {
            return null;
        }

        $map = rescue(fn (): array => ButtonComponent::make()->getColorMap($palette), report: false);

        if (blank($map)) {
            return null;
        }

        // Shade 0 means white in Filament's color maps.
        $hex = fn (int $shade): ?string => $shade === 0
            ? '#ffffff'
            : static::toHex(static::resolveShade($palette, $shade));

        $colors = [
            'bg' => $hex($map['bg'] ?? 600),
            'text' => $hex($map['text'] ?? 0),
            'hoverBg' => $hex($map['hover:bg'] ?? 500),
            'hoverText' => $hex($map['hover:text'] ?? 0),
            'darkBg' => $hex($map['dark:bg'] ?? 600),
            'darkText' => $hex($map['dark:text'] ?? 0),
            'darkHoverBg' => $hex($map['dark:hover:bg'] ?? 500),
            'darkHoverText' => $hex($map['dark:hover:text'] ?? 0),
        ];

        return in_array(null, $colors, true) ? null : $colors;
    }

    /**
     * Hex value of a registered panel color shade, e.g. primary-600.
     * Only meaningful once the panel has booted (request or bootCurrentPanel()).
     */
    public static function panelColorToHex(string $name, int $shade): ?string
    {
        $palette = rescue(fn (): ?array => FilamentColor::getColor($name), report: false);

        if (blank($palette)) {
            return null;
        }

        return static::toHex(static::resolveShade($palette, $shade));
    }

    /**
     * Resolve a shade from a Filament palette, following integer aliases
     * (e.g. 600 => 500) and the literal 0 (white), like Filament's own
     * AssetManager::resolveColorShadeFromPalette().
     *
     * @param  array<int | string, string | int>  $palette
     */
    public static function resolveShade(array $palette, int | string $shade): ?string
    {
        $color = $palette[$shade] ?? null;

        while ($color !== null && ! is_string($color)) {
            if ($color === 0) {
                return 'oklch(1 0 0)';
            }

            $color = $palette[$color] ?? null;
        }

        return $color;
    }

    /**
     * Any CSS color Filament works with (hex, rgb(), oklch(), "r, g, b") to #rrggbb.
     * Color::convertToHex() exists only in Filament v5, so this stays on
     * convertToRgb(), which both v4 and v5 ship.
     */
    public static function toHex(?string $color): ?string
    {
        if (blank($color)) {
            return null;
        }

        $color = trim($color);

        if (str_starts_with($color, '#')) {
            if (preg_match('/^#([0-9a-f]{3})$/i', $color, $matches)) {
                return '#' . strtolower($matches[1][0] . $matches[1][0] . $matches[1][1] . $matches[1][1] . $matches[1][2] . $matches[1][2]);
            }

            return preg_match('/^#[0-9a-f]{6}$/i', $color) ? strtolower($color) : null;
        }

        try {
            if (! str_starts_with($color, 'rgb')) {
                $color = Color::convertToRgb($color);
            }

            if (preg_match('/(\d+(?:\.\d+)?)[,\s]+(\d+(?:\.\d+)?)[,\s]+(\d+(?:\.\d+)?)/', $color, $matches)) {
                return sprintf(
                    '#%02x%02x%02x',
                    min(255, max(0, (int) round((float) $matches[1]))),
                    min(255, max(0, (int) round((float) $matches[2]))),
                    min(255, max(0, (int) round((float) $matches[3]))),
                );
            }
        } catch (Throwable) {
            // Unparseable color — the caller falls back to its default.
        }

        return null;
    }
}
