/* pwa-for-filament client runtime: service worker registration, install
 * banner (native prompt on Chromium, instructions on Firefox), and app icon
 * badging. Plain JS, no build step. */
(() => {
    const configEl = document.getElementById('pwa-config');

    if (!configEl) return;

    const cfg = JSON.parse(configEl.textContent);

    // --- Service worker ---------------------------------------------------
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register(cfg.swUrl, { scope: cfg.scope }).catch(() => {});
    }

    // --- Badging ----------------------------------------------------------
    const applyBadge = (count) => {
        if (!('setAppBadge' in navigator)) return;

        count > 0
            ? navigator.setAppBadge(count).catch(() => {})
            : navigator.clearAppBadge().catch(() => {});
    };

    const readBadge = () => {
        if (!cfg.badge) return;

        const el = document.getElementById('pwa-badge-count');

        if (el) applyBadge(parseInt(el.dataset.count || '0', 10));
    };

    window.Pwa = { setBadge: applyBadge, clearBadge: () => applyBadge(0) };
    window.addEventListener('pwa:badge', (event) => applyBadge((event.detail && event.detail.count) || 0));
    document.addEventListener('livewire:navigated', readBadge);
    readBadge();

    // --- Install banner ---------------------------------------------------
    if (!cfg.banner.enabled) return;

    const COOKIE = 'pwa_banner_dismissed_' + cfg.panelId;
    const isDismissed = () => document.cookie.split('; ').some((cookie) => cookie.startsWith(COOKIE + '='));
    const isStandalone = () =>
        window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    // Session cookie (no expiry): dismissal is shared across tabs and
    // forgotten when the browser closes.
    const rememberDismissal = () => {
        document.cookie = COOKIE + '=1; path=' + (cfg.scope || '/') + '; SameSite=Lax';
    };

    let deferredPrompt = null;
    let visibleVariant = null;

    const banner = () => document.getElementById('pwa-install-banner');

    const hide = () => {
        visibleVariant = null;

        const el = banner();

        if (el) el.hidden = true;
    };

    const dismiss = () => {
        rememberDismissal();
        hide();
    };

    const show = (variant, delay) => {
        if (isDismissed() || isStandalone()) return;

        visibleVariant = variant;

        setTimeout(() => {
            const el = banner();

            if (!el || visibleVariant !== variant) return;

            el.querySelectorAll('[data-pwa-variant]').forEach((section) => {
                section.hidden = section.dataset.pwaVariant !== variant;
            });
            el.hidden = false;
        }, delay);
    };

    // Livewire SPA navigation swaps the body, so (re-)bind through delegation.
    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-pwa-dismiss]')) dismiss();

        if (event.target.closest('[data-pwa-install]') && deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.finally(() => {
                deferredPrompt = null;
                dismiss();
            });
        }
    });

    window.addEventListener('appinstalled', dismiss);

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        show('native', cfg.banner.delay);
    });

    const isFirefox = navigator.userAgent.includes('Firefox') && !('onbeforeinstallprompt' in window);

    if (isFirefox) show('firefox', cfg.banner.delay);

    // Restore visibility after SPA navigations (the swapped-in banner starts hidden).
    document.addEventListener('livewire:navigated', () => {
        if (visibleVariant) show(visibleVariant, 0);
    });
})();
