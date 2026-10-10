<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                @php
                    $backUrl = isset($report) && $report
                        ? route('metrology.reports.show', $report->id)
                        : (isset($verification) && $verification->id
                            ? route('metrology.reports.report-chromatograph.show', $verification->id)
                            : route('metrology.reports.report-chromatograph.index'));
                @endphp
                <a href="{{ $backUrl }}"
                   class="p-2.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition shadow-2xs"
                   title="{{ __('Back') }}">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <x-tool-icon name="chromatograph" class="w-12 h-12 sm:w-14 sm:h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ __('Gas Chromatograph (CPG) Saisie') }} &bull; <span dir="ltr" class="font-mono text-brand-600 dark:text-brand-400">{{ $instrument->tag_number }}</span>
                        </h2>
                        <x-badge variant="neutral" size="sm">{{ __('Chromatograph CPG') }}</x-badge>
                        @if(isset($verification) && $verification->id)
                            @if($verification->overall_status)
                                <x-badge variant="success" size="sm" :dot="true">{{ __('COMPLIANT') }}</x-badge>
                            @else
                                <x-badge variant="danger" size="sm" :dot="true">{{ __('NON-COMPLIANT') }}</x-badge>
                            @endif
                        @else
                            <x-badge variant="warning" size="sm" :dot="true">{{ __('New Verification Session') }}</x-badge>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex flex-wrap items-center gap-2">
                        <span>{{ __('Site:') }} <strong class="text-gray-700 dark:text-gray-300">{{ $instrument->site?->full_name ?? ($instrument->site?->short_name ?? '-') }}</strong></span>
                        <span>&bull;</span>
                        <span>{{ __('Serial:') }} <span class="font-mono text-gray-700 dark:text-gray-300">{{ $instrument->serial_number ?? '-' }}</span></span>
                        <span>&bull;</span>
                        <span>{{ __('Technology:') }} <span class="text-gray-700 dark:text-gray-300">{{ $instrument->technology ?? 'Gas Chromatography' }} ({{ $instrument->measurement_type ?? 'C6+' }})</span></span>
                        @if(isset($report) && $report)
                            <span>&bull;</span>
                            <span>{{ __('Report:') }} <strong class="font-mono text-brand-600 dark:text-brand-400">{{ $report->report_number }}</strong></span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Excel Import Trigger -->
                <button type="button"
                        class="btn-import-excel-trigger inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition shadow-2xs cursor-pointer"
                        title="{{ __('Import Excel (.xlsx, .csv)') }}">
                    <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zM6 20V4h7v5h5v11H6z"/>
                        <path d="M12 12v6m-3-3l3-3 3 3"/>
                    </svg>
                    <span>{{ __('Import Excel') }}</span>
                </button>
                <input type="file" id="excel-file-input" accept=".xlsx, .xls, .csv" class="hidden">

                @if(isset($verification) && $verification->id)
                    <a href="{{ route('metrology.reports.report-chromatograph.excel', $verification->id) }}" title="{{ __('Excel Report') }}">
                        <x-secondary-button type="button" class="gap-1.5 text-xs">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zM6 20V4h7v5h5v11H6z"/>
                                <path d="M8.8 15.3l1.8-2.8-1.7-2.7h1.6l1 1.8 1-1.8h1.5l-1.7 2.7 1.8 2.8h-1.6l-1-1.9-1 1.9H8.8z"/>
                            </svg>
                            <span>{{ __('Excel') }}</span>
                        </x-secondary-button>
                    </a>

                    <a href="{{ route('metrology.reports.report-chromatograph.pdf', $verification->id) }}" target="_blank" title="{{ __('PDF Report (EMT)') }}">
                        <x-danger-button type="button" class="gap-1.5 text-xs">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ __('PDF') }}</span>
                        </x-danger-button>
                    </a>
                @else
                    <a href="{{ route('metrology.reports.report-chromatograph.template', $instrument->id) }}" title="{{ __('Download Blank Excel Template') }}">
                        <x-secondary-button type="button" class="gap-1.5 text-xs">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span>{{ __('Template') }}</span>
                        </x-secondary-button>
                    </a>
                @endif

                <!-- Save Button -->
                <x-primary-button type="submit" form="calibration-form" class="gap-1.5 text-xs shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    <span>{{ __('Save Verification') }}</span>
                </x-primary-button>
            </div>
        </div>
    </x-slot>

    @php
        $formAction = route('metrology.reports.report-chromatograph.store');
    @endphp

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-metrology-tabs active="reports" />
                </aside>

                <!-- Main Form Area -->
                <main class="flex-1 w-full min-w-0 space-y-6">

                    <!-- Feedback Alert -->
                    @if(session('success'))
                        <x-alert variant="success" :title="session('success')" :dismissible="true" />
                    @endif
                    @if(session('error'))
                        <x-alert variant="danger" :title="session('error')" :dismissible="true" />
                    @endif
                    @if(isset($errors) && $errors->any())
                        <x-alert variant="danger" :title="__('Please correct the following errors:')" :dismissible="true">
                            <ul class="list-disc list-inside space-y-0.5 mt-2 text-xs">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    @endif

                    <form id="calibration-form" action="{{ $formAction }}" method="POST" class="space-y-6">
                        @csrf
                        <input type="hidden" name="instrument_id" value="{{ $instrument->id }}">
                        @if(isset($verification) && $verification->id)
                            <input type="hidden" name="verification_id" value="{{ $verification->id }}">
                        @endif
                        @if(isset($report) && $report)
                            <input type="hidden" name="report_mission_id" value="{{ $report->id }}">
                        @endif

                        <!-- Card 1: Session Information & Certified Standard Gas Cylinder -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700/60 flex-wrap gap-2">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                                        {{ __('General Session & Certified Standard Gas Cylinder') }}
                                    </h3>
                                </div>
                                <x-badge variant="neutral" size="sm">OIML R 140 Class A / ISO 6974-2</x-badge>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <!-- Reference Number -->
                                <div>
                                    <x-input-label for="reference_number" class="text-xs mb-1">
                                        {{ __('Reference Number') }} <span class="text-rose-500">*</span>
                                    </x-input-label>
                                    <input type="text"
                                           id="reference_number"
                                           name="reference_number"
                                           value="{{ old('reference_number', $verification->reference_number ?? ($suggestedReference ?? '')) }}"
                                           required
                                           dir="ltr"
                                           placeholder="Ex: VERIF-GC-2026-0001"
                                           class="w-full text-xs font-mono font-bold rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-brand-700 dark:text-brand-300 focus:border-brand-500 focus:ring-brand-500 py-2 px-3" />
                                </div>

                                <!-- Verification Date -->
                                <div>
                                    <x-input-label for="verification_date" class="text-xs mb-1">
                                        {{ __('Verification Date') }} <span class="text-rose-500">*</span>
                                    </x-input-label>
                                    @php
                                        $formattedDate = old('verification_date', isset($verification->verification_date)
                                            ? ($verification->verification_date instanceof \DateTimeInterface ? $verification->verification_date->format('Y-m-d') : \Carbon\Carbon::parse($verification->verification_date)->format('Y-m-d'))
                                            : date('Y-m-d'));
                                    @endphp
                                    <input type="date"
                                           id="verification_date"
                                           name="verification_date"
                                           value="{{ $formattedDate }}"
                                           required
                                           class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-brand-500 py-2 px-3" />
                                </div>

                                <!-- Standard Gas Bottle Number -->
                                <div>
                                    <x-input-label for="standard_gas_bottle_number" class="text-xs mb-1">
                                        {{ __('Standard Gas Bottle Number') }} <span class="text-rose-500">*</span>
                                    </x-input-label>
                                    <input type="text"
                                           id="standard_gas_bottle_number"
                                           name="standard_gas_bottle_number"
                                           value="{{ old('standard_gas_bottle_number', $verification->standard_gas_bottle_number ?? '') }}"
                                           required
                                           dir="ltr"
                                           placeholder="Ex: CYL-32874654"
                                           class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-brand-500 py-2 px-3" />
                                </div>

                                <!-- Certificate Number -->
                                <div>
                                    <x-input-label for="certificate_number" class="text-xs mb-1">
                                        {{ __('Certificate Number') }} <span class="text-rose-500">*</span>
                                    </x-input-label>
                                    <input type="text"
                                           id="certificate_number"
                                           name="certificate_number"
                                           value="{{ old('certificate_number', $verification->certificate_number ?? '') }}"
                                           required
                                           dir="ltr"
                                           placeholder="Ex: CERT-2026-OAM-001"
                                           class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-brand-500 py-2 px-3" />
                                </div>

                                <!-- Reference Conditions -->
                                <div>
                                    <x-input-label for="reference_conditions" class="text-xs mb-1">
                                        {{ __('Reference Conditions') }} <span class="text-rose-500">*</span>
                                    </x-input-label>
                                    <input type="text"
                                           id="reference_conditions"
                                           name="reference_conditions"
                                           value="{{ old('reference_conditions', $verification->reference_conditions ?? '15°C / 101.325 kPa') }}"
                                           required
                                           dir="ltr"
                                           placeholder="15°C / 101.325 kPa"
                                           class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-brand-500 py-2 px-3" />
                                </div>

                                <!-- Cylinder Validity Date -->
                                <div>
                                    <x-input-label for="cylinder_validity_date" class="text-xs mb-1">
                                        {{ __('Cylinder Validity Date') }}
                                    </x-input-label>
                                    @php
                                        $validityDate = old('cylinder_validity_date', isset($verification->cylinder_validity_date)
                                            ? ($verification->cylinder_validity_date instanceof \DateTimeInterface ? $verification->cylinder_validity_date->format('Y-m-d') : \Carbon\Carbon::parse($verification->cylinder_validity_date)->format('Y-m-d'))
                                            : '');
                                    @endphp
                                    <input type="date"
                                           id="cylinder_validity_date"
                                           name="cylinder_validity_date"
                                           value="{{ $validityDate }}"
                                           class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-brand-500 py-2 px-3" />
                                </div>

                                <!-- Cylinder Pressure (bar) -->
                                <div>
                                    <x-input-label for="cylinder_pressure_bar" class="text-xs mb-1">
                                        {{ __('Cylinder Pressure (bar)') }}
                                    </x-input-label>
                                    <input type="number"
                                           step="0.1"
                                           id="cylinder_pressure_bar"
                                           name="cylinder_pressure_bar"
                                           value="{{ old('cylinder_pressure_bar', $verification->cylinder_pressure_bar ?? '') }}"
                                           placeholder="Ex: 35.0"
                                           class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-brand-500 py-2 px-3" />
                                </div>

                                <!-- Ambient Temp & Pressure -->
                                <div>
                                    <x-input-label class="text-xs mb-1">
                                        {{ __('Ambient Temp (°C) / Pressure (mbar)') }}
                                    </x-input-label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="number"
                                               step="0.1"
                                               name="ambient_temperature"
                                               value="{{ old('ambient_temperature', $verification->ambient_temperature ?? '') }}"
                                               placeholder="20.0 °C"
                                               class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-brand-500 py-2 px-2" />
                                        <input type="number"
                                               step="0.1"
                                               name="ambient_pressure"
                                               value="{{ old('ambient_pressure', $verification->ambient_pressure ?? '') }}"
                                               placeholder="1013 mbar"
                                               class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-brand-500 py-2 px-2" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: 12 Molar Components Table (ASTM D 1945 & ISO 6974-2) -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700/60 flex-wrap gap-2">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                                        {{ __('Exactitude and Repeatability of Chromatographic Analyses') }}
                                    </h3>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-badge variant="neutral" size="sm">{{ __('ASTM 1945-14') }}</x-badge>
                                    <x-badge variant="neutral" size="sm">{{ __('ISO 6974-2 Accuracy') }}</x-badge>
                                </div>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700/60 shadow-2xs">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs" id="components-table">
                                    <thead>
                                        <tr class="bg-gray-100 dark:bg-gray-900/80 text-[11px] font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                            <th rowspan="2" class="px-2.5 py-2.5 text-center w-10 border-b border-gray-200 dark:border-gray-700">#</th>
                                            <th colspan="2" class="px-3 py-2 text-center border-x border-b border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-900/50">
                                                {{ __('Standard Mixture') }}
                                            </th>
                                            <th colspan="5" class="px-3 py-2 text-center border-r border-b border-gray-200 dark:border-gray-700">
                                                {{ __('5 Chromatographic Measurement Runs') }}
                                            </th>
                                            <th colspan="4" class="px-3 py-2 text-center border-r border-b border-gray-200 dark:border-gray-700 bg-teal-50/60 dark:bg-teal-950/30 text-teal-800 dark:text-teal-300">
                                                {{ __('Accuracy ISO 6974-2') }}
                                            </th>
                                            <th colspan="3" class="px-3 py-2 text-center border-b border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-900/50">
                                                {{ __('Repeatability ASTM 1945-14') }}
                                            </th>
                                        </tr>
                                        <tr class="bg-gray-50/90 dark:bg-gray-900/60 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            <th class="px-3 py-2 text-left min-w-[130px]">{{ __('Component') }}</th>
                                            <th class="px-3 py-2 text-right min-w-[90px] border-r border-gray-200 dark:border-gray-700">{{ __('Ref. (% mol)') }}</th>
                                            <th class="px-2 py-2 text-right min-w-[75px]">R1</th>
                                            <th class="px-2 py-2 text-right min-w-[75px]">R2</th>
                                            <th class="px-2 py-2 text-right min-w-[75px]">R3</th>
                                            <th class="px-2 py-2 text-right min-w-[75px]">R4</th>
                                            <th class="px-2 py-2 text-right min-w-[75px] border-r border-gray-200 dark:border-gray-700">R5</th>
                                            <th class="px-3 py-2 text-right min-w-[85px] bg-teal-50/40 dark:bg-teal-950/20 font-bold text-gray-800 dark:text-gray-200">{{ __('Mean') }}</th>
                                            <th class="px-2 py-2 text-right min-w-[70px] bg-teal-50/40 dark:bg-teal-950/20">{{ __('Error %') }}</th>
                                            <th class="px-2 py-2 text-right min-w-[65px] bg-teal-50/40 dark:bg-teal-950/20 text-gray-400">{{ __('EMT') }}</th>
                                            <th class="px-2 py-2 text-center min-w-[75px] bg-teal-50/40 dark:bg-teal-950/20 border-r border-gray-200 dark:border-gray-700">{{ __('Result') }}</th>
                                            <th class="px-2 py-2 text-right min-w-[75px]">{{ __('Rep. r') }}</th>
                                            <th class="px-2 py-2 text-right min-w-[65px] text-gray-400">{{ __('Limit') }}</th>
                                            <th class="px-2 py-2 text-center min-w-[75px]">{{ __('Result') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800 font-mono">
                                        @php
                                            $savedPointsByOrder = $verification?->compositionPoints?->keyBy('step_order') ?? collect();
                                            $savedPointsByName = $verification?->compositionPoints?->keyBy(function($p) {
                                                $str = strtolower(trim(($p->component_symbol ?? '') . ' ' . ($p->component_name ?? '')));
                                                return match(true) {
                                                    str_contains($str, 'c6') || str_contains($str, 'hexane') => 'C6+',
                                                    str_contains($str, 'c3') || str_contains($str, 'propane') => 'Propane',
                                                    str_contains($str, 'ic4') || str_contains($str, 'iso-butane') || str_contains($str, 'i-butane') => 'i-Butane',
                                                    str_contains($str, 'nc4') || str_contains($str, 'normal-butane') || str_contains($str, 'n-butane') => 'n-Butane',
                                                    str_contains($str, 'neoc5') || str_contains($str, 'neo-pentane') || str_contains($str, 'neopentane') => 'Neopentane',
                                                    str_contains($str, 'ic5') || str_contains($str, 'iso-pentane') || str_contains($str, 'i-pentane') => 'i-Pentane',
                                                    str_contains($str, 'nc5') || str_contains($str, 'normal-pentane') || str_contains($str, 'n-pentane') => 'n-Pentane',
                                                    str_contains($str, 'n2') || str_contains($str, 'nitrogen') || str_contains($str, 'azote') => 'Nitrogen',
                                                    str_contains($str, 'c1') || str_contains($str, 'methane') || str_contains($str, 'méthane') => 'Methane',
                                                    str_contains($str, 'co2') || str_contains($str, 'carbon dioxide') || str_contains($str, 'dioxyde') => 'Carbon Dioxide',
                                                    str_contains($str, 'c2') || str_contains($str, 'ethane') => 'Ethane',
                                                    str_contains($str, 'he') || str_contains($str, 'helium') => 'Helium',
                                                    default => $p->component_symbol,
                                                };
                                            }) ?? collect();
                                        @endphp
                                        @foreach($defaultComponents as $index => $comp)
                                            @php
                                                $order = $comp['default_order'];
                                                $pt = $savedPointsByName->get($comp['name']) ?? $savedPointsByOrder->get($order);
                                                $refVal = old("components.{$index}.reference_value", $pt?->reference_value ?? '');
                                                $r1 = old("components.{$index}.run_1", $pt?->run_1 ?? '');
                                                $r2 = old("components.{$index}.run_2", $pt?->run_2 ?? '');
                                                $r3 = old("components.{$index}.run_3", $pt?->run_3 ?? '');
                                                $r4 = old("components.{$index}.run_4", $pt?->run_4 ?? '');
                                                $r5 = old("components.{$index}.run_5", $pt?->run_5 ?? '');
                                            @endphp
                                            <tr class="comp-row hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors" data-order="{{ $order }}">
                                                <td class="px-2.5 py-2 text-center text-gray-400 font-sans font-bold text-[11px]">{{ $order }}</td>
                                                <td class="px-3 py-2 text-left font-sans font-semibold text-gray-900 dark:text-white">
                                                    <input type="hidden" name="components[{{ $index }}][step_order]" value="{{ $order }}">
                                                    <input type="hidden" name="components[{{ $index }}][component_name]" value="{{ $comp['name'] }}">
                                                    <input type="hidden" name="components[{{ $index }}][component_symbol]" value="{{ $comp['symbol'] }}">
                                                    <span>{{ $comp['name'] }}</span>
                                                </td>
                                                <td class="px-2 py-1.5 border-r border-gray-200 dark:border-gray-700">
                                                    <input type="number" step="any" name="components[{{ $index }}][reference_value]"
                                                           value="{{ $refVal }}" required
                                                           class="input-ref w-full text-right font-mono text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 py-1.5 px-2"
                                                           placeholder="0.0000" />
                                                </td>
                                                <td class="px-1.5 py-1.5">
                                                    <input type="number" step="any" name="components[{{ $index }}][run_1]"
                                                           value="{{ $r1 }}" required
                                                           class="input-run input-r1 w-full text-right font-mono text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 py-1.5 px-1.5"
                                                           placeholder="0.0000" />
                                                </td>
                                                <td class="px-1.5 py-1.5">
                                                    <input type="number" step="any" name="components[{{ $index }}][run_2]"
                                                           value="{{ $r2 }}" required
                                                           class="input-run input-r2 w-full text-right font-mono text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 py-1.5 px-1.5"
                                                           placeholder="0.0000" />
                                                </td>
                                                <td class="px-1.5 py-1.5">
                                                    <input type="number" step="any" name="components[{{ $index }}][run_3]"
                                                           value="{{ $r3 }}" required
                                                           class="input-run input-r3 w-full text-right font-mono text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 py-1.5 px-1.5"
                                                           placeholder="0.0000" />
                                                </td>
                                                <td class="px-1.5 py-1.5">
                                                    <input type="number" step="any" name="components[{{ $index }}][run_4]"
                                                           value="{{ $r4 }}" required
                                                           class="input-run input-r4 w-full text-right font-mono text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 py-1.5 px-1.5"
                                                           placeholder="0.0000" />
                                                </td>
                                                <td class="px-1.5 py-1.5 border-r border-gray-200 dark:border-gray-700">
                                                    <input type="number" step="any" name="components[{{ $index }}][run_5]"
                                                           value="{{ $r5 }}" required
                                                           class="input-run input-r5 w-full text-right font-mono text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 py-1.5 px-1.5"
                                                           placeholder="0.0000" />
                                                </td>

                                                <!-- Computed Accuracy -->
                                                <td class="px-2.5 py-2 text-right font-bold text-brand-700 dark:text-brand-300 bg-teal-50/20 dark:bg-teal-950/10 cell-mean">-</td>
                                                <td class="px-2 py-2 text-right font-bold cell-err bg-teal-50/20 dark:bg-teal-950/10">-</td>
                                                <td class="px-2 py-2 text-right text-gray-400 text-[11px] bg-teal-50/20 dark:bg-teal-950/10 cell-iso-lim">-</td>
                                                <td class="px-2 py-2 text-center font-sans cell-iso-decision bg-teal-50/20 dark:bg-teal-950/10 border-r border-gray-200 dark:border-gray-700">-</td>

                                                <!-- Computed Repeatability -->
                                                <td class="px-2.5 py-2 text-right font-bold cell-rep">-</td>
                                                <td class="px-2 py-2 text-right text-gray-400 text-[11px] cell-astm-lim">-</td>
                                                <td class="px-2 py-2 text-center font-sans cell-rep-decision">-</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="font-bold bg-gray-50/90 dark:bg-gray-900/80 border-t border-gray-200 dark:border-gray-700 text-xs">
                                            <td colspan="2" class="px-4 py-3 text-right text-gray-700 dark:text-gray-300 font-sans uppercase tracking-wider">
                                                {{ __('TOTAL (% mol) :') }}
                                            </td>
                                            <td id="sum-ref" class="px-3 py-3 text-right font-mono text-brand-700 dark:text-brand-300 font-bold border-r border-gray-200 dark:border-gray-700">-</td>
                                            <td colspan="5" class="px-3 py-3 text-center text-gray-400 font-sans text-[11px] border-r border-gray-200 dark:border-gray-700">
                                                {{ __('Sum of the 5 measurement runs') }}
                                            </td>
                                            <td id="sum-mean" class="px-3 py-3 text-right font-mono text-brand-700 dark:text-brand-300 font-bold bg-teal-50/40 dark:bg-teal-950/20">-</td>
                                            <td colspan="6" class="px-3 py-3"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Card 3: Natural Gas Energetic & Physical Properties + AGA8 Calculation Engine -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700/60 flex-wrap gap-3">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343a7.975 7.975 0 012.343 5.657c0 2.12-.82 4.14-2.342 5.657z"/>
                                    </svg>
                                    <div>
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                                            {{ __('Exactitude and Repeatability of Physical Properties (ISO 6976 / OIML R 140)') }}
                                        </h3>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ __('PCS / PCI (±0.50%) | Real Density (±0.35%) | Compressibility Factor Z (±0.30%)') }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Live AGA8 / ISO 6976 Calculation Trigger Button -->
                                <button type="button"
                                        id="btn-calc-aga8"
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl font-bold text-xs bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white shadow-sm transition duration-150 cursor-pointer">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    <span>{{ __('Calculate Physical & Energy Properties (ISO 6976 & AGA8)') }}</span>
                                </button>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700/60 shadow-2xs">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs" id="properties-table">
                                    <thead>
                                        <tr class="bg-gray-100 dark:bg-gray-900/80 text-[11px] font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                            <th rowspan="2" class="px-3 py-2.5 text-left min-w-[180px] border-b border-gray-200 dark:border-gray-700">{{ __('Property / Quantity') }}</th>
                                            <th rowspan="2" class="px-2 py-2.5 text-center min-w-[65px] border-b border-gray-200 dark:border-gray-700">{{ __('Unit') }}</th>
                                            <th rowspan="2" class="px-3 py-2.5 text-right min-w-[110px] border-r border-b border-gray-200 dark:border-gray-700">{{ __('Cert. Ref.') }}</th>
                                            <th colspan="5" class="px-3 py-2 text-center border-r border-b border-gray-200 dark:border-gray-700">
                                                {{ __('Chromatographic Runs (Auto-Calculated via AGA8)') }}
                                            </th>
                                            <th colspan="4" class="px-3 py-2 text-center border-r border-b border-gray-200 dark:border-gray-700 bg-teal-50/60 dark:bg-teal-950/30 text-teal-800 dark:text-teal-300">
                                                {{ __('Accuracy ISO 6976') }}
                                            </th>
                                            <th colspan="3" class="px-3 py-2 text-center border-b border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-900/50">
                                                {{ __('Repeatability OIML R 140') }}
                                            </th>
                                        </tr>
                                        <tr class="bg-gray-50/90 dark:bg-gray-900/60 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            <th class="px-2 py-2 text-right min-w-[95px]">R1</th>
                                            <th class="px-2 py-2 text-right min-w-[95px]">R2</th>
                                            <th class="px-2 py-2 text-right min-w-[95px]">R3</th>
                                            <th class="px-2 py-2 text-right min-w-[95px]">R4</th>
                                            <th class="px-2 py-2 text-right min-w-[95px] border-r border-gray-200 dark:border-gray-700">R5</th>
                                            <th class="px-3 py-2 text-right min-w-[100px] bg-teal-50/40 dark:bg-teal-950/20 font-bold text-gray-800 dark:text-gray-200">{{ __('Mean') }}</th>
                                            <th class="px-2 py-2 text-right min-w-[70px] bg-teal-50/40 dark:bg-teal-950/20">{{ __('Error %') }}</th>
                                            <th class="px-2 py-2 text-right min-w-[65px] bg-teal-50/40 dark:bg-teal-950/20 text-gray-400">{{ __('EMT') }}</th>
                                            <th class="px-2 py-2 text-center min-w-[75px] bg-teal-50/40 dark:bg-teal-950/20 border-r border-gray-200 dark:border-gray-700">{{ __('Result') }}</th>
                                            <th class="px-2 py-2 text-right min-w-[75px]">{{ __('Repeatability') }}</th>
                                            <th class="px-2 py-2 text-right min-w-[65px] text-gray-400">{{ __('Limit') }}</th>
                                            <th class="px-2 py-2 text-center min-w-[75px]">{{ __('Result') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800 font-mono">
                                        @php
                                            $savedProps = $verification?->physicalProperties?->keyBy('property_symbol') ?? collect();
                                            $physPropOrderMap = [
                                                'PCS' => 1,
                                                'PCI' => 2,
                                                'Pb' => 3,
                                                'rho' => 3,
                                                'Zb' => 4,
                                                'Z' => 4,
                                            ];
                                            $sortedDefaultProps = collect($defaultPhysicalProperties)->sortBy(function($p) use ($physPropOrderMap) {
                                                return $physPropOrderMap[$p['symbol']] ?? 99;
                                            })->values()->all();
                                        @endphp
                                        @foreach($sortedDefaultProps as $pIndex => $prop)
                                            @php
                                                $sym = $prop['symbol'];
                                                $savedP = $savedProps->get($sym)
                                                    ?? ($sym === 'Pb' ? $savedProps->get('rho') : ($sym === 'Zb' ? $savedProps->get('Z') : null));
                                                $refP = old("physical_properties.{$pIndex}.reference_value", $savedP?->reference_value ?? '');
                                                $pr1 = old("physical_properties.{$pIndex}.run_1", $savedP?->run_1 ?? '');
                                                $pr2 = old("physical_properties.{$pIndex}.run_2", $savedP?->run_2 ?? '');
                                                $pr3 = old("physical_properties.{$pIndex}.run_3", $savedP?->run_3 ?? '');
                                                $pr4 = old("physical_properties.{$pIndex}.run_4", $savedP?->run_4 ?? '');
                                                $pr5 = old("physical_properties.{$pIndex}.run_5", $savedP?->run_5 ?? '');
                                                $isPcs = strtoupper($sym) === 'PCS';
                                                $displayRepLimit = $isPcs ? '0.10' : '-';
                                            @endphp
                                            <tr class="prop-row hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors"
                                                data-symbol="{{ $sym }}"
                                                data-emt="{{ $prop['emt_limit'] }}"
                                                data-has-rep="{{ $isPcs ? '1' : '0' }}"
                                                data-rep-limit="{{ $isPcs ? '0.1' : '' }}">
                                                <td class="px-3 py-2 text-left font-sans font-semibold text-gray-900 dark:text-white">
                                                    <input type="hidden" name="physical_properties[{{ $pIndex }}][property_name]" value="{{ $prop['name'] }}">
                                                    <input type="hidden" name="physical_properties[{{ $pIndex }}][property_symbol]" value="{{ $sym }}">
                                                    <input type="hidden" name="physical_properties[{{ $pIndex }}][unit]" value="{{ $prop['unit'] }}">
                                                    <span>{{ $prop['name'] }}</span>
                                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-teal-100 dark:bg-teal-900/40 text-teal-800 dark:text-teal-300">
                                                        {{ $sym }}
                                                    </span>
                                                </td>
                                                <td class="px-2 py-2 text-center text-gray-500 font-sans">{{ $prop['unit'] ?: '-' }}</td>
                                                <td class="px-2 py-1.5 border-r border-gray-200 dark:border-gray-700">
                                                    <input type="number" step="any" name="physical_properties[{{ $pIndex }}][reference_value]"
                                                           value="{{ $refP }}" required
                                                           class="prop-input-ref w-full text-right font-mono text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 py-1.5 px-2"
                                                           placeholder="0.0000" />
                                                </td>
                                                <td class="px-1.5 py-1.5">
                                                    <input type="number" step="any" name="physical_properties[{{ $pIndex }}][run_1]"
                                                           value="{{ $pr1 }}" required
                                                           class="prop-input-run prop-r1 w-full text-right font-mono text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 py-1.5 px-1.5"
                                                           placeholder="0.0000" />
                                                </td>
                                                <td class="px-1.5 py-1.5">
                                                    <input type="number" step="any" name="physical_properties[{{ $pIndex }}][run_2]"
                                                           value="{{ $pr2 }}" required
                                                           class="prop-input-run prop-r2 w-full text-right font-mono text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 py-1.5 px-1.5"
                                                           placeholder="0.0000" />
                                                </td>
                                                <td class="px-1.5 py-1.5">
                                                    <input type="number" step="any" name="physical_properties[{{ $pIndex }}][run_3]"
                                                           value="{{ $pr3 }}" required
                                                           class="prop-input-run prop-r3 w-full text-right font-mono text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 py-1.5 px-1.5"
                                                           placeholder="0.0000" />
                                                </td>
                                                <td class="px-1.5 py-1.5">
                                                    <input type="number" step="any" name="physical_properties[{{ $pIndex }}][run_4]"
                                                           value="{{ $pr4 }}" required
                                                           class="prop-input-run prop-r4 w-full text-right font-mono text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 py-1.5 px-1.5"
                                                           placeholder="0.0000" />
                                                </td>
                                                <td class="px-1.5 py-1.5 border-r border-gray-200 dark:border-gray-700">
                                                    <input type="number" step="any" name="physical_properties[{{ $pIndex }}][run_5]"
                                                           value="{{ $pr5 }}" required
                                                           class="prop-input-run prop-r5 w-full text-right font-mono text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 py-1.5 px-1.5"
                                                           placeholder="0.0000" />
                                                </td>

                                                <!-- Computed Accuracy -->
                                                <td class="px-2.5 py-2 text-right font-bold text-brand-700 dark:text-brand-300 bg-teal-50/20 dark:bg-teal-950/10 prop-cell-mean">-</td>
                                                <td class="px-2 py-2 text-right font-bold prop-cell-err bg-teal-50/20 dark:bg-teal-950/10">-</td>
                                                <td class="px-2 py-2 text-right text-gray-400 text-[11px] bg-teal-50/20 dark:bg-teal-950/10">±{{ number_format($prop['emt_limit'], 2) }}%</td>
                                                <td class="px-2 py-2 text-center font-sans prop-cell-err-decision bg-teal-50/20 dark:bg-teal-950/10 border-r border-gray-200 dark:border-gray-700">-</td>

                                                <!-- Computed Repeatability -->
                                                <td class="px-2.5 py-2 text-right font-bold prop-cell-rep">-</td>
                                                <td class="px-2 py-2 text-right text-gray-400 text-[11px]">{{ $displayRepLimit }}</td>
                                                <td class="px-2 py-2 text-center font-sans prop-cell-rep-decision">-</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Card 4: Metrological Summary & Remarks -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700/60">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                                        {{ __('Metrological Summary & Final Conformity Decision') }}
                                    </h3>
                                </div>
                            </div>

                            <!-- Live Metrological Verdict Grid (Updated automatically via JS) -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
                                <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-700/60 bg-gray-50/60 dark:bg-gray-900/40 text-center">
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider mb-1">
                                        {{ __('Composition Repeatability') }}
                                    </div>
                                    <div id="badge-summary-rep" class="font-bold text-sm text-gray-900 dark:text-white">-</div>
                                </div>

                                <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-700/60 bg-gray-50/60 dark:bg-gray-900/40 text-center">
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider mb-1">
                                        {{ __('Composition Accuracy') }}
                                    </div>
                                    <div id="badge-summary-comp" class="font-bold text-sm text-gray-900 dark:text-white">-</div>
                                </div>

                                <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-700/60 bg-gray-50/60 dark:bg-gray-900/40 text-center">
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider mb-1">
                                        {{ __('Physical Properties') }}
                                    </div>
                                    <div id="badge-summary-prop" class="font-bold text-sm text-gray-900 dark:text-white">-</div>
                                </div>

                                <div class="p-3.5 rounded-xl border border-teal-200 dark:border-teal-800 bg-teal-50/60 dark:bg-teal-950/30 text-center">
                                    <div class="text-[11px] text-teal-700 dark:text-teal-300 font-semibold uppercase tracking-wider mb-1">
                                        {{ __('Overall Decision') }}
                                    </div>
                                    <div id="badge-summary-overall" class="font-bold text-sm text-teal-800 dark:text-teal-200">-</div>
                                </div>
                            </div>

                            <!-- Inspector Remarks Textarea -->
                            <div class="mt-4">
                                <x-input-label for="remarks" class="text-xs mb-1">
                                    {{ __('Inspector Remarks & Metrological Observations') }}
                                </x-input-label>
                                <textarea id="remarks"
                                          name="remarks"
                                          rows="3"
                                          placeholder="{{ __('Specific observations regarding the chromatograph, peak stability, thermal regulation, or cylinder pressure...') }}"
                                          class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-brand-500 focus:ring-brand-500 p-3 leading-relaxed">{{ old('remarks', $verification->remarks ?? '') }}</textarea>
                            </div>
                        </div>

                        <!-- Bottom Action Bar -->
                        <div class="flex items-center justify-between gap-4 p-4 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 shadow-sm flex-wrap">
                            <a href="{{ $backUrl }}">
                                <x-secondary-button type="button" class="gap-1.5 text-xs">
                                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                    </svg>
                                    <span>{{ __('Back') }}</span>
                                </x-secondary-button>
                            </a>

                            <div class="flex items-center gap-2">
                                <button type="button"
                                        class="btn-import-excel-trigger inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition cursor-pointer">
                                    <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zM6 20V4h7v5h5v11H6z"/>
                                        <path d="M12 12v6m-3-3l3-3 3 3"/>
                                    </svg>
                                    <span>{{ __('Import Excel') }}</span>
                                </button>

                                <x-primary-button type="submit" class="gap-1.5 text-xs shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                                    </svg>
                                    <span>{{ __('Save Calibration Data') }}</span>
                                </x-primary-button>
                            </div>
                        </div>

                    </form>
                </main>
            </div>
        </div>
    </div>

    @push('scripts')
    <!-- SheetJS for Live Excel Import -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Limite de répétabilité ASTM D 1945
        function getAstmRepeatabilityLimit(mean) {
            const m = Math.abs(mean);
            if (m <= 0.09) return 0.010;
            if (m <= 0.90) return 0.040;
            if (m <= 4.90) return 0.070;
            if (m <= 10.00) return 0.080;
            return 0.100;
        }

        // 2. Limite EMT ISO 6974-2 (%)
        function getIsoEmtLimit(ref) {
            const r = Math.abs(ref);
            if (r < 0.10) return 100.0;
            if (r < 1.00) return 50.0;
            if (r < 10.00) return 10.0;
            if (r < 50.00) return 5.0;
            return 3.0;
        }

        // 3. Formatage dynamique des nombres (préserve jusqu'à 13 décimales, min 4 décimales)
        function formatDynamicNumber(val, minDecimals = 4, maxDecimals = 13) {
            if (isNaN(val) || val === null || val === undefined) return '-';
            let s = Number(val).toFixed(maxDecimals);
            let parts = s.split('.');
            if (parts.length === 2) {
                let dec = parts[1].replace(/0+$/, '');
                while (dec.length < minDecimals) dec += '0';
                return parts[0] + '.' + dec;
            }
            return s;
        }

        const compRows = document.querySelectorAll('.comp-row');
        const propRows = document.querySelectorAll('.prop-row');

        function calculateAll() {
            let allCompRepConforme = true;
            let allCompErrConforme = true;
            let sumRef = 0;
            let sumMean = 0;
            let compCountFilled = 0;

            // --- A. Calcul des Composants ---
            compRows.forEach(row => {
                const inputRef = row.querySelector('.input-ref');
                const runInputs = row.querySelectorAll('.input-run');
                const cellMean = row.querySelector('.cell-mean');
                const cellErr = row.querySelector('.cell-err');
                const cellIsoLim = row.querySelector('.cell-iso-lim');
                const cellIsoDecision = row.querySelector('.cell-iso-decision');
                const cellRep = row.querySelector('.cell-rep');
                const cellAstmLim = row.querySelector('.cell-astm-lim');
                const cellRepDecision = row.querySelector('.cell-rep-decision');

                const refVal = parseFloat(inputRef.value);
                const runs = [];
                runInputs.forEach(inp => {
                    const v = parseFloat(inp.value);
                    if (!isNaN(v)) runs.push(v);
                });

                if (!isNaN(refVal)) {
                    sumRef += refVal;
                }

                if (runs.length === 5 && !isNaN(refVal) && refVal > 0) {
                    compCountFilled++;
                    const rawMean = runs.reduce((a, b) => a + b, 0) / 5;
                    sumMean += rawMean;
                    const formattedMean = formatDynamicNumber(rawMean, 4, 13);
                    cellMean.textContent = formattedMean;
                    cellMean.title = formattedMean;

                    // Erreur ISO 6974-2: (RawMean - Ref) / Ref (3 décimales)
                    const rawFraction = Math.abs(refVal) > 0.0000001 ? ((rawMean - refVal) / refVal) : 0;
                    const errRel = parseFloat(rawFraction.toFixed(3));
                    const isoLim = getIsoEmtLimit(refVal);
                    const percentError = Math.abs(rawFraction) * 100;
                    const errOk = percentError <= (isoLim + 0.000001);
                    const errStr = Math.abs(errRel) < 0.0005 ? '0.0 %' : (errRel.toFixed(3) + ' %');

                    cellErr.textContent = errStr;
                    cellIsoLim.textContent = isoLim.toFixed(1) + ' %';
                    if (errOk) {
                        cellErr.className = 'px-2 py-2 text-right font-bold text-emerald-600 dark:text-emerald-400 bg-teal-50/20 dark:bg-teal-950/10 cell-err';
                        cellIsoDecision.innerHTML = '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300">OK</span>';
                    } else {
                        cellErr.className = 'px-2 py-2 text-right font-bold text-rose-600 dark:text-rose-400 bg-teal-50/20 dark:bg-teal-950/10 cell-err';
                        cellIsoDecision.innerHTML = '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 dark:bg-rose-900/40 text-rose-800 dark:text-rose-300">NOK</span>';
                        allCompErrConforme = false;
                    }

                    // Répétabilité ASTM D 1945: max(runs) - min(runs)
                    const maxR = Math.max(...runs);
                    const minR = Math.min(...runs);
                    const rep = maxR - minR;
                    const astmLim = getAstmRepeatabilityLimit(rawMean);
                    const repOk = rep <= (astmLim + 0.000001);

                    cellRep.textContent = rep.toFixed(4);
                    cellAstmLim.textContent = astmLim.toFixed(3);
                    if (repOk) {
                        cellRep.className = 'px-2.5 py-2 text-right font-bold text-gray-700 dark:text-gray-300 cell-rep';
                        cellRepDecision.innerHTML = '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300">OK</span>';
                    } else {
                        cellRep.className = 'px-2.5 py-2 text-right font-bold text-rose-600 dark:text-rose-400 cell-rep';
                        cellRepDecision.innerHTML = '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 dark:bg-rose-900/40 text-rose-800 dark:text-rose-300">NOK</span>';
                        allCompRepConforme = false;
                    }
                } else {
                    cellMean.textContent = '-';
                    cellErr.textContent = '-';
                    cellIsoLim.textContent = '-';
                    cellIsoDecision.textContent = '-';
                    cellRep.textContent = '-';
                    cellAstmLim.textContent = '-';
                    cellRepDecision.textContent = '-';
                    allCompRepConforme = false;
                    allCompErrConforme = false;
                }
            });

            const sumRefEl = document.getElementById('sum-ref');
            const sumMeanEl = document.getElementById('sum-mean');
            if (sumRefEl) sumRefEl.textContent = sumRef > 0 ? formatDynamicNumber(sumRef, 4, 13) : '-';
            if (sumMeanEl) sumMeanEl.textContent = sumMean > 0 ? formatDynamicNumber(sumMean, 4, 13) : '-';

            // --- B. Calcul des Propriétés Physiques ---
            let allPropErrConforme = true;
            let allPropRepConforme = true;
            let propCountFilled = 0;

            propRows.forEach(row => {
                const inputRef = row.querySelector('.prop-input-ref');
                const runInputs = row.querySelectorAll('.prop-input-run');
                const cellMean = row.querySelector('.prop-cell-mean');
                const cellErr = row.querySelector('.prop-cell-err');
                const cellErrDecision = row.querySelector('.prop-cell-err-decision');
                const cellRep = row.querySelector('.prop-cell-rep');
                const cellRepDecision = row.querySelector('.prop-cell-rep-decision');

                const emtLimit = parseFloat(row.dataset.emt) || 0.5;
                const hasRep = row.dataset.hasRep === '1';
                const repLimit = parseFloat(row.dataset.repLimit) || 0.1;

                const refVal = parseFloat(inputRef.value);
                const runs = [];
                runInputs.forEach(inp => {
                    const v = parseFloat(inp.value);
                    if (!isNaN(v)) runs.push(v);
                });

                if (runs.length === 5 && !isNaN(refVal) && refVal > 0) {
                    propCountFilled++;
                    const rawMean = runs.reduce((a, b) => a + b, 0) / 5;
                    const formattedMean = formatDynamicNumber(rawMean, 6, 13);
                    cellMean.textContent = formattedMean;
                    cellMean.title = formattedMean;

                    // Erreur relative ISO 6976: ((RawMean - Ref) / Ref) * 100
                    const relErrPercent = Math.abs(refVal) > 0.0000001 ? (((rawMean - refVal) / refVal) * 100) : 0;
                    const errOk = Math.abs(relErrPercent) <= (emtLimit + 0.000001);

                    cellErr.textContent = relErrPercent.toFixed(2) + ' %';
                    if (errOk) {
                        cellErr.className = 'px-2 py-2 text-right font-bold text-emerald-600 dark:text-emerald-400 bg-teal-50/20 dark:bg-teal-950/10 prop-cell-err';
                        cellErrDecision.innerHTML = '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300">OK</span>';
                    } else {
                        cellErr.className = 'px-2 py-2 text-right font-bold text-rose-600 dark:text-rose-400 bg-teal-50/20 dark:bg-teal-950/10 prop-cell-err';
                        cellErrDecision.innerHTML = '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 dark:bg-rose-900/40 text-rose-800 dark:text-rose-300">NOK</span>';
                        allPropErrConforme = false;
                    }

                    // Répétabilité OIML R 140 (seulement pour PCS: 0.1 MJ/m3)
                    if (hasRep) {
                        const maxR = Math.max(...runs);
                        const minR = Math.min(...runs);
                        const rep = maxR - minR;
                        const repOk = rep <= (repLimit + 0.000001);

                        cellRep.textContent = rep.toFixed(3);
                        if (repOk) {
                            cellRep.className = 'px-2.5 py-2 text-right font-bold text-gray-700 dark:text-gray-300 prop-cell-rep';
                            cellRepDecision.innerHTML = '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300">OK</span>';
                        } else {
                            cellRep.className = 'px-2.5 py-2 text-right font-bold text-rose-600 dark:text-rose-400 prop-cell-rep';
                            cellRepDecision.innerHTML = '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 dark:bg-rose-900/40 text-rose-800 dark:text-rose-300">NOK</span>';
                            allPropRepConforme = false;
                        }
                    } else {
                        cellRep.textContent = '-';
                        cellRepDecision.textContent = '-';
                    }
                } else {
                    cellMean.textContent = '-';
                    cellErr.textContent = '-';
                    cellErrDecision.textContent = '-';
                    cellRep.textContent = '-';
                    cellRepDecision.textContent = '-';
                    allPropErrConforme = false;
                }
            });

            // --- C. Synthèse globale & Badges de décision ---
            const badgeRep = document.getElementById('badge-summary-rep');
            const badgeComp = document.getElementById('badge-summary-comp');
            const badgeProp = document.getElementById('badge-summary-prop');
            const badgeOverall = document.getElementById('badge-summary-overall');

            const hasCompletedComps = (compCountFilled >= 11);
            const hasCompletedProps = (propCountFilled >= 4);

            if (badgeRep) {
                if (!hasCompletedComps) {
                    badgeRep.innerHTML = '<span class="text-gray-400">{{ __('Incomplete') }}</span>';
                } else if (allCompRepConforme) {
                    badgeRep.innerHTML = '<span class="text-emerald-600 dark:text-emerald-400"><i class="fas fa-check-circle"></i> {{ __('COMPLIANT') }}</span>';
                } else {
                    badgeRep.innerHTML = '<span class="text-rose-600 dark:text-rose-400"><i class="fas fa-times-circle"></i> {{ __('NON-COMPLIANT') }}</span>';
                }
            }

            if (badgeComp) {
                if (!hasCompletedComps) {
                    badgeComp.innerHTML = '<span class="text-gray-400">{{ __('Incomplete') }}</span>';
                } else if (allCompErrConforme) {
                    badgeComp.innerHTML = '<span class="text-emerald-600 dark:text-emerald-400"><i class="fas fa-check-circle"></i> {{ __('COMPLIANT') }}</span>';
                } else {
                    badgeComp.innerHTML = '<span class="text-rose-600 dark:text-rose-400"><i class="fas fa-times-circle"></i> {{ __('NON-COMPLIANT') }}</span>';
                }
            }

            if (badgeProp) {
                if (!hasCompletedProps) {
                    badgeProp.innerHTML = '<span class="text-gray-400">{{ __('Incomplete') }}</span>';
                } else if (allPropErrConforme && allPropRepConforme) {
                    badgeProp.innerHTML = '<span class="text-emerald-600 dark:text-emerald-400"><i class="fas fa-check-circle"></i> {{ __('COMPLIANT') }}</span>';
                } else {
                    badgeProp.innerHTML = '<span class="text-rose-600 dark:text-rose-400"><i class="fas fa-times-circle"></i> {{ __('NON-COMPLIANT') }}</span>';
                }
            }

            if (badgeOverall) {
                if (!hasCompletedComps || !hasCompletedProps) {
                    badgeOverall.innerHTML = '<span class="text-gray-400 uppercase text-xs tracking-wider">{{ __('Pending Data Entry') }}</span>';
                } else if (allCompRepConforme && allCompErrConforme && allPropErrConforme && allPropRepConforme) {
                    badgeOverall.innerHTML = '<span class="text-emerald-700 dark:text-emerald-300 font-extrabold uppercase tracking-wider"><i class="fas fa-check-circle"></i> {{ __('COMPLIANT (OIML R 140 / ISO 6974)') }}</span>';
                } else {
                    badgeOverall.innerHTML = '<span class="text-rose-700 dark:text-rose-300 font-extrabold uppercase tracking-wider"><i class="fas fa-times-circle"></i> {{ __('NON-COMPLIANT') }}</span>';
                }
            }
        }

        // Écouter les changements dans toutes les cellules d'entrée
        document.querySelectorAll('.input-ref, .input-run, .prop-input-ref, .prop-input-run').forEach(inp => {
            inp.addEventListener('input', calculateAll);
            inp.addEventListener('change', calculateAll);
        });

        // 4. Calcul Automatique via AGA8-Detail
        const btnAga8 = document.getElementById('btn-calc-aga8');
        if (btnAga8) {
            btnAga8.addEventListener('click', async function() {
                const originalHtml = btnAga8.innerHTML;
                btnAga8.disabled = true;
                btnAga8.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> <span>' + @json(__('Calculating properties with AGA8...')) + '</span>';

                try {
                    const refComp = {};
                    const runComps = { 1: {}, 2: {}, 3: {}, 4: {}, 5: {} };
                    let hasAnyInput = false;

                    compRows.forEach(row => {
                        const nameInput = row.querySelector('input[name*="[component_name]"]');
                        const compName = nameInput ? nameInput.value : row.querySelector('td:nth-child(2) span')?.textContent?.trim();
                        if (!compName) return;

                        const refVal = parseFloat(row.querySelector('.input-ref')?.value);
                        if (!isNaN(refVal) && refVal > 0) {
                            refComp[compName] = refVal;
                            hasAnyInput = true;
                        }

                        for (let r = 1; r <= 5; r++) {
                            const rVal = parseFloat(row.querySelector('.input-r' + r)?.value);
                            if (!isNaN(rVal) && rVal > 0) {
                                runComps[r][compName] = rVal;
                                hasAnyInput = true;
                            }
                        }
                    });

                    if (!hasAnyInput) {
                        alert(@json(__('Please enter at least reference composition or Run 1 values first.')));
                        return;
                    }

                    const tempC = 15.0;
                    const pressKpa = 101.325;

                    const calculateSet = async (comp) => {
                        if (!comp || Object.keys(comp).length === 0) return null;
                        try {
                            const response = await fetch('{{ route('api.aga8.calculate') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    pressure: pressKpa,
                                    pressure_unit: 'kpa',
                                    temperature: tempC,
                                    temperature_unit: 'c',
                                    composition: comp
                                })
                            });
                            if (response.ok) {
                                const res = await response.json();
                                if (res && res.success && res.data) {
                                    const iso = res.energy_properties_iso6976 || res.data?.energy_properties_iso6976 || {};
                                    const data = res.data;

                                    const pcs = iso.gross_calorific_value_pcs_mj_m3 ?? data.gross_calorific_value_pcs_mj_m3 ?? null;
                                    const pci = iso.net_calorific_value_pci_mj_m3 ?? data.net_calorific_value_pci_mj_m3 ?? null;
                                    const zb = iso.compressibility_factor_zb ?? data.compressibility_factor_zb ?? data.compressibility_factor_z;
                                    const pb = iso.density_pb_kg_m3 ?? data.density_pb_kg_m3 ?? (parseFloat(data.molar_density_mol_l) * parseFloat(data.molar_mass_g_mol));

                                    return { PCS: pcs, PCI: pci, Zb: zb, Pb: pb };
                                }
                            }
                        } catch (e) {
                            console.warn('API AGA8 unreachable:', e);
                        }
                        return null;
                    };

                    const results = {
                        ref: await calculateSet(refComp),
                        r1: await calculateSet(runComps[1]),
                        r2: await calculateSet(runComps[2]),
                        r3: await calculateSet(runComps[3]),
                        r4: await calculateSet(runComps[4]),
                        r5: await calculateSet(runComps[5]),
                    };

                    // Assigner aux champs correspondants
                    propRows.forEach(row => {
                        const sym = row.dataset.symbol;
                        const key = (sym === 'rho') ? 'Pb' : ((sym === 'Z') ? 'Zb' : sym);

                        if (results.ref && results.ref[key] !== undefined && results.ref[key] !== null) {
                            const refInput = row.querySelector('.prop-input-ref');
                            if (refInput && (!refInput.value || parseFloat(refInput.value) === 0)) {
                                refInput.value = results.ref[key];
                            }
                        }

                        for (let r = 1; r <= 5; r++) {
                            const rKey = 'r' + r;
                            if (results[rKey] && results[rKey][key] !== undefined && results[rKey][key] !== null) {
                                const runInput = row.querySelector('.prop-r' + r);
                                if (runInput) {
                                    runInput.value = results[rKey][key];
                                }
                            }
                        }
                    });

                    calculateAll();
                } catch (err) {
                    console.error('AGA8 calculation error:', err);
                    alert('Erreur lors du calcul AGA8: ' + err.message);
                } finally {
                    btnAga8.disabled = false;
                    btnAga8.innerHTML = originalHtml;
                }
            });
        }

        // 5. Live Excel Import via SheetJS
        const importExcelButtons = document.querySelectorAll('.btn-import-excel-trigger');
        const excelFileInput = document.getElementById('excel-file-input');

        if (importExcelButtons.length > 0 && excelFileInput) {
            importExcelButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    excelFileInput.click();
                });
            });

            excelFileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                if (typeof window.XLSX === 'undefined') {
                    alert('La bibliothèque SheetJS (XLSX) n\'est pas chargée.');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(evt) {
                    try {
                        const data = new Uint8Array(evt.target.result);
                        const workbook = window.XLSX.read(data, { type: 'array' });

                        if (!workbook.SheetNames || workbook.SheetNames.length === 0) {
                            alert(@json(__('Invalid Excel file format or no chromatographic data found.')));
                            return;
                        }

                        let targetSheet = null;
                        for (const sName of workbook.SheetNames) {
                            const ws = workbook.Sheets[sName];
                            const text = JSON.stringify(ws);
                            if (text.toLowerCase().includes('fraction') || text.toLowerCase().includes('chromato') || text.toLowerCase().includes('cpg') || text.toLowerCase().includes('run 1') || text.toLowerCase().includes('methane') || text.toLowerCase().includes('c1')) {
                                targetSheet = ws;
                                break;
                            }
                        }
                        if (!targetSheet) {
                            targetSheet = workbook.Sheets[workbook.SheetNames[0]];
                        }

                        const rows = window.XLSX.utils.sheet_to_json(targetSheet, { header: 1, defval: '' });

                        const matchComponent = (str) => {
                            const s = String(str || '').toLowerCase().trim();
                            if (!s) return null;
                            if (s === 'c6+' || s.includes('hexane') || s.includes('c6')) return 'C6+';
                            if (s === 'c3' || s.includes('propane')) return 'Propane';
                            if (s.includes('neoc5') || s.includes('neo-pentane') || s.includes('neopentane')) return 'Neopentane';
                            if (s.includes('ic4') || s.includes('iso-butane') || s.includes('isobutane') || s.includes('i-butane')) return 'i-Butane';
                            if (s.includes('nc4') || s.includes('normal-butane') || s.includes('n-butane') || s === 'butane') return 'n-Butane';
                            if (s.includes('ic5') || s.includes('iso-pentane') || s.includes('isopentane') || s.includes('i-pentane')) return 'i-Pentane';
                            if (s.includes('nc5') || s.includes('normal-pentane') || s.includes('n-pentane') || s === 'pentane') return 'n-Pentane';
                            if (s === 'n2' || s.includes('nitrogen') || s.includes('azote')) return 'Nitrogen';
                            if (s === 'c1' || s.includes('methane') || s.includes('méthane')) return 'Methane';
                            if (s === 'co2' || s.includes('carbon dioxide') || s.includes('dioxyde')) return 'Carbon Dioxide';
                            if (s === 'c2' || s.includes('ethane') || s.includes('éthane')) return 'Ethane';
                            if (s === 'he' || s.includes('helium') || s.includes('hélium')) return 'Helium';
                            return null;
                        };

                        const matchProperty = (str) => {
                            const s = String(str || '').toUpperCase().trim();
                            if (s.includes('PCS')) return 'PCS';
                            if (s.includes('PCI')) return 'PCI';
                            if (s.includes('PB') || s.includes('RHO') || s.includes('DENSITY') || s.includes('MASSE')) return 'Pb';
                            if (s.includes('ZB') || s === 'Z' || s.includes('COMPRESSIBILIT')) return 'Zb';
                            return null;
                        };

                        let refCol = 4, r1Col = 5, r2Col = 6, r3Col = 7, r4Col = 8, r5Col = 9;

                        for (let i = 0; i < rows.length; i++) {
                            const r = rows[i];
                            if (!Array.isArray(r)) continue;

                            for (let j = 0; j < r.length - 1; j++) {
                                const cellStr = String(r[j]).toLowerCase();
                                const nextVal = String(r[j + 1] || '').trim();
                                if (!nextVal) continue;

                                if (cellStr.includes('bouteille') && cellStr.includes('gaz')) {
                                    const inp = document.querySelector('input[name="standard_gas_bottle_number"]');
                                    if (inp) inp.value = nextVal;
                                } else if (cellStr.includes('certificat n')) {
                                    const inp = document.querySelector('input[name="certificate_number"]');
                                    if (inp) inp.value = nextVal;
                                } else if (cellStr.includes('validit')) {
                                    const dateMatch = nextVal.match(/(\d{2})[\/\-](\d{2})[\/\-](\d{4})/);
                                    if (dateMatch) {
                                        const inp = document.querySelector('input[name="cylinder_validity_date"]');
                                        if (inp) inp.value = `${dateMatch[3]}-${dateMatch[2]}-${dateMatch[1]}`;
                                    }
                                } else if (cellStr.includes('pression') && cellStr.includes('bouteille')) {
                                    const num = parseFloat(nextVal.replace(/[^\d.]/g, ''));
                                    if (!isNaN(num)) {
                                        const inp = document.querySelector('input[name="cylinder_pressure_bar"]');
                                        if (inp) inp.value = num;
                                    }
                                } else if (cellStr.includes('conditions de r')) {
                                    const inp = document.querySelector('input[name="reference_conditions"]');
                                    if (inp) inp.value = nextVal;
                                } else if (cellStr.includes('température amb') || cellStr.includes('temperature amb')) {
                                    const num = parseFloat(nextVal.replace(/[^\d.]/g, ''));
                                    if (!isNaN(num)) {
                                        const inp = document.querySelector('input[name="ambient_temperature"]');
                                        if (inp) inp.value = num;
                                    }
                                } else if (cellStr.includes('pression amb')) {
                                    const num = parseFloat(nextVal.replace(/[^\d.]/g, ''));
                                    if (!isNaN(num)) {
                                        const inp = document.querySelector('input[name="ambient_pressure"]');
                                        if (inp) inp.value = num;
                                    }
                                }
                            }

                            const rowStr = r.map(c => String(c).toLowerCase()).join(' ');
                            if (rowStr.includes('run 1') && (rowStr.includes('composant') || rowStr.includes('valeur r') || rowStr.includes('ref'))) {
                                for (let c = 0; c < r.length; c++) {
                                    const h = String(r[c]).toLowerCase();
                                    if (h.includes('r') && (h.includes('f') || h.includes('ref') || h.includes('cert'))) refCol = c;
                                    else if (h.includes('run 1') || h === 'r1') r1Col = c;
                                    else if (h.includes('run 2') || h === 'r2') r2Col = c;
                                    else if (h.includes('run 3') || h === 'r3') r3Col = c;
                                    else if (h.includes('run 4') || h === 'r4') r4Col = c;
                                    else if (h.includes('run 5') || h === 'r5') r5Col = c;
                                }
                            }
                        }

                        let importedCount = 0;
                        rows.forEach(row => {
                            if (!Array.isArray(row) || row.length < 2) return;

                            let matchedComp = null;
                            for (let c = 0; c < Math.min(row.length, 4); c++) {
                                matchedComp = matchComponent(row[c]);
                                if (matchedComp) break;
                            }
                            if (!matchedComp) return;

                            const domRow = Array.from(compRows).find(cr => {
                                const nameInp = cr.querySelector('input[name*="[component_name]"]');
                                const symInp = cr.querySelector('input[name*="[component_symbol]"]');
                                const name = nameInp ? nameInp.value : cr.querySelector('td:nth-child(2) span')?.textContent?.trim();
                                const sym = symInp ? symInp.value : '';
                                return matchComponent(name) === matchedComp || matchComponent(sym) === matchedComp;
                            });

                            if (!domRow) return;

                            const parseVal = (v) => {
                                if (v === null || v === undefined || v === '') return '';
                                const num = parseFloat(String(v).replace(',', '.').replace(/[^\d.-]/g, ''));
                                return isNaN(num) ? '' : num;
                            };

                            const refVal = parseVal(row[refCol]);
                            const r1Val = parseVal(row[r1Col]);
                            const r2Val = parseVal(row[r2Col]);
                            const r3Val = parseVal(row[r3Col]);
                            const r4Val = parseVal(row[r4Col]);
                            const r5Val = parseVal(row[r5Col]);

                            if (refVal !== '') domRow.querySelector('.input-ref').value = refVal;
                            if (r1Val !== '') domRow.querySelector('.input-r1').value = r1Val;
                            if (r2Val !== '') domRow.querySelector('.input-r2').value = r2Val;
                            if (r3Val !== '') domRow.querySelector('.input-r3').value = r3Val;
                            if (r4Val !== '') domRow.querySelector('.input-r4').value = r4Val;
                            if (r5Val !== '') domRow.querySelector('.input-r5').value = r5Val;

                            importedCount++;
                        });

                        rows.forEach(row => {
                            if (!Array.isArray(row) || row.length < 2) return;
                            let matchedProp = null;
                            for (let c = 0; c < Math.min(row.length, 4); c++) {
                                matchedProp = matchProperty(row[c]);
                                if (matchedProp) break;
                            }
                            if (!matchedProp) return;

                            const domPropRow = Array.from(propRows).find(pr => {
                                const s = pr.dataset.symbol;
                                return matchProperty(s) === matchedProp;
                            });

                            if (!domPropRow) return;

                            const parseVal = (v) => {
                                if (v === null || v === undefined || v === '') return '';
                                const num = parseFloat(String(v).replace(',', '.').replace(/[^\d.-]/g, ''));
                                return isNaN(num) ? '' : num;
                            };

                            const refVal = parseVal(row[refCol]);
                            const r1Val = parseVal(row[r1Col]);
                            const r2Val = parseVal(row[r2Col]);
                            const r3Val = parseVal(row[r3Col]);
                            const r4Val = parseVal(row[r4Col]);
                            const r5Val = parseVal(row[r5Col]);

                            if (refVal !== '') domPropRow.querySelector('.prop-input-ref').value = refVal;
                            if (r1Val !== '') domPropRow.querySelector('.prop-r1').value = r1Val;
                            if (r2Val !== '') domPropRow.querySelector('.prop-r2').value = r2Val;
                            if (r3Val !== '') domPropRow.querySelector('.prop-r3').value = r3Val;
                            if (r4Val !== '') domPropRow.querySelector('.prop-r4').value = r4Val;
                            if (r5Val !== '') domPropRow.querySelector('.prop-r5').value = r5Val;
                        });

                        if (importedCount > 0) {
                            calculateAll();
                            alert(@json(__('Imported :count components from Excel successfully.')).replace(':count', importedCount));
                        } else {
                            alert(@json(__('Invalid Excel file format or no chromatographic data found.')));
                        }
                    } catch (err) {
                        console.error('Excel Import Error:', err);
                        alert('Erreur lors de la lecture du fichier Excel: ' + err.message);
                    } finally {
                        excelFileInput.value = '';
                    }
                };

                reader.readAsArrayBuffer(file);
            });
        }

        calculateAll();
    });
    </script>
    @endpush
</x-app-layout>
