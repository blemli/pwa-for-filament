@php
    $panelId = $panel->getId();
    $iconDirectory = public_path("pwa/{$panelId}");
    [$lightThemeColor, $darkThemeColor] = $plugin->getThemeColors($panel);
@endphp

<link rel="manifest" href="{{ $panel->route('pwa.manifest', absolute: false) }}" />

@if ($lightThemeColor && $darkThemeColor)
    <meta name="theme-color" content="{{ $lightThemeColor }}" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="{{ $darkThemeColor }}" media="(prefers-color-scheme: dark)" />
@elseif ($lightThemeColor)
    <meta name="theme-color" content="{{ $lightThemeColor }}" />
@endif

<meta name="mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="default" />
<meta name="apple-mobile-web-app-title" content="{{ $plugin->getShortName($panel) }}" />

@if (file_exists("{$iconDirectory}/apple-touch-icon.png"))
    <link rel="apple-touch-icon" href="{{ asset("pwa/{$panelId}/apple-touch-icon.png") }}" />
@endif

@if (blank($panel->getFavicon()))
    @if (file_exists("{$iconDirectory}/icon.svg"))
        <link rel="icon" type="image/svg+xml" href="{{ asset("pwa/{$panelId}/icon.svg") }}" />
    @elseif (file_exists("{$iconDirectory}/icon-192.png"))
        <link rel="icon" type="image/png" href="{{ asset("pwa/{$panelId}/icon-192.png") }}" />
    @endif
@endif

<script type="application/json" id="pwa-config">{!! json_encode($plugin->getClientConfig($panel), JSON_UNESCAPED_SLASHES) !!}</script>
