<?php

namespace Blemli\Pwa\Support;

use Blemli\Pwa\PwaPlugin;
use Filament\Panel;

/**
 * Builds the web app manifest for a panel at request time, entirely from
 * derivable facts: panel branding, registered colors, the generated icon and
 * screenshot inventory, and the choices persisted by pwa:install.
 */
class ManifestBuilder
{
    public function __construct(
        protected Panel $panel,
        protected PwaPlugin $plugin,
    ) {}

    public static function for(Panel $panel): self
    {
        /** @var PwaPlugin $plugin */
        $plugin = $panel->getPlugin('pwa-for-filament');

        return new self($panel, $plugin);
    }

    /** @return array<string, mixed> */
    public function build(): array
    {
        $base = $this->basePath();
        [$themeColor] = $this->plugin->getThemeColors($this->panel);

        $manifest = [
            'id' => $base,
            'name' => $this->plugin->getName($this->panel),
            'short_name' => $this->plugin->getShortName($this->panel),
            'description' => $this->plugin->getDescription(),
            'start_url' => $base,
            'scope' => $base,
            'display' => 'standalone',
            'display_override' => ['standalone', 'minimal-ui'],
            'orientation' => 'any',
            'lang' => str_replace('_', '-', app()->getLocale()),
            'dir' => 'auto',
            'theme_color' => $themeColor,
            'background_color' => $this->plugin->getBackgroundColor(),
            'icons' => $this->icons(),
            'shortcuts' => $this->shortcuts(),
            'screenshots' => $this->screenshots(),
            'categories' => $this->plugin->getCategories($this->panel),
            'share_target' => $this->shareTarget($base),
            'launch_handler' => ['client_mode' => 'navigate-existing'],
            'prefer_related_applications' => false,
        ];

        return array_filter($manifest, fn (mixed $value): bool => $value !== null && $value !== []);
    }

    protected function basePath(): string
    {
        $path = trim($this->panel->getPath(), '/');

        return $path === '' ? '/' : "/{$path}/";
    }

    /** @return array<array<string, string>> */
    protected function icons(): array
    {
        $panelId = $this->panel->getId();
        $directory = InstallState::directory($panelId);

        $icons = [];

        if (is_file("{$directory}/icon.svg")) {
            $icons[] = [
                'src' => asset("pwa/{$panelId}/icon.svg"),
                'sizes' => 'any',
                'type' => 'image/svg+xml',
            ];
        }

        foreach ([192, 512] as $size) {
            if (is_file("{$directory}/icon-{$size}.png")) {
                $icons[] = [
                    'src' => asset("pwa/{$panelId}/icon-{$size}.png"),
                    'sizes' => "{$size}x{$size}",
                    'type' => 'image/png',
                    'purpose' => 'any',
                ];
            }

            if (is_file("{$directory}/icon-maskable-{$size}.png")) {
                $icons[] = [
                    'src' => asset("pwa/{$panelId}/icon-maskable-{$size}.png"),
                    'sizes' => "{$size}x{$size}",
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ];
            }
        }

        return $icons;
    }

    /** @return array<array<string, string>> */
    protected function shortcuts(): array
    {
        $shortcuts = [];

        foreach (array_slice($this->panel->getResources(), 0, 4) as $resource) {
            $shortcut = rescue(fn (): array => [
                'name' => (string) $resource::getPluralModelLabel(),
                'url' => $resource::getUrl(panel: $this->panel->getId()),
            ], report: false);

            if (filled($shortcut['name'] ?? null) && filled($shortcut['url'] ?? null)) {
                $shortcuts[] = $shortcut;
            }
        }

        return $shortcuts;
    }

    /** @return array<array<string, string>> */
    protected function screenshots(): array
    {
        $panelId = $this->panel->getId();
        $directory = InstallState::directory($panelId) . '/screenshots';

        $screenshots = [];

        foreach (glob("{$directory}/*.png") ?: [] as $file) {
            $dimensions = rescue(fn (): array | false => getimagesize($file), false, report: false);

            if ($dimensions === false) {
                continue;
            }

            [$width, $height] = $dimensions;
            $name = basename($file);
            $wide = str_starts_with($name, 'wide-') || (! str_starts_with($name, 'narrow-') && $width >= $height);

            $screenshots[] = [
                'src' => asset("pwa/{$panelId}/screenshots/{$name}"),
                'sizes' => "{$width}x{$height}",
                'type' => 'image/png',
                'form_factor' => $wide ? 'wide' : 'narrow',
                'label' => $wide
                    ? __('pwa-for-filament::pwa.manifest.screenshot_desktop')
                    : __('pwa-for-filament::pwa.manifest.screenshot_mobile'),
            ];
        }

        return $screenshots;
    }

    /** @return array<string, mixed> | null */
    protected function shareTarget(string $base): ?array
    {
        $target = $this->plugin->getShareTarget($this->panel);

        if ($target === null) {
            return null;
        }

        return [
            'action' => "{$base}pwa/share",
            'method' => 'POST',
            'enctype' => 'multipart/form-data',
            'params' => [
                'title' => 'title',
                'text' => 'text',
                'url' => 'url',
                'files' => [
                    [
                        'name' => 'file',
                        'accept' => $target['accept'] ?? ['*/*'],
                    ],
                ],
            ],
        ];
    }
}
