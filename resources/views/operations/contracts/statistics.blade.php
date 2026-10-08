<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('operations.contracts.show', $contract->id) }}" class="p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Unit Economics & Financial Statistics') }}: <span class="text-brand-600 dark:text-brand-400">{{ $contract->reference }}</span>
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Real-time revenue consumption, operating expenses, and gross profitability metrics') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('operations.contracts.show', $contract->id) }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-sm font-semibold rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>{{ __('Contract Details') }}</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- 1. Top Financial Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Revenue -->
                <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actual Invoiced') }}</span>
                        <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-2 font-mono">
                        <bdi>{{ number_format((float) $stats['revenue']['total'], 2, '.', ' ') }}</bdi> <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">DA</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('Planned') }}: <span class="font-mono"><bdi>{{ number_format((float) $stats['revenue']['planned'], 2, '.', ' ') }}</bdi> DA</span> (<span class="font-mono"><bdi>{{ $stats['revenue']['consumption_rate'] }}%</bdi></span>)
                    </div>
                    <div class="text-xs mt-1.5 flex items-center gap-1 {{ $stats['revenue']['remaining'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                        {{ __('Unconsumed Value') }}: <span class="font-mono font-semibold"><bdi>{{ number_format((float) $stats['revenue']['remaining'], 2, '.', ' ') }}</bdi> DA</span>
                    </div>
                </div>

                <!-- Total Expenses -->
                <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-help border-b border-dotted border-gray-400" title="{{ __('Operating Expenses = Sum of direct expenses for all missions linked to this contract') }}">{{ __('Operating Expenses') }}</span>
                        <div class="p-2 rounded-lg bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-rose-600 dark:text-rose-400 mt-2 font-mono">
                        <bdi>{{ number_format((float) $stats['costs']['total_expenses'], 2, '.', ' ') }}</bdi> <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">DA</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('HR') }}: <span class="font-mono"><bdi>{{ number_format((float) $stats['costs']['total_hr'], 2, '.', ' ') }}</bdi> DA</span> · {{ __('Direct') }}: <span class="font-mono"><bdi>{{ number_format((float) $stats['costs']['direct_charges'], 2, '.', ' ') }}</bdi> DA</span>
                    </div>
                </div>

                <!-- Gross Profit -->
                <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Gross Margin') }}</span>
                        <div class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold {{ $stats['profit']['gross'] >= 0 ? 'text-brand-600 dark:text-brand-400' : 'text-rose-600 dark:text-rose-400' }} mt-2 font-mono">
                        <bdi>{{ number_format((float) $stats['profit']['gross'], 2, '.', ' ') }}</bdi> <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">DA</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('Revenue minus Total Expenses') }}
                    </div>
                </div>

                <!-- Margin % -->
                <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Profit Margin') }}</span>
                        <div class="p-2 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-amber-600 dark:text-amber-400 mt-2 font-mono">
                        <bdi>{{ $stats['profit']['gross_margin'] }}%</bdi>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('Return on invoiced services') }}
                    </div>
                </div>
            </div>

            <!-- 2. Consumption & Timeline Progress Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm space-y-5">
                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-gray-700">
                    <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>{{ __('Financial Execution Progress') }}</span>
                </h3>

                <!-- Budget Bar -->
                <div>
                    <div class="flex items-center justify-between text-xs font-semibold mb-2">
                        <span class="text-gray-700 dark:text-gray-300">{{ __('Budget Invoiced vs Planned Amount') }}</span>
                        <span class="text-brand-600 dark:text-brand-400 font-mono"><bdi>{{ $stats['revenue']['consumption_rate'] }}%</bdi></span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                        <div class="h-3 rounded-full bg-gradient-to-r from-brand-500 to-emerald-500 transition-all duration-500" style="width: {{ min(100, $stats['revenue']['consumption_rate']) }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mt-1.5 font-mono">
                        <span><bdi>{{ number_format((float) $stats['revenue']['total'], 2, '.', ' ') }}</bdi> DA</span>
                        <span><bdi>{{ number_format((float) $stats['revenue']['planned'], 2, '.', ' ') }}</bdi> DA</span>
                    </div>
                </div>

                <!-- Time Progress Bar -->
                @php
                    $totalDays = $stats['contract_info']['total_days'];
                    $elapsedDays = $stats['contract_info']['elapsed_days'];
                    $timePct = $totalDays > 0 ? round(($elapsedDays / $totalDays) * 100, 1) : 0;
                @endphp
                <div>
                    <div class="flex items-center justify-between text-xs font-semibold mb-2">
                        <span class="text-gray-700 dark:text-gray-300">{{ __('Time Elapsed vs Contract Duration') }}</span>
                        <span class="text-indigo-600 dark:text-indigo-400 font-mono"><bdi>{{ $timePct }}%</bdi></span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                        <div class="h-3 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-500" style="width: {{ min(100, $timePct) }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mt-1.5">
                        <span><bdi class="font-mono">{{ $elapsedDays }}</bdi> {{ __('days elapsed') }}</span>
                        <span><bdi class="font-mono">{{ $stats['contract_info']['remaining_days'] }}</bdi> {{ __('days remaining') }} (<bdi class="font-mono">{{ $totalDays }}</bdi> {{ __('total') }})</span>
                    </div>
                </div>
            </div>

            <!-- 3. Operational & Field Metrics Card -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Operational Summary -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm space-y-4">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <x-tool-icon name="missions" class="w-5 h-5 shrink-0" />
                        <span>{{ __('Field Operations Telemetry') }}</span>
                    </h3>

                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Total Linked Missions') }}</span>
                            <span class="font-bold text-gray-900 dark:text-white font-mono"><bdi>{{ $stats['operational']['missions_count'] }}</bdi></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Billing Attachments') }}</span>
                            <span class="font-bold text-gray-900 dark:text-white font-mono"><bdi>{{ $stats['operational']['attachments_count'] }}</bdi></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Cumulative Transit / Mobilization Days') }}</span>
                            <span class="font-bold text-gray-900 dark:text-white"><bdi class="font-mono">{{ $stats['operational']['total_mob_days'] }}</bdi> {{ __('days') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Average Mobilization per Mission') }}</span>
                            <span class="font-bold text-gray-900 dark:text-white"><bdi class="font-mono">{{ $stats['operational']['average_mob_days'] }}</bdi> {{ __('days') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Average Expense per Mission') }}</span>
                            <span class="font-bold text-gray-900 dark:text-white font-mono"><bdi>{{ number_format((float) $stats['operational']['average_mission_mob_and_expenses'], 2, '.', ' ') }}</bdi> DA</span>
                        </div>
                    </div>
                </div>

                <!-- Cost Composition -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm space-y-4">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>{{ __('Direct Cost Allocation') }}</span>
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-xs font-medium mb-1">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('Team Daily Allowances & HR') }}</span>
                                <span class="font-semibold text-gray-900 dark:text-white font-mono"><bdi>{{ number_format((float) $stats['costs']['total_hr'], 2, '.', ' ') }}</bdi> DA</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                                @php
                                    $hrPct = $stats['costs']['total_expenses'] > 0 ? round(($stats['costs']['total_hr'] / $stats['costs']['total_expenses']) * 100, 1) : 0;
                                @endphp
                                <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $hrPct }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs font-medium mb-1">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('Direct Operational Charges') }}</span>
                                <span class="font-semibold text-gray-900 dark:text-white font-mono"><bdi>{{ number_format((float) $stats['costs']['direct_charges'], 2, '.', ' ') }}</bdi> DA</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                                @php
                                    $chgPct = $stats['costs']['total_expenses'] > 0 ? round(($stats['costs']['direct_charges'] / $stats['costs']['total_expenses']) * 100, 1) : 0;
                                @endphp
                                <div class="bg-amber-500 h-2 rounded-full" style="width: {{ $chgPct }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Missions Breakdown Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-tool-icon name="missions" class="w-5 h-5 shrink-0" />
                        <span>{{ __('Mission Costs & Execution Log') }}</span>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-start text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="py-3 px-4 text-start">{{ __('Mission Ref') }}</th>
                                <th class="py-3 px-4 text-start">{{ __('Dates') }}</th>
                                <th class="py-3 px-4 text-center">{{ __('Mob Days') }}</th>
                                <th class="py-3 px-4 text-end">{{ __('Revenue') }}</th>
                                <th class="py-3 px-4 text-end">{{ __('HR Costs') }}</th>
                                <th class="py-3 px-4 text-end">{{ __('Direct Charges') }}</th>
                                <th class="py-3 px-4 text-end font-bold text-rose-600 dark:text-rose-400">{{ __('Total Expenses') }}</th>
                                <th class="py-3 px-4 text-end">{{ __('Gross Profit') }}</th>
                                <th class="py-3 px-4 text-center">{{ __('Status') }}</th>
                                <th class="py-3 px-4 text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($stats['operational']['missions_detail'] as $m)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="py-3 px-4 font-semibold text-brand-600 dark:text-brand-400 font-mono">
                                        <a href="{{ route('operations.missions.show', $m['id']) }}" class="hover:underline">
                                            <bdi>{{ $m['reference'] }}</bdi>
                                        </a>
                                    </td>
                                    <td class="py-3 px-4 text-xs text-gray-600 dark:text-gray-400 font-mono whitespace-nowrap">
                                        <bdi>{{ $m['start_date'] ?? '—' }}</bdi>
                                        <span class="text-gray-400 mx-0.5">→</span>
                                        <bdi>{{ $m['end_date'] ?? '—' }}</bdi>
                                    </td>
                                    <td class="py-3 px-4 text-center text-xs font-medium text-gray-700 dark:text-gray-300 font-mono">
                                        <bdi>{{ $m['mob_days'] }}</bdi>
                                    </td>
                                    <td class="py-3 px-4 text-end text-xs font-mono font-medium text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                        <bdi>{{ number_format((float) $m['revenue'], 2, '.', ' ') }}</bdi> <span class="text-[10px] text-gray-500">DA</span>
                                    </td>
                                    <td class="py-3 px-4 text-end text-xs font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                        <bdi>{{ number_format((float) $m['hr_cost'], 2, '.', ' ') }}</bdi> <span class="text-[10px] text-gray-500">DA</span>
                                    </td>
                                    <td class="py-3 px-4 text-end text-xs font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                        <bdi>{{ number_format((float) $m['direct_charges'], 2, '.', ' ') }}</bdi> <span class="text-[10px] text-gray-500">DA</span>
                                    </td>
                                    <td class="py-3 px-4 text-end text-xs font-mono font-bold text-rose-600 dark:text-rose-400 bg-rose-50/40 dark:bg-rose-950/20 whitespace-nowrap">
                                        <bdi>{{ number_format((float) $m['total_expenses'], 2, '.', ' ') }}</bdi> <span class="text-[10px] text-gray-500">DA</span>
                                    </td>
                                    <td class="py-3 px-4 text-end text-xs font-mono font-semibold {{ $m['profit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} whitespace-nowrap">
                                        <bdi>{{ number_format((float) $m['profit'], 2, '.', ' ') }}</bdi> <span class="text-[10px] text-gray-500">DA</span>
                                    </td>
                                    <td class="py-3 px-4 text-center text-xs">
                                        <x-badge variant="neutral" size="sm">
                                            {{ ucfirst($m['status']) }}
                                        </x-badge>
                                    </td>
                                    <td class="py-3 px-4 text-end">
                                        <div class="inline-flex items-center gap-1">
                                            <a href="{{ route('operations.missions.statistics', $m['id']) }}" class="p-1 rounded text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-gray-700 transition-colors" title="{{ __('Mission Statistics') }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                                            </a>
                                            <a href="{{ route('operations.missions.show', $m['id']) }}" class="p-1 rounded text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="{{ __('Details') }}">
                                                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('No missions executed under this contract yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($stats['operational']['missions_detail']) > 0)
                            <tfoot class="bg-gray-50 dark:bg-gray-900/70 border-t-2 border-gray-200 dark:border-gray-700 font-semibold text-xs text-gray-900 dark:text-white">
                                <tr>
                                    <td colspan="2" class="py-3.5 px-4 uppercase tracking-wider text-start font-bold">
                                        {{ __('Total') }} ({{ count($stats['operational']['missions_detail']) }} {{ __('Missions') }})
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-mono">
                                        <bdi>{{ $stats['operational']['total_mob_days'] }}</bdi>
                                    </td>
                                    <td class="py-3.5 px-4 text-end font-mono text-emerald-600 dark:text-emerald-400 font-bold whitespace-nowrap">
                                        <bdi>{{ number_format((float) $stats['revenue']['total'], 2, '.', ' ') }}</bdi> <span class="text-[10px] text-gray-500">DA</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-end font-mono whitespace-nowrap">
                                        <bdi>{{ number_format((float) $stats['costs']['total_hr'], 2, '.', ' ') }}</bdi> <span class="text-[10px] text-gray-500">DA</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-end font-mono whitespace-nowrap">
                                        <bdi>{{ number_format((float) $stats['costs']['direct_charges'], 2, '.', ' ') }}</bdi> <span class="text-[10px] text-gray-500">DA</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-end font-mono font-extrabold text-rose-600 dark:text-rose-400 bg-rose-100/50 dark:bg-rose-900/30 whitespace-nowrap">
                                        <bdi>{{ number_format((float) $stats['costs']['total_expenses'], 2, '.', ' ') }}</bdi> <span class="text-[10px] text-gray-500">DA</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-end font-mono font-bold {{ $stats['profit']['gross'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} whitespace-nowrap">
                                        <bdi>{{ number_format((float) $stats['profit']['gross'], 2, '.', ' ') }}</bdi> <span class="text-[10px] text-gray-500">DA</span>
                                    </td>
                                    <td colspan="2" class="py-3.5 px-4"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
