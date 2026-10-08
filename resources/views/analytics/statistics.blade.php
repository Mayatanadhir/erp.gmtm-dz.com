@php
    $year       = (int) ($stats['current_year'] ?? now()->year);
    $annual     = $stats['company_annual_stats'] ?? [];
    $revenue    = (float) data_get($annual, 'revenue', 0);
    $expenses   = (float) data_get($annual, 'expenses', 0);
    $profit     = (float) data_get($annual, 'gross_profit', 0);
    $margin     = (float) data_get($annual, 'gross_margin', 0);
    $details    = data_get($annual, 'details', []);
    $gmtm       = data_get($details, 'gmtm', []);
    $payroll    = $stats['payroll'] ?? [];
    $daysStats  = $stats['days'] ?? [];
    $rates      = $stats['rates'] ?? [];
    $missions   = collect($stats['missions_details'] ?? []);

    $isProfit     = $profit >= 0;
    $expenseRatio = $revenue > 0 ? ($expenses / $revenue) * 100 : 0;
    $marginWidth  = number_format(max(0, min(100, $margin)), 2, '.', '');

    $expenseItems = [
        ['label' => __('Mission Charges'),        'val' => (float) data_get($details, 'mission_charges', 0),        'color' => 'bg-blue-500'],
        ['label' => __('Contract Charges'),       'val' => (float) data_get($details, 'contract_charges', 0),       'color' => 'bg-indigo-500'],
        ['label' => __('GMTM Office Charges'),    'val' => (float) data_get($gmtm, 'total_expenses', 0),            'color' => 'bg-amber-500'],
        ['label' => __('Prisma Charges'),         'val' => (float) data_get($details, 'prisma_charges', 0),         'color' => 'bg-purple-500'],
        ['label' => __('Annual Payroll'),         'val' => (float) data_get($details, 'annual_payroll', 0),         'color' => 'bg-rose-500'],
        ['label' => __('Mission HR Incentives'),  'val' => (float) data_get($details, 'mission_hr_cost', 0),        'color' => 'bg-cyan-500'],
        ['label' => __('Calibration Costs'),      'val' => (float) data_get($details, 'calibration_costs', 0),      'color' => 'bg-emerald-500'],
        ['label' => __('Active Guarantees'),     'val' => (float) data_get($details, 'active_guarantees_total', 0), 'color' => 'bg-teal-500'],
    ];

    $itemsSum = collect($expenseItems)->sum('val');
    $base     = max($expenses, $itemsSum, 1);

    // مجاميع جدول المهمات
    $sumDays     = $missions->sum('total_days');
    $sumRevenue  = $missions->sum('revenue');
    $sumHr       = $missions->sum('hr_cost');
    $sumDirect   = $missions->sum('direct_charges');
    $sumExpenses = $missions->sum('total_expenses');
    $sumNet      = $missions->sum('net_profit');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-tool-icon name="statistics" class="w-14 h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight flex items-center gap-2">
                        <span>{{ __('Company Statistics') }}</span>
                        <span class="text-brand-600 dark:text-brand-400 font-mono text-xl font-bold" dir="ltr">({{ $year }})</span>
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Analytical dashboards, operational metrics, and growth indicators') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <form action="{{ route('analytics.statistics') }}" method="GET" class="flex items-center gap-2">
                    <label for="year" class="sr-only">{{ __('Select Year') }}</label>
                    <div class="relative">
                        <select name="year" id="year"
                                class="py-1.5 ps-2.5 pe-8 text-xs font-semibold rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600 shadow-sm cursor-pointer">
                            @foreach ($availableYears as $y)
                                <option value="{{ $y }}" @selected((string) $year === (string) $y)>
                                    {{ __('Year') }} {{ $y }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <x-secondary-button type="submit" class="text-xs">{{ __('Apply') }}</x-secondary-button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0" aria-label="{{ __('Analytics navigation') }}">
                    <x-analytics-tabs active="statistics" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-8">
                    {{-- ===== 1. المؤشرات المالية الرئيسية ===== --}}
                    <section class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                    <span>{{ __('Company Annual Activity') }}</span>
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ __('Global financial performance and profitability overview for the year') }} {{ $year }}
                                </p>
                            </div>
                            <x-badge variant="neutral" size="sm">
                                <span class="tabular-nums font-mono">{{ data_get($daysStats, 'total_missions_this_year', 0) }}</span>
                                <span class="ms-1">{{ __('missions') }}</span>
                            </x-badge>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                            {{-- الإيرادات --}}
                            <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Revenue') }}</span>
                                    <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                </div>
                                <div class="text-2xl font-black font-mono text-gray-900 dark:text-white" dir="ltr">
                                    {{ number_format($revenue, 2, '.', ' ') }} <span class="text-xs font-normal text-gray-400">{{ __('DA') }}</span>
                                </div>
                                <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ __('Base turnover') }}</span>
                                    <span class="font-semibold text-blue-600 dark:text-blue-400 font-mono">100%</span>
                                </div>
                            </div>

                            {{-- المصاريف --}}
                            <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Expenses') }}</span>
                                    <div class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    </div>
                                </div>
                                <div class="text-2xl font-black font-mono text-gray-900 dark:text-white" dir="ltr">
                                    {{ number_format($expenses, 2, '.', ' ') }} <span class="text-xs font-normal text-gray-400">{{ __('DA') }}</span>
                                </div>
                                <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ __('Expense ratio') }}</span>
                                    <span class="font-semibold text-rose-600 dark:text-rose-400 font-mono" dir="ltr">{{ number_format($expenseRatio, 1) }}%</span>
                                </div>
                            </div>

                            {{-- الربح --}}
                            <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Gross Profit') }}</span>
                                    <div class="w-8 h-8 rounded-lg {{ $isProfit ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400' }} flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                    </div>
                                </div>
                                <div class="text-2xl font-black font-mono {{ $isProfit ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}" dir="ltr">
                                    {{ number_format($profit, 2, '.', ' ') }} <span class="text-xs font-normal text-gray-400">{{ __('DA') }}</span>
                                </div>
                                <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ __('Net result status') }}</span>
                                    <span class="font-semibold {{ $isProfit ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ $isProfit ? __('Positive Yield') : __('Deficit') }}
                                    </span>
                                </div>
                            </div>

                            {{-- الهامش --}}
                            <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Gross Margin') }}</span>
                                    <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                    </div>
                                </div>
                                <div class="text-2xl font-black font-mono text-gray-900 dark:text-white" dir="ltr">
                                    {{ number_format($margin, 2, '.', ' ') }} <span class="text-xs font-normal text-gray-400">%</span>
                                </div>
                                <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700/60">
                                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $marginWidth }}">
                                        <div class="bg-amber-500 h-1.5 rounded-full" style="width: {{ $marginWidth }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- ===== 2. توزيع التكاليف ===== --}}
                    <section class="rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700/60 p-6 space-y-5">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    <span>{{ __('Cost Breakdown') }}</span>
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ __('Detailed distribution of annual corporate charges and operational expenditures') }}
                                </p>
                            </div>
                            <x-badge variant="danger" size="md">
                                <span>{{ __('Total Expenses') }}:</span>
                                <span class="font-mono font-bold ms-1" dir="ltr">{{ number_format($expenses, 2, '.', ' ') }} {{ __('DA') }}</span>
                            </x-badge>
                        </div>

                        {{-- شريط التوزيع الملوّن --}}
                        <div class="h-2.5 w-full rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden flex" aria-hidden="true">
                            @foreach ($expenseItems as $item)
                                @php $pct = ((float) $item['val'] / $base) * 100; @endphp
                                @if ($pct > 0.5)
                                    <div class="{{ $item['color'] }} h-full"
                                         style="width: {{ number_format($pct, 2, '.', '') }}%"
                                         title="{{ $item['label'] }}: {{ number_format($pct, 1) }}%"></div>
                                @endif
                            @endforeach
                        </div>

                        <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                            @foreach ($expenseItems as $item)
                                @php $itemPct = ((float) $item['val'] / $base) * 100; @endphp
                                <li class="p-3.5 rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50/70 dark:bg-gray-900/40">
                                    <div class="flex items-start justify-between gap-2">
                                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 leading-snug truncate" title="{{ $item['label'] }}">
                                            {{ $item['label'] }}
                                        </span>
                                        <span class="text-xs font-bold px-1.5 py-0.5 rounded-md bg-gray-200/70 dark:bg-gray-800 text-gray-600 dark:text-gray-300 font-mono" dir="ltr">
                                            {{ number_format($itemPct, 1) }}%
                                        </span>
                                    </div>
                                    <div class="mt-2 text-base font-extrabold font-mono text-gray-900 dark:text-white" dir="ltr">
                                        {{ number_format($item['val'], 2, '.', ' ') }} <span class="text-xs font-normal text-gray-400">{{ __('DA') }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>

                    {{-- ===== 3. المؤشرات التشغيلية والعمالة ===== --}}
                    <section class="space-y-4">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <span>{{ __('Operational & Workforce Indicators') }}</span>
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ __('Payroll metrics, corporate active days, and daily profit indices') }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
                            <div class="rounded-xl bg-white dark:bg-gray-800 p-4 shadow-sm border border-gray-100 dark:border-gray-700/60">
                                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Monthly Payroll') }}</p>
                                <p class="mt-1 text-xl font-black font-mono text-gray-900 dark:text-white" dir="ltr">
                                    {{ number_format((float) data_get($payroll, 'monthly', 0), 2, '.', ' ') }}
                                </p>
                                <p class="mt-1 text-xs text-gray-400">{{ __('All Active Employees') }}</p>
                            </div>

                            <div class="rounded-xl bg-white dark:bg-gray-800 p-4 shadow-sm border border-gray-100 dark:border-gray-700/60">
                                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Annual Payroll') }}</p>
                                <p class="mt-1 text-xl font-black font-mono text-gray-900 dark:text-white" dir="ltr">
                                    {{ number_format((float) data_get($payroll, 'annual', 0), 2, '.', ' ') }}
                                </p>
                                <p class="mt-1 text-xs text-gray-400">{{ __('All Active Employees') }}</p>
                            </div>

                            <div class="rounded-xl bg-white dark:bg-gray-800 p-4 shadow-sm border border-gray-100 dark:border-gray-700/60">
                                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Company Working Days') }}</p>
                                <p class="mt-1 text-xl font-black font-mono text-gray-900 dark:text-white" dir="ltr">
                                    {{ data_get($daysStats, 'total_company_working_days', 0) }} <span class="text-xs font-normal text-gray-400">{{ __('days') }}</span>
                                </p>
                                <p class="mt-1 text-xs text-gray-400">{{ __('Sum of all completed missions') }}</p>
                            </div>

                            <div class="rounded-xl bg-white dark:bg-gray-800 p-4 shadow-sm border border-gray-100 dark:border-gray-700/60">
                                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Missions This Year') }}</p>
                                <p class="mt-1 text-xl font-black font-mono text-gray-900 dark:text-white" dir="ltr">
                                    {{ data_get($daysStats, 'total_missions_this_year', 0) }} <span class="text-xs font-normal text-gray-400">{{ __('missions') }}</span>
                                </p>
                                <p class="mt-1 text-xs text-gray-400">{{ __('Current Year') }}</p>
                            </div>

                            <div class="rounded-xl bg-white dark:bg-gray-800 p-4 shadow-sm border border-gray-100 dark:border-gray-700/60">
                                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Annual Profit Rate') }}</p>
                                <p class="mt-1 text-xl font-black font-mono text-emerald-600 dark:text-emerald-400" dir="ltr">
                                    {{ number_format((float) data_get($rates, 'rate_j_gmtm_ann', 0), 2, '.', ' ') }} <span class="text-xs font-normal text-gray-400">{{ __('DA/j') }}</span>
                                </p>
                                <p class="mt-1 text-xs text-gray-400">{{ __('Daily profit rate') }}</p>
                            </div>
                        </div>
                    </section>

                    {{-- ===== 4. نشاط مكتب GMTM السنوي ===== --}}
                    <section class="rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700/60 p-6 space-y-4">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                <span>{{ __('GMTM Office Annual Activity') }}</span>
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ __('Head office operational absorption, fixed commitments, and overhead yield') }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                            <div class="p-4 rounded-xl bg-gray-50/70 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700/60">
                                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Fixed GMTM Charges') }}</p>
                                <p class="mt-1 text-lg font-black font-mono text-gray-900 dark:text-white" dir="ltr">
                                    {{ number_format((float) data_get($gmtm, 'fixed', 0), 2, '.', ' ') }} <span class="text-xs font-normal text-gray-400">{{ __('DA') }}</span>
                                </p>
                            </div>

                            <div class="p-4 rounded-xl bg-gray-50/70 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700/60">
                                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Variable GMTM Charges') }}</p>
                                <p class="mt-1 text-lg font-black font-mono text-gray-900 dark:text-white" dir="ltr">
                                    {{ number_format((float) data_get($gmtm, 'variable', 0), 2, '.', ' ') }} <span class="text-xs font-normal text-gray-400">{{ __('DA') }}</span>
                                </p>
                            </div>

                            <div class="p-4 rounded-xl bg-gray-50/70 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700/60">
                                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Annual Profit Rate (GMTM)') }}</p>
                                <p class="mt-1 text-lg font-black font-mono text-emerald-600 dark:text-emerald-400" dir="ltr">
                                    {{ number_format((float) data_get($gmtm, 'profit_rate', 0), 2, '.', ' ') }} <span class="text-xs font-normal text-gray-400">{{ __('DA/j') }}</span>
                                </p>
                                <p class="mt-0.5 text-xs text-gray-400">{{ __('Daily profit rate after GMTM deductions') }}</p>
                            </div>

                            <div class="p-4 rounded-xl bg-gray-50/70 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700/60">
                                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Daily Expense Rate (GMTM)') }}</p>
                                <p class="mt-1 text-lg font-black font-mono text-rose-600 dark:text-rose-400" dir="ltr">
                                    {{ number_format((float) data_get($gmtm, 'expense_rate', 0), 2, '.', ' ') }} <span class="text-xs font-normal text-gray-400">{{ __('DA/j') }}</span>
                                </p>
                                <p class="mt-0.5 text-xs text-gray-400">{{ __('General daily office expense') }}</p>
                            </div>
                        </div>
                    </section>

                    {{-- ===== 5. جدول المهمات السنوي الموحد (x-table) ===== --}}
                    <x-table>
                        <x-slot:toolbar>
                            <div class="flex items-center justify-between w-full">
                                <div class="flex items-center gap-2.5">
                                    <x-tool-icon name="missions" class="w-6 h-6 shrink-0" />
                                    <div>
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                            {{ __('Detailed Annual Mission Activity') }}
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ __('Individual project statements, billing totals, costs, and profit breakdown') }}
                                        </p>
                                    </div>
                                </div>
                                <x-badge variant="neutral" size="md">
                                    <span class="font-mono font-bold">{{ $missions->count() }}</span>
                                    <span class="ms-1">{{ __('missions') }}</span>
                                </x-badge>
                            </div>
                        </x-slot:toolbar>

                        <x-slot:header>
                            <x-table.th>{{ __('Mission / Client') }}</x-table.th>
                            <x-table.th class="text-center">{{ __('Duration') }}</x-table.th>
                            <x-table.th class="text-center">{{ __('Crew') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Total Revenue') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('HR Costs') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Direct Exp.') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Total Costs') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Net Profit') }}</x-table.th>
                            <x-table.th class="text-center">{{ __('Stats') }}</x-table.th>
                        </x-slot:header>

                        @forelse ($missions as $m)
                            @php $net = (float) $m['net_profit']; @endphp
                            <x-table.tr>
                                <x-table.td>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-mono font-bold bg-gray-100 dark:bg-gray-700 text-brand-700 dark:text-brand-300 border border-gray-200 dark:border-gray-600">
                                        <bdi>#{{ $m['reference'] }}</bdi>
                                    </span>
                                    <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $m['customer_name'] }}</span>
                                        @if (! empty($m['site_name']))
                                            <span class="text-gray-400"> • {{ $m['site_name'] }}</span>
                                        @endif
                                    </div>
                                </x-table.td>

                                <x-table.td class="text-center whitespace-nowrap">
                                    <x-badge variant="neutral" size="sm">
                                        {{ $m['total_days'] }} {{ __('days') }}
                                    </x-badge>
                                    @if (! empty($m['start_date']) && ! empty($m['end_date']))
                                        <div class="text-xs text-gray-400 dark:text-gray-500 font-mono mt-0.5" dir="ltr">
                                            {{ \Illuminate\Support\Carbon::parse($m['start_date'])->format('d/m') }} → {{ \Illuminate\Support\Carbon::parse($m['end_date'])->format('d/m/Y') }}
                                        </div>
                                    @endif
                                </x-table.td>

                                <x-table.td class="text-center whitespace-nowrap font-mono text-xs">
                                    <x-badge variant="neutral" size="sm">
                                        {{ $m['workers_count'] }}
                                    </x-badge>
                                </x-table.td>

                                <x-table.td class="text-end font-mono font-bold text-blue-600 dark:text-blue-400" dir="ltr">
                                    {{ number_format((float) $m['revenue'], 2, '.', ' ') }}
                                </x-table.td>

                                <x-table.td class="text-end font-mono text-gray-600 dark:text-gray-300" dir="ltr">
                                    {{ number_format((float) $m['hr_cost'], 2, '.', ' ') }}
                                </x-table.td>

                                <x-table.td class="text-end font-mono text-gray-600 dark:text-gray-300" dir="ltr">
                                    {{ number_format((float) $m['direct_charges'], 2, '.', ' ') }}
                                </x-table.td>

                                <x-table.td class="text-end font-mono font-bold text-rose-600 dark:text-rose-400" dir="ltr">
                                    {{ number_format((float) $m['total_expenses'], 2, '.', ' ') }}
                                </x-table.td>

                                <x-table.td class="text-end whitespace-nowrap font-mono font-bold {{ $net >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}" dir="ltr">
                                    {{ $net > 0 ? '+' : '' }}{{ number_format($net, 2, '.', ' ') }}
                                </x-table.td>

                                <x-table.td class="text-center whitespace-nowrap">
                                    @can('view operations')
                                        <x-table.action-view
                                            href="{{ route('operations.missions.statistics', $m['id']) }}"
                                            :title="__('Stats')"
                                        />
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">—</span>
                                    @endcan
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty :colspan="9" :message="__('No missions recorded for this year.')" />
                        @endforelse

                        @if ($missions->isNotEmpty())
                            <tfoot class="bg-gray-50 dark:bg-gray-900 border-t-2 border-gray-200 dark:border-gray-700 font-bold text-xs text-gray-700 dark:text-gray-200">
                                <tr>
                                    <th scope="row" class="px-5 py-3.5 text-start font-black">{{ __('Total') }} ({{ $missions->count() }})</th>
                                    <td class="px-5 py-3.5 text-center font-mono font-black">{{ $sumDays }} {{ __('days') }}</td>
                                    <td class="px-5 py-3.5 text-center text-gray-400">—</td>
                                    <td class="px-5 py-3.5 text-end font-mono font-black text-blue-600 dark:text-blue-400" dir="ltr">{{ number_format((float) $sumRevenue, 2, '.', ' ') }}</td>
                                    <td class="px-5 py-3.5 text-end font-mono font-bold text-gray-600 dark:text-gray-300" dir="ltr">{{ number_format((float) $sumHr, 2, '.', ' ') }}</td>
                                    <td class="px-5 py-3.5 text-end font-mono font-bold text-gray-600 dark:text-gray-300" dir="ltr">{{ number_format((float) $sumDirect, 2, '.', ' ') }}</td>
                                    <td class="px-5 py-3.5 text-end font-mono font-black text-rose-600 dark:text-rose-400" dir="ltr">{{ number_format((float) $sumExpenses, 2, '.', ' ') }}</td>
                                    <td class="px-5 py-3.5 text-end font-mono font-black {{ $sumNet >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}" dir="ltr">
                                        {{ $sumNet > 0 ? '+' : '' }}{{ number_format((float) $sumNet, 2, '.', ' ') }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center text-gray-400">—</td>
                                </tr>
                            </tfoot>
                        @endif
                    </x-table>
                </main>
            </div>
        </div>
    </div>
</x-app-layout>
