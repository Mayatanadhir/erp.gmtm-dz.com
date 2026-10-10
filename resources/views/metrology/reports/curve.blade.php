<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('metrology.reports.show', $report->id) }}"
                   class="p-2.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition shadow-2xs"
                   title="{{ __('Back to Report') }}">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <x-tool-icon name="instruments" class="w-12 h-12 sm:w-14 sm:h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    @php
                        $typeVal = strtolower($instrument->instrument_type instanceof \BackedEnum ? $instrument->instrument_type->value : (string) $instrument->instrument_type);
                        $typeLabel = match($typeVal) {
                            'transmitter' => __('Transmitter'),
                            'probe' => __('Temperature Probe'),
                            'flowcomputer', 'flow_computer' => __('Flow Computer'),
                            'standard_gauge' => __('Standard Gauge'),
                            'prover' => __('Prover'),
                            'chromatograph' => __('Gas Chromatograph'),
                            default => ucfirst($typeVal),
                        };

                        $allConformes = collect($curves)->filter(fn($c) => !empty($c['conformity']));
                        $globalConformity = $allConformes->isNotEmpty() && $allConformes->every(fn($c) => $c['conformity'] === 'Conforme')
                            ? 'Conforme'
                            : ($allConformes->isNotEmpty() ? 'Non Conforme' : ($conformity ?? null));
                    @endphp
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ $title ?? __('reports.curve.title') }} &bull; <span dir="ltr" class="font-mono text-brand-600 dark:text-brand-400">{{ $instrument->tag_number }}</span>
                        </h2>
                        <x-badge variant="neutral" size="sm">{{ $typeLabel }}</x-badge>
                        @if($globalConformity === 'Conforme')
                            <x-badge variant="success" size="sm" :dot="true">{{ __('reports.curve.conformity_ok') }}</x-badge>
                        @elseif($globalConformity === 'Non Conforme')
                            <x-badge variant="danger" size="sm" :dot="true">{{ __('reports.curve.conformity_nok') }}</x-badge>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex flex-wrap items-center gap-2">
                        <span>{{ __('Report:') }} <strong class="font-mono text-gray-700 dark:text-gray-300">{{ $report->report_number }}</strong></span>
                        <span>&bull;</span>
                        <span>{{ $report->mission?->site?->full_name ?? $report->mission?->site?->short_name ?? __('Site Unassigned') }}</span>
                        @if($report->mission)
                            <span>&bull;</span>
                            <span>{{ __('Mission:') }} <strong class="font-mono text-gray-700 dark:text-gray-300">{{ $report->mission->reference ?? $report->mission->code }}</strong></span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Header Actions -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('metrology.reports.saisie', ['report' => $report->id, 'instrument' => $instrument->id]) }}">
                    <x-info-button type="button" class="gap-1.5 text-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>{{ __('Saisie / Edit Data') }}</span>
                    </x-info-button>
                </a>

                <x-primary-button type="button" onclick="window.print()" class="gap-1.5 text-xs shadow-xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>{{ __('reports.curve.btn_print') }}</span>
                </x-primary-button>

                <a href="{{ route('metrology.reports.show', $report->id) }}">
                    <x-secondary-button type="button" class="gap-1.5 text-xs">
                        <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>{{ __('reports.curve.btn_back') }}</span>
                    </x-secondary-button>
                </a>
            </div>
        </div>
    </x-slot>

    @push('styles')
    <style>
        /* ── SVG Wrapper & Responsive Canvas ── */
        .svg-wrapper {
            width: 100%;
            background: #ffffff;
            border-radius: 1rem;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease-in-out;
        }
        .svg-wrapper svg {
            display: block;
            width: 100%;
            height: auto;
        }

        /* ── SVG Chart Elements styling for Dark Mode ── */
        .dark .svg-wrapper {
            background: #0b1120 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5) !important;
        }
        .dark .gmtm-svg-chart { background: transparent !important; }
        .dark .svg-bg-full { fill: #0f172a !important; }
        .dark .svg-plot-area { fill: #0b1120 !important; stroke: rgba(255, 255, 255, 0.09) !important; }
        .dark .svg-grid-line { stroke: #1e293b !important; }
        .dark .svg-cycle-divider { stroke: #475569 !important; }
        .dark .svg-axis-label-ascending { fill: #60a5fa !important; }
        .dark .svg-axis-label-descending { fill: #34d399 !important; }
        .dark .svg-axis-label-y { fill: #94a3b8 !important; }
        .dark .svg-zero-line { stroke: #475569 !important; }
        .dark .svg-zero-label { fill: #cbd5e1 !important; }
        .dark .svg-emt-band { fill: #064e3b !important; fill-opacity: 0.3 !important; }
        .dark .svg-emt-lines line { stroke: #f87171 !important; }
        .dark .svg-emt-badge-bg { fill: rgba(69, 10, 10, 0.95) !important; stroke: #ef4444 !important; }
        .dark .svg-emt-label { fill: #fca5a5 !important; }
        .dark .svg-axis-lines line { stroke: #64748b !important; }
        .dark .svg-axis-title { fill: #e2e8f0 !important; }
        .dark .svg-path-ascending { stroke: #60a5fa !important; }
        .dark .svg-path-descending { stroke: #34d399 !important; }
        .dark .svg-point-ascending { fill: #60a5fa !important; stroke: #0b1120 !important; }
        .dark .svg-point-descending { fill: #34d399 !important; stroke: #0b1120 !important; }
        .dark .svg-label-bg-ascending { fill: rgba(15, 23, 42, 0.95) !important; stroke: #3b82f6 !important; }
        .dark .svg-label-bg-descending { fill: rgba(15, 23, 42, 0.95) !important; stroke: #10b981 !important; }
        .dark .svg-label-text-ascending { fill: #93c5fd !important; }
        .dark .svg-label-text-descending { fill: #6ee7b7 !important; }
        .dark .svg-legend-line.svg-legend-ascending { stroke: #60a5fa !important; }
        .dark .svg-legend-line.svg-legend-descending { stroke: #34d399 !important; }
        .dark .svg-legend-line.svg-legend-emt { stroke: #f87171 !important; }
        .dark .svg-legend-dot { fill: #0b1120 !important; }
        .dark .svg-legend-text { fill: #cbd5e1 !important; }

        /* ── Print Media Optimization ── */
        @media print {
            nav, header, aside, .no-print {
                display: none !important;
            }
            body, main {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .py-8 { padding-top: 0 !important; padding-bottom: 0 !important; }
            .svg-wrapper {
                box-shadow: none !important;
                border: 1px solid #cbd5e1 !important;
                background: #ffffff !important;
                padding: 12px !important;
            }
            .print-channel-card {
                display: block !important;
                page-break-before: always;
            }
            .print-channel-card:first-of-type {
                page-break-before: avoid;
            }
        }
    </style>
    @endpush

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation (Hidden in print) -->
                <aside class="w-full lg:w-64 shrink-0 no-print">
                    <x-metrology-tabs active="reports" />
                </aside>

                <!-- Main Content Area -->
                <main class="flex-1 w-full min-w-0 space-y-6">

                    <!-- Metrological & Instrument Summary Card -->
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-xs border border-gray-100 dark:border-gray-700/60">
                        <div class="flex flex-wrap items-center justify-between pb-3 mb-4 border-b border-gray-100 dark:border-gray-700/60">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>{{ __('Instrument & Calibration Summary') }}</span>
                            </h3>
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                {{ __('reports.curve.subtitle') }}
                            </span>
                        </div>

                        @php
                            $firstSpec = $instrument->specifications->first();
                            $rangeMin = $firstSpec?->range_min ?? $instrument->range_min;
                            $rangeMax = $firstSpec?->range_max ?? $instrument->range_max;
                            $unitStr = $firstSpec?->grandeur?->symbol ?? ($instrument->unit ?? '');
                        @endphp

                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 text-xs">
                            <!-- Tag Number -->
                            <div class="p-3 rounded-xl bg-gray-50/70 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800/80">
                                <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">{{ __('Tag Number') }}</span>
                                <span class="text-sm font-bold font-mono text-brand-600 dark:text-brand-400 mt-1 block" dir="ltr">
                                    {{ $instrument->tag_number ?? '---' }}
                                </span>
                            </div>

                            <!-- Designation -->
                            <div class="p-3 rounded-xl bg-gray-50/70 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800/80">
                                <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">{{ __('reports.curve.info_designation') }}</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white mt-1 block truncate" title="{{ $instrument->designation ?? '---' }}">
                                    {{ $instrument->designation ?? '---' }}
                                </span>
                            </div>

                            <!-- Serial Number -->
                            <div class="p-3 rounded-xl bg-gray-50/70 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800/80">
                                <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">{{ __('reports.curve.info_serial') }}</span>
                                <span class="text-sm font-bold font-mono text-gray-900 dark:text-white mt-1 block" dir="ltr">
                                    {{ $instrument->serial_number ?? '---' }}
                                </span>
                            </div>

                            <!-- Measuring Range -->
                            <div class="p-3 rounded-xl bg-gray-50/70 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800/80">
                                <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">{{ __('reports.curve.info_range') }}</span>
                                <span class="text-sm font-bold font-mono text-gray-900 dark:text-white mt-1 block" dir="ltr">
                                    @if($rangeMin !== null && $rangeMax !== null)
                                        {{ $rangeMin }} &rarr; {{ $rangeMax }} {{ $unitStr }}
                                    @else
                                        ---
                                    @endif
                                </span>
                            </div>

                            <!-- Overall Decision -->
                            <div class="p-3 rounded-xl bg-gray-50/70 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800/80">
                                <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">{{ __('reports.curve.info_decision') }}</span>
                                <div class="mt-1">
                                    @if($globalConformity === 'Conforme')
                                        <x-badge variant="success" size="md" :dot="true">{{ __('reports.curve.conformity_ok') }}</x-badge>
                                    @elseif($globalConformity === 'Non Conforme')
                                        <x-badge variant="danger" size="md" :dot="true">{{ __('reports.curve.conformity_nok') }}</x-badge>
                                    @else
                                        <x-badge variant="neutral" size="md">{{ __('Pending') }}</x-badge>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Calibration Curve Card -->
                    <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xs border border-gray-100 dark:border-gray-700/60 overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50/70 dark:bg-gray-900/40 border-b border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                                <span>{{ $title ?? __('reports.curve.card_title') }}</span>
                            </h3>

                            @if(!empty($emt) && $emt != 0)
                                <span class="text-xs font-mono font-semibold px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                    {{ __('reports.curve.info_emt') }}: &plusmn;{{ number_format(abs($emt), 4) }}
                                </span>
                            @endif
                        </div>

                        <div class="p-6">
                            @if(isset($curves) && count($curves) > 1)
                                {{-- ═════════════════════════════════════════════════════════════════
                                     Multi-Channel Mode (Flow Computer with multiple simulated channels)
                                     ═════════════════════════════════════════════════════════════════ --}}
                                <div x-data="{ activeTab: 0 }">
                                    <!-- Channel Tabs Header (Interactive screen navigation) -->
                                    <div class="flex items-center gap-2 p-1.5 bg-gray-100/80 dark:bg-gray-900/60 rounded-xl border border-gray-200/80 dark:border-gray-700/60 overflow-x-auto no-print mb-6">
                                        @foreach($curves as $idx => $curve)
                                            @php
                                                $curveConf = $curve['conformity'] ?? null;
                                                $badgeClass = match($curveConf) {
                                                    'Conforme' => 'text-emerald-700 bg-emerald-500/10 border-emerald-500/30 dark:text-emerald-300',
                                                    'Non Conforme' => 'text-rose-700 bg-rose-500/10 border-rose-500/30 dark:text-rose-300',
                                                    default => 'text-gray-600 bg-gray-200/60 dark:text-gray-400',
                                                };
                                            @endphp
                                            <button type="button"
                                                    @click="activeTab = {{ $idx }}"
                                                    :class="activeTab === {{ $idx }}
                                                        ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-xs font-bold border-gray-200 dark:border-gray-700'
                                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium border-transparent'"
                                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs transition border cursor-pointer shrink-0">
                                                <svg class="w-3.5 h-3.5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                                <span>{{ $curve['label'] }}</span>
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold border {{ $badgeClass }}">
                                                    {{ $curveConf === 'Conforme' ? '✓' : ($curveConf === 'Non Conforme' ? '✗' : '–') }}
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>

                                    <!-- Panes Content -->
                                    @foreach($curves as $idx => $curve)
                                        <div x-show="activeTab === {{ $idx }}"
                                             class="print-channel-card"
                                             x-cloak>
                                            <div class="flex flex-wrap items-center justify-between gap-3 pb-4 mb-4 border-b border-gray-100 dark:border-gray-700/60">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                                                        <svg class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                                        <span>{{ __('Channel / Transmitter:') }} <strong class="font-mono text-brand-600 dark:text-brand-400">{{ $curve['label'] }}</strong></span>
                                                    </span>
                                                    @if(!empty($curve['emt']) && $curve['emt'] != 0)
                                                        <span class="text-xs font-mono px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300">
                                                            {{ __('reports.curve.info_emt') }}: &plusmn;{{ number_format(abs($curve['emt']), 4) }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <div>
                                                    @if(($curve['conformity'] ?? null) === 'Conforme')
                                                        <x-badge variant="success" size="sm" :dot="true">{{ __('reports.curve.conformity_ok') }}</x-badge>
                                                    @elseif(($curve['conformity'] ?? null) === 'Non Conforme')
                                                        <x-badge variant="danger" size="sm" :dot="true">{{ __('reports.curve.conformity_nok') }}</x-badge>
                                                    @else
                                                        <x-badge variant="neutral" size="sm">{{ __('N/A') }}</x-badge>
                                                    @endif
                                                </div>
                                            </div>

                                            @if(!empty($curve['svg']))
                                                <div class="svg-wrapper">
                                                    {!! $curve['svg'] !!}
                                                </div>
                                            @else
                                                <div class="flex flex-col items-center justify-center p-12 text-center rounded-2xl bg-gray-50/50 dark:bg-gray-900/30 border border-dashed border-gray-200 dark:border-gray-700">
                                                    <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ __('No calibration data recorded for this channel.') }}</h4>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm">{{ __('Please enter verification points in the saisie form to generate the error curve.') }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                {{-- ═════════════════════════════════════════════════════════════════
                                     Single-Channel Mode (Transmitters, Probes)
                                     ═════════════════════════════════════════════════════════════════ --}}
                                @php
                                    $single = $curves[0] ?? null;
                                    $renderSvg = $single['svg'] ?? $svgCurve ?? null;
                                @endphp

                                @if(!empty($renderSvg))
                                    <div class="svg-wrapper">
                                        {!! $renderSvg !!}
                                    </div>
                                @else
                                    <div class="flex flex-col items-center justify-center p-14 text-center rounded-2xl bg-gray-50/50 dark:bg-gray-900/30 border border-dashed border-gray-200 dark:border-gray-700">
                                        <div class="w-14 h-14 rounded-2xl bg-brand-50 dark:bg-brand-950/40 text-brand-600 dark:text-brand-400 flex items-center justify-center mb-3">
                                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                                        </div>
                                        <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ __('No calibration data available to plot the curve.') }}</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm">
                                            {{ __('Please enter calibration points in the saisie form before viewing the error curve.') }}
                                        </p>
                                        <div class="mt-4">
                                            <a href="{{ route('metrology.reports.saisie', ['report' => $report->id, 'instrument' => $instrument->id]) }}">
                                                <x-primary-button class="gap-1.5 text-xs">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    <span>{{ __('Enter Calibration Points') }}</span>
                                                </x-primary-button>
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                </main>
            </div>
        </div>
    </div>
</x-app-layout>
