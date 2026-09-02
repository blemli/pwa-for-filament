@php
    use Blemli\Pwa\Support\ColorConverter;

    $panelId = $panel->getId();
    $appName = $plugin->getName($panel);
    $hasIcon = file_exists(public_path("pwa/{$panelId}/icon-192.png"));

    // Every color is resolved from the panel's registered palettes at render
    // time, so the page follows whatever theme each app configures. Fallbacks
    // only cover panels whose colors are not booted.
    $color = fn (string $name, int $shade, string $fallback): string => ColorConverter::panelColorToHex($name, $shade) ?? $fallback;

    $hasDarkMode = $panel->hasDarkMode();
    $darkModeForced = $panel->hasDarkModeForced();
@endphp
<!DOCTYPE html>
{{-- Standalone by design: this page is precached and shown without any other
     asset being reachable, so all styling is inline. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ __('pwa-for-filament::pwa.offline.title') }} - {{ $appName }}</title>
        @if ($hasDarkMode && ! $darkModeForced)
            {{-- Same theme resolution as Filament's own bootstrap: the user's
                 in-app choice from localStorage, falling back to the OS. --}}
            <script>
                const theme = localStorage.getItem('theme');

                if (theme === 'dark' || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            </script>
        @endif
        <style>
            :root {
                color-scheme: light;
                --bg: {{ $color('gray', 50, '#fafafa') }};
                --surface: #ffffff;
                --text: {{ $color('gray', 950, '#09090b') }};
                --muted: {{ $color('gray', 500, '#71717a') }};
                --ring: {{ $color('gray', 950, '#09090b') }}0d;
                --btn-bg: {{ $color('primary', 600, '#18181b') }};
                --btn-bg-hover: {{ $color('primary', 500, '#27272a') }};
            }

            @if ($hasDarkMode)
            {{ $darkModeForced ? ':root' : '.dark' }} {
                color-scheme: dark;
                --bg: {{ $color('gray', 950, '#09090b') }};
                --surface: {{ $color('gray', 900, '#18181b') }};
                --text: #ffffff;
                --muted: {{ $color('gray', 400, '#a1a1aa') }};
                --ring: rgba(255, 255, 255, 0.1);
            }
            @endif

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                display: grid;
                place-items: center;
                background: var(--bg);
                color: var(--text);
                font-family: {{ $panel->getFontFamily() }}, ui-sans-serif, system-ui, -apple-system, sans-serif;
                padding: 1.5rem;
            }

            main {
                background: var(--surface);
                border-radius: 0.75rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 0 0 1px var(--ring);
                padding: 2.5rem;
                max-width: 24rem;
                text-align: center;
                display: grid;
                gap: 1rem;
                justify-items: center;
            }

            img {
                width: 4rem;
                height: 4rem;
                border-radius: 0.75rem;
            }

            h1 {
                font-size: 1.125rem;
                font-weight: 600;
                margin: 0;
            }

            p {
                font-size: 0.875rem;
                color: var(--muted);
                margin: 0;
            }

            button {
                appearance: none;
                border: 0;
                border-radius: 0.5rem;
                background: var(--btn-bg);
                color: #ffffff;
                font: inherit;
                font-size: 0.875rem;
                font-weight: 500;
                padding: 0.5rem 0.75rem;
                cursor: pointer;
                transition: background-color 75ms;
            }

            button:hover {
                background: var(--btn-bg-hover);
            }
        </style>
    </head>
    <body>
        <main>
            @if ($hasIcon)
                <img src="{{ asset("pwa/{$panelId}/icon-192.png") }}" alt="" />
            @endif

            <h1>{{ __('pwa-for-filament::pwa.offline.title') }}</h1>
            <p>{{ $plugin->getOfflineMessage() }}</p>

            <button type="button" onclick="window.location.reload()">
                {{ __('pwa-for-filament::pwa.offline.retry') }}
            </button>
        </main>
    </body>
</html>
