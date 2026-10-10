<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-tool-icon name="prover" class="w-14 h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ __('Edit Prover Calibration Session') }}
                        </h2>
                        <x-badge variant="info" size="md" :dot="true">{{ $proverVerification->reference_number }}</x-badge>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('API MPMS Ch. 4 / ISO 7278 volumetric calibration with real-time metrological calculations and repeatability analysis') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('metrology.reports.report-prover.show', $proverVerification) }}">
                    <x-secondary-button type="button" class="gap-2 text-xs">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>{{ __('View Certificate') }}</span>
                    </x-secondary-button>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8"
         x-data="proverCalibrationSession({
             provers: {{ Js::from($provers) }},
             gauges: {{ Js::from($gauges) }},
             sites: {{ Js::from($sites) }},
             storeUrl: '{{ route('metrology.reports.report-prover.update', $proverVerification) }}',
             csrfToken: '{{ csrf_token() }}',
             isEdit: true,
             initialSiteId: '{{ $proverVerification->prover?->site_id ?? $proverVerification->jauge?->site_id ?? '' }}',
             initialProverId: '{{ $proverVerification->prover_id }}',
             initialGaugeId: '{{ $proverVerification->jauge_id }}',
             initialReferenceNumber: '{{ $proverVerification->reference_number }}',
             initialCalibrationDate: '{{ $proverVerification->calibration_date?->format('Y-m-d') }}',
             initialReferenceTemperature: {{ $proverVerification->reference_temperature }},
             initialPressureUnit: '{{ $proverVerification->pressure_unit }}',
             initialRemarks: {{ Js::from($proverVerification->remarks ?? '') }},
             initialRuns: {{ Js::from($initialRuns) }},
             initialSummary: {
                 bpv: {{ (float) ($proverVerification->base_prover_volume ?? 0) }},
                 bpvFormatted: '{{ $proverVerification->base_prover_volume ? number_format((float) $proverVerification->base_prover_volume, 5) : '' }}',
                 repeatability: {{ (float) ($proverVerification->repeatability_percent ?? 0) }},
                 repeatabilityFormatted: '{{ $proverVerification->repeatability_percent !== null ? number_format((float) $proverVerification->repeatability_percent, 4) . '%' : '' }}',
                 spread: {{ (float) (($proverVerification->max_run_volume && $proverVerification->min_run_volume) ? ($proverVerification->max_run_volume - $proverVerification->min_run_volume) : 0) }},
                 spreadFormatted: '{{ ($proverVerification->max_run_volume && $proverVerification->min_run_volume) ? number_format((float) ($proverVerification->max_run_volume - $proverVerification->min_run_volume), 5) : '' }}',
                 isConforme: {{ $proverVerification->is_conforme ? 'true' : 'false' }},
                 isSessionComplete: {{ $proverVerification->base_prover_volume ? 'true' : 'false' }}
             }
         })">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Operational Feedback Alerts -->
            @if(session('error'))
                <x-alert variant="danger">{{ session('error') }}</x-alert>
            @endif
            @if($errors->any())
                <x-alert variant="danger" :title="__('Validation errors encountered:')">
                    <ul class="list-disc list-inside text-xs mt-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            <!-- Live Calculation KPI Summary Banner -->
            <div class="rounded-xl p-5 border transition-all duration-200"
                 :class="summary.isConforme
                    ? 'bg-emerald-500/10 dark:bg-emerald-950/30 border-emerald-500/30 dark:border-emerald-800/50 text-emerald-900 dark:text-emerald-100'
                    : (summary.isSessionComplete
                        ? 'bg-rose-500/10 dark:bg-rose-950/30 border-rose-500/30 dark:border-rose-800/50 text-rose-900 dark:text-rose-100'
                        : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700/60 text-gray-900 dark:text-white')">

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-center">
                    <!-- Base Prover Volume (BPV) / Mean -->
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Mean Volume') }} (BPV)
                            </span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-brand-500/10 dark:bg-brand-500/20 text-brand-700 dark:text-brand-300 font-bold border border-brand-500/20">{{ __('Average') }}</span>
                        </div>
                        <div class="text-2xl font-black font-mono mt-1 text-brand-600 dark:text-brand-400">
                            <span x-text="summary.bpvFormatted || '---'"></span>
                            <span class="text-xs font-normal text-gray-500 dark:text-gray-400">L</span>
                        </div>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                            {{ __('Average of valid round trips') }}
                        </p>
                    </div>

                    <!-- Repeatability (r%) -->
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Repeatability (r%)') }}
                        </span>
                        <div class="text-2xl font-black font-mono mt-1"
                             :class="summary.isConforme ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                            <span x-text="summary.repeatabilityFormatted || '---'"></span>
                        </div>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                            {{ __('Regulatory Target') }}: <span class="font-mono font-semibold">≤ 0.020%</span>
                        </p>
                    </div>

                    <!-- Volume Spread (Max - Min) -->
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Volume Spread (Max - Min)') }}
                        </span>
                        <div class="text-2xl font-black font-mono mt-1 text-gray-900 dark:text-white">
                            <span x-text="summary.spreadFormatted || '---'"></span>
                            <span class="text-xs font-normal text-gray-500">L</span>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-0.5">
                            {{ __('Maximum pass dispersion') }}
                        </p>
                    </div>

                    <!-- Legal Verdict & Result -->
                    <div class="flex flex-col items-start md:items-end">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                            {{ __('Result & Compliance Verdict') }}
                        </span>
                        <template x-if="!summary.isSessionComplete">
                            <x-badge variant="neutral" size="lg" :dot="true">
                                {{ __('Pending Data (Min 3 Runs)') }}
                            </x-badge>
                        </template>
                        <template x-if="summary.isSessionComplete && summary.isConforme">
                            <x-badge variant="success" size="lg" :dot="true">
                                {{ __('CONFORME (r ≤ 0.020%)') }}
                            </x-badge>
                        </template>
                        <template x-if="summary.isSessionComplete && !summary.isConforme">
                            <x-badge variant="danger" size="lg" :dot="true">
                                {{ __('NON-CONFORME (r > 0.020%)') }}
                            </x-badge>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Configuration & Pairing Card -->
            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-6">
                <div class="flex items-center gap-3 pb-3 border-b border-gray-100 dark:border-gray-700">
                    <x-tool-icon name="equipment" class="w-5 h-5 text-brand-600 dark:text-brand-400" />
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ __('Session Configuration & Instrument Pairing') }}
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <!-- Site -->
                    <div>
                        <x-input-label for="site_id" :value="__('Calibration Site')" :required="true" />
                        <select id="site_id"
                                x-model="selectedSiteId"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">{{ __('--- Select Site ---') }}</option>
                            <template x-for="site in sites" :key="site.id">
                                <option :value="site.id" x-text="site.short_name ? `${site.short_name} (${site.site_code})` : site.full_name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Prover Selection -->
                    <div>
                        <x-input-label for="prover_id" :value="__('Prover Tube Under Calibration')" :required="true" />
                        <select id="prover_id"
                                x-model="selectedProverId"
                                @change="onProverChanged()"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">{{ __('--- Select Prover ---') }}</option>
                            <template x-for="prover in filteredProvers" :key="prover.id">
                                <option :value="prover.id" x-text="prover.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Standard Gauge Selection -->
                    <div>
                        <x-input-label for="jauge_id" :value="__('Reference Standard Gauge (Jauge)')" :required="true" />
                        <select id="jauge_id"
                                x-model="selectedGaugeId"
                                @change="onGaugeChanged()"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">{{ __('--- Select Standard Gauge ---') }}</option>
                            <template x-for="gauge in filteredGauges" :key="gauge.id">
                                <option :value="gauge.id" x-text="gauge.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Certificate Reference Number -->
                    <div>
                        <x-input-label for="reference_number" :value="__('Certificate Reference')" />
                        <x-text-input id="reference_number"
                                      type="text"
                                      class="mt-1 block w-full font-mono text-sm"
                                      x-model="referenceNumber"
                                      placeholder="FE/WDP/XXXX" />
                    </div>

                    <!-- Calibration Date -->
                    <div>
                        <x-input-label for="calibration_date" :value="__('Calibration Date')" :required="true" />
                        <x-text-input id="calibration_date"
                                      type="date"
                                      class="mt-1 block w-full text-sm"
                                      x-model="calibrationDate" />
                    </div>

                    <!-- Reference Temperature -->
                    <div>
                        <x-input-label for="reference_temperature" :value="__('Reference Temperature (Tb)')" :required="true" />
                        <div class="relative mt-1">
                            <x-text-input id="reference_temperature"
                                          type="number"
                                          step="0.01"
                                          class="block w-full text-sm font-mono pe-10"
                                          x-model="referenceTemperature"
                                          @input="recalculateAll()" />
                            <span class="absolute inset-y-0 end-0 pe-3 flex items-center pointer-events-none text-xs text-gray-500">
                                °C
                            </span>
                        </div>
                    </div>

                    <!-- Pressure Unit -->
                    <div>
                        <x-input-label for="pressure_unit" :value="__('Pressure Unit')" :required="true" />
                        <select id="pressure_unit"
                                x-model="pressureUnit"
                                @change="recalculateAll()"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="bar">bar</option>
                            <option value="kPa">kPa</option>
                        </select>
                    </div>

                    <!-- Prover Type Indicator -->
                    <div>
                        <x-input-label :value="__('Prover Technology')" />
                        <div class="mt-2 text-xs font-semibold text-gray-700 dark:text-gray-300">
                            <span x-text="selectedProver ? selectedProver.type_label : '---'"></span>
                            <span x-show="isSvp" class="ms-1 text-sky-600 dark:text-sky-400 font-mono">(Dual Chamber / Shaft Model)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calibration Runs Table (Water Draw Runs) -->
            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ __('Field Calibration Runs (Water Draw Passes)') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ __('Enter measured volumes and thermodynamic conditions for each pass (Min 3 consecutive passes required).') }}
                        </p>
                    </div>
                    <x-secondary-button type="button" @click="addRun()" class="gap-1.5 text-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>{{ __('Add Round Trip (Run)') }}</span>
                    </x-secondary-button>
                </div>

                <!-- Runs Container -->
                <div class="space-y-6">
                    <template x-for="(run, rIdx) in runs" :key="rIdx">
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-gray-50/50 dark:bg-gray-800/40">
                            <!-- Run Header -->
                            <div class="bg-gray-100/80 dark:bg-gray-850 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700/60 px-4 py-2.5 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-600 text-white font-mono font-bold text-xs">
                                        <span x-text="run.run_number"></span>
                                    </span>
                                    <span class="font-bold text-sm text-gray-900 dark:text-white font-mono">
                                        {{ __('RUN') }} #<span x-text="run.run_number"></span>
                                    </span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-xs font-mono">
                                        <span class="text-gray-500 dark:text-gray-400">{{ __('Corrected Run Volume') }}:</span>
                                        <span class="font-bold text-brand-700 dark:text-brand-400 ms-1"
                                              x-text="calculateRunTotal(rIdx) + ' L'"></span>
                                    </div>
                                    <button type="button"
                                            @click="addFill(rIdx)"
                                            class="text-xs text-brand-600 hover:text-brand-700 dark:text-brand-400 font-semibold inline-flex items-center gap-1">
                                        + {{ __('Add Partial Filling') }}
                                    </button>
                                    <button type="button"
                                            x-show="runs.length > 3"
                                            @click="removeRun(rIdx)"
                                            class="text-xs text-rose-600 hover:text-rose-700 dark:text-rose-400 font-semibold">
                                        {{ __('Remove Run') }}
                                    </button>
                                </div>
                            </div>

                            <!-- Fills Table -->
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs text-start">
                                    <thead class="bg-gray-50/90 dark:bg-gray-900/60 text-gray-600 dark:text-gray-300 font-semibold border-b border-gray-200 dark:border-gray-700">
                                        <tr>
                                            <th class="px-3 py-2 text-center w-12">{{ __('Fill') }}</th>
                                            <th class="px-3 py-2 text-start">{{ __('Indicated Vol (L)') }} <span class="text-rose-500">*</span></th>
                                            <th class="px-3 py-2 text-start">{{ __('T. Gauge (°C)') }} <span class="text-rose-500">*</span></th>
                                            <th class="px-3 py-2 text-start">{{ __('T. Prover (°C)') }} <span class="text-rose-500">*</span></th>
                                            <th class="px-3 py-2 text-start" x-show="isSvp">{{ __('T. Shaft (°C)') }}</th>
                                            <th class="px-3 py-2 text-start">{{ __('Pressure') }} (<span x-text="pressureUnit"></span>)</th>
                                            <th class="px-3 py-2 text-center w-16">Ctdw</th>
                                            <th class="px-3 py-2 text-center w-16">Ctsm</th>
                                            <th class="px-3 py-2 text-center w-16">Ctsp</th>
                                            <th class="px-3 py-2 text-center w-16">Cpsp</th>
                                            <th class="px-3 py-2 text-center w-16">Cplp</th>
                                            <th class="px-3 py-2 text-end">{{ __('V. Corrected (L)') }}</th>
                                            <th class="px-2 py-2 text-center w-10"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700/60">
                                        <template x-for="(fill, fIdx) in run.fills" :key="fIdx">
                                            <tr>
                                                <td class="px-3 py-2 text-center font-mono font-bold text-gray-400 dark:text-gray-500" x-text="fill.fill_number"></td>
                                                <td class="px-3 py-2">
                                                    <input type="number"
                                                           step="0.0001"
                                                           x-model="fill.indicated_volume"
                                                           @input="recalculateAll()"
                                                           placeholder="120.0000"
                                                           class="input-base w-28 text-xs font-mono py-1 px-2 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700" />
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input type="number"
                                                           step="0.01"
                                                           x-model="fill.gauge_temperature"
                                                           @input="recalculateAll()"
                                                           placeholder="20.00"
                                                           class="input-base w-20 text-xs font-mono py-1 px-2 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700" />
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input type="number"
                                                           step="0.01"
                                                           x-model="fill.prover_temperature"
                                                           @input="recalculateAll()"
                                                           placeholder="20.00"
                                                           class="input-base w-20 text-xs font-mono py-1 px-2 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700" />
                                                </td>
                                                <td class="px-3 py-2" x-show="isSvp">
                                                    <input type="number"
                                                           step="0.01"
                                                           x-model="fill.shaft_temperature"
                                                           @input="recalculateAll()"
                                                           placeholder="20.00"
                                                           class="input-base w-20 text-xs font-mono py-1 px-2 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700" />
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input type="number"
                                                           step="0.01"
                                                           x-model="fill.prover_pressure"
                                                           @input="recalculateAll()"
                                                           placeholder="0.00"
                                                           class="input-base w-20 text-xs font-mono py-1 px-2 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700" />
                                                </td>
                                                <!-- Calculated factors preview -->
                                                <td class="px-3 py-2 text-center font-mono text-[11px] text-gray-500 dark:text-gray-400" x-text="getFactor(rIdx, fIdx, 'c_tdw')"></td>
                                                <td class="px-3 py-2 text-center font-mono text-[11px] text-gray-500 dark:text-gray-400" x-text="getFactor(rIdx, fIdx, 'c_tsm')"></td>
                                                <td class="px-3 py-2 text-center font-mono text-[11px] text-gray-500 dark:text-gray-400" x-text="getFactor(rIdx, fIdx, 'c_tsp')"></td>
                                                <td class="px-3 py-2 text-center font-mono text-[11px] text-gray-500 dark:text-gray-400" x-text="getFactor(rIdx, fIdx, 'c_psp')"></td>
                                                <td class="px-3 py-2 text-center font-mono text-[11px] text-gray-500 dark:text-gray-400" x-text="getFactor(rIdx, fIdx, 'c_plp')"></td>
                                                <!-- Corrected volume -->
                                                <td class="px-3 py-2 text-end font-mono font-bold text-gray-900 dark:text-white"
                                                    x-text="getCorrectedVol(rIdx, fIdx)"></td>
                                                <td class="px-2 py-2 text-center">
                                                    <button type="button"
                                                            x-show="run.fills.length > 1"
                                                            @click="removeFill(rIdx, fIdx)"
                                                            class="text-rose-500 hover:text-rose-700">
                                                        &times;
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Passes Summary Table: Individual Runs, Mean (BPV) & Result -->
            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700/60">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-teal-500/10 dark:bg-teal-500/20 text-teal-600 dark:text-teal-400 flex items-center justify-center font-bold border border-teal-500/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ __('Calibration Result & Runs Mean Summary') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Comparative table of valid passes, calculated mean (BPV), and final repeatability verdict') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-start">
                        <thead class="bg-gray-50/90 dark:bg-gray-900/60 text-gray-600 dark:text-gray-300 font-semibold border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-2.5 text-start">{{ __('Pass / Round Trip') }}</th>
                                <th class="px-4 py-2.5 text-center">{{ __('Number of Fills') }}</th>
                                <th class="px-4 py-2.5 text-end">{{ __('Corrected Run Volume (V_b)') }}</th>
                                <th class="px-4 py-2.5 text-end">{{ __('Deviation from Mean (ΔV)') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 font-mono">
                            <template x-for="(run, rIdx) in runs" :key="rIdx">
                                <tr>
                                    <td class="px-4 py-2.5 font-bold text-brand-600 dark:text-brand-400">
                                        {{ __('RUN') }} #<span x-text="run.run_number"></span>
                                    </td>
                                    <td class="px-4 py-2.5 text-center text-gray-500 dark:text-gray-400" x-text="run.fills.length"></td>
                                    <td class="px-4 py-2.5 text-end font-bold text-gray-900 dark:text-white">
                                        <span x-text="calculateRunTotal(rIdx)"></span> L
                                    </td>
                                    <td class="px-4 py-2.5 text-end text-gray-500 dark:text-gray-400">
                                        <span x-text="getRunDelta(rIdx)"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="border-t-2 border-brand-500 bg-brand-500/10 dark:bg-gray-900/80 font-mono text-xs">
                            <tr class="font-bold">
                                <td colspan="2" class="px-4 py-3 text-start text-brand-900 dark:text-brand-300 uppercase tracking-wide">
                                    {{ __('Mean / Base Prover Volume (BPV)') }}:
                                </td>
                                <td class="px-4 py-3 text-end text-brand-700 dark:text-brand-300 text-sm font-black">
                                    <span x-text="summary.bpvFormatted ? summary.bpvFormatted + ' L' : '---'"></span>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Max Spread') }}:</span>
                                    <span class="font-bold ms-1 text-gray-900 dark:text-gray-100" x-text="summary.spreadFormatted ? summary.spreadFormatted + ' L' : '---'"></span>
                                </td>
                            </tr>
                            <tr class="border-t border-brand-500/20 dark:border-gray-700/70 bg-brand-500/5 dark:bg-gray-900/50">
                                <td colspan="2" class="px-4 py-2.5 text-start text-gray-700 dark:text-gray-300">
                                    {{ __('Repeatability (r %)') }}: <span class="font-bold text-brand-600 dark:text-brand-400" x-text="summary.repeatabilityFormatted || '---'"></span>
                                    <span class="text-[11px] text-gray-400 ms-1">(≤ 0.020%)</span>
                                </td>
                                <td colspan="2" class="px-4 py-2.5 text-end">
                                    <span class="text-gray-600 dark:text-gray-400 me-2">{{ __('Result') }}:</span>
                                    <template x-if="summary.isSessionComplete && summary.isConforme">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 font-bold border border-emerald-500/20">
                                            ✓ {{ __('CONFORME (Pass)') }}
                                        </span>
                                    </template>
                                    <template x-if="summary.isSessionComplete && !summary.isConforme">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-rose-500/10 dark:bg-rose-500/20 text-rose-700 dark:text-rose-300 font-bold border border-rose-500/20">
                                            ✗ {{ __('NON-CONFORME (Fail)') }}
                                        </span>
                                    </template>
                                    <template x-if="!summary.isSessionComplete">
                                        <span class="text-gray-400 dark:text-gray-500 italic">{{ __('Pending Data (Min 3 Runs)') }}</span>
                                    </template>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Remarks & Environmental Notes -->
            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-4">
                <x-input-label for="remarks" :value="__('Technical Remarks & Environmental Observations')" />
                <textarea id="remarks"
                          rows="3"
                          x-model="remarks"
                          class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:border-brand-500 focus:ring-brand-500"
                          placeholder="{{ __('Add any observations regarding water cleanliness, ambient temperature, pressure stability...') }}"></textarea>
            </div>

            <!-- Submit Action Footer -->
            <div class="flex items-center justify-end gap-3 pt-4">
                <a href="{{ route('metrology.reports.report-prover.show', $proverVerification) }}">
                    <x-secondary-button type="button">
                        {{ __('Cancel') }}
                    </x-secondary-button>
                </a>
                <x-primary-button type="button"
                                  @click="submitSession()"
                                  class="gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ __('Save & Record Prover Verification Session') }}</span>
                </x-primary-button>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        function proverCalibrationSession(config) {
            return {
                provers: config.provers || [],
                gauges: config.gauges || [],
                sites: config.sites || [],
                storeUrl: config.storeUrl,
                csrfToken: config.csrfToken,
                isEdit: config.isEdit || false,

                selectedSiteId: config.initialSiteId || '',
                selectedProverId: config.initialProverId || '',
                selectedGaugeId: config.initialGaugeId || '',
                referenceNumber: config.initialReferenceNumber || '',
                calibrationDate: config.initialCalibrationDate || new Date().toISOString().split('T')[0],
                referenceTemperature: config.initialReferenceTemperature || 20.00,
                pressureUnit: config.initialPressureUnit || 'bar',
                remarks: config.initialRemarks || '',

                runs: config.initialRuns && config.initialRuns.length ? config.initialRuns : [
                    { run_number: 1, fills: [{ fill_number: 1, indicated_volume: '', gauge_temperature: '', prover_temperature: '', shaft_temperature: '', prover_pressure: 0.0 }] },
                    { run_number: 2, fills: [{ fill_number: 1, indicated_volume: '', gauge_temperature: '', prover_temperature: '', shaft_temperature: '', prover_pressure: 0.0 }] },
                    { run_number: 3, fills: [{ fill_number: 1, indicated_volume: '', gauge_temperature: '', prover_temperature: '', shaft_temperature: '', prover_pressure: 0.0 }] }
                ],

                previewFactors: {},

                summary: config.initialSummary || {
                    bpv: 0,
                    bpvFormatted: '',
                    repeatability: 0,
                    repeatabilityFormatted: '',
                    spread: 0,
                    spreadFormatted: '',
                    isConforme: false,
                    isSessionComplete: false
                },

                get filteredProvers() {
                    if (!this.selectedSiteId) return this.provers;
                    return this.provers.filter(p => !p.site_id || p.site_id == this.selectedSiteId);
                },

                get filteredGauges() {
                    if (!this.selectedSiteId) return this.gauges;
                    return this.gauges.filter(g => !g.site_id || g.site_id == this.selectedSiteId);
                },

                get selectedProver() {
                    return this.provers.find(p => p.id == this.selectedProverId) || null;
                },

                get selectedGauge() {
                    return this.gauges.find(g => g.id == this.selectedGaugeId) || null;
                },

                get isSvp() {
                    return this.selectedProver && this.selectedProver.type === 'compact_svp';
                },

                init() {
                    this.runs.forEach((run, rIdx) => {
                        run.fills.forEach((fill, fIdx) => {
                            const key = `${rIdx}_${fIdx}`;
                            if (fill.corrected_volume) {
                                this.previewFactors[key] = {
                                    c_tdw: '---',
                                    c_tsm: '---',
                                    c_tsp: '---',
                                    c_psp: '---',
                                    c_plp: '---',
                                    v_corr: parseFloat(fill.corrected_volume).toFixed(5)
                                };
                            }
                        });
                    });
                    this.recalculateAll();
                },

                addRun() {
                    const nextNum = this.runs.length + 1;
                    this.runs.push({
                        run_number: nextNum,
                        fills: [{ fill_number: 1, indicated_volume: '', gauge_temperature: '', prover_temperature: '', shaft_temperature: '', prover_pressure: 0.0 }]
                    });
                },

                removeRun(rIdx) {
                    if (this.runs.length > 3) {
                        this.runs.splice(rIdx, 1);
                        this.runs.forEach((r, idx) => r.run_number = idx + 1);
                        this.recalculateAll();
                    }
                },

                addFill(rIdx) {
                    const nextFill = this.runs[rIdx].fills.length + 1;
                    this.runs[rIdx].fills.push({
                        fill_number: nextFill,
                        indicated_volume: '',
                        gauge_temperature: '',
                        prover_temperature: '',
                        shaft_temperature: '',
                        prover_pressure: 0.0
                    });
                },

                removeFill(rIdx, fIdx) {
                    if (this.runs[rIdx].fills.length > 1) {
                        this.runs[rIdx].fills.splice(fIdx, 1);
                        this.runs[rIdx].fills.forEach((f, idx) => f.fill_number = idx + 1);
                        this.recalculateAll();
                    }
                },

                onProverChanged() {
                    if (this.selectedProver && !this.referenceNumber) {
                        this.referenceNumber = 'FE/WDP/' + this.selectedProver.serial_number;
                    }
                    this.recalculateAll();
                },

                onGaugeChanged() {
                    this.recalculateAll();
                },

                waterDensity(tempC) {
                    const t = parseFloat(tempC);
                    if (isNaN(t)) return 998.2;
                    const a1 = -3.983035;
                    const a2 = 301.797;
                    const a3 = 522528.9;
                    const a4 = 69.34881;
                    const a5 = 999.974950;
                    const num = Math.pow(t + a1, 2) * (t + a2);
                    const den = a3 * (t + a4);
                    return a5 * (1 - (num / den));
                },

                recalculateAll() {
                    const tb = parseFloat(this.referenceTemperature) || 15.0;
                    const isKpa = this.pressureUnit.toLowerCase() === 'kpa';

                    const gcm = this.selectedGauge
                        ? parseFloat(this.selectedGauge.thermal_expansion) || 0.0000335
                        : 0.0000335;

                    const isSvp = this.isSvp;
                    const ga = isSvp ? (parseFloat(this.selectedProver?.area_expansion) || 0.0000335) : 0;
                    const gl = isSvp ? (parseFloat(this.selectedProver?.linear_expansion) || 0.0000160) : 0;
                    const gc = !isSvp ? (parseFloat(this.selectedProver?.cubical_expansion_coef) || 0.0000335) : 0;

                    const id = parseFloat(this.selectedProver?.inner_diameter) || 300.0;
                    const wt = parseFloat(this.selectedProver?.wall_thickness) || 10.0;
                    const eMod = parseFloat(this.selectedProver?.elasticity_modulus) || 195000.0;

                    let validRunCount = 0;
                    const runCorrectedSums = [];

                    this.runs.forEach((run, rIdx) => {
                        let runSum = 0;
                        let runHasValidFills = true;

                        run.fills.forEach((fill, fIdx) => {
                            const vi = parseFloat(fill.indicated_volume);
                            const tGauge = parseFloat(fill.gauge_temperature);
                            const tProver = parseFloat(fill.prover_temperature);
                            const tShaft = parseFloat(fill.shaft_temperature) || tProver;
                            let p = parseFloat(fill.prover_pressure) || 0.0;
                            if (isKpa) p = p / 100.0;

                            if (isNaN(vi) || isNaN(tGauge) || isNaN(tProver) || vi <= 0) {
                                runHasValidFills = false;
                                return;
                            }

                            const rhoGauge = this.waterDensity(tGauge);
                            const rhoProver = this.waterDensity(tProver);
                            const cTdw = rhoGauge / rhoProver;
                            const cTsm = 1.0 + (tGauge - tb) * gcm;

                            let cTsp = 1.0;
                            if (isSvp) {
                                cTsp = (1.0 + (tProver - tb) * ga) * (1.0 + (tShaft - tb) * gl);
                            } else {
                                cTsp = 1.0 + (tProver - tb) * gc;
                            }

                            let cPsp = 1.0;
                            if (wt > 0 && eMod > 0) {
                                cPsp = 1.0 + (p * id) / (eMod * wt);
                            }

                            const fWater = (4.99e-5) - (0.002e-5 * tProver);
                            const cPlp = 1.0 / (1.0 - (p * fWater));

                            const ccts = cTsm / cTsp;
                            const vCorr = (vi * ccts * cTdw) / (cPsp * cPlp);

                            const key = `${rIdx}_${fIdx}`;
                            this.previewFactors[key] = {
                                c_tdw: cTdw.toFixed(6),
                                c_tsm: cTsm.toFixed(6),
                                c_tsp: cTsp.toFixed(6),
                                c_psp: cPsp.toFixed(6),
                                c_plp: cPlp.toFixed(6),
                                v_corr: vCorr.toFixed(5)
                            };

                            runSum += vCorr;
                        });

                        if (runHasValidFills && run.fills.length > 0) {
                            validRunCount++;
                            runCorrectedSums.push(runSum);
                        }
                    });

                    if (validRunCount >= 3 && runCorrectedSums.length >= 3) {
                        const sumTotal = runCorrectedSums.reduce((a, b) => a + b, 0);
                        const bpv = sumTotal / runCorrectedSums.length;
                        const maxV = Math.max(...runCorrectedSums);
                        const minV = Math.min(...runCorrectedSums);
                        const spread = maxV - minV;
                        const rPercent = (spread / minV) * 100.0;

                        this.summary.bpv = bpv;
                        this.summary.bpvFormatted = bpv.toFixed(5);
                        this.summary.repeatability = rPercent;
                        this.summary.repeatabilityFormatted = rPercent.toFixed(4) + '%';
                        this.summary.spread = spread;
                        this.summary.spreadFormatted = spread.toFixed(5);
                        this.summary.isConforme = rPercent <= 0.020;
                        this.summary.isSessionComplete = true;
                    } else {
                        this.summary.bpv = 0;
                        this.summary.bpvFormatted = '';
                        this.summary.repeatability = 0;
                        this.summary.repeatabilityFormatted = '';
                        this.summary.spread = 0;
                        this.summary.spreadFormatted = '';
                        this.summary.isConforme = false;
                        this.summary.isSessionComplete = false;
                    }
                },

                getFactor(rIdx, fIdx, factor) {
                    const key = `${rIdx}_${fIdx}`;
                    return this.previewFactors[key] ? this.previewFactors[key][factor] : '---';
                },

                getCorrectedVol(rIdx, fIdx) {
                    const key = `${rIdx}_${fIdx}`;
                    return this.previewFactors[key] ? this.previewFactors[key]['v_corr'] : '---';
                },

                calculateRunTotal(rIdx) {
                    const run = this.runs[rIdx];
                    if (!run) return '0.00000';
                    let total = 0;
                    run.fills.forEach((fill, fIdx) => {
                        const key = `${rIdx}_${fIdx}`;
                        if (this.previewFactors[key] && this.previewFactors[key]['v_corr'] !== '---') {
                            total += parseFloat(this.previewFactors[key]['v_corr']) || 0;
                        } else if (fill.corrected_volume) {
                            total += parseFloat(fill.corrected_volume) || 0;
                        }
                    });
                    return total > 0 ? total.toFixed(5) : '0.00000';
                },

                getRunDelta(rIdx) {
                    if (!this.summary.bpv || this.summary.bpv <= 0) return '---';
                    const runTot = parseFloat(this.calculateRunTotal(rIdx));
                    if (isNaN(runTot) || runTot <= 0) return '---';
                    const delta = runTot - this.summary.bpv;
                    const sign = delta >= 0 ? '+' : '';
                    return sign + delta.toFixed(5) + ' L';
                },

                submitSession() {
                    if (!this.selectedProverId) {
                        alert('{{ __("Please select a Prover.") }}');
                        return;
                    }
                    if (!this.selectedGaugeId) {
                        alert('{{ __("Please select a Standard Gauge.") }}');
                        return;
                    }
                    if (!this.calibrationDate) {
                        alert('{{ __("Please enter the calibration date.") }}');
                        return;
                    }
                    if (!this.summary.isSessionComplete) {
                        alert('{{ __("Please complete all fields for at least 3 runs.") }}');
                        return;
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = this.storeUrl;

                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = this.csrfToken;
                    form.appendChild(csrf);

                    if (this.isEdit) {
                        const methodInput = document.createElement('input');
                        methodInput.type = 'hidden';
                        methodInput.name = '_method';
                        methodInput.value = 'PUT';
                        form.appendChild(methodInput);
                    }

                    const appendField = (name, val) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = name;
                        input.value = val ?? '';
                        form.appendChild(input);
                    };

                    appendField('site_id', this.selectedSiteId);
                    appendField('prover_id', this.selectedProverId);
                    appendField('jauge_id', this.selectedGaugeId);
                    appendField('reference_number', this.referenceNumber);
                    appendField('calibration_date', this.calibrationDate);
                    appendField('reference_temperature', this.referenceTemperature);
                    appendField('pressure_unit', this.pressureUnit);
                    appendField('remarks', this.remarks);

                    let flatIdx = 0;
                    this.runs.forEach(r => {
                        r.fills.forEach(f => {
                            appendField(`runs[${flatIdx}][run_number]`, r.run_number);
                            appendField(`runs[${flatIdx}][fill_number]`, f.fill_number);
                            appendField(`runs[${flatIdx}][indicated_volume]`, f.indicated_volume);
                            appendField(`runs[${flatIdx}][gauge_temperature]`, f.gauge_temperature);
                            appendField(`runs[${flatIdx}][prover_temperature]`, f.prover_temperature);
                            appendField(`runs[${flatIdx}][shaft_temperature]`, f.shaft_temperature);
                            appendField(`runs[${flatIdx}][prover_pressure]`, f.prover_pressure);
                            flatIdx++;
                        });
                    });

                    document.body.appendChild(form);
                    form.submit();
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
