<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('metrology.reports.report-chromatograph.index') }}"
                   class="p-2.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition shadow-2xs"
                   title="{{ __('Back to List') }}">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <x-tool-icon name="chromatograph" class="w-12 h-12 sm:w-14 sm:h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ __('Chromatograph Verification Report') }} &bull; <span dir="ltr" class="font-mono text-brand-600 dark:text-brand-400">{{ $chromatographVerification->reference_number }}</span>
                        </h2>
                        <x-badge variant="neutral" size="sm">{{ __('Chromatograph CPG') }}</x-badge>
                        @if($chromatographVerification->overall_status)
                            <x-badge variant="success" size="sm" :dot="true">
                                {{ __('CONFORME (OIML R 140 / ISO 6974)') }}
                            </x-badge>
                        @else
                            <x-badge variant="danger" size="sm" :dot="true">
                                {{ __('NON-CONFORME') }}
                            </x-badge>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex flex-wrap items-center gap-2">
                        <span>{{ __('Tag:') }} <strong class="font-mono text-gray-900 dark:text-white">{{ $chromatographVerification->instrument?->tag_number ?? '-' }}</strong></span>
                        <span>&bull;</span>
                        <span>{{ __('Site:') }} <strong class="text-gray-700 dark:text-gray-300">{{ $chromatographVerification->instrument?->site?->full_name ?? ($chromatographVerification->instrument?->site?->short_name ?? '-') }}</strong></span>
                        <span>&bull;</span>
                        <span>{{ __('Date:') }} <span class="font-mono text-gray-700 dark:text-gray-300">{{ $chromatographVerification->verification_date ? $chromatographVerification->verification_date->format('d/m/Y') : '-' }}</span></span>
                        @if($chromatographVerification->report)
                            <span>&bull;</span>
                            <span>{{ __('Mission Report:') }} <a href="{{ route('metrology.reports.show', $chromatographVerification->report->id) }}" class="font-mono text-brand-600 dark:text-brand-400 hover:underline font-semibold">{{ $chromatographVerification->report->report_number }}</a></span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                @canany(['view chromatograph verifications', 'view reports'])
                    <a href="{{ route('metrology.reports.report-chromatograph.excel', $chromatographVerification->id) }}"
                       title="{{ __('Excel Report') }}">
                        <x-secondary-button type="button" class="gap-1.5 text-xs">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zM6 20V4h7v5h5v11H6z"/>
                                <path d="M8.8 15.3l1.8-2.8-1.7-2.7h1.6l1 1.8 1-1.8h1.5l-1.7 2.7 1.8 2.8h-1.6l-1-1.9-1 1.9H8.8z"/>
                            </svg>
                            <span>{{ __('Excel') }}</span>
                        </x-secondary-button>
                    </a>

                    <a href="{{ route('metrology.reports.report-chromatograph.pdf', $chromatographVerification->id) }}"
                       target="_blank"
                       title="{{ __('Official Detailed Verification Certificate (PDF with EMT)') }}">
                        <x-danger-button type="button" class="gap-1.5 text-xs">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ __('PDF (EMT)') }}</span>
                        </x-danger-button>
                    </a>

                    <a href="{{ route('metrology.reports.report-chromatograph.pdf-not-emt', $chromatographVerification->id) }}"
                       target="_blank"
                       title="{{ __('Raw Metrological Inspection Report (PDF without EMT)') }}">
                        <x-info-button type="button" class="gap-1.5 text-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>{{ __('PDF (Without EMT)') }}</span>
                        </x-info-button>
                    </a>
                @endcanany

                @canany(['edit chromatograph verifications', 'edit reports'])
                    <a href="{{ route('metrology.reports.report-chromatograph.saisie', $chromatographVerification->id) }}">
                        <x-warning-button type="button" class="gap-1.5 text-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span>{{ __('Edit / Saisie') }}</span>
                        </x-warning-button>
                    </a>
                @endcanany

                <a href="{{ route('metrology.reports.report-chromatograph.index') }}">
                    <x-secondary-button type="button" class="gap-1.5 text-xs">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>{{ __('Dashboard') }}</span>
                    </x-secondary-button>
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $pcs = $chromatographVerification->physicalProperties->firstWhere('property_symbol', 'PCS');
        $pci = $chromatographVerification->physicalProperties->firstWhere('property_symbol', 'PCI');
        $pb = $chromatographVerification->physicalProperties->firstWhere('property_symbol', 'Pb') ?? $chromatographVerification->physicalProperties->firstWhere('property_symbol', 'rho');
        $zb = $chromatographVerification->physicalProperties->firstWhere('property_symbol', 'Zb') ?? $chromatographVerification->physicalProperties->firstWhere('property_symbol', 'Z');
    @endphp

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-metrology-tabs active="reports" />
                </aside>

                <!-- Main Content Area -->
                <main class="flex-1 w-full min-w-0 space-y-6">

                    <!-- Feedback Alert -->
                    @if(session('success'))
                        <x-alert variant="success" :title="session('success')" :dismissible="true" />
                    @endif
                    @if(session('error'))
                        <x-alert variant="danger" :title="session('error')" :dismissible="true" />
                    @endif

                    <!-- Metrological Key Indicator KPI Cards (4 Properties Grid) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                        <!-- Card 1: PCS -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 border border-gray-100 dark:border-gray-700/60 shadow-xs flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Gross Calorific Value (PCS)') }}
                                </span>
                                <x-badge variant="neutral" size="sm">ISO 6976</x-badge>
                            </div>
                            <div class="mt-3">
                                <div class="flex items-baseline gap-1">
                                    <span class="font-mono text-2xl font-extrabold text-brand-600 dark:text-brand-400">
                                        {{ $pcs && $pcs->mean_value !== null ? number_format((float) $pcs->mean_value, 4) : '-' }}
                                    </span>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">MJ/m³</span>
                                </div>
                                <div class="mt-2 flex items-center justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">
                                        &Delta;: <strong class="{{ ($pcs && $pcs->is_conforme) ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">{{ $pcs ? number_format((float) $pcs->relative_error_percent, 2) : '-' }}%</strong>
                                    </span>
                                    <span class="text-[11px] text-gray-400 dark:text-gray-500">
                                        {{ __('EMT: ±0.50%') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: PCI -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 border border-gray-100 dark:border-gray-700/60 shadow-xs flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Net Calorific Value (PCI)') }}
                                </span>
                                <x-badge variant="neutral" size="sm">ISO 6976</x-badge>
                            </div>
                            <div class="mt-3">
                                <div class="flex items-baseline gap-1">
                                    <span class="font-mono text-2xl font-extrabold text-brand-600 dark:text-brand-400">
                                        {{ $pci && $pci->mean_value !== null ? number_format((float) $pci->mean_value, 4) : '-' }}
                                    </span>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">MJ/m³</span>
                                </div>
                                <div class="mt-2 flex items-center justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">
                                        &Delta;: <strong class="{{ ($pci && $pci->is_conforme) ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">{{ $pci ? number_format((float) $pci->relative_error_percent, 2) : '-' }}%</strong>
                                    </span>
                                    <span class="text-[11px] text-gray-400 dark:text-gray-500">
                                        {{ __('EMT: ±0.50%') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Real Density (Pb) -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 border border-gray-100 dark:border-gray-700/60 shadow-xs flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Real Density (Pb / ρb)') }}
                                </span>
                                <x-badge variant="neutral" size="sm">ISO 6976</x-badge>
                            </div>
                            <div class="mt-3">
                                <div class="flex items-baseline gap-1">
                                    <span class="font-mono text-2xl font-extrabold text-brand-600 dark:text-brand-400">
                                        {{ $pb && $pb->mean_value !== null ? number_format((float) $pb->mean_value, 6) : '-' }}
                                    </span>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">kg/m³</span>
                                </div>
                                <div class="mt-2 flex items-center justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">
                                        &Delta;: <strong class="{{ ($pb && $pb->is_conforme) ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">{{ $pb ? number_format((float) $pb->relative_error_percent, 2) : '-' }}%</strong>
                                    </span>
                                    <span class="text-[11px] text-gray-400 dark:text-gray-500">
                                        {{ __('EMT: ±0.35%') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card 4: Compressibility Factor (Zb) -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 border border-gray-100 dark:border-gray-700/60 shadow-xs flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Compressibility Factor (Zb)') }}
                                </span>
                                <x-badge variant="neutral" size="sm">AGA8 / ISO</x-badge>
                            </div>
                            <div class="mt-3">
                                <div class="flex items-baseline gap-1">
                                    <span class="font-mono text-2xl font-extrabold text-brand-600 dark:text-brand-400">
                                        {{ $zb && $zb->mean_value !== null ? number_format((float) $zb->mean_value, 6) : '-' }}
                                    </span>
                                </div>
                                <div class="mt-2 flex items-center justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">
                                        &Delta;: <strong class="{{ ($zb && $zb->is_conforme) ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">{{ $zb ? number_format((float) $zb->relative_error_percent, 2) : '-' }}%</strong>
                                    </span>
                                    <span class="text-[11px] text-gray-400 dark:text-gray-500">
                                        {{ __('EMT: ±0.30%') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Official Verification Certificate Sheet -->
                    <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 shadow-sm p-6 sm:p-8 space-y-8">

                        <!-- Sheet Title & Status Header -->
                        <div class="pb-6 border-b border-gray-100 dark:border-gray-700/60 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300 border border-brand-200 dark:border-brand-800">
                                        {{ __('Official Metrological Sheet') }}
                                    </span>
                                    <span class="text-xs text-gray-400 font-mono">{{ $chromatographVerification->reference_number }}</span>
                                </div>
                                <h3 class="text-xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {{ __('Gas Chromatograph (CPG) & Natural Gas Thermodynamics') }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ __('Reference Standards:') }} <strong class="text-gray-700 dark:text-gray-300">ASTM D 1945 &bull; ISO 6974-2 &bull; ISO 6976:1995 &bull; OIML R 140 Class A</strong>
                                </p>
                            </div>
                            <div class="shrink-0">
                                @if($chromatographVerification->overall_status)
                                    <div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 font-bold text-sm shadow-2xs">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>{{ __('CONFORME (OIML R 140)') }}</span>
                                    </div>
                                @else
                                    <div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 font-bold text-sm shadow-2xs">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>{{ __('NON-CONFORME') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- 1. Equipment & Site Details Grid -->
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                                </svg>
                                <span>{{ __('1. Equipment & Site Under Inspection') }}</span>
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
                                <div class="p-3.5 rounded-xl bg-gray-50/80 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 block uppercase tracking-wider">{{ __('Tag Number') }}</span>
                                    <span class="font-mono font-bold text-sm text-gray-900 dark:text-white mt-1 block">{{ $chromatographVerification->instrument?->tag_number ?? '-' }}</span>
                                </div>
                                <div class="p-3.5 rounded-xl bg-gray-50/80 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 block uppercase tracking-wider">{{ __('Serial Number') }}</span>
                                    <span class="font-mono font-bold text-sm text-gray-900 dark:text-white mt-1 block">{{ $chromatographVerification->instrument?->serial_number ?? '-' }}</span>
                                </div>
                                <div class="p-3.5 rounded-xl bg-gray-50/80 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 block uppercase tracking-wider">{{ __('Site / Installation') }}</span>
                                    <span class="font-medium text-sm text-gray-900 dark:text-white mt-1 block truncate">{{ $chromatographVerification->instrument?->site?->full_name ?? ($chromatographVerification->instrument?->site?->short_name ?? '-') }}</span>
                                </div>
                                <div class="p-3.5 rounded-xl bg-gray-50/80 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 block uppercase tracking-wider">{{ __('Technology / Type') }}</span>
                                    <span class="font-medium text-sm text-gray-900 dark:text-white mt-1 block truncate">
                                        {{ $chromatographVerification->instrument?->technology ?? 'Gas Chromatography' }} ({{ $chromatographVerification->instrument?->measurement_type ?? 'C6+' }})
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Certified Standard Gas Bottle & Conditions -->
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                                <span>{{ __('2. Certified Standard Gas Cylinder & Reference Conditions') }}</span>
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
                                <div class="p-3.5 rounded-xl bg-gray-50/80 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 block uppercase tracking-wider">{{ __('Standard Bottle N°') }}</span>
                                    <span class="font-mono font-bold text-xs text-gray-900 dark:text-white mt-1 block truncate">{{ $chromatographVerification->standard_gas_bottle_number ?? '-' }}</span>
                                </div>
                                <div class="p-3.5 rounded-xl bg-gray-50/80 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 block uppercase tracking-wider">{{ __('Certificate N°') }}</span>
                                    <span class="font-mono font-bold text-xs text-gray-900 dark:text-white mt-1 block truncate">{{ $chromatographVerification->certificate_number ?? '-' }}</span>
                                </div>
                                <div class="p-3.5 rounded-xl bg-gray-50/80 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 block uppercase tracking-wider">{{ __('Validity Date') }}</span>
                                    <span class="font-mono text-xs text-gray-900 dark:text-white mt-1 block">
                                        {{ $chromatographVerification->cylinder_validity_date ? $chromatographVerification->cylinder_validity_date->format('Y-m-d') : '-' }}
                                    </span>
                                </div>
                                <div class="p-3.5 rounded-xl bg-gray-50/80 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 block uppercase tracking-wider">{{ __('Cylinder Pressure') }}</span>
                                    <span class="font-mono text-xs text-gray-900 dark:text-white mt-1 block">
                                        {{ $chromatographVerification->cylinder_pressure_bar ? number_format((float) $chromatographVerification->cylinder_pressure_bar, 1).' bar' : '-' }}
                                    </span>
                                </div>
                                <div class="p-3.5 rounded-xl bg-gray-50/80 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 block uppercase tracking-wider">{{ __('Reference Cond.') }}</span>
                                    <span class="font-mono text-xs text-gray-900 dark:text-white mt-1 block truncate">{{ $chromatographVerification->reference_conditions ?? '15°C / 101.325 kPa' }}</span>
                                </div>
                                <div class="p-3.5 rounded-xl bg-gray-50/80 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 block uppercase tracking-wider">{{ __('Ambient T & P') }}</span>
                                    <span class="font-mono text-xs text-gray-900 dark:text-white mt-1 block truncate">
                                        {{ $chromatographVerification->ambient_temperature ? number_format((float) $chromatographVerification->ambient_temperature, 1).'°C' : '-' }}
                                        |
                                        {{ $chromatographVerification->ambient_pressure ? number_format((float) $chromatographVerification->ambient_pressure, 0).' mbar' : '-' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Reference Calibration Standards (If configured) -->
                        @if($chromatographVerification->calibrators->isNotEmpty())
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span>{{ __('3. Certified Calibration Equipment (Etalons)') }}</span>
                                </h4>
                                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700/60">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50/80 dark:bg-gray-900/50 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 text-left">{{ __('Equipment Name') }}</th>
                                                <th scope="col" class="px-4 py-3 text-left">{{ __('Serial Number') }}</th>
                                                <th scope="col" class="px-4 py-3 text-left">{{ __('Certificate Number') }}</th>
                                                <th scope="col" class="px-4 py-3 text-left">{{ __('Expiry Date') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800 text-xs">
                                            @foreach($chromatographVerification->calibrators as $cal)
                                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30">
                                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $cal->name }}</td>
                                                    <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-300">{{ $cal->serial_number }}</td>
                                                    <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-300">{{ $cal->latestCertificate?->certificate_number ?? '-' }}</td>
                                                    <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-300">
                                                        {{ $cal->latestCertificate?->expiry_date ? $cal->latestCertificate->expiry_date->format('Y-m-d') : '-' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        <!-- 4. Gas Composition Table (ASTM D 1945 & ISO 6974-2) -->
                        <div>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                    <svg class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                    <span>{{ __('4. Molar Composition Analysis & Component Repeatability') }}</span>
                                </h4>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('Standards: ASTM D 1945 & ISO 6974-2') }}
                                </span>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700/60 shadow-2xs">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                                    <thead class="bg-gray-50/80 dark:bg-gray-900/50 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-3 py-3 text-center w-10">#</th>
                                            <th scope="col" class="px-3 py-3 text-left">{{ __('Component') }}</th>
                                            <th scope="col" class="px-3 py-3 text-right">{{ __('Ref (% mol)') }}</th>
                                            <th scope="col" class="px-2 py-3 text-right">R1</th>
                                            <th scope="col" class="px-2 py-3 text-right">R2</th>
                                            <th scope="col" class="px-2 py-3 text-right">R3</th>
                                            <th scope="col" class="px-2 py-3 text-right">R4</th>
                                            <th scope="col" class="px-2 py-3 text-right">R5</th>
                                            <th scope="col" class="px-3 py-3 text-right font-bold text-gray-800 dark:text-gray-200">{{ __('Mean') }}</th>
                                            <th scope="col" class="px-3 py-3 text-right">{{ __('Rep. (r)') }}</th>
                                            <th scope="col" class="px-3 py-3 text-right text-gray-400">{{ __('ASTM Lim.') }}</th>
                                            <th scope="col" class="px-3 py-3 text-right">{{ __('Error %') }}</th>
                                            <th scope="col" class="px-3 py-3 text-right text-gray-400">{{ __('EMT %') }}</th>
                                            <th scope="col" class="px-3 py-3 text-center">{{ __('Verdict') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800 font-mono">
                                        @foreach($chromatographVerification->compositionPoints as $cp)
                                            <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30">
                                                <td class="px-3 py-2.5 text-center text-gray-400 text-[11px] font-sans font-medium">{{ $cp->step_order }}</td>
                                                <td class="px-3 py-2.5 text-left font-sans font-bold text-gray-900 dark:text-white">{{ $cp->component_name }}</td>
                                                <td class="px-3 py-2.5 text-right font-medium text-gray-700 dark:text-gray-300">{{ number_format((float) $cp->reference_value, 4) }}</td>
                                                <td class="px-2 py-2.5 text-right text-gray-500 dark:text-gray-400">{{ number_format((float) $cp->run_1, 4) }}</td>
                                                <td class="px-2 py-2.5 text-right text-gray-500 dark:text-gray-400">{{ number_format((float) $cp->run_2, 4) }}</td>
                                                <td class="px-2 py-2.5 text-right text-gray-500 dark:text-gray-400">{{ number_format((float) $cp->run_3, 4) }}</td>
                                                <td class="px-2 py-2.5 text-right text-gray-500 dark:text-gray-400">{{ number_format((float) $cp->run_4, 4) }}</td>
                                                <td class="px-2 py-2.5 text-right text-gray-500 dark:text-gray-400">{{ number_format((float) $cp->run_5, 4) }}</td>
                                                <td class="px-3 py-2.5 text-right font-bold text-brand-600 dark:text-brand-400 bg-brand-50/30 dark:bg-brand-950/20">
                                                    {{ number_format((float) $cp->mean_value, 4) }}
                                                </td>
                                                <td class="px-3 py-2.5 text-right {{ ($cp->repeatability <= $cp->repeatability_limit_astm) ? 'text-gray-700 dark:text-gray-300' : 'text-rose-600 dark:text-rose-400 font-bold' }}">
                                                    {{ number_format((float) $cp->repeatability, 4) }}
                                                </td>
                                                <td class="px-3 py-2.5 text-right text-gray-400 text-[11px]">{{ number_format((float) $cp->repeatability_limit_astm, 4) }}</td>
                                                <td class="px-3 py-2.5 text-right font-semibold {{ $cp->error_is_conforme ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                                    {{ number_format((float) $cp->relative_error_percent, 2) }}%
                                                </td>
                                                <td class="px-3 py-2.5 text-right text-gray-400 text-[11px]">±{{ number_format((float) $cp->emt_limit_percent, 2) }}%</td>
                                                <td class="px-3 py-2.5 text-center font-sans">
                                                    @if($cp->is_conforme)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300">
                                                            OK
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-900/40 text-rose-800 dark:text-rose-300">
                                                            NOK
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- 5. Physical & Energetic Properties Table (ISO 6976 & OIML R 140) -->
                        <div>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                    <svg class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343a7.975 7.975 0 012.343 5.657c0 2.12-.82 4.14-2.342 5.657z"/>
                                    </svg>
                                    <span>{{ __('5. Energetic & Physical Gas Properties') }}</span>
                                </h4>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('Standards: ISO 6976:1995 & OIML R 140 Class A') }}
                                </span>
                            </div>

                            @php
                                $physPropOrderMap = [
                                    'PCS' => 1,
                                    'PCI' => 2,
                                    'Pb' => 3,
                                    'rho' => 3,
                                    'Zb' => 4,
                                    'Z' => 4,
                                ];
                                $sortedPhysProps = $chromatographVerification->physicalProperties->sortBy(function($prop) use ($physPropOrderMap) {
                                    return $physPropOrderMap[$prop->property_symbol] ?? 99;
                                });
                            @endphp

                            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700/60 shadow-2xs">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                                    <thead class="bg-gray-50/80 dark:bg-gray-900/50 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left">{{ __('Property Name') }}</th>
                                            <th scope="col" class="px-3 py-3 text-center">{{ __('Symbol') }}</th>
                                            <th scope="col" class="px-3 py-3 text-center">{{ __('Unit') }}</th>
                                            <th scope="col" class="px-4 py-3 text-right">{{ __('Reference Value') }}</th>
                                            <th scope="col" class="px-4 py-3 text-right font-bold text-gray-800 dark:text-gray-200">{{ __('Mean Measured') }}</th>
                                            <th scope="col" class="px-3 py-3 text-right">{{ __('Error %') }}</th>
                                            <th scope="col" class="px-3 py-3 text-right text-gray-400">{{ __('EMT %') }}</th>
                                            <th scope="col" class="px-3 py-3 text-right">{{ __('Repeatability') }}</th>
                                            <th scope="col" class="px-3 py-3 text-right text-gray-400">{{ __('Limit') }}</th>
                                            <th scope="col" class="px-3 py-3 text-center">{{ __('Decision') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800 font-mono">
                                        @foreach($sortedPhysProps as $prop)
                                            @php
                                                $displaySymbol = match($prop->property_symbol) {
                                                    'rho' => 'Pb',
                                                    'Z' => 'Zb',
                                                    default => $prop->property_symbol,
                                                };
                                                $isPcs = strtoupper($prop->property_symbol) === 'PCS';
                                                $repLimit = $isPcs ? '0.10' : '-';
                                            @endphp
                                            <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30">
                                                <td class="px-4 py-3 font-sans font-bold text-gray-900 dark:text-white">{{ $prop->property_name }}</td>
                                                <td class="px-3 py-3 text-center font-bold text-brand-600 dark:text-brand-400">{{ $displaySymbol }}</td>
                                                <td class="px-3 py-3 text-center text-gray-500 font-sans">{{ $prop->unit ?: '-' }}</td>
                                                <td class="px-4 py-3 text-right font-medium text-gray-700 dark:text-gray-300">{{ number_format((float) $prop->reference_value, 6) }}</td>
                                                <td class="px-4 py-3 text-right font-bold text-brand-600 dark:text-brand-400 bg-brand-50/30 dark:bg-brand-950/20">
                                                    {{ number_format((float) $prop->mean_value, 6) }}
                                                </td>
                                                <td class="px-3 py-3 text-right font-semibold {{ $prop->is_conforme ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                                    {{ number_format((float) $prop->relative_error_percent, 2) }}%
                                                </td>
                                                <td class="px-3 py-3 text-right text-gray-400 text-[11px]">±{{ number_format((float) $prop->emt_limit_percent, 2) }}%</td>
                                                <td class="px-3 py-3 text-right {{ ($isPcs && $prop->repeatability !== null && $prop->repeatability <= 0.1) ? 'text-gray-700 dark:text-gray-300' : 'text-gray-500' }}">
                                                    {{ ($isPcs && $prop->repeatability !== null) ? number_format((float) $prop->repeatability, 3) : '-' }}
                                                </td>
                                                <td class="px-3 py-3 text-right text-gray-400 text-[11px]">{{ $repLimit }}</td>
                                                <td class="px-3 py-3 text-center font-sans">
                                                    @if($prop->is_conforme)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300">
                                                            {{ __('CONFORME') }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-900/40 text-rose-800 dark:text-rose-300">
                                                            {{ __('NON-CONFORME') }}
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- 6. Inspector Remarks (If present) -->
                        @if($chromatographVerification->remarks)
                            <div class="rounded-xl p-4 bg-gray-50/80 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider block mb-1 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                    </svg>
                                    {{ __('Inspector Remarks & Metrological Observations') }}
                                </span>
                                <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                                    {{ $chromatographVerification->remarks }}
                                </p>
                            </div>
                        @endif

                        <!-- 7. Final Metrological Conformity Decision Banner -->
                        <div class="rounded-xl p-4 border {{ $chromatographVerification->overall_status ? 'bg-emerald-50/90 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200' : 'bg-rose-50/90 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800 text-rose-900 dark:text-rose-200' }}">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg {{ $chromatographVerification->overall_status ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white' }} shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($chromatographVerification->overall_status)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        @endif
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="text-xs font-bold uppercase tracking-wider">
                                        {{ __('Final Metrological Conformity Decision (Decision de Conformite Metrologique):') }}
                                    </div>
                                    <div class="text-sm font-extrabold mt-0.5">
                                        @if($chromatographVerification->overall_status)
                                            {{ __('CONFORME AUX EXIGENCES REGLEMENTAIRES OIML R 140 / ISO 6974 (CLASSE A)') }}
                                        @else
                                            {{ __('NON-CONFORME AUX EXIGENCES REGLEMENTAIRES OIML R 140') }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </main>
            </div>
        </div>
    </div>
</x-app-layout>
