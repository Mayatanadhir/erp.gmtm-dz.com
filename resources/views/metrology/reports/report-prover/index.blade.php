<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-tool-icon name="prover" class="w-14 h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ __('Prover & Standard Gauges Hub') }}
                        </h2>
                        <x-badge variant="info" size="md" :dot="true">{{ __('Prover') }}</x-badge>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Tracking and verification of pipe provers, compact provers (SVP), and volumetric standard gauges (API MPMS Ch. 4 / ISO 7278 / OIML R 117)') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('metrology.reports.index') }}">
                    <x-secondary-button type="button" class="gap-2 text-xs">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>{{ __('Reports Hub') }}</span>
                    </x-secondary-button>
                </a>
                <a href="{{ route('metrology.reports.report-prover.create') }}">
                    <x-primary-button type="button" class="gap-2 text-xs shadow-sm shadow-brand-500/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>{{ __('New Verification') }}</span>
                    </x-primary-button>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-metrology-tabs active="reports" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-6">

                    <!-- Specialized Dashboards Switcher (Unified 4-Tab Suite) -->
                    @include('metrology.reports.partials.switcher', ['active' => 'prover'])

                    <!-- Operational Feedback Alerts -->
                    @if(session('success'))
                        <x-alert variant="success">{{ session('success') }}</x-alert>
                    @endif
                    @if(session('error'))
                        <x-alert variant="danger">{{ session('error') }}</x-alert>
                    @endif
                    @if(session('warning'))
                        <x-alert variant="warning">{{ session('warning') }}</x-alert>
                    @endif
                    @if(session('info'))
                        <x-alert variant="info">{{ session('info') }}</x-alert>
                    @endif

                    <!-- KPI Statistics Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                        <!-- Card 1: Verification Sessions -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Verification Sessions') }}
                                </p>
                                <h3 class="text-2xl font-extrabold text-brand-600 dark:text-brand-400 mt-1">
                                    {{ $stats['total_verifications'] ?? 0 }}
                                </h3>
                                <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5 font-medium">
                                    {{ $stats['compliant_verifications'] ?? 0 }} {{ __('Compliant') }}
                                </p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center border border-brand-500/20 shrink-0">
                                <x-tool-icon name="reports" class="w-6 h-6" />
                            </div>
                        </div>

                        <!-- Card 2: Pipe & Compact Provers -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Pipe & Compact Provers') }}
                                </p>
                                <h3 class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {{ $stats['total_provers'] ?? 0 }}
                                </h3>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                                    {{ __('Bidirectional / SVP') }}
                                </p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-sky-50 dark:bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center border border-sky-500/20 shrink-0">
                                <x-tool-icon name="prover" class="w-6 h-6" />
                            </div>
                        </div>

                        <!-- Card 3: Volumetric Standard Gauges -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Standard Gauges') }}
                                </p>
                                <h3 class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {{ $stats['total_gauges'] ?? 0 }}
                                </h3>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                                    {{ __('Reference Standard Gauges') }}
                                </p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center border border-amber-500/20 shrink-0">
                                <x-tool-icon name="units" class="w-6 h-6" />
                            </div>
                        </div>

                        <!-- Card 4: Repeatability Target -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Repeatability Target') }}
                                </p>
                                <h3 class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                                    {{ $stats['repeatability_target'] ?? '≤ 0.020%' }}
                                </h3>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                                    {{ __('API MPMS Ch. 4 / ISO 7278') }}
                                </p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center border border-emerald-500/20 shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Unified Global Filter -->
                    <x-global-filter
                        :action="route('metrology.reports.report-prover.index')"
                        :search-placeholder="__('Tag Instrument, Serial Number, Site...')"
                        :search-value="$filters['search'] ?? request('search')"
                        :search-width="'w-64 sm:w-80'"
                    >
                        <!-- Device Type Filter -->
                        <x-global-filter.select
                            name="type"
                            :placeholder="__('All Types')"
                            :value="$filters['type'] ?? request('type')"
                        >
                            <option value="prover" @selected(($filters['type'] ?? request('type')) === 'prover')>{{ __('Provers (Pipe / Compact)') }}</option>
                            <option value="standard_gauge" @selected(($filters['type'] ?? request('type')) === 'standard_gauge')>{{ __('Standard Gauges (Jauges)') }}</option>
                        </x-global-filter.select>

                        <!-- Site Location Filter -->
                        <x-global-filter.select
                            name="site_id"
                            :placeholder="__('All Sites')"
                            :value="$filters['site_id'] ?? request('site_id')"
                        >
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}" @selected(($filters['site_id'] ?? request('site_id')) == $site->id)>
                                    {{ $site->short_name ?? $site->full_name }}{{ $site->site_code ? ' (' . $site->site_code . ')' : '' }}
                                </option>
                            @endforeach
                        </x-global-filter.select>

                        <!-- Operational Status Filter -->
                        <x-global-filter.select
                            name="status"
                            :placeholder="__('All Statuses')"
                            :value="$filters['status'] ?? request('status')"
                        >
                            <option value="active" @selected(($filters['status'] ?? request('status')) === 'active')>{{ __('Active') }}</option>
                            <option value="inactive" @selected(($filters['status'] ?? request('status')) === 'inactive')>{{ __('Inactive') }}</option>
                            <option value="conforme" @selected(($filters['status'] ?? request('status')) === 'conforme')>{{ __('Compliant') }}</option>
                            <option value="non_conforme" @selected(($filters['status'] ?? request('status')) === 'non_conforme')>{{ __('Non-Compliant') }}</option>
                        </x-global-filter.select>
                    </x-global-filter>

                    <!-- Interactive Dual-Tab Workspace Container -->
                    <div x-data="{ activeTab: '{{ request('tab', 'sessions') }}' }" class="space-y-4">
                        <!-- Navigation Tab Pills -->
                        <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-700/60 pb-3">
                            <button
                                type="button"
                                @click="activeTab = 'sessions'"
                                :class="activeTab === 'sessions'
                                    ? 'bg-brand-50 text-brand-700 border-brand-500 dark:bg-brand-500/10 dark:text-brand-400 dark:border-brand-500 font-bold shadow-sm'
                                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800 border-transparent font-medium'"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm rounded-lg border transition-all"
                            >
                                <x-tool-icon name="reports" class="w-4 h-4" />
                                <span>{{ __('Verification Sessions') }}</span>
                                <span class="px-2 py-0.5 text-xs rounded-full font-mono bg-brand-100 dark:bg-brand-500/20 text-brand-700 dark:text-brand-300">
                                    {{ $verifications->total() }}
                                </span>
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'instruments'"
                                :class="activeTab === 'instruments'
                                    ? 'bg-brand-50 text-brand-700 border-brand-500 dark:bg-brand-500/10 dark:text-brand-400 dark:border-brand-500 font-bold shadow-sm'
                                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800 border-transparent font-medium'"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm rounded-lg border transition-all"
                            >
                                <x-tool-icon name="prover" class="w-4 h-4" />
                                <span>{{ __('Instruments & Standards') }}</span>
                                <span class="px-2 py-0.5 text-xs rounded-full font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                    {{ $stats['total_all'] ?? count($provers) }}
                                </span>
                            </button>
                        </div>

                        <!-- Tab 1: Prover Verification Sessions Table -->
                        <div x-show="activeTab === 'sessions'">
                            <x-table>
                                <x-slot:toolbar>
                                    <div class="flex items-center justify-between gap-3 w-full">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-brand-50 dark:bg-brand-500/20 text-brand-600 dark:text-brand-400 flex items-center justify-center shrink-0 border border-brand-500/20">
                                                <x-tool-icon name="reports" class="w-5 h-5 shrink-0" />
                                            </div>
                                            <div class="flex items-baseline gap-2.5">
                                                <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">
                                                    {{ __('Calibration Certificates') }}
                                                </h3>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                                    ({{ $verifications->total() }} {{ __('sessions') }})
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            @if(!empty(array_filter($filters ?? [])))
                                                <a href="{{ route('metrology.reports.report-prover.index', ['tab' => 'sessions']) }}">
                                                    <x-secondary-button type="button" class="text-xs gap-1.5">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        <span>{{ __('Reset Filters') }}</span>
                                                    </x-secondary-button>
                                                </a>
                                            @endif
                                            <a href="{{ route('metrology.reports.report-prover.create') }}">
                                                <x-primary-button type="button" class="text-xs gap-1.5">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    <span>{{ __('New Session') }}</span>
                                                </x-primary-button>
                                            </a>
                                        </div>
                                    </div>
                                </x-slot:toolbar>

                                <x-slot:header>
                                    <x-table.th class="text-center w-12">#</x-table.th>
                                    <x-table.th>{{ __('Certificate Reference') }}</x-table.th>
                                    <x-table.th>{{ __('Calibration Date') }}</x-table.th>
                                    <x-table.th>{{ __('Prover Device') }}</x-table.th>
                                    <x-table.th>{{ __('Standard Gauge') }}</x-table.th>
                                    <x-table.th>{{ __('Site') }}</x-table.th>
                                    <x-table.th class="text-end">{{ __('Base Volume (BPV)') }}</x-table.th>
                                    <x-table.th class="text-center">{{ __('Repeatability') }}</x-table.th>
                                    <x-table.th class="text-center">{{ __('Verdict') }}</x-table.th>
                                    <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                                </x-slot:header>

                                @forelse($verifications as $vIndex => $verif)
                                    @php
                                        $vRow = ($verifications instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                                            ? $verifications->firstItem() + $vIndex
                                            : $vIndex + 1;
                                    @endphp
                                    <x-table.tr>
                                        <x-table.td class="text-center font-bold text-gray-500 dark:text-gray-400">
                                            {{ $vRow }}
                                        </x-table.td>

                                        <x-table.td>
                                            <a href="{{ route('metrology.reports.report-prover.show', $verif) }}"
                                               class="font-bold font-mono text-sm text-brand-700 dark:text-brand-400 hover:underline">
                                                {{ $verif->reference_number }}
                                            </a>
                                        </x-table.td>

                                        <x-table.td>
                                            <div class="text-xs font-semibold text-gray-900 dark:text-white">
                                                {{ $verif->calibration_date?->format('d/m/Y') ?? '---' }}
                                            </div>
                                        </x-table.td>

                                        <x-table.td>
                                            <div>
                                                <div class="font-semibold text-xs text-gray-900 dark:text-white font-mono">
                                                    {{ $verif->prover?->tag_number ?? '---' }}
                                                </div>
                                                <div class="text-[11px] text-gray-500 dark:text-gray-400 font-mono">
                                                    SN: {{ $verif->prover?->serial_number ?: '---' }}
                                                </div>
                                            </div>
                                        </x-table.td>

                                        <x-table.td>
                                            <div>
                                                <div class="font-semibold text-xs text-gray-900 dark:text-white font-mono">
                                                    {{ $verif->jauge?->tag_number ?? '---' }}
                                                </div>
                                                <div class="text-[11px] text-gray-500 dark:text-gray-400 font-mono">
                                                    SN: {{ $verif->jauge?->serial_number ?: '---' }}
                                                </div>
                                            </div>
                                        </x-table.td>

                                        <x-table.td>
                                            <div class="font-semibold text-xs text-gray-900 dark:text-white">
                                                {{ $verif->prover?->site?->short_name ?? $verif->prover?->site?->full_name ?? '---' }}
                                            </div>
                                            @if(!empty($verif->prover?->site?->site_code))
                                                <span class="text-[10px] text-gray-400 font-mono">{{ $verif->prover->site->site_code }}</span>
                                            @endif
                                        </x-table.td>

                                        <x-table.td class="text-end">
                                            <span class="font-mono font-bold text-sm text-brand-700 dark:text-brand-400">
                                                {{ number_format((float) $verif->base_prover_volume, 5) }}
                                            </span>
                                            <span class="text-xs text-gray-500">L</span>
                                        </x-table.td>

                                        <x-table.td class="text-center">
                                            <div class="space-y-0.5">
                                                <div class="font-mono font-bold text-xs {{ $verif->repeatability_percent !== null && (float)$verif->repeatability_percent <= 0.02 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                                    {{ $verif->repeatability_percent !== null ? number_format((float) $verif->repeatability_percent, 4) . '%' : '---' }}
                                                </div>
                                                <div class="text-[10px] text-gray-400">
                                                    {{ $verif->runs->count() }} {{ __('runs') }}
                                                </div>
                                            </div>
                                        </x-table.td>

                                        <x-table.td class="text-center">
                                            <x-badge :variant="$verif->is_conforme ? 'success' : 'danger'" size="sm" :dot="true">
                                                {{ $verif->is_conforme ? __('Compliant') : __('Non-Compliant') }}
                                            </x-badge>
                                        </x-table.td>

                                        <x-table.td class="text-end">
                                            <x-table.actions>
                                                <x-table.action-view href="{{ route('metrology.reports.report-prover.show', $verif) }}" title="{{ __('View Certificate') }}" />
                                                <x-table.action-edit href="{{ route('metrology.reports.report-prover.edit', $verif) }}" title="{{ __('Edit Session') }}" />
                                                <x-table.action-pdf href="{{ route('metrology.reports.report-prover.pdf', $verif) }}" target="_blank" title="{{ __('Detailed Report (PDF)') }}" />
                                                <a href="{{ route('metrology.reports.report-prover.pdf-not-emt', $verif) }}" target="_blank" class="p-1.5 text-gray-500 hover:text-amber-600 dark:text-gray-400 dark:hover:text-amber-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition" title="{{ __('Summary Report (PDF)') }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                </a>
                                                <x-table.action-delete
                                                    :actionUrl="route('metrology.reports.report-prover.destroy', $verif)"
                                                    :itemName="$verif->reference_number"
                                                    :confirmMessage="__('Are you sure you want to delete prover verification certificate :ref?', ['ref' => $verif->reference_number])"
                                                />
                                            </x-table.actions>
                                        </x-table.td>
                                    </x-table.tr>
                                @empty
                                    <x-table.empty :colspan="10" :message="__('No prover verification certificates found.')" />
                                @endforelse

                                @if($verifications instanceof \Illuminate\Contracts\Pagination\Paginator && $verifications->hasPages())
                                    <x-slot:pagination>
                                        {{ $verifications->links() }}
                                    </x-slot:pagination>
                                @endif
                            </x-table>
                        </div>

                        <!-- Tab 2: Prover & Gauges Table Suite -->
                        <div x-show="activeTab === 'instruments'">
                            <x-table>
                                <!-- Table Toolbar Slot -->
                                <x-slot:toolbar>
                                    <div class="flex items-center justify-between gap-3 w-full">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-brand-50 dark:bg-brand-500/20 text-brand-600 dark:text-brand-400 flex items-center justify-center shrink-0 border border-brand-500/20">
                                                <x-tool-icon name="prover" class="w-5 h-5 shrink-0" />
                                            </div>
                                            <div class="flex items-baseline gap-2.5">
                                                <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">
                                                    {{ __('Prover & Standard Gauges Inventory') }}
                                                </h3>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    ({{ $stats['total_all'] ?? count($provers) }} {{ __('instruments') }})
                                                </span>
                                            </div>
                                        </div>

                                        @if(!empty(array_filter($filters ?? [])))
                                            <a href="{{ route('metrology.reports.report-prover.index', ['tab' => 'instruments']) }}">
                                                <x-secondary-button type="button" class="text-xs gap-1.5">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    <span>{{ __('Reset Filters') }}</span>
                                                </x-secondary-button>
                                            </a>
                                        @endif
                                    </div>
                                </x-slot:toolbar>

                                <!-- Table Header Slot -->
                                <x-slot:header>
                                    <x-table.th class="text-center w-12">#</x-table.th>
                                    <x-table.th>{{ __('Instrument') }}</x-table.th>
                                    <x-table.th>{{ __('Type') }}</x-table.th>
                                    <x-table.th>{{ __('Site') }}</x-table.th>
                                    <x-table.th>{{ __('Specifications') }}</x-table.th>
                                    <x-table.th class="text-center">{{ __('Latest Calibration') }}</x-table.th>
                                    <x-table.th class="text-center">{{ __('Status') }}</x-table.th>
                                    <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                                </x-slot:header>

                                <!-- Table Body -->
                                @forelse($provers as $index => $prover)
                                    @php
                                        $typeValue = strtolower($prover->instrument_type instanceof \BackedEnum ? $prover->instrument_type->value : (string) $prover->instrument_type);
                                        $isProver = in_array($typeValue, ['prover', 'compact_svp'], true);
                                        $latestVerif = $prover->latestProverVerification ?? null;
                                        $statusValue = strtolower($prover->status instanceof \BackedEnum ? $prover->status->value : (string) $prover->status);
                                        $isActive = ($statusValue === 'active');
                                        $rowNumber = ($provers instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                                            ? $provers->firstItem() + $index
                                            : $index + 1;
                                    @endphp
                                    <x-table.tr>
                                        <!-- # -->
                                        <x-table.td class="text-center font-bold text-gray-500 dark:text-gray-400">
                                            {{ $rowNumber }}
                                        </x-table.td>

                                        <!-- Instrument (Tag & S/N) -->
                                        <x-table.td>
                                            <div class="flex items-center gap-3">
                                                @if(!empty($prover->image_url))
                                                    <img src="{{ $prover->image_url }}"
                                                         alt="{{ $prover->tag_number }}"
                                                         class="w-9 h-9 rounded-lg object-contain bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 p-0.5 shrink-0" />
                                                @else
                                                    <div class="w-9 h-9 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 font-bold text-xs shrink-0">
                                                        {{ strtoupper(substr($prover->tag_number, 0, 2)) }}
                                                    </div>
                                                @endif
                                                <div>
                                                    <a href="{{ route('metrology.instruments.show', $prover) }}"
                                                       class="font-bold font-mono text-sm text-brand-700 dark:text-brand-400 hover:underline">
                                                        {{ $prover->tag_number }}
                                                    </a>
                                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 font-mono mt-0.5">
                                                        SN: {{ $prover->serial_number ?: '---' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </x-table.td>

                                        <!-- Type -->
                                        <x-table.td>
                                            @if($isProver)
                                                <div class="space-y-1">
                                                    <x-badge variant="info" size="sm">
                                                        {{ __('Prover') }}
                                                    </x-badge>
                                                    @if($prover->proverSpecification?->type)
                                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">
                                                            {{ $prover->proverSpecification->type->label() }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="space-y-1">
                                                    <x-badge variant="neutral" size="sm">
                                                        {{ __('Standard Gauge') }}
                                                    </x-badge>
                                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">
                                                        {{ __('Volumetric Standard Gauge') }}
                                                    </div>
                                                </div>
                                            @endif
                                        </x-table.td>

                                        <!-- Site -->
                                        <x-table.td>
                                            @if($prover->site)
                                                <div class="flex items-center gap-1.5">
                                                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                    <div>
                                                        <div class="font-semibold text-xs text-gray-900 dark:text-white">
                                                            {{ $prover->site->short_name ?? $prover->site->full_name }}
                                                        </div>
                                                        @if(!empty($prover->site->site_code))
                                                            <span class="text-[10px] text-gray-400 font-mono">{{ $prover->site->site_code }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400 font-mono">---</span>
                                            @endif
                                        </x-table.td>

                                        <!-- Specifications -->
                                        <x-table.td>
                                            @if($isProver && $prover->proverSpecification)
                                                <div class="text-xs space-y-0.5">
                                                    @if($prover->proverSpecification->nominal_base_volume !== null)
                                                        <div class="font-mono font-semibold text-gray-900 dark:text-white">
                                                            V0: {{ number_format((float) $prover->proverSpecification->nominal_base_volume, 2) }} L
                                                        </div>
                                                    @endif
                                                    @if($prover->proverSpecification->inner_diameter || $prover->proverSpecification->wall_thickness)
                                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 font-mono">
                                                            ID: {{ $prover->proverSpecification->inner_diameter ?? '---' }} mm • WT: {{ $prover->proverSpecification->wall_thickness ?? '---' }} mm
                                                        </div>
                                                    @endif
                                                    @if(!empty($prover->proverSpecification->material))
                                                        <div class="text-[10px] text-gray-400">
                                                            {{ $prover->proverSpecification->material }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @elseif(!$isProver && $prover->standardGaugeSpecification)
                                                <div class="text-xs space-y-0.5">
                                                    @if($prover->standardGaugeSpecification->nominal_capacity_liters !== null)
                                                        <div class="font-mono font-semibold text-gray-900 dark:text-white">
                                                            Cap: {{ number_format((float) $prover->standardGaugeSpecification->nominal_capacity_liters, 2) }} L
                                                        </div>
                                                    @endif
                                                    @if(!empty($prover->standardGaugeSpecification->calibration_certificate_number))
                                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 font-mono">
                                                            Cert: {{ $prover->standardGaugeSpecification->calibration_certificate_number }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400 font-mono">---</span>
                                            @endif
                                        </x-table.td>

                                        <!-- Latest Calibration -->
                                        <x-table.td class="text-center">
                                            @if($latestVerif)
                                                <div class="space-y-1 inline-flex flex-col items-center">
                                                    <div class="text-xs font-semibold text-gray-900 dark:text-white">
                                                        {{ $latestVerif->calibration_date?->format('d/m/Y') ?? '---' }}
                                                    </div>
                                                    @if($latestVerif->repeatability_percent !== null)
                                                        <div class="text-[11px] font-mono text-gray-500 dark:text-gray-400">
                                                            Rep: {{ number_format((float) $latestVerif->repeatability_percent, 4) }}%
                                                        </div>
                                                    @endif
                                                    <x-badge :variant="$latestVerif->is_conforme ? 'success' : 'danger'" size="sm" :dot="true">
                                                        {{ $latestVerif->is_conforme ? __('Compliant') : __('Non-Compliant') }}
                                                    </x-badge>
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400 dark:text-gray-500 font-mono">
                                                    {{ __('Not Verified') }}
                                                </span>
                                            @endif
                                        </x-table.td>

                                        <!-- Status -->
                                        <x-table.td class="text-center">
                                            <x-badge :variant="$isActive ? 'success' : 'neutral'" size="sm" :dot="true">
                                                {{ $isActive ? __('Active') : __('Inactive') }}
                                            </x-badge>
                                        </x-table.td>

                                        <!-- Actions -->
                                        <x-table.td class="text-end">
                                            <x-table.actions>
                                                <x-table.action-view href="{{ route('metrology.instruments.show', $prover) }}" title="{{ __('View Instrument') }}" />
                                                <x-table.action-edit href="{{ route('metrology.instruments.edit', $prover) }}" title="{{ __('Edit Instrument') }}" />
                                            </x-table.actions>
                                        </x-table.td>
                                    </x-table.tr>
                                @empty
                                    <x-table.empty :colspan="8" :message="__('No provers or standard gauges found matching the criteria.')" />
                                @endforelse

                                <!-- Table Pagination Slot -->
                                @if($provers instanceof \Illuminate\Contracts\Pagination\Paginator && $provers->hasPages())
                                    <x-slot:pagination>
                                        {{ $provers->links() }}
                                    </x-slot:pagination>
                                @endif
                            </x-table>
                        </div>
                    </div>

                </main>
            </div>
        </div>
    </div>
</x-app-layout>
