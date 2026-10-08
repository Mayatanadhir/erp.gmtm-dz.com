<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('operations.missions.show', $mission->id) }}" class="p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight flex items-center gap-2.5">
                        <svg class="w-7 h-7 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>{{ __('Mission Statistics') }}</span>
                        <span class="text-brand-600 dark:text-brand-400 font-mono text-xl">#{{ $mission->reference }}</span>
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5 flex flex-wrap items-center gap-2">
                        <span>{{ $mission->site?->full_name }}</span>
                        <span>•</span>
                        <span class="font-mono">{{ $mission->start_date?->format('d/m/Y') }} → {{ $mission->end_date?->format('d/m/Y') }}</span>
                    </p>
                </div>
            </div>

        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Mission Context Banner with Linked Attachments -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/70">
                                {{ __('Current Mission Statistics') }}
                            </span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white font-mono">
                                #{{ $mission->reference }}
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>{{ __('Fiscal Year') }}: <bdi class="font-mono">{{ $stats['current_year'] }}</bdi></span>
                            </span>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-300 mt-2 flex flex-wrap items-center gap-x-4 gap-y-1">
                            @if($mission->contract?->customer)
                                <span><strong class="font-medium text-gray-500 dark:text-gray-400">{{ __('Customer') }}:</strong> {{ $mission->contract->customer->company_name ?? $mission->contract->customer->short_name }}</span>
                            @endif
                            @if($mission->contract)
                                <span><strong class="font-medium text-gray-500 dark:text-gray-400">{{ __('Contract') }}:</strong> {{ $mission->contract->contract_number ?? $mission->contract->title }}</span>
                            @endif
                            @if($mission->site)
                                <span><strong class="font-medium text-gray-500 dark:text-gray-400">{{ __('Site') }}:</strong> {{ $mission->site->full_name }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Linked Attachments -->
                    @if($mission->attachments->isNotEmpty())
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700">
                            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <span>{{ __('Attachments') }} ({{ $mission->attachments->count() }})</span>
                            </div>
                            <div class="text-xs font-mono font-bold text-brand-600 dark:text-brand-400">
                                {{ $mission->attachments->pluck('code_ref')->filter()->implode(' / ') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 1. Executive Financial Summary Cards (Top 4 Metrics) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Revenue Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-help border-b border-dotted border-gray-400" title="{{ __('Total Revenue = Sum of (Actual Quantity × Unit Price) from linked attachments') }}">
                            {{ __('Total Revenue') }}
                        </span>
                        <span class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    </div>
                    <div class="text-2xl font-black text-gray-900 dark:text-white font-mono">
                        <bdi>{{ number_format($stats['revenue']['total'], 2) }}</bdi> <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">DZD</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Billed via service attachments') }}</div>
                </div>

                <!-- Total Direct Expenses Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-help border-b border-dotted border-gray-400" title="{{ __('Total Expenses = HR Costs (Mission Orders) + Direct Charges') }}">
                            {{ __('Total Expenses') }}
                        </span>
                        <span class="p-2 rounded-lg bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </span>
                    </div>
                    <div class="text-2xl font-black text-rose-600 font-mono">
                        <bdi>{{ number_format($stats['costs']['total_depenses'], 2) }}</bdi> <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">DZD</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('HR Per-Diems + Operational Charges') }}
                    </div>
                </div>

                <!-- Gross Profit Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-help border-b border-dotted border-gray-400" title="{{ __('Gross Profit = Total Revenue - Total Expenses') }}">
                            {{ __('Gross Profit') }}
                        </span>
                        <span class="p-2 rounded-lg {{ $stats['profit']['gross'] >= 0 ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400' : 'bg-rose-50 dark:bg-rose-900/30 text-rose-600' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </span>
                    </div>
                    <div class="text-2xl font-black {{ $stats['profit']['gross'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600' }} font-mono">
                        <bdi>{{ number_format($stats['profit']['gross'], 2) }}</bdi> <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">DZD</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Before Overhead Absorption') }}</div>
                </div>

                <!-- Gross Margin Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-help border-b border-dotted border-gray-400" title="{{ __('Gross Margin = (Gross Profit ÷ Total Revenue) × 100') }}">
                            {{ __('Gross Margin') }}
                        </span>
                        <span class="p-2 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </span>
                    </div>
                    <div class="text-2xl font-black text-amber-600 dark:text-amber-400 font-mono">
                        <bdi>{{ number_format($stats['profit']['gross_margin'], 2) }}%</bdi>
                    </div>
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('profitability margin') }}
                    </div>
                </div>
            </div>

            <!-- 2. Detailed Costs Breakdown -->
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span>{{ __('Cost Breakdown') }}</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Detailed Mob / Demob -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
                        <div class="flex items-center gap-2 pb-2 mb-3 border-b border-gray-100 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-sm">
                            <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                            <span>{{ __('Detailed Mobilization & Demobilization') }}</span>
                        </div>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">{{ __('Mobility Days') }}:</span>
                                <span class="font-mono font-bold text-gray-900 dark:text-white"><bdi>{{ $stats['days']['mob_demob'] }}</bdi> {{ __('days') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">{{ __('Operational Days') }}:</span>
                                <span class="font-mono font-bold text-gray-900 dark:text-white"><bdi>{{ $stats['days']['operational'] }}</bdi> {{ __('days') }}</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-gray-100 dark:border-gray-700">
                                <span class="font-semibold text-gray-700 dark:text-gray-300 cursor-help border-b border-dotted border-gray-400" title="{{ __('Total Due Days = Operational Days + Mobility Days') }}">{{ __('Total Due Days') }}:</span>
                                <span class="font-mono font-bold text-brand-600 dark:text-brand-400"><bdi>{{ $stats['days']['total_due_days'] }}</bdi> {{ __('days') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Operational HR Summary -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
                        <div class="flex items-center gap-2 pb-2 mb-3 border-b border-gray-100 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-sm cursor-help" title="{{ __('Total HR Cost = Sum of (Days × Daily Rate) per deployed employee') }}">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span class="border-b border-dotted border-gray-400">{{ __('Operational HR Costs') }}</span>
                        </div>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">{{ __('Assigned Staff') }}:</span>
                                <span class="font-mono font-bold text-gray-900 dark:text-white"><bdi>{{ $stats['staff_count'] ?? count($stats['costs']['hr_breakdown']) }}</bdi> {{ __('Specialists') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">{{ __('Total Paid Days') }}:</span>
                                <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400"><bdi>{{ $stats['days']['paid'] }}</bdi> {{ __('man-days') }}</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-gray-100 dark:border-gray-700">
                                <span class="font-semibold text-gray-700 dark:text-gray-300 cursor-help border-b border-dotted border-gray-400" title="{{ __('Total HR Cost = Sum of (Days × Daily Rate) per deployed employee') }}">{{ __('Total HR Cost') }}:</span>
                                <span class="font-mono font-bold text-rose-600"><bdi>{{ number_format($stats['costs']['total_hr'], 2) }}</bdi> DZD</span>
                            </div>
                        </div>
                    </div>

                    <!-- Direct Expenses -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
                        <div class="flex items-center gap-2 pb-2 mb-3 border-b border-gray-100 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-sm cursor-help" title="{{ __('Direct Expenses = Sum of mission operating charges (fuel, hotel, transport, consumables)') }}">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <span class="border-b border-dotted border-gray-400">{{ __('Direct Expenses') }}</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Direct Mission Operating Expenses') }}</p>
                        <div class="mt-4 pt-1 font-mono text-xl font-black text-rose-600">
                            <bdi>{{ number_format($stats['costs']['direct_charges'], 2) }}</bdi> <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">DZD</span>
                        </div>
                    </div>

                    <!-- Mobility & Direct Expenses -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
                        <div class="flex items-center gap-2 pb-2 mb-3 border-b border-gray-100 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-sm cursor-help" title="{{ __('(Direct Charges) + (Daily Team Rate × Mobility Days)') }}">
                            <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span class="border-b border-dotted border-gray-400">{{ __('Mobility & Direct Expenses') }}</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ __('Travel and mobilization day expenses') }}</p>
                        <div class="mt-4 pt-1 font-mono text-xl font-black text-rose-600">
                            <bdi>{{ number_format($stats['costs']['total_expenses_mission'] ?? 0, 2) }}</bdi> <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">DZD</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Personnel Per-Diem Allocation Breakdown Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span>{{ __('Personnel Per-Diem Allocation (Frais de Mission)') }}</span>
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Daily allowance compensation per engineer and deployed specialist') }}</p>
                    </div>
                    <div class="text-start sm:text-end">
                        <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ __('Total HR Cost') }}</span>
                        <span class="font-mono text-base font-black text-rose-600"><bdi>{{ number_format($stats['costs']['total_hr'], 2) }}</bdi> DZD</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-start text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/60 text-gray-700 dark:text-gray-300 font-semibold text-xs uppercase tracking-wider border-b border-gray-100 dark:border-gray-700/60">
                            <tr>
                                <th class="px-5 py-3 text-start">{{ __('Employee') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Assignment') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Daily Rate') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Deployment Period') }}</th>
                                <th class="px-5 py-3 text-center">{{ __('days') }}</th>
                                <th class="px-5 py-3 text-end">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($stats['costs']['hr_breakdown'] as $member)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/40 transition-colors">
                                    <td class="px-5 py-3.5 font-medium text-gray-900 dark:text-white">
                                        {{ $member->full_name }}
                                    </td>
                                    <td class="px-5 py-3.5 text-xs">
                                        @if ($member->is_leader)
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
                                                {{ __('Team Leader') }}
                                            </span>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400">{{ __('Specialist') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 font-mono text-xs text-gray-700 dark:text-gray-300">
                                        <bdi>{{ number_format((float) $member->daily_rate, 2) }}</bdi> DZD
                                    </td>
                                    <td class="px-5 py-3.5 text-xs text-gray-500 dark:text-gray-400 font-mono">
                                        <bdi>{{ $member->started_at ?? '—' }}</bdi> → <bdi>{{ $member->ended_at ?? '—' }}</bdi>
                                    </td>
                                    <td class="px-5 py-3.5 text-center font-bold text-gray-900 dark:text-white font-mono">
                                        <bdi>{{ $member->days_count ?? 0 }}</bdi>
                                    </td>
                                    <td class="px-5 py-3.5 text-end font-mono font-bold text-rose-600">
                                        <bdi>{{ number_format((float) $member->total_amount, 2) }}</bdi> DZD
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-gray-400 text-xs">
                                        {{ __('No staff members assigned to this mission.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(!empty($stats['costs']['hr_breakdown']) && count($stats['costs']['hr_breakdown']) > 0)
                            <tfoot class="bg-gray-50/75 dark:bg-gray-900/40 font-semibold text-xs border-t border-gray-200 dark:border-gray-700">
                                <tr>
                                    <td colspan="4" class="px-5 py-3 text-start text-gray-700 dark:text-gray-300 uppercase">{{ __('Total') }}</td>
                                    <td class="px-5 py-3 text-center font-mono font-bold text-gray-900 dark:text-white"><bdi>{{ $stats['days']['paid'] }}</bdi> {{ __('days') }}</td>
                                    <td class="px-5 py-3 text-end font-mono font-black text-rose-600"><bdi>{{ number_format($stats['costs']['total_hr'], 2) }}</bdi> DZD</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <!-- 4. Unit Economics (Daily Yields) -->
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                    <span>{{ __('Unit Economics') }}</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Gross Daily Yield -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm text-center">
                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-help border-b border-dotted border-gray-400 inline-block pb-1 mb-2" title="{{ __('Gross Daily Yield = Mission Gross Profit ÷ Operational Days (Excl. Mob/Demob)') }}">
                            {{ __('Gross Daily Yield') }}
                        </div>
                        <div class="text-2xl font-black text-gray-900 dark:text-white font-mono mt-1">
                            <bdi>{{ number_format($stats['rates']['rate_j_avec_depenses'], 2) }}</bdi> <small class="text-xs font-semibold text-gray-500 dark:text-gray-400">DA/j</small>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ __('Before Overhead Absorption') }}</div>
                    </div>

                    <!-- Net Daily Yield Reel -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-emerald-200 dark:border-emerald-800/40 p-5 shadow-sm text-center bg-gradient-to-br from-emerald-50/40 to-transparent dark:from-emerald-950/20">
                        <div class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider cursor-help border-b border-dotted border-emerald-400 inline-block pb-1 mb-2" title="{{ __('Net Daily Yield = Mission Gross Profit ÷ Total Mission Days') }}">
                            {{ __('Net Daily Yield') }} (Reel)
                        </div>
                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-1">
                            <bdi>{{ number_format($stats['rates']['rate_j_reel'], 2) }}</bdi> <small class="text-xs font-semibold text-emerald-600/70">DA/j</small>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ __('Real Profit Per Day') }}</div>
                    </div>

                    <!-- Mission Mobility Cost -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border-t-4 border-t-amber-500 border-x border-b border-gray-200 dark:border-gray-700 p-5 shadow-sm text-center">
                        <div class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider cursor-help border-b border-dotted border-amber-400 inline-block pb-1 mb-2" title="{{ __('Direct Mission Charges + (Mobility Days × Total Daily Team Incentives)') }}">
                            {{ __('Mission Mobility Cost') }}
                        </div>
                        <div class="text-2xl font-black text-amber-600 dark:text-amber-400 font-mono mt-1">
                            <bdi>{{ number_format($stats['costs']['mobility_cost'], 2) }}</bdi> <small class="text-xs font-semibold text-gray-500 dark:text-gray-400">DA</small>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ __('Total deployment and transit cost for team and assets') }}</div>
                    </div>

                    <!-- General Turnover Per Day -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border-t-4 border-t-brand-500 border-x border-b border-gray-200 dark:border-gray-700 p-5 shadow-sm text-center">
                        <div class="text-xs font-semibold text-brand-600 dark:text-brand-400 uppercase tracking-wider cursor-help border-b border-dotted border-brand-400 inline-block pb-1 mb-2" title="{{ __('Total Revenue ÷ Total Mission Days') }}">
                            {{ __('Turnover Per Day') }}
                        </div>
                        <div class="text-2xl font-black text-brand-600 dark:text-brand-400 font-mono mt-1">
                            <bdi>{{ number_format($stats['rates']['rate_j_general'], 2) }}</bdi> <small class="text-xs font-semibold text-gray-500 dark:text-gray-400">DA/j</small>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ __('General Mission Turnover Rate') }}</div>
                    </div>
                </div>
            </div>

            <!-- 5. Final Net Operating Profit & Profitability Margins -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Final Net Profit Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-emerald-500/40 dark:border-emerald-500/30 p-6 text-center shadow-sm">
                    <div class="inline-flex items-center gap-2 text-emerald-700 dark:text-emerald-400 font-bold text-sm uppercase tracking-wider pb-2 mb-3 border-b border-emerald-200 dark:border-emerald-800 cursor-help" title="{{ __('Gross Profit - (Total Mission Days × General Daily Office Expense (GMTM))') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                        <span>{{ __('Final Net Profit') }} ({{ __('Tax Rate') }}: 0%)</span>
                    </div>
                    <div class="text-3xl sm:text-4xl font-black text-emerald-600 dark:text-emerald-400 font-mono">
                        <bdi>{{ number_format($stats['profit']['net'], 2) }}</bdi> <span class="text-sm font-bold text-gray-500 dark:text-gray-400">DZD</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 font-mono">
                        {{ __('Gross Profit - (Total Mission Days × General Daily Office Expense (GMTM))') }}
                    </p>
                </div>

                <!-- Final Net Margin Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-brand-500/40 dark:border-brand-500/30 p-6 text-center shadow-sm">
                    <div class="inline-flex items-center gap-2 text-brand-700 dark:text-brand-400 font-bold text-sm uppercase tracking-wider pb-2 mb-3 border-b border-brand-200 dark:border-brand-800 cursor-help" title="{{ __('Final Net Profit ÷ Total Revenue') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                        <span>{{ __('Net Margin') }}</span>
                    </div>
                    <div class="text-3xl sm:text-4xl font-black {{ $stats['profit']['net_margin'] >= 0 ? 'text-brand-600 dark:text-brand-400' : 'text-rose-600' }} font-mono">
                        <bdi>{{ number_format($stats['profit']['net_margin'] ?? 0, 2) }}%</bdi>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 font-mono">
                        {{ __('Final Net Profit ÷ Total Revenue') }}
                    </p>
                </div>
            </div>

            <!-- 6. Corporate Office Absorption & Annual Context Box -->
            <div class="bg-gray-50 dark:bg-gray-900/40 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <span class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </span>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ __('GMTM Office Daily Overhead Absorption') }} ({{ $stats['current_year'] }})
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ __('Calculated based on reference year office expenses divided by annual active/forecast days.') }}
                            </p>
                        </div>
                    </div>
                    <div class="text-start sm:text-end">
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Office Daily Rate') }}</div>
                        <div class="font-mono text-sm font-bold text-gray-900 dark:text-white">
                            <bdi>{{ number_format($stats['gmtm_expense_rate'], 2) }}</bdi> DZD/day
                        </div>
                    </div>
                </div>

                <!-- Annual Context Breakdown Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4 pt-1">
                    <div class="p-3 rounded-lg bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80">
                        <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ __('Fiscal Year') }}</span>
                        <span class="font-mono text-base font-bold text-gray-900 dark:text-white">{{ $stats['current_year'] }}</span>
                    </div>
                    <div class="p-3 rounded-lg bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80">
                        <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ __('Annual Office Overhead') }}</span>
                        <span class="font-mono text-base font-bold text-gray-900 dark:text-white"><bdi>{{ number_format($stats['company_annual_stats']['details']['gmtm']['total_expenses'], 2) }}</bdi> DZD</span>
                    </div>
                    <div class="p-3 rounded-lg bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80">
                        <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ __('Company Active Days') }}</span>
                        <span class="font-mono text-base font-bold text-gray-900 dark:text-white"><bdi>{{ $stats['days']['total_company_working_days'] }}</bdi> {{ __('days') }}</span>
                    </div>
                    <div class="p-3 rounded-lg bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80">
                        <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ __('Mission Absorbed Overhead') }}</span>
                        <span class="font-mono text-base font-bold text-rose-600 dark:text-rose-400"><bdi>{{ number_format($stats['total_mission_days'] * $stats['gmtm_expense_rate'], 2) }}</bdi> DZD</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
