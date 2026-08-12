# Changelog

All notable changes to `pwa-for-filament` will be documented in this file.

## v0.1.1 - 2026-08-12

- SVG logos are now rasterized via librsvg or Inkscape (called through captured processes) before falling back to Imagick — no more delegate deprecation warnings leaking into `pwa:install` output, and SVG sources now work on GD-only systems that have either tool
- Screenshot login route additionally requires a non-production environment (on top of debug mode and a signed URL)
- `pwa:install` warns that captured dashboard screenshots are publicly served and should be reviewed before deploying
- README banner shot retaken in English (mouseless demo app)

## v0.1.0 - 2026-08-12

Initial release.

- `pwa:install` turns a Filament panel (v4/v5) into an installable PWA in one command
- Web app manifest derived entirely from the panel: name, colors (theme/background from the registered palette), start URL/scope, shortcuts from resources, `display: standalone`
- Icon set (192/512, maskable, apple-touch) generated from the panel's brand logo — string paths, view closures and SVG sources supported (Imagick, GD fallback)
- Install banner: native prompt on Chromium, instructions on Firefox; dismissal remembered per browser session; auto-hidden when running standalone
- Minimal Livewire-safe service worker with a customizable, translated offline page
- App-icon badge synced with the unread database notifications count (`navigator.setAppBadge`), plus a JS/Livewire event API
- Web Share Target: share files from the OS straight into a FileUpload field picked during install
- Manifest screenshots (wide/narrow, light/dark) captured from the authenticated dashboard via headless Chrome
- W3C category picker, seven UI languages (en, de, fr, es, it, nl, zh_CN)
- `pwa:uninstall` removes every published and generated artifact

Release QA: 46 Pest tests green, PHPStan level 4, Pint clean; full browser pass against a Filament v5 SPA-mode panel (install prompt → standalone, service worker + offline precache, badge, share target with visible prefill, zero console errors).
