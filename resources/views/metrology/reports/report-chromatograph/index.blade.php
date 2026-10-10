<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-tool-icon name="chromatograph" class="w-14 h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ __('Chromatograph Verification Hub') }}
                        </h2>
                        <x-badge variant="info" size="md" :dot="true">{{ __('Chromatograph') }}</x-badge>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Instrument loop calibration tracking, OIML R 140 EMT tolerance verification, and conformity states') }}
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
                @can('create reports')
                    <a href="{{ route('metrology.reports.report-chromatograph.create') }}">
                        <x-primary-button type="button" class="gap-2 text-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>{{ __('New Verification') }}</span>
                        </x-primary-button>
                    </a>
                @endcan
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
                    @include('metrology.reports.partials.switcher', ['active' => 'chromatograph'])

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
                        <!-- Card 1: Total Verifications -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Total Sessions') }}
                                </p>
                                <h3 class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {{ $totalCount ?? 0 }}
                                </h3>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                                    {{ __('Calibrations performed') }}
                                </p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center border border-teal-500/20 shrink-0">
                                <x-tool-icon name="chromatograph" class="w-6 h-6" />
                            </div>
                        </div>

                        <!-- Card 2: Conformes -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Compliant') }}
                                </p>
                                <h3 class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">
                                    {{ $conformeCount ?? 0 }}
                                </h3>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                                    {{ __('OIML R 140 Class A') }}
                                </p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center border border-emerald-500/20 shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>

                        <!-- Card 3: Non Conformes -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Non-Compliant') }}
                                </p>
                                <h3 class="text-2xl font-extrabold text-rose-600 dark:text-rose-400 mt-1">
                                    {{ $nonConformeCount ?? 0 }}
                                </h3>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                                    {{ __('Out of tolerances') }}
                                </p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center border border-rose-500/20 shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>

                        <!-- Card 4: Standard Norms -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Compliance Norm') }}
                                </p>
                                <h3 class="text-base font-extrabold text-gray-900 dark:text-white mt-1">
                                    ASTM D 1945
                                </h3>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                                    ISO 6974 / ISO 6976
                                </p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center border border-blue-500/20 shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Unified Global Filter -->
                    <x-global-filter
                        :action="route('metrology.reports.report-chromatograph.index')"
                        :search-placeholder="__('Instrument Tag, Serial No., Cylinder...')"
                        :search-value="request('search')"
                        :search-width="'w-64 sm:w-80'"
                    >
                        <!-- Associated Mission Filter -->
                        <x-global-filter.select
                            name="mission_id"
                            :placeholder="__('Associated Mission')"
                            :value="request('mission_id')"
                        >
                            <option value="">{{ __('All Missions') }}</option>
                            <option value="standalone" @selected(request('mission_id') === 'standalone')>{{ __('Standalone (No Mission)') }}</option>
                            @foreach($missions ?? [] as $m)
                                <option value="{{ $m->id }}" @selected(request('mission_id') == $m->id)>
                                    {{ $m->reference ?? 'MS-'.$m->id }}{{ $m->site ? ' (' . ($m->site->short_name ?? $m->site->name) . ')' : '' }}
                                </option>
                            @endforeach
                        </x-global-filter.select>

                        <!-- Site Location Filter -->
                        <x-global-filter.select
                            name="site_id"
                            :placeholder="__('All Sites')"
                            :value="request('site_id')"
                        >
                            <option value="">{{ __('All Sites') }}</option>
                            @foreach($sites ?? [] as $site)
                                <option value="{{ $site->id }}" @selected(request('site_id') == $site->id)>
                                    {{ $site->short_name ?? $site->full_name }}{{ $site->site_code ? ' (' . $site->site_code . ')' : '' }}
                                </option>
                            @endforeach
                        </x-global-filter.select>

                        <!-- Conformance Status Filter -->
                        <x-global-filter.select
                            name="status"
                            :placeholder="__('All Statuses')"
                            :value="request('status')"
                        >
                            <option value="">{{ __('All Statuses') }}</option>
                            <option value="conforme" @selected(request('status') === 'conforme')>{{ __('Compliant') }}</option>
                            <option value="non_conforme" @selected(request('status') === 'non_conforme')>{{ __('Non-Compliant') }}</option>
                        </x-global-filter.select>
                    </x-global-filter>                    <!-- Table of Chromatograph Verifications -->
                    <x-table>
                        <!-- Table Card Toolbar -->
                        <x-slot:toolbar>
                            <div class="flex items-center justify-between gap-3 w-full">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-brand-50 dark:bg-brand-500/20 text-brand-600 dark:text-brand-400 flex items-center justify-center shrink-0 border border-brand-500/20">
                                        <x-tool-icon name="chromatograph" class="w-5 h-5 shrink-0" />
                                    </div>
                                    <div>
                                        <h3 class="text-base font-extrabold text-gray-900 dark:text-white">
                                            {{ __('Chromatograph Verifications List') }}
                                        </h3>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ __('Total') }} : <strong class="text-brand-700 dark:text-brand-400">{{ $totalCount ?? count($verifications) }}</strong> {{ __('sessions') }}
                                        </span>
                                    </div>
                                </div>

                                @if(request()->anyFilled(['search', 'mission_id', 'site_id', 'status']))
                                    <a href="{{ route('metrology.reports.report-chromatograph.index') }}">
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
                            <x-table.th>{{ __('Reference') }}</x-table.th>
                            <x-table.th>{{ __('Date') }}</x-table.th>
                            <x-table.th>{{ __('Site') }}</x-table.th>
                            <x-table.th>{{ __('Chromatograph') }}</x-table.th>
                            <x-table.th>{{ __('Gas Bottle') }}</x-table.th>
                            <x-table.th class="text-center">{{ __('Conformity') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                        </x-slot:header>

                        <!-- Table Body -->
                        @forelse($verifications as $index => $v)
                            @php
                                $site = $v->instrument?->site ?? $v->report?->mission?->site;
                                $rowNumber = ($verifications instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                                    ? $verifications->firstItem() + $index
                                    : $index + 1;
                            @endphp
                            <x-table.tr>
                                <!-- # -->
                                <x-table.td class="text-center font-bold text-gray-500 dark:text-gray-400">
                                    {{ $rowNumber }}
                                </x-table.td>

                                <!-- Reference -->
                                <x-table.td>
                                    <a href="{{ route('metrology.reports.report-chromatograph.show', $v->id) }}"
                                       class="font-mono font-bold text-sm text-brand-600 dark:text-brand-400 hover:underline">
                                        {{ $v->reference_number }}
                                    </a>
                                    @if($v->report?->report_number && $v->reference_number !== $v->report->report_number)
                                        <span class="block text-[11px] font-mono text-gray-400">{{ $v->report->report_number }}</span>
                                    @endif
                                </x-table.td>

                                <!-- Verification Date -->
                                <x-table.td class="font-medium">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>{{ $v->verification_date ? $v->verification_date->format('d/m/Y') : '-' }}</span>
                                    </div>
                                </x-table.td>

                                <!-- Site -->
                                <x-table.td>
                                    @if($site)
                                        <div class="font-semibold text-gray-900 dark:text-white flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-brand-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span>{{ $site->short_name ?? $site->full_name ?? $site->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </x-table.td>

                                <!-- Chromatograph -->
                                <x-table.td>
                                    <div class="flex items-center gap-2">
                                        <div class="font-bold text-gray-900 dark:text-white">
                                            {{ $v->instrument?->tag_number ?? 'N/A' }}
                                        </div>
                                        @if($v->instrument?->serial_number)
                                            <span class="text-[11px] font-mono text-gray-500 dark:text-gray-400">
                                                ({{ $v->instrument->serial_number }})
                                            </span>
                                        @endif
                                    </div>
                                </x-table.td>

                                <!-- Gas Bottle -->
                                <x-table.td class="font-mono">
                                    {{ $v->standard_gas_bottle_number ?? ($v->gas_bottle_number ?? '-') }}
                                </x-table.td>

                                <!-- Conformity Verdict -->
                                <x-table.td class="text-center">
                                    @if($v->overall_status)
                                        <x-badge variant="success" size="md" :dot="true">
                                            {{ __('COMPLIANT (OIML R 140)') }}
                                        </x-badge>
                                    @else
                                        <x-badge variant="danger" size="md" :dot="true">
                                            {{ __('Non-Compliant') }}
                                        </x-badge>
                                    @endif
                                </x-table.td>

                                <!-- Actions -->
                                <x-table.td class="text-end">
                                    <x-table.actions>
                                        {{-- Inspect / Show --}}
                                        <x-table.action-view
                                            href="{{ route('metrology.reports.report-chromatograph.show', $v->id) }}"
                                            :title="__('Inspect')"
                                        />

                                        {{-- PDF Official --}}
                                        <x-table.action-pdf
                                            href="{{ route('metrology.reports.report-chromatograph.pdf', $v->id) }}"
                                            target="_blank"
                                            :title="__('PDF Report (Official)')"
                                        />

                                        {{-- PDF Not EMT --}}
                                        <x-table.action
                                            type="pdf"
                                            href="{{ route('metrology.reports.report-chromatograph.pdf-not-emt', $v->id) }}"
                                            target="_blank"
                                            :title="__('PDF Report (Not EMT)')"
                                            class="opacity-75"
                                        />

                                        {{-- Excel / CSV Export --}}
                                        <x-table.action-excel
                                            href="{{ route('metrology.reports.report-chromatograph.excel', $v->id) }}"
                                            :title="__('Export CSV/Excel')"
                                        />

                                        {{-- Edit / Saisie --}}
                                        @can('edit reports')
                                            <x-table.action-edit
                                                href="{{ route('metrology.reports.report-chromatograph.saisie', $v->id) }}"
                                                :title="__('Edit / Saisie')"
                                            />
                                        @endcan

                                        {{-- Delete --}}
                                        @can('delete reports')
                                            <x-table.action-delete
                                                :action-url="route('metrology.reports.report-chromatograph.destroy', $v->id)"
                                                :confirm-message="__('Are you sure you want to delete this chromatograph verification session?')"
                                            />
                                        @endcan
                                    </x-table.actions>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty :colspan="8" :message="__('No chromatograph verification records found')">
                                @can('create reports')
                                    <div class="mt-3">
                                        <a href="{{ route('metrology.reports.report-chromatograph.create') }}">
                                            <x-primary-button type="button" class="text-xs">
                                                {{ __('New Verification') }}
                                            </x-primary-button>
                                        </a>
                                    </div>
                                @endcan
                            </x-table.empty>
                        @endforelse

                        <!-- Pagination Footer -->
                        @if($verifications instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $verifications->hasPages())
                            <x-slot:pagination>
                                {{ $verifications->links() }}
                            </x-slot:pagination>
                        @endif
                    </x-table>

                </main>
            </div>
        </div>
    </div>
</x-app-layout>
