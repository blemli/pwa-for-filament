@php
    $panelId = $panel->getId();
    $appName = $plugin->getName($panel);
    $hasIcon = file_exists(public_path("pwa/{$panelId}/icon-192.png"));
    [$lightThemeColor] = $plugin->getThemeColors($panel);
@endphp
<!DOCTYPE html>
{{-- Standalone by design: this page is precached and shown without any other
     asset being reachable, so all styling is inline. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ __('pwa-for-filament::pwa.offline.title') }} - {{ $appName }}</title>
        <style>
            :root {
                color-scheme: light dark;
                --bg: #fafafa;
                --surface: #ffffff;
                --text: #18181b;
                --muted: #71717a;
                --ring: rgba(9, 9, 11, 0.1);
                --accent: {{ $lightThemeColor ?? '#18181b' }};
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    --bg: #09090b;
                    --surface: #18181b;
                    --text: #fafafa;
                    --muted: #a1a1aa;
                    --ring: rgba(255, 255, 255, 0.1);
                }
            }

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
                font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
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
                background: var(--accent);
                color: #ffffff;
                font: inherit;
                font-size: 0.875rem;
                font-weight: 600;
                padding: 0.5rem 1rem;
                cursor: pointer;
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
