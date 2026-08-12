<?php

namespace Blemli\Pwa;

use Blemli\Pwa\Http\Controllers\ManifestController;
use Blemli\Pwa\Http\Controllers\OfflineController;
use Blemli\Pwa\Http\Controllers\ScreenshotLoginController;
use Blemli\Pwa\Http\Controllers\ServiceWorkerController;
use Blemli\Pwa\Http\Controllers\ShareTargetController;
use Blemli\Pwa\Support\ColorConverter;
use Blemli\Pwa\Support\InstallState;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Assets\Js;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class PwaPlugin implements Plugin
{
    use EvaluatesClosures;

    protected string | Closure | null $name = null;

    protected string | Closure | null $shortName = null;

    protected string | Closure | null $description = null;

    protected string | Closure | null $themeColor = null;

    protected string | Closure | null $backgroundColor = null;

    protected string | Closure | null $offlineMessage = null;

    /** @var array<string> | Closure | null */
    protected array | Closure | null $categories = null;

    protected string | Closure | null $iconSource = null;

    protected string | Closure | null $iconBackground = null;

    protected bool | Closure $badgeEnabled = true;

    protected bool | Closure $bannerEnabled = true;

    protected int $bannerDelay = 2000;

    protected string | Closure | null $screenshotsUrl = null;

    /** @var array{resource: class-string, field: string} | null */
    protected ?array $shareTarget = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function current(): ?static
    {
        return rescue(fn (): ?Plugin => Filament::getCurrentPanel()?->getPlugin('pwa-for-filament'), report: false); // @phpstan-ignore return.type
    }

    public function getId(): string
    {
        return 'pwa-for-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->routes(function (): void {
            Route::get('manifest.webmanifest', ManifestController::class)->name('pwa.manifest');
            Route::get('sw.js', ServiceWorkerController::class)->name('pwa.sw');
            Route::get('pwa/offline', OfflineController::class)->name('pwa.offline');
            Route::post('pwa/share', [ShareTargetController::class, 'store'])
                ->middleware('throttle:20,1')
                ->name('pwa.share');
            Route::get('pwa/share/resume', [ShareTargetController::class, 'resume'])->name('pwa.share.resume');
            Route::get('pwa/screenshot-login', ScreenshotLoginController::class)
                ->middleware('signed:relative')
                ->name('pwa.screenshot-login');
        });

        $panel->renderHook(PanelsRenderHook::HEAD_START, fn (): View => view('pwa-for-filament::theme-forcer'));

        $panel->renderHook(PanelsRenderHook::HEAD_END, fn (): View => view('pwa-for-filament::meta', [
            'plugin' => $this,
            'panel' => $panel,
        ]));

        $panel->renderHook(
            PanelsRenderHook::BODY_START,
            fn (): View | string => $this->isBannerEnabled() ? view('pwa-for-filament::banner', [
                'plugin' => $this,
                'panel' => $panel,
            ]) : '',
        );

        $panel->renderHook(PanelsRenderHook::BODY_END, fn (): string => $this->badgeCountElement($panel));

        $panel->assets([
            Js::make('pwa', __DIR__ . '/../resources/dist/pwa.js'),
        ], 'blemli/pwa-for-filament');
    }

    public function boot(Panel $panel): void
    {
        $this->exemptShareRouteFromCsrf($panel);
    }

    public function name(string | Closure | null $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function shortName(string | Closure | null $shortName): static
    {
        $this->shortName = $shortName;

        return $this;
    }

    public function description(string | Closure | null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function themeColor(string | Closure | null $color): static
    {
        $this->themeColor = $color;

        return $this;
    }

    public function backgroundColor(string | Closure | null $color): static
    {
        $this->backgroundColor = $color;

        return $this;
    }

    public function offlineMessage(string | Closure | null $message): static
    {
        $this->offlineMessage = $message;

        return $this;
    }

    /** @param  array<string> | Closure | null  $categories */
    public function categories(array | Closure | null $categories): static
    {
        $this->categories = $categories;

        return $this;
    }

    public function icon(string | Closure | null $source): static
    {
        $this->iconSource = $source;

        return $this;
    }

    public function iconBackground(string | Closure | null $color): static
    {
        $this->iconBackground = $color;

        return $this;
    }

    public function badge(bool | Closure $enabled = true): static
    {
        $this->badgeEnabled = $enabled;

        return $this;
    }

    public function banner(bool | Closure $enabled = true, int $delayMs = 2000): static
    {
        $this->bannerEnabled = $enabled;
        $this->bannerDelay = $delayMs;

        return $this;
    }

    public function screenshotsUrl(string | Closure | null $url): static
    {
        $this->screenshotsUrl = $url;

        return $this;
    }

    /** @param  class-string  $resource */
    public function shareTarget(string $resource, string $field): static
    {
        $this->shareTarget = ['resource' => $resource, 'field' => $field];

        return $this;
    }

    public function getName(Panel $panel): string
    {
        $name = $this->evaluate($this->name) ?? config('pwa-for-filament.name');

        if (filled($name)) {
            return $name;
        }

        $brandName = $panel->getBrandName();

        if ($brandName instanceof Htmlable) {
            $brandName = trim(strip_tags($brandName->toHtml()));
        }

        return filled($brandName) ? $brandName : config('app.name', 'Laravel');
    }

    public function getShortName(Panel $panel): string
    {
        return $this->evaluate($this->shortName)
            ?? config('pwa-for-filament.short_name')
            ?? Str::limit($this->getName($panel), 12, '');
    }

    public function getDescription(): ?string
    {
        return $this->evaluate($this->description) ?? config('pwa-for-filament.description');
    }

    public function getOfflineMessage(): string
    {
        return $this->evaluate($this->offlineMessage)
            ?? config('pwa-for-filament.offline_message')
            ?? __('pwa-for-filament::pwa.offline.message');
    }

    /** @return array<string> | null */
    public function getCategories(Panel $panel): ?array
    {
        $categories = $this->evaluate($this->categories)
            ?? config('pwa-for-filament.categories')
            ?? InstallState::read($panel->getId())['categories'] ?? null;

        return filled($categories) ? array_values($categories) : null;
    }

    public function getIconSource(): ?string
    {
        return $this->evaluate($this->iconSource) ?? config('pwa-for-filament.icons.source');
    }

    public function getIconBackground(): ?string
    {
        return $this->evaluate($this->iconBackground) ?? config('pwa-for-filament.icons.background');
    }

    public function isBadgeEnabled(): bool
    {
        return (bool) ($this->evaluate($this->badgeEnabled) && config('pwa-for-filament.badge.enabled', true));
    }

    public function isBannerEnabled(): bool
    {
        return (bool) ($this->evaluate($this->bannerEnabled) && config('pwa-for-filament.banner.enabled', true));
    }

    public function getBannerDelay(): int
    {
        return $this->bannerDelay !== 2000 ? $this->bannerDelay : (int) config('pwa-for-filament.banner.delay', 2000);
    }

    public function getScreenshotsUrl(): ?string
    {
        return $this->evaluate($this->screenshotsUrl) ?? config('pwa-for-filament.screenshots.url');
    }

    /** @return array{resource: class-string, field: string, accept?: array<string> | null, multiple?: bool} | null */
    public function getShareTarget(Panel $panel): ?array
    {
        return $this->shareTarget
            ?? config('pwa-for-filament.share_target')
            ?? InstallState::read($panel->getId())['share_target'] ?? null;
    }

    /**
     * Theme colors for the browser UI, as [light, dark] hex values.
     *
     * @return array{0: ?string, 1: ?string}
     */
    public function getThemeColors(Panel $panel): array
    {
        $light = $this->evaluate($this->themeColor)
            ?? config('pwa-for-filament.theme_color')
            ?? ColorConverter::panelColorToHex('primary', 600);

        $dark = $panel->hasDarkMode()
            ? (ColorConverter::panelColorToHex('gray', 900) ?? $light)
            : null;

        return [$light, $dark];
    }

    public function getBackgroundColor(): string
    {
        return $this->evaluate($this->backgroundColor)
            ?? config('pwa-for-filament.background_color')
            ?? ColorConverter::panelColorToHex('gray', 50)
            ?? '#ffffff';
    }

    /**
     * The JSON blob pwa.js reads from the page head.
     *
     * @return array<string, mixed>
     */
    public function getClientConfig(Panel $panel): array
    {
        $path = trim($panel->getPath(), '/');

        return [
            'panelId' => $panel->getId(),
            'scope' => $path === '' ? '/' : "/{$path}/",
            'swUrl' => $panel->route('pwa.sw', absolute: false),
            'badge' => $this->isBadgeEnabled(),
            'banner' => [
                'enabled' => $this->isBannerEnabled(),
                'delay' => $this->getBannerDelay(),
            ],
        ];
    }

    protected function badgeCountElement(Panel $panel): string
    {
        if (! $this->isBadgeEnabled()) {
            return '';
        }

        if (! $panel->hasDatabaseNotifications()) {
            return '';
        }

        $user = Filament::auth()->user();

        if (! $user || ! method_exists($user, 'unreadNotifications')) {
            return '';
        }

        return '<div id="pwa-badge-count" data-count="' . $user->unreadNotifications()->count() . '" hidden></div>';
    }

    protected function exemptShareRouteFromCsrf(Panel $panel): void
    {
        // The OS share sheet posts without a CSRF token. except() exists on
        // every supported Laravel; newer renames keep a class alias.
        ValidateCsrfToken::except(trim(trim($panel->getPath(), '/') . '/pwa/share', '/'));
    }
}
