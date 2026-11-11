{{-- Lets pwa:install capture dark-mode screenshots: ?pwa-theme=dark|light is
     written to localStorage before Filament's own theme bootstrap reads it.
     Filament reads `filament:theme:mode` since v4.15.3 / v5.11.0 and `theme`
     before that, so both keys are written.
     Debug-only so the query parameter is inert in production. --}}
@if (app()->hasDebugModeEnabled())
    <script>
        (() => {
            const theme = new URLSearchParams(window.location.search).get('pwa-theme');

            if (theme === 'dark' || theme === 'light') {
                localStorage.setItem('filament:theme:mode', theme);
                localStorage.setItem('theme', theme);
            }
        })();
    </script>
@endif
