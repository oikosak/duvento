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

            <section class="landing-hero">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-6">
                        <span class="landing-eyebrow"><i class="mdi mdi-radar"></i> {{ __('app.landing.eyebrow') }}</span>
                        <h1>{{ __('app.landing.headline') }}</h1>
                        <p class="landing-lead">{{ __('app.landing.lead') }}</p>

                        <div class="landing-actions">
                            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">
                                {{ __('app.landing.open_client') }} <i class="mdi mdi-arrow-right ms-1"></i>
                            </a>
                            @if ($showAdmin)
                                <a href="{{ $adminUrl }}" class="btn btn-outline-secondary btn-lg">
                                    {{ __('app.landing.open_admin') }}
                                </a>
                            @endif
                        </div>

                        <div class="landing-proof">
                            <span><i class="mdi mdi-check-circle"></i>{{ __('app.landing.proof_email') }}</span>
                            <span><i class="mdi mdi-check-circle"></i>{{ __('app.landing.proof_ssl') }}</span>
                            <span><i class="mdi mdi-check-circle"></i>{{ __('app.landing.proof_csv') }}</span>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="landing-preview card">
                            <div class="card-header">
                                <div>
                                    <span class="landing-preview-label">{{ __('app.landing.preview_label') }}</span>
                                    <h5>{{ __('app.landing.preview_title') }}</h5>
                                </div>
                                <span class="badge badge-soft-primary">{{ __('app.landing.demo_badge') }}</span>
                            </div>
                            <div class="list-group list-group-flush">
                                @foreach ($assets as $asset)
                                    <x-asset-countdown :asset="$asset" />
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="portal-section" aria-labelledby="portal-heading">
                <div class="section-heading">
                    <span class="landing-eyebrow">{{ __('app.landing.portals_eyebrow') }}</span>
                    <h2 id="portal-heading">{{ __('app.landing.portals_title') }}</h2>
                    <p>{{ __('app.landing.portals_sub') }}</p>
                </div>

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
