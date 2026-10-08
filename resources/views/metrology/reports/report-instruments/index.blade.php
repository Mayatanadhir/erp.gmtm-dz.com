<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-tool-icon name="instruments" class="w-14 h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ __('Measuring Instruments Verification Hub') }}
                        </h2>
                        <x-badge variant="info" size="md" :dot="true">{{ __('Measuring Instruments') }}</x-badge>
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
                    <a href="{{ route('metrology.reports.create', ['category' => 'instruments']) }}">
                        <x-primary-button type="button" class="gap-2 text-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>{{ __('New Report') }}</span>
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
                    @include('metrology.reports.partials.switcher', ['active' => 'instruments'])

                    <!-- Unified Global Filter -->
                    <x-global-filter
                        :action="route('metrology.reports.report-instruments.index')"
                        :search-placeholder="__('Tag Instrument, N° Série, Site...')"
                        :search-value="request('search')"
                        :search-width="'w-64 sm:w-80'"
                    >
                        <!-- Associated Mission Filter -->
                        <x-global-filter.select
                            name="mission_id"
                            :placeholder="__('Associated Mission')"
                            :value="request('mission_id')"
                        >
                            @foreach($missions as $m)
                                <option value="{{ $m->id }}" @selected(request('mission_id') == $m->id)>
                                    {{ $m->reference ?? 'MS-'.$m->id }}{{ $m->site ? ' (' . ($m->site->short_name ?? $m->site->name) . ')' : '' }}
                                </option>
                            @endforeach
                        </x-global-filter.select>

                        <!-- Instrument Type Filter -->
                        <x-global-filter.select
                            name="type"
                            :placeholder="__('All Types')"
                            :value="request('type')"
                        >
                            <option value="transmitter" @selected(request('type') === 'transmitter')>{{ __('Transmitters') }}</option>
                            <option value="probe" @selected(request('type') === 'probe')>{{ __('Pt100 Probes') }}</option>
                            <option value="flow_computer" @selected(request('type') === 'flow_computer')>{{ __('Flow Computers') }}</option>
                        </x-global-filter.select>

                        <!-- Conformance Status Filter -->
                        <x-global-filter.select
                            name="status"
                            :placeholder="__('All Statuses')"
                            :value="request('status')"
                        >
                            <option value="conforme" @selected(request('status') === 'conforme')>{{ __('Compliant') }}</option>
                            <option value="non_conforme" @selected(request('status') === 'non_conforme')>{{ __('Non-Compliant') }}</option>
                            <option value="en_cours" @selected(request('status') === 'en_cours')>{{ __('In Progress') }}</option>
                        </x-global-filter.select>
                    </x-global-filter>

                    <!-- ========================================================================= -->
                    <!-- TABLEAU DES RAPPORTS & INSTRUMENTS (ACCORDION SUITE) -->
                    <!-- ========================================================================= -->
                    @if(isset($reportGroups) && $reportGroups->isNotEmpty())
                        <div x-data="{
                            expandedRows: {
                                @foreach($reportGroups as $grp)
                                    '{{ $grp['report_id'] }}': false,
                                @endforeach
                            },
                            allExpanded: false,
                            toggleAll() {
                                this.allExpanded = !this.allExpanded;
                                for (let key in this.expandedRows) {
                                    this.expandedRows[key] = this.allExpanded;
                                }
                            },
                            toggleRow(id) {
                                this.expandedRows[id] = !this.expandedRows[id];
                            },
                            isExpanded(id) {
                                return !!this.expandedRows[id];
                            }
                        }" class="space-y-6">

                            <!-- Master Table Container -->
                            <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden">

                                <!-- Table Card Header: Title & Total Reports -->
                                <div class="p-3.5 sm:p-4 border-b border-gray-100 dark:border-gray-700/60 bg-white dark:bg-gray-800 flex items-center justify-between gap-3 flex-nowrap overflow-x-auto">
                                    <div class="flex items-center gap-3 shrink-0">
                                        <div class="w-9 h-9 rounded-xl bg-brand-50 dark:bg-brand-500/20 text-brand-600 dark:text-brand-400 flex items-center justify-center shrink-0 border border-brand-500/20">
                                            <x-tool-icon name="reports" class="w-5 h-5 shrink-0" />
                                        </div>
                                        <div class="flex items-baseline gap-2.5">
                                            <h3 class="text-lg sm:text-xl font-extrabold text-gray-900 dark:text-white">
                                                {{ __('Reports') }}
                                            </h3>
                                            <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                                <span>{{ __('Total') }} : <strong class="text-brand-700 dark:text-brand-400">{{ $totalReportsCount ?? count($reportGroups) }}</strong> {{ __('reports') }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status Tabs & Expand All Button -->
                                    <div class="flex items-center gap-2 shrink-0 flex-nowrap">
                                        <x-badge variant="warning" size="md">
                                            <span>{{ __('In Progress') }}</span>
                                            @if(($inProgressReportsCount ?? 0) > 0)
                                                <span class="ms-1 px-1.5 py-0.2 rounded-full bg-amber-500/20 text-[10px]">{{ $inProgressReportsCount }}</span>
                                            @endif
                                        </x-badge>

                                        <x-badge variant="success" size="md" :dot="true">
                                            <span>{{ __('Completed') }}</span>
                                            <span class="ms-1 px-1.5 py-0.2 rounded-full bg-emerald-500/20 text-[10px]">{{ $completedReportsCount ?? count($reportGroups) }}</span>
                                        </x-badge>

                                        <button type="button"
                                                @click="toggleAll()"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition shadow-2xs">
                                            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': allExpanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            <span x-text="allExpanded ? '{{ __('Collapse All') }}' : '{{ __('Expand All') }}'"></span>
                                        </button>
                                    </div>
                                </div>

                                <!-- The Main Reports Table -->
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50/80 dark:bg-gray-900/50 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3.5 text-center w-12">#</th>
                                                <th scope="col" class="px-4 py-3.5 text-left">{{ __('REPORT NO') }}</th>
                                                <th scope="col" class="px-4 py-3.5 text-left">{{ __('MISSION') }}</th>
                                                <th scope="col" class="px-4 py-3.5 text-left">{{ __('DATE CREATED') }}</th>
                                                <th scope="col" class="px-4 py-3.5 text-center">{{ __('INSTRUMENTS') }}</th>
                                                <th scope="col" class="px-4 py-3.5 text-center">{{ __('STATUS') }}</th>
                                                <th scope="col" class="px-4 py-3.5 text-center">{{ __('ACTIONS') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                            @foreach($reportGroups as $index => $group)
                                                <!-- Report Master Row -->
                                                <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors">
                                                    <!-- # -->
                                                    <td class="px-4 py-4 text-center whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                                                        {{ $index + 1 }}
                                                    </td>

                                                    <!-- REPORT NO -->
                                                    <td class="px-4 py-4 whitespace-nowrap">
                                                        <div class="flex items-center gap-2">
                                                            <a href="{{ $group['show_url'] ?? '#' }}"
                                                               class="font-bold font-mono text-sm text-brand-700 dark:text-brand-400 hover:underline">
                                                                {{ $group['report_number'] }}
                                                            </a>
                                                        </div>
                                                    </td>

                                                    <!-- MISSION -->
                                                    <td class="px-4 py-4 whitespace-nowrap">
                                                        <x-badge variant="neutral" size="sm">
                                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                                            <span>{{ $group['mission_ref'] }} @if(!empty($group['site_short_name'])) ({{ $group['site_short_name'] }}) @elseif(!empty($group['site_code'])) ({{ $group['site_code'] }}) @endif</span>
                                                        </x-badge>
                                                    </td>

                                                    <!-- DATE CREATED -->
                                                    <td class="px-4 py-4 whitespace-nowrap text-xs text-gray-600 dark:text-gray-300 font-medium">
                                                        <div class="flex items-center gap-1.5">
                                                            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                            <span>{{ $group['created_at'] }}</span>
                                                        </div>
                                                    </td>

                                                    <!-- INSTRUMENTS COUNT -->
                                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                                        <span class="inline-flex items-center justify-center min-w-[38px] px-3 py-1 rounded-full text-xs font-bold text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 shadow-2xs">
                                                            {{ $group['active_instruments_count'] }}
                                                        </span>
                                                    </td>

                                                    <!-- STATUS -->
                                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                                        @if($group['report_status'] === 'completed')
                                                            <x-badge variant="success" size="md" :dot="true">
                                                                {{ __('Completed') }}
                                                            </x-badge>
                                                        @else
                                                            <x-badge variant="warning" size="md" :dot="true">
                                                                {{ __('In Progress') }}
                                                            </x-badge>
                                                        @endif
                                                    </td>

                                                    <!-- ACTIONS -->
                                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                                        <x-table.actions class="justify-center">
                                                            @if($group['show_url'])
                                                                <x-table.action-view href="{{ $group['show_url'] }}" />
                                                            @endif

                                                            @if($group['pdf_url'])
                                                                <x-table.action-pdf href="{{ $group['pdf_url'] }}" target="_blank" />
                                                            @endif

                                                            @can('edit reports')
                                                                @if($group['edit_url'])
                                                                    <x-table.action-edit href="{{ $group['edit_url'] }}" />
                                                                @endif
                                                            @endcan

                                                            @can('delete reports')
                                                                @if($group['delete_url'])
                                                                    <x-table.action-delete
                                                                        :action-url="$group['delete_url']"
                                                                        :confirm-message="__('Are you sure you want to delete this report and its associated verifications?')"
                                                                    />
                                                                @endif
                                                            @endcan

                                                            <!-- Accordion Toggle Button -->
                                                            <button type="button"
                                                                    @click="toggleRow('{{ $group['report_id'] }}')"
                                                                    class="p-1.5 rounded-lg border border-brand-500/20 bg-brand-50/50 dark:bg-brand-500/10 text-brand-700 dark:text-brand-300 hover:bg-brand-600 hover:text-white dark:hover:bg-brand-600 dark:hover:text-white transition shadow-sm ml-1"
                                                                    :title="isExpanded('{{ $group['report_id'] }}') ? '{{ __('Collapse instruments table') }}' : '{{ __('Expand instruments table') }}'">
                                                                <svg class="w-3.5 h-3.5 transform transition-transform duration-200"
                                                                     :class="{ 'rotate-180': isExpanded('{{ $group['report_id'] }}') }"
                                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                                </svg>
                                                            </button>
                                                        </x-table.actions>
                                                    </td>
                                                </tr>

                                                <!-- Collapsible Sub-Row: Dedicated Instruments Table For This Report -->
                                                <tr x-show="isExpanded('{{ $group['report_id'] }}')"
                                                    x-collapse
                                                    class="bg-gray-50/70 dark:bg-gray-900/60">
                                                    <td colspan="7" class="p-0 border-b border-gray-200 dark:border-gray-700">
                                                        <div class="p-4 sm:p-6 space-y-4">

                                                            <!-- Sub-Table Header Bar -->
                                                            <div class="rounded-xl bg-white dark:bg-gray-800 p-4 border border-gray-100 dark:border-gray-700/60 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                                                <div class="flex items-center gap-3">
                                                                    <div class="w-9 h-9 rounded-lg bg-brand-50 dark:bg-brand-500/20 text-brand-600 dark:text-brand-400 flex items-center justify-center shrink-0 border border-brand-500/20">
                                                                        <x-tool-icon name="instruments" class="w-5 h-5 shrink-0" />
                                                                    </div>
                                                                    <div>
                                                                        <h4 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                                                                            <span>{{ __('Report Instruments') }} : {{ $group['report_number'] }}</span>
                                                                            <x-badge variant="neutral" size="sm">{{ $group['stats']['total'] }} {{ __('devices') }}</x-badge>
                                                                        </h4>
                                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                                            {{ $group['site_name'] }} &bull; {{ __('Mission:') }} {{ $group['mission_ref'] }}
                                                                        </p>
                                                                    </div>
                                                                </div>

                                                                <!-- Sub-table stats breakdown -->
                                                                <div class="flex flex-wrap items-center gap-2">
                                                                    <x-badge variant="neutral" size="sm">
                                                                        {{ $group['stats']['transmitters'] }} PT/TT &bull; {{ $group['stats']['probes'] }} Pt100 &bull; {{ $group['stats']['flow_computers'] }} FC
                                                                    </x-badge>

                                                                    <x-badge :variant="$group['stats']['non_conforme'] === 0 ? 'success' : 'warning'" size="sm" :dot="true">
                                                                        {{ $group['stats']['conforme'] }} / {{ $group['stats']['total'] }} {{ __('Compliant') }} ({{ $group['stats']['rate'] }}%)
                                                                    </x-badge>
                                                                </div>
                                                            </div>

                                                            <!-- Dedicated Instruments Table -->
                                                            <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-700/60 bg-white dark:bg-gray-800 shadow-2xs">
                                                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                                                    <thead class="bg-gray-50/90 dark:bg-gray-900/60 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                                                        <tr>
                                                                            <th scope="col" class="px-3 py-2.5 text-center w-10">#</th>
                                                                            <th scope="col" class="px-4 py-2.5 text-left">{{ __('Instrument (Tag / S/N)') }}</th>
                                                                            <th scope="col" class="px-4 py-2.5 text-left">{{ __('Type & Range') }}</th>
                                                                            <th scope="col" class="px-4 py-2.5 text-left">{{ __('Date & Ambient') }}</th>
                                                                            <th scope="col" class="px-4 py-2.5 text-center">{{ __('Points') }}</th>
                                                                            <th scope="col" class="px-4 py-2.5 text-center">{{ __('Error vs EMT') }}</th>
                                                                            <th scope="col" class="px-4 py-2.5 text-center">{{ __('Verdict') }}</th>
                                                                            <th scope="col" class="px-4 py-2.5 text-right">{{ __('Actions') }}</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
                                                                        @forelse($group['items'] as $itemIdx => $item)
                                                                            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition-colors">
                                                                                <!-- Index -->
                                                                                <td class="px-3 py-2.5 text-center whitespace-nowrap text-xs font-semibold text-gray-400 dark:text-gray-500">
                                                                                    {{ $itemIdx + 1 }}
                                                                                </td>

                                                                                <!-- Instrument Tag & Info -->
                                                                                <td class="px-4 py-2.5 whitespace-nowrap">
                                                                                    <div class="flex items-center gap-3">
                                                                                        @if(!empty($item['instrument']['image_url']))
                                                                                            <img src="{{ $item['instrument']['image_url'] }}"
                                                                                                 alt="{{ $item['instrument']['tag'] }}"
                                                                                                 class="w-8 h-8 rounded-lg object-contain bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 p-0.5" />
                                                                                        @else
                                                                                            <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 font-bold text-xs">
                                                                                                {{ strtoupper(substr($item['instrument']['tag'], 0, 2)) }}
                                                                                            </div>
                                                                                        @endif
                                                                                        <div>
                                                                                            <div class="font-bold text-xs text-gray-900 dark:text-white flex items-center gap-1.5">
                                                                                                <span>{{ $item['instrument']['tag'] }}</span>
                                                                                                @if(!empty($item['instrument']['technology']) && $item['instrument']['technology'] !== '---')
                                                                                                    <x-badge variant="neutral" size="sm">
                                                                                                        {{ $item['instrument']['technology'] }}
                                                                                                    </x-badge>
                                                                                                @endif
                                                                                            </div>
                                                                                            <div class="text-[11px] text-gray-500 dark:text-gray-400 font-mono">
                                                                                                SN: {{ $item['instrument']['serial'] }}
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </td>

                                                                                <!-- Type & Range -->
                                                                                <td class="px-4 py-2.5 whitespace-nowrap">
                                                                                    <div class="text-xs font-semibold text-gray-900 dark:text-white">
                                                                                        {{ $item['type_label'] }}
                                                                                    </div>
                                                                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 font-mono">
                                                                                        {{ $item['instrument']['range'] }}
                                                                                    </div>
                                                                                </td>

                                                                                <!-- Date & Ambient Conditions -->
                                                                                <td class="px-4 py-2.5 whitespace-nowrap">
                                                                                    <div class="text-xs font-semibold text-gray-900 dark:text-white flex items-center gap-1">
                                                                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                                                        <span>{{ $item['verification_date'] }}</span>
                                                                                    </div>
                                                                                    @if($item['ambient_temperature'] || $item['ambient_pressure'])
                                                                                        <div class="text-[10px] text-gray-400 dark:text-gray-500 font-mono">
                                                                                            {{ $item['ambient_temperature'] ? $item['ambient_temperature'].'°C' : '' }}
                                                                                            {{ $item['ambient_pressure'] ? ' • '.$item['ambient_pressure'].' mbar' : '' }}
                                                                                        </div>
                                                                                    @endif
                                                                                </td>

                                                                                <!-- Points Count -->
                                                                                <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                                                                    <x-badge :variant="$item['points_calibrated'] >= $item['points_total'] && $item['points_total'] > 0 ? 'success' : 'warning'" size="sm">
                                                                                        {{ $item['points_calibrated'] }} / {{ $item['points_total'] }} pts
                                                                                    </x-badge>
                                                                                </td>

                                                                                <!-- Error vs EMT -->
                                                                                <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                                                                    @if($item['max_error'] !== null && $item['max_emt'] !== null)
                                                                                        <div class="text-xs font-mono font-bold {{ (float)$item['max_error'] <= (float)$item['max_emt'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                                                                            ±{{ $item['max_error'] }}
                                                                                        </div>
                                                                                        <div class="text-[10px] text-gray-400 font-mono">
                                                                                            EMT: ±{{ $item['max_emt'] }}
                                                                                        </div>
                                                                                    @else
                                                                                        <span class="text-xs text-gray-400 font-mono">---</span>
                                                                                    @endif
                                                                                </td>

                                                                                <!-- Overall Status Badge -->
                                                                                <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                                                                    @if($item['overall_status_key'] === 'conforme')
                                                                                        <x-badge variant="success" size="sm" :dot="true">
                                                                                            {{ __('Compliant') }}
                                                                                        </x-badge>
                                                                                    @elseif($item['overall_status_key'] === 'non_conforme')
                                                                                        <x-badge variant="danger" size="sm" :dot="true">
                                                                                            {{ __('Non-Compliant') }}
                                                                                        </x-badge>
                                                                                    @else
                                                                                        <x-badge variant="warning" size="sm" :dot="true">
                                                                                            {{ __('In Progress') }}
                                                                                        </x-badge>
                                                                                    @endif
                                                                                </td>

                                                                                <!-- Actions -->
                                                                                <td class="px-4 py-2.5 whitespace-nowrap text-right text-xs">
                                                                                    <x-table.actions class="justify-end">
                                                                                        @if($item['curve_url'])
                                                                                            <x-table.action type="view" href="{{ $item['curve_url'] }}" title="{{ __('View SVG Error Curve') }}">
                                                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                                                                                                <span class="hidden xl:inline">{{ __('Curve') }}</span>
                                                                                            </x-table.action>
                                                                                        @endif

                                                                                        @if($item['saisie_url'])
                                                                                            <x-table.action type="primary" href="{{ $item['saisie_url'] }}" title="{{ __('Edit or Enter Measurement Points') }}">
                                                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                                                                <span>{{ __('Saisie') }}</span>
                                                                                            </x-table.action>
                                                                                        @endif
                                                                                    </x-table.actions>
                                                                                </td>
                                                                            </tr>
                                                                        @empty
                                                                            <tr>
                                                                                <td colspan="8" class="px-6 py-6 text-center text-xs text-gray-500 dark:text-gray-400">
                                                                                    {{ __('No instruments matched the applied filters in this report.') }}
                                                                                </td>
                                                                            </tr>
                                                                        @endforelse
                                                                    </tbody>
                                                                </table>
                                                            </div>

                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Table Footer -->
                                <div class="px-5 py-3.5 bg-gray-50/70 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700/60 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <div class="flex items-center gap-2">
                                        <span>{{ __('Showing') }} <strong>{{ count($reportGroups) }}</strong> {{ __('metrology reports') }}</span>
                                    </div>
                                    <div class="font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        <span>{{ $stats['conforme'] }} / {{ $stats['total'] }} {{ __('OIML Conformance Rate') }} ({{ $stats['conformance_rate'] }}%)</span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    @else
                        <!-- Empty State when no reports or instruments found -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700/60 p-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 mb-3">
                                    <x-tool-icon name="instruments" class="w-8 h-8" />
                                </div>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-1">
                                    {{ __('No instrument verification reports found') }}
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm mb-4">
                                    {{ __('No calibrations matched the selected filter criteria. Create a new report or reset filters.') }}
                                </p>
                                <a href="{{ route('metrology.reports.report-instruments.index') }}">
                                    <x-secondary-button type="button" class="text-xs">
                                        {{ __('Reset Filters') }}
                                    </x-secondary-button>
                                </a>
                            </div>
                        </div>
                    @endif

                </main>
            </div>
        </div>
    </div>
</x-app-layout>
