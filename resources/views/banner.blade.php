@php
    $panelId = $panel->getId();
    $appName = $plugin->getName($panel);
    $hasIcon = file_exists(public_path("pwa/{$panelId}/icon-192.png"));
@endphp

<div
    id="pwa-install-banner"
    hidden
    role="dialog"
    aria-label="{{ __('pwa-for-filament::pwa.banner.title', ['app' => $appName]) }}"
    style="position: fixed; inset-block-end: 1rem; inset-inline-end: 1rem; z-index: 40; width: min(24rem, calc(100vw - 2rem));"
>
    <section class="fi-section" style="box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25);">
        <div class="fi-section-content-ctn">
            <div class="fi-section-content" style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 1rem;">
                @if ($hasIcon)
                    <img
                        src="{{ asset("pwa/{$panelId}/icon-192.png") }}"
                        alt=""
                        style="width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; flex-shrink: 0;"
                    />
                @endif

                <div style="display: grid; gap: 0.375rem; flex: 1; min-width: 0;">
                    <h4 class="fi-section-header-heading">
                        {{ __('pwa-for-filament::pwa.banner.title', ['app' => $appName]) }}
                    </h4>

                    <div data-pwa-variant="native" hidden>
                        <p class="fi-section-header-description">
                            {{ __('pwa-for-filament::pwa.banner.description') }}
                        </p>

                        <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem;">
                            <x-filament::button size="sm" data-pwa-install>
                                {{ __('pwa-for-filament::pwa.banner.install') }}
                            </x-filament::button>

                            <x-filament::button size="sm" color="gray" outlined data-pwa-dismiss>
                                {{ __('pwa-for-filament::pwa.banner.dismiss') }}
                            </x-filament::button>
                        </div>
                    </div>

                    <div data-pwa-variant="firefox" hidden>
                        <p class="fi-section-header-description">
                            {{ __('pwa-for-filament::pwa.banner.firefox_instructions') }}
                        </p>

                        <div style="margin-top: 0.75rem;">
                            <x-filament::button size="sm" color="gray" outlined data-pwa-dismiss>
                                {{ __('pwa-for-filament::pwa.banner.dismiss') }}
                            </x-filament::button>
                        </div>
                    </div>
                </div>

                <x-filament::icon-button
                    :icon="\Filament\Support\Icons\Heroicon::XMark"
                    color="gray"
                    size="sm"
                    :label="__('pwa-for-filament::pwa.banner.dismiss')"
                    data-pwa-dismiss
                />
            </div>
        </div>
    </section>
</div>
