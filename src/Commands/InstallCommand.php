<?php

namespace Blemli\Pwa\Commands;

use Blemli\Pwa\PwaPlugin;
use Blemli\Pwa\Support\BrandLogoResolver;
use Blemli\Pwa\Support\ChromeFinder;
use Blemli\Pwa\Support\ColorConverter;
use Blemli\Pwa\Support\FileUploadFinder;
use Blemli\Pwa\Support\IconGenerator;
use Blemli\Pwa\Support\InstallState;
use Blemli\Pwa\Support\ScreenshotCapturer;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Throwable;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

class InstallCommand extends Command
{
    /** The W3C-listed web app manifest categories. */
    public const CATEGORIES = [
        'books', 'business', 'education', 'entertainment', 'finance', 'fitness', 'food', 'games',
        'government', 'health', 'kids', 'lifestyle', 'magazines', 'medical', 'music', 'navigation',
        'news', 'personalization', 'photo', 'politics', 'productivity', 'security', 'shopping',
        'social', 'sports', 'travel', 'utilities', 'weather',
    ];

    public $signature = 'pwa:install
        {--force : Skip prompts, reusing previous choices}
        {--panel= : The panel ID to install for}
        {--skip-screenshots : Do not capture manifest screenshots}
        {--url= : Base URL to capture screenshots from}';

    public $description = 'Turn a Filament panel into an installable PWA: icons, manifest choices, screenshots';

    public function handle(): int
    {
        $panel = $this->resolvePanel();

        if ($panel === null) {
            $this->error('No Filament panel found.');

            return self::FAILURE;
        }

        try {
            Filament::setCurrentPanel($panel);
            Filament::bootCurrentPanel();
        } catch (Throwable $exception) {
            $this->warn("Could not fully boot panel [{$panel->getId()}]: {$exception->getMessage()}");
        }

        $panelId = $panel->getId();
        $state = InstallState::read($panelId);

        $this->publishConfig();
        $this->publishAssets();

        if (! $this->generateIcons($panel, $state)) {
            return self::FAILURE;
        }

        $this->chooseShareTarget($panel, $state);
        $this->chooseCategories($state);

        if (! $this->option('skip-screenshots')) {
            $this->captureScreenshots($panel, $panelId);
        }

        $state['version'] = now()->format('YmdHis');
        InstallState::write($panelId, $state);

        $this->reportRegistration($panel);

        $this->newLine();
        $this->info("Done. Panel [{$panelId}] is now an installable PWA.");
        $this->line('  - Manifest:       ' . $panel->route('pwa.manifest', absolute: false));
        $this->line('  - Service worker: ' . $panel->route('pwa.sw', absolute: false));
        $this->line('  - Generated files under: ' . str_replace(base_path() . '/', '', InstallState::directory($panelId)));
        $this->line('  - Drop additional screenshots (PNG) into the screenshots/ folder to include them in the manifest.');

        return self::SUCCESS;
    }

    protected function resolvePanel(): ?Panel
    {
        $panels = Filament::getPanels();

        if ($panels === []) {
            return null;
        }

        if ($id = $this->option('panel')) {
            $panel = $panels[$id] ?? null;

            if ($panel === null) {
                $this->error("Unknown panel [{$id}]. Available: " . implode(', ', array_keys($panels)));
            }

            return $panel;
        }

        if (count($panels) === 1 || $this->option('force') || ! $this->input->isInteractive()) {
            return Filament::getDefaultPanel();
        }

        $id = select('Which panel should become a PWA?', array_keys($panels), default: Filament::getDefaultPanel()->getId());

        return $panels[$id];
    }

    protected function publishConfig(): void
    {
        if (file_exists(config_path('pwa-for-filament.php'))) {
            return;
        }

        $this->callSilently('vendor:publish', ['--tag' => 'pwa-for-filament-config']);
        $this->line('Published config/pwa-for-filament.php');
    }

    protected function publishAssets(): void
    {
        $this->callSilently('filament:assets');
        $this->line('Published Filament assets (public/js/blemli/pwa-for-filament/pwa.js)');
    }

    /** @param  array<string, mixed>  $state */
    protected function generateIcons(Panel $panel, array &$state): bool
    {
        $plugin = $this->plugin($panel);

        $source = BrandLogoResolver::resolve($panel, $plugin?->getIconSource());

        if ($source === null) {
            $this->error('No usable icon source found. The panel has no resolvable ->brandLogo() or ->favicon().');
            $this->line('Fix: pass one explicitly — PwaPlugin::make()->icon(public_path(\'logo.svg\')) — or set icons.source in config/pwa-for-filament.php.');

            return false;
        }

        $this->line('Icon source: ' . $source['path'] . ($source['svg'] ? ' (SVG)' : ''));

        $generator = (new IconGenerator(
            source: $source['path'],
            svg: $source['svg'],
            outputDirectory: InstallState::directory($panel->getId()),
            background: $plugin?->getIconBackground()
                ?? ColorConverter::panelColorToHex('gray', 50)
                ?? '#ffffff',
        ))->generate();

        foreach ($generator->generated as $file) {
            $this->line('  - ' . str_replace(base_path() . '/', '', $file));
        }

        foreach ($generator->warnings as $warning) {
            $this->warn($warning);
        }

        $state['icon_source'] = $source['path'];

        return true;
    }

    /** @param  array<string, mixed>  $state */
    protected function chooseShareTarget(Panel $panel, array &$state): void
    {
        if ($this->option('force') || ! $this->input->isInteractive()) {
            return; // Keep the previous choice.
        }

        $candidates = FileUploadFinder::find($panel);

        if ($candidates === []) {
            $this->line('No FileUpload fields found in this panel — skipping share target.');

            return;
        }

        $options = ['none' => 'No share target'];

        foreach ($candidates as $index => $candidate) {
            $accept = filled($candidate['accept']) ? implode(', ', $candidate['accept']) : 'any file';
            $options[(string) $index] = class_basename($candidate['resource']) . " -> {$candidate['field']} ({$accept})";
        }

        $default = 'none';

        foreach ($candidates as $index => $candidate) {
            if (($state['share_target']['resource'] ?? null) === $candidate['resource']
                && ($state['share_target']['field'] ?? null) === $candidate['field']) {
                $default = (string) $index;
            }
        }

        $choice = select(
            'Files shared to the installed app can be sent straight into a FileUpload field. Which one?',
            $options,
            default: $default,
        );

        if ($choice === 'none') {
            unset($state['share_target']);

            return;
        }

        $candidate = $candidates[(int) $choice];

        $state['share_target'] = [
            'resource' => $candidate['resource'],
            'field' => $candidate['field'],
            'accept' => $candidate['accept'],
            'multiple' => $candidate['multiple'],
        ];
    }

    /** @param  array<string, mixed>  $state */
    protected function chooseCategories(array &$state): void
    {
        if ($this->option('force') || ! $this->input->isInteractive()) {
            return; // Keep the previous choice.
        }

        $selection = multiselect(
            'Which categories describe this app? (shown in app stores and installers)',
            array_combine(self::CATEGORIES, self::CATEGORIES),
            default: array_intersect($state['categories'] ?? [], self::CATEGORIES),
            scroll: 14,
        );

        if ($selection === []) {
            unset($state['categories']);
        } else {
            $state['categories'] = array_values($selection);
        }
    }

    protected function captureScreenshots(Panel $panel, string $panelId): void
    {
        $chrome = ChromeFinder::find();

        if ($chrome === null) {
            $this->warn('No Chrome/Chromium binary found — skipping screenshots. Set PWA_CHROME_BINARY or drop PNGs into '
                . str_replace(base_path() . '/', '', InstallState::directory($panelId)) . '/screenshots/ yourself.');

            return;
        }

        [$baseUrl, $serveProcess] = $this->screenshotBaseUrl();

        if ($baseUrl === null) {
            $this->warn('Could not reach the app for screenshots (tried app.url and a temporary `artisan serve`). Use --url=.');

            return;
        }

        try {
            $capturer = new ScreenshotCapturer($chrome);

            // Target the login page directly: hitting the panel root gets
            // redirected there anyway, and the redirect drops query params.
            $targetPath = rescue(fn (): ?string => parse_url($panel->getLoginUrl() ?? '', PHP_URL_PATH), report: false)
                ?: '/' . trim($panel->getPath(), '/');
            $targetUrl = rtrim($baseUrl, '/') . $targetPath;
            $directory = InstallState::directory($panelId) . '/screenshots';

            $shots = [
                'wide-light' => [1280, 800, null],
                'narrow-light' => [390, 844, null],
            ];

            if ($panel->hasDarkMode()) {
                $shots['wide-dark'] = [1280, 800, 'dark'];
                $shots['narrow-dark'] = [390, 844, 'dark'];
            }

            foreach ($shots as $name => [$width, $height, $theme]) {
                // pwa-screenshot=1 keeps the install banner out of the shot.
                $url = $targetUrl . '?pwa-screenshot=1' . ($theme !== null ? '&pwa-theme=' . $theme : '');

                $capturer->capture($url, "{$directory}/{$name}.png", $width, $height)
                    ? $this->line("  - screenshots/{$name}.png ({$width}x{$height})")
                    : $this->warn("  - screenshots/{$name}.png failed");
            }

            if ($panel->hasDarkMode() && ! app()->hasDebugModeEnabled()) {
                $this->warn('Dark-mode screenshots need APP_DEBUG=true (the theme forcer only runs in debug mode).');
            }
        } finally {
            $serveProcess?->stop();
        }
    }

    /** @return array{0: ?string, 1: ?object} */
    protected function screenshotBaseUrl(): array
    {
        $configured = $this->option('url') ?? $this->plugin(Filament::getCurrentPanel())?->getScreenshotsUrl();

        if (filled($configured)) {
            return [$configured, null];
        }

        $appUrl = config('app.url');

        if (filled($appUrl) && rescue(fn (): bool => Http::timeout(3)->get($appUrl)->status() < 500, false, report: false)) {
            return [$appUrl, null];
        }

        $port = $this->freePort();
        $process = Process::timeout(300)->start(['php', 'artisan', 'serve', '--host=127.0.0.1', "--port={$port}"]);

        $url = "http://127.0.0.1:{$port}";

        foreach (range(1, 20) as $attempt) {
            usleep(250_000);

            if (rescue(fn (): bool => Http::timeout(2)->get($url)->status() < 500, false, report: false)) {
                $this->line("Started temporary server at {$url} for screenshots.");

                return [$url, $process];
            }
        }

        $process->stop();

        return [null, null];
    }

    protected function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        $port = (int) Str::afterLast(stream_socket_get_name($socket, false), ':');
        fclose($socket);

        return $port;
    }

    protected function reportRegistration(Panel $panel): void
    {
        if ($panel->hasPlugin('pwa-for-filament')) {
            return;
        }

        $this->newLine();
        $this->warn("PwaPlugin is not registered on panel [{$panel->getId()}]. Add it to the panel provider:");
        $this->line('    ->plugin(\Blemli\Pwa\PwaPlugin::make())');
    }

    protected function plugin(?Panel $panel): ?PwaPlugin
    {
        return rescue(fn (): ?PwaPlugin => $panel?->getPlugin('pwa-for-filament'), report: false); // @phpstan-ignore return.type
    }
}
