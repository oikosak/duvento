@php
    $tiles = [
        'critical' => ['ribbon' => 'ribbon-rose', 'icon' => 'warm', 'mdi' => 'mdi-alert'],
        'urgent' => ['ribbon' => 'ribbon-amber', 'icon' => 'amber', 'mdi' => 'mdi-clock-alert-outline'],
        'upcoming' => ['ribbon' => 'ribbon-cool', 'icon' => 'cool', 'mdi' => 'mdi-calendar-clock'],
        'ok' => ['ribbon' => 'ribbon-mint', 'icon' => 'mint', 'mdi' => 'mdi-check-circle-outline'],
        'unknown' => ['ribbon' => '', 'icon' => '', 'mdi' => 'mdi-help-circle-outline'],
    ];
@endphp

<div>
    @php($isOwner = auth()->user()->ownsCurrentWorkspace())
    <x-page-head :title="__('app.dashboard.title')" :sub="__('app.dashboard.sub')">
        <x-ui.button wire:click="openClientForm()">
            <i class="mdi mdi-account-plus-outline me-1"></i>{{ __('app.clients.edit') }}
        </x-ui.button>
        <x-ui.button href="{{ route('assets.create') }}" wire:navigate>
            <i class="mdi mdi-plus me-1"></i>{{ __('app.assets.edit') }}
        </x-ui.button>
        @if ($isOwner)
            <x-ui.button href="{{ route('import') }}" wire:navigate>
                <i class="mdi mdi-upload-outline me-1"></i>{{ __('app.common.import') }}
            </x-ui.button>
        @endif
        <x-ui.button href="{{ route('export.assets', request()->query()) }}">
            <i class="mdi mdi-download me-1"></i>{{ __('app.common.csv') }}
        </x-ui.button>
    </x-page-head>

    @if ($remaining > 0)
        <div class="card mb-3">
            <div class="card-header d-flex align-items-start justify-content-between gap-3">
                <div class="d-flex align-items-start gap-2">
                    <i class="mdi mdi-format-list-checks fs-4 text-muted"></i>
                    <div>
                        <h5 class="mb-0">{{ __('app.dashboard.setup') }}</h5>
                        <div class="small text-muted">{{ __('app.dashboard.steps_done', ['done' => $doneCount, 'total' => count($steps)]) }}</div>
                    </div>
                </div>
                @if ($isOwner)
                    <x-ui.button href="{{ route('import') }}" wire:navigate>
                        <i class="mdi mdi-upload-outline me-1"></i>{{ __('app.nav.import') }}
                    </x-ui.button>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach ($steps as $step)
                        <div class="col-lg-6">
                            <div class="setup-step {{ $step['done'] ? 'is-done' : '' }}">
                                <i class="mdi {{ $step['done'] ? 'mdi-check-circle' : 'mdi-circle-outline' }} setup-step-mark"></i>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold">{{ $step['title'] }}</div>
                                    <div class="small text-muted">{{ $step['hint'] }}</div>
                                </div>
                                <div class="setup-step-actions">
                                    <x-ui.button icon="arrow-right" :tip="__('app.common.open')" wire:click="openStep('{{ $step['key'] }}')" />
                                    @if (! $step['done'])
                                        <button type="button" class="setup-step-skip" wire:click="markStepDone('{{ $step['key'] }}')">{{ __('app.common.mark') }}</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <a href="{{ route('assets', ['status' => 'critical']) }}" class="text-decoration-none" wire:navigate>
                <div class="card card-ribbon ribbon-rose h-100">
                    <div class="stat-tile">
                        <div class="stat-label">{{ __('app.dashboard.critical') }}</div>
                        <div class="stat-value">{{ $counts['critical'] }}</div>
                        <div class="small text-muted">{{ __('app.dashboard.critical_hint') }}</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="{{ route('assets', ['expiry' => 'missing']) }}" class="text-decoration-none" wire:navigate>
                <div class="card h-100">
                    <div class="stat-tile">
                        <div class="stat-label">{{ __('app.dashboard.no_date') }}</div>
                        <div class="stat-value">{{ $extras['missing_expiry'] }}</div>
                        <div class="small text-muted">{{ __('app.dashboard.no_date_hint') }}</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="{{ route('assets', ['owner' => 'unknown']) }}" class="text-decoration-none" wire:navigate>
                <div class="card h-100">
                    <div class="stat-tile">
                        <div class="stat-label">{{ __('app.dashboard.unknown_owner') }}</div>
                        <div class="stat-value">{{ $extras['unknown_owner'] }}</div>
                        <div class="small text-muted">{{ __('app.dashboard.unknown_owner_hint') }}</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="hero-metric hero-metric-tile h-100">
                <div class="hero-label">{{ __('app.dashboard.pulse') }}</div>
                <div class="hero-value mt-1">{{ $counts['ok'] + $counts['upcoming'] + $counts['urgent'] + $counts['critical'] }}</div>
                <div class="hero-desc mt-1">{{ __('app.dashboard.pulse_hint', ['critical' => $counts['critical']]) }}</div>
                <div class="progress-pulse mt-auto" aria-hidden="true">
                    @foreach (['ok', 'upcoming', 'urgent', 'critical'] as $key)
                        @if ($counts[$key] > 0)
                            <span class="seg-{{ $key }}" style="width: {{ ($counts[$key] / $pulseTotal) * 100 }}%"></span>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Inline @php only: a block @php here clashes with the inline @php($isOwner) above --}}
    @php($quickErrors = $errors->hasAny(['quickClientName', 'quickClientEmail', 'quickAssetClientId', 'quickAssetTypeId', 'quickAssetName', 'quickExpiresAt', 'quickRenewalMonths', 'quickRenewalCost']))
    <div class="mb-3" x-data="{ open: @js($quickErrors) }">
        <button type="button" class="btn btn-outline-secondary btn-sm" @click="open = !open" :aria-expanded="open.toString()">
            <i class="mdi" :class="open ? 'mdi-minus' : 'mdi-plus'"></i>
            {{ __('app.dashboard.quick_add') }}
        </button>

        <div x-show="open" x-cloak x-transition.opacity.duration.150ms class="mt-3">
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">{{ __('app.dashboard.quick_client') }}</h5></div>
                <div class="card-body">
                    <form wire:submit="saveQuickClient" class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <x-ui.input :label="__('app.fields.name')" wire:model="quickClientName" group-class="mb-0" />
                            @error('quickClientName') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <x-ui.input :label="__('app.fields.email')" type="email" wire:model="quickClientEmail" group-class="mb-0" />
                            @error('quickClientEmail') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <x-ui.button variant="accent" type="submit" class="w-100">{{ __('app.dashboard.add_client') }}</x-ui.button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('app.dashboard.quick_asset') }}</h5></div>
                <div class="card-body">
                    @if ($clients->isEmpty())
                        <p class="text-muted mb-0">{{ __('app.dashboard.need_client') }}</p>
                    @else
                        <form wire:submit="saveQuickAsset">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <x-ui.select :label="__('app.fields.client')" wire:model="quickAssetClientId">
                                        <option value="">{{ __('app.common.select') }}</option>
                                        @foreach ($clients as $client)
                                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                                        @endforeach
                                    </x-ui.select>
                                    @error('quickAssetClientId') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <x-ui.select :label="__('app.fields.type')" wire:model="quickAssetTypeId">
                                        <option value="">{{ __('app.common.select') }}</option>
                                        @foreach ($types as $type)
                                            <option value="{{ $type->id }}">{{ $type->displayLabel() }}</option>
                                        @endforeach
                                    </x-ui.select>
                                    @error('quickAssetTypeId') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <x-ui.input :label="__('app.fields.asset_name')" wire:model="quickAssetName" />
                                    @error('quickAssetName') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <x-ui.input :label="__('app.fields.expires')" type="date" wire:model="quickExpiresAt" />
                                </div>
                                <div class="col-md-4">
                                    <x-ui.select :label="__('app.fields.renewal_period')" wire:model="quickRenewalMonths">
                                        @foreach (\App\Models\Asset::RENEWAL_PERIODS as $months)
                                            <option value="{{ $months }}">{{ \App\Models\Asset::renewalPeriodLabel($months) }}</option>
                                        @endforeach
                                    </x-ui.select>
                                </div>
                                <div class="col-md-4">
                                    <x-ui.input :label="__('app.fields.renewal_cost').' ('.$currencyCode.')'" type="number" min="0" step="0.01" wire:model="quickRenewalCost" />
                                    @error('quickRenewalCost') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <x-ui.button variant="accent" type="submit">{{ __('app.dashboard.add_asset') }}</x-ui.button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach ($statusKeys as $key)
            <div class="col-6 col-xl">
                <button type="button" class="stat-tile-btn {{ $status === $key ? 'is-active' : '' }}" wire:click="filterStatus('{{ $key }}')">
                    <div class="card card-ribbon {{ $tiles[$key]['ribbon'] }} h-100">
                        <div class="stat-tile">
                            <div class="d-flex align-items-start justify-content-between mb-2">
                                <div class="stat-icon-wrap {{ $tiles[$key]['icon'] }}"><i class="mdi {{ $tiles[$key]['mdi'] }}"></i></div>
                            </div>
                            <div class="stat-label">{{ \App\Enums\AssetStatus::from($key)->label() }}</div>
                            <div class="stat-value">{{ $counts[$key] }}</div>
                            <div class="stat-corner"></div>
                        </div>
                    </div>
                </button>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <x-cashflow-card
                :summary="$cashflow"
                :days="$cashflowDays"
                :open="$cashflowOpen"
                toggle
            />
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card h-100">
                <div class="card-body">
                    <div class="stat-label mb-3">{{ __('app.portfolio.title') }}</div>
                    <div class="row g-4">
                        <div class="col-sm-6 col-xl-3">
                            <div class="small text-muted">{{ __('app.portfolio.annual') }}</div>
                            <div class="cashflow-amount">{{ $portfolio['annual_label'] ?: __('app.common.empty') }}</div>
                            <div class="small text-muted mt-1">{{ __('app.portfolio.annual_hint') }}</div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="small text-muted">{{ __('app.portfolio.next12') }}</div>
                            <div class="cashflow-amount">{{ $portfolio['next12_label'] ?: __('app.common.empty') }}</div>
                            <div class="small text-muted mt-1">{{ trans_choice('app.portfolio.next12_hint', $portfolio['next12_count'], ['count' => $portfolio['next12_count']]) }}</div>
                        </div>
                        <div class="col-xl-6">
                            <div class="small text-muted mb-1">{{ __('app.portfolio.top_clients') }}</div>
                            @forelse ($portfolio['top_clients'] as $row)
                                <div class="d-flex justify-content-between gap-3 py-1 {{ $loop->last ? '' : 'border-bottom' }}">
                                    <a href="{{ route('clients.show', $row['id']) }}" class="text-truncate" wire:navigate>{{ $row['name'] }}</a>
                                    <span class="fw-semibold text-nowrap">{{ $row['label'] }} <span class="text-muted small fw-normal">{{ __('app.portfolio.per_year') }}</span></span>
                                </div>
                            @empty
                                <div class="text-muted">{{ __('app.common.empty') }}</div>
                            @endforelse
                        </div>
                    </div>
                    @if ($portfolio['unpriced'] > 0)
                        <div class="small text-muted mt-3">
                            <i class="mdi mdi-information-outline me-1"></i>{{ trans_choice('app.portfolio.unpriced', $portfolio['unpriced'], ['count' => $portfolio['unpriced']]) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg">
            <x-ui.input wire:model.live.debounce.300ms="search" placeholder="{{ __('app.filters.search_assets') }}" />
        </div>
        <div class="col-lg-3">
            <x-ui.select wire:model.live="clientId">
                <option value="">{{ __('app.filters.all_clients') }}</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                @endforeach
            </x-ui.select>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5>{{ $cashflowOpen ? __('app.cashflow.title') : __('app.dashboard.upcoming') }}</h5>
            <span class="badge badge-soft-primary">{{ $assets->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('app.table.days') }}</th>
                        <th>{{ __('app.table.asset') }}</th>
                        <th>{{ __('app.table.client') }}</th>
                        <th>{{ __('app.table.type') }}</th>
                        <th class="text-nowrap">{{ __('app.table.cost') }}</th>
                        <th>{{ __('app.table.expires') }}</th>
                        @if ($cashflowOpen)
                            <th>{{ __('app.table.pays') }}</th>
                        @endif
                        <th>{{ __('app.table.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $asset)
                        <tr class="{{ $asset->status->borderClass() }}">
                            <td><span class="countdown-days">{{ $asset->days_left === null ? __('app.common.empty') : $asset->days_left }}</span></td>
                            <td>
                                <a href="{{ route('assets.show', $asset) }}" class="fw-semibold" wire:navigate>{{ $asset->name }}</a>
                            </td>
                            <td>
                                @if ($asset->client)
                                    <a href="{{ route('clients.show', $asset->client) }}" class="text-muted text-decoration-none" wire:navigate>{{ $asset->client->name }}</a>
                                @endif
                            </td>
                            <td class="text-muted">{{ $asset->assetType?->displayLabel() }}</td>
                            <td class="text-nowrap">
                                @if ($asset->renewal_cost !== null)
                                    <span class="fw-semibold">{{ \App\Support\UpcomingPayments::format($asset->currency ?: $currencyCode, $asset->renewal_cost) }}</span>
                                    <span class="text-muted small">/ {{ \App\Models\Asset::renewalPeriodLabel($asset->renewal_months ?: 12) }}</span>
                                @else
                                    <span class="text-muted">{{ __('app.common.empty') }}</span>
                                @endif
                            </td>
                            <td><code>{{ \App\Support\DisplayDate::date($asset->expires_at) ?? __('app.common.empty') }}</code></td>
                            @if ($cashflowOpen)
                                <td class="text-muted">{{ $asset->payer->label() }}</td>
                            @endif
                            <td>
                                <span class="{{ $asset->status->badgeClass() }}">
                                    <span class="{{ $asset->status->dotClass() }} me-1"></span>
                                    {{ $asset->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $cashflowOpen ? 8 : 7 }}" class="ny-list-empty">
                                @if ($extras['assets'] === 0)
                                    {{ __('app.dashboard.empty_assets') }}
                                    <a href="{{ route('assets.create') }}" class="btn btn-link p-0 align-baseline" wire:navigate>{{ __('app.common.add') }}</a>
                                    @if ($isOwner)
                                        {{ __('app.common.or') }}
                                        <a href="{{ route('import') }}" wire:navigate>{{ __('app.common.import_csv') }}</a>.
                                    @endif
                                @else
                                    {{ __('app.dashboard.empty_filtered') }}
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-ui.modal :open="$showClientForm" :title="$editingClientId ? __('app.clients.edit') : __('app.clients.new')">
        <form wire:submit="saveClient">
            <x-ui.input :label="__('app.fields.name')" wire:model="clientName" />
            @error('clientName') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
            <x-ui.input :label="__('app.fields.contact')" wire:model="clientContactName" placeholder="{{ __('app.fields.contact_placeholder') }}" />
            <x-ui.input :label="__('app.fields.email')" type="email" wire:model="clientEmail" />
            @error('clientEmail') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
            <x-ui.input :label="__('app.fields.website')" wire:model="clientWebsite" placeholder="example.com" />
            <x-ui.textarea :label="__('app.fields.notes')" wire:model="clientNotes" rows="3" />
            <div class="d-flex justify-content-end gap-2">
                <x-ui.button type="button" wire:click="closeForms">{{ __('app.common.cancel') }}</x-ui.button>
                <x-ui.button variant="accent" type="submit">{{ __('app.common.save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
