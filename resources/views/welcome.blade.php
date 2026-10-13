@php
    // Admin links are always shown (owner's choice); they point to the configured ADMIN_PATH.
    $showAdmin = true;
    $adminUrl = url(\App\Support\AdminPath::url());
@endphp
<x-layouts.guest :title="__('app.landing.title')">
    <main class="landing-page">
        <div class="container">
            <header class="landing-top">
                <a href="{{ url('/') }}" class="brand" aria-label="{{ __('app.brand') }}">
                    <span class="brand-mark">D</span>
                    <span class="brand-text">{{ __('app.brand') }}</span>
                </a>

                <nav class="landing-nav" aria-label="{{ __('app.landing.nav_label') }}">
                    <span class="badge badge-soft-primary text-uppercase">{{ __('app.landing.edition.'.$edition) }}</span>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="mdi mdi-account-outline me-1"></i>{{ __('app.landing.client_portal') }}
                    </a>
                    @if ($showAdmin)
                        <a href="{{ $adminUrl }}" class="btn btn-primary btn-sm">
                            <i class="mdi mdi-shield-account-outline me-1"></i>{{ __('app.landing.admin_portal') }}
                        </a>
                    @endif
                </nav>
            </header>

            <section class="portal-section" aria-label="{{ __('app.landing.nav_label') }}">
                <div class="row g-4">
                    <div class="{{ $showAdmin ? 'col-md-6' : 'col-md-8 mx-auto' }}">
                        <a href="{{ route('dashboard') }}" class="portal-card">
                            <span class="portal-icon"><i class="mdi mdi-view-dashboard-outline"></i></span>
                            <span class="portal-copy">
                                <small>{{ __('app.landing.client_kicker') }}</small>
                                <strong>{{ __('app.landing.client_portal') }}</strong>
                                <span>{{ __('app.landing.client_desc') }}</span>
                            </span>
                            <i class="mdi mdi-arrow-right portal-arrow"></i>
                        </a>
                    </div>
                    @if ($showAdmin)
                        <div class="col-md-6">
                            <a href="{{ $adminUrl }}" class="portal-card portal-card-admin">
                                <span class="portal-icon"><i class="mdi mdi-shield-crown-outline"></i></span>
                                <span class="portal-copy">
                                    <small>{{ __('app.landing.admin_kicker') }}</small>
                                    <strong>{{ __('app.landing.admin_portal') }}</strong>
                                    <span>{{ __('app.landing.admin_desc') }}</span>
                                </span>
                                <i class="mdi mdi-arrow-right portal-arrow"></i>
                            </a>
                        </div>
                    @endif
                </div>
            </section>

            <section class="landing-info">
                <div>
                    <i class="mdi mdi-server-outline"></i>
                    <span>
                        <strong>{{ __('app.landing.info_title') }}</strong>
                        {{ __('app.landing.info_text') }}
                    </span>
                </div>
                <a href="https://duvento.com" target="_blank" rel="noopener noreferrer">
                    Duvento.com <i class="mdi mdi-open-in-new"></i>
                </a>
            </section>

            <footer class="app-footer">
                <span>© {{ now()->year }} {{ __('app.brand') }}</span>
                <span>{{ __('app.landing.footer_tagline') }}</span>
            </footer>
        </div>
    </main>
</x-layouts.guest>
