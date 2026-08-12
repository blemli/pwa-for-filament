<?php

namespace Blemli\Pwa\Support;

use Illuminate\Support\Facades\File;

/**
 * Choices made during pwa:install, persisted next to the generated files in
 * public/pwa/{panelId}/install.json so re-runs are idempotent and the manifest
 * can be built without any database.
 */
class InstallState
{
    public static function directory(string $panelId): string
    {
        return public_path("pwa/{$panelId}");
    }

    public static function path(string $panelId): string
    {
        return static::directory($panelId) . '/install.json';
    }

    /** @return array<string, mixed> */
    public static function read(string $panelId): array
    {
        $path = static::path($panelId);

        if (! is_file($path)) {
            return [];
        }

        return rescue(fn (): array => json_decode(File::get($path), associative: true, flags: JSON_THROW_ON_ERROR), [], report: false);
    }

    /** @param  array<string, mixed>  $state */
    public static function write(string $panelId, array $state): void
    {
        File::ensureDirectoryExists(static::directory($panelId));

        File::put(static::path($panelId), json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }
}
