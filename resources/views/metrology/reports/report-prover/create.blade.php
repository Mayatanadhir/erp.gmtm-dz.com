<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-tool-icon name="prover" class="w-14 h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ __('New Meter Prover Calibration Session') }}
                        </h2>
                        <x-badge variant="info" size="md" :dot="true">{{ __('Water Draw Method') }}</x-badge>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('API MPMS Ch. 4 / ISO 7278 volumetric calibration with real-time metrological calculations and repeatability analysis') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('metrology.reports.report-prover.index') }}">
                    <x-secondary-button type="button" class="gap-2 text-xs">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>{{ __('Prover Hub') }}</span>
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
             storeUrl: '{{ route('metrology.reports.report-prover.store') }}',
             csrfToken: '{{ csrf_token() }}'
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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-center">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider block opacity-75">
                            {{ __('Base Prover Volume (BPV)') }}
                        </span>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-2xl font-black font-mono" x-text="summary.bpv">---</span>
                            <span class="text-xs opacity-60">L</span>
                        </div>
                        <span class="text-[11px] opacity-75 block mt-0.5">
                            {{ __('Average of valid round trips') }}
                        </span>
                    </div>

                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider block opacity-75">
                            {{ __('Repeatability (r%)') }}
                        </span>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-2xl font-black font-mono" x-text="summary.repeatability !== '-' ? summary.repeatability + '%' : '---'">---</span>
                        </div>
                        <span class="text-[11px] opacity-75 block mt-0.5">
                            {{ __('Regulatory Target') }}: &le; 0.0200%
                        </span>
                    </div>

                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider block opacity-75">
                            {{ __('Volume Spread (Max - Min)') }}
                        </span>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-2xl font-black font-mono" x-text="summary.spread !== '-' ? summary.spread + ' L' : '---'">---</span>
                        </div>
                        <span class="text-[11px] opacity-75 block mt-0.5">
                            {{ __('Maximum pass dispersion') }}
                        </span>
                    </div>

                    <div class="flex flex-col items-start sm:items-end justify-center">
                        <span class="text-xs font-semibold uppercase tracking-wider block opacity-75 mb-1.5">
                            {{ __('Compliance Verdict') }}
                        </span>
                        <template x-if="!summary.isSessionComplete">
                            <x-badge variant="neutral" size="md">
                                {{ __('Pending Data (Min 3 Runs)') }}
                            </x-badge>
                        </template>
                        <template x-if="summary.isSessionComplete && summary.isConforme">
                            <x-badge variant="success" size="md" :dot="true">
                                {{ __('CONFORME (r ≤ 0.020%)') }}
                            </x-badge>
                        </template>
                        <template x-if="summary.isSessionComplete && !summary.isConforme">
                            <x-badge variant="danger" size="md" :dot="true">
                                {{ __('NON-CONFORME (r > 0.020%)') }}
                            </x-badge>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Configuration & Pairing Card -->
            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60 space-y-6">
                <h3 class="text-base font-bold text-gray-900 dark:text-white pb-3 border-b border-gray-100 dark:border-gray-700/60 flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                    <span>{{ __('Session Configuration & Instrument Pairing') }}</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Site Selection -->
                    <div>
                        <x-input-label for="site_id" class="text-xs mb-1">
                            {{ __('Calibration Site') }} <span class="text-rose-500">*</span>
                        </x-input-label>
                        <select id="site_id" x-model="selectedSiteId" class="input-base w-full text-xs py-2 px-3">
                            <option value="">{{ __('--- Select Site ---') }}</option>
                            <template x-for="s in sites" :key="s.id">
                                <option :value="s.id" x-text="s.short_name ? s.short_name + ' (' + s.full_name + ')' : s.full_name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Prover Selection -->
                    <div>
                        <x-input-label for="prover_id" class="text-xs mb-1">
                            {{ __('Prover Tube Under Calibration') }} <span class="text-rose-500">*</span>
                        </x-input-label>
                        <select id="prover_id" x-model="selectedProverId" class="input-base w-full text-xs py-2 px-3">
                            <option value="">{{ __('--- Select Prover ---') }}</option>
                            <template x-for="p in filteredProvers" :key="p.id">
                                <option :value="p.id" x-text="p.name + ' [' + p.type_label + ']'"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Standard Gauge Selection -->
                    <div>
                        <x-input-label for="jauge_id" class="text-xs mb-1">
                            {{ __('Reference Standard Gauge (Jauge)') }} <span class="text-rose-500">*</span>
                        </x-input-label>
                        <select id="jauge_id" x-model="selectedGaugeId" class="input-base w-full text-xs py-2 px-3">
                            <option value="">{{ __('--- Select Standard Gauge ---') }}</option>
                            <template x-for="g in filteredGauges" :key="g.id">
                                <option :value="g.id" x-text="g.name + ' (' + g.nominal_volume + ' L)'"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Reference Number -->
                    <div>
                        <x-input-label for="reference_number" class="text-xs mb-1">
                            {{ __('Reference Number') }}
                        </x-input-label>
                        <input type="text" id="reference_number" x-model="referenceNumber"
                               placeholder="FE/WDP/----"
                               class="input-base w-full text-xs font-mono py-2 px-3" />
                    </div>

                    <!-- Calibration Date -->
                    <div>
                        <x-input-label for="calibration_date" class="text-xs mb-1">
                            {{ __('Calibration Date') }} <span class="text-rose-500">*</span>
                        </x-input-label>
                        <input type="date" id="calibration_date" x-model="calibrationDate" required
                               class="input-base w-full text-xs py-2 px-3" />
                    </div>

                    <!-- Reference Temperature -->
                    <div>
                        <x-input-label for="reference_temperature" class="text-xs mb-1">
                            {{ __('Reference Temperature (Tb)') }}
                        </x-input-label>
                        <div class="relative">
                            <input type="number" step="0.01" id="reference_temperature" x-model="referenceTemperature"
                                   class="input-base w-full text-xs py-2 px-3 pe-8 font-mono" />
                            <span class="absolute inset-y-0 end-2 flex items-center text-xs text-gray-400">°C</span>
                        </div>
                    </div>

                    <!-- Pressure Unit -->
                    <div>
                        <x-input-label for="pressure_unit" class="text-xs mb-1">
                            {{ __('Pressure Unit') }}
                        </x-input-label>
                        <select id="pressure_unit" x-model="pressureUnit" class="input-base w-full text-xs py-2 px-3 font-mono">
                            <option value="bar">bar</option>
                            <option value="kPa">kPa</option>
                        </select>
                    </div>

                    <!-- Prover Type Specs Preview -->
                    <div>
                        <x-input-label class="text-xs mb-1">
                            {{ __('Prover Technology') }}
                        </x-input-label>
                        <div class="h-[38px] px-3 rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60 flex items-center text-xs font-semibold text-gray-700 dark:text-gray-300">
                            <span x-text="selectedProver ? selectedProver.type_label : '---'">---</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Interactive Measurement Runs Matrix -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <span>{{ __('Field Calibration Runs (Water Draw Passes)') }}</span>
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ __('Enter measured volumes and thermodynamic conditions for each pass (Min 3 consecutive passes required).') }}
                        </p>
                    </div>

                    <x-secondary-button type="button" @click="addRun()" class="gap-1.5 text-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>{{ __('Add Round Trip (Run)') }}</span>
                    </x-secondary-button>
                </div>

                <!-- Runs Loop -->
                <template x-for="(run, rIdx) in runs" :key="rIdx">
                    <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700/60">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-teal-50 dark:bg-teal-500/10 text-teal-700 dark:text-teal-300 font-bold font-mono text-xs border border-teal-500/20">
                                    {{ __('RUN') }} #<span x-text="run.run_number"></span>
                                </span>
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                                    {{ __('Corrected Run Volume') }}:
                                    <span class="font-mono font-bold text-gray-900 dark:text-white" x-text="calculateRunTotal(run) + ' L'"></span>
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" @click="addFill(rIdx)"
                                        class="px-2.5 py-1 text-xs font-semibold rounded-md text-brand-600 dark:text-brand-400 hover:bg-brand-50 dark:hover:bg-brand-900/30 transition">
                                    + {{ __('Add Partial Filling') }}
                                </button>
                                <template x-if="runs.length > 3">
                                    <button type="button" @click="removeRun(rIdx)"
                                            class="text-xs text-rose-500 hover:text-rose-700 font-medium px-2 py-1">
                                        {{ __('Remove Run') }}
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Fills Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-start">
                                <thead>
                                    <tr class="text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700/60">
                                        <th class="py-2 px-2 text-center w-12">{{ __('Fill') }}</th>
                                        <th class="py-2 px-2">{{ __('Indicated Vol (L)') }} <span class="text-rose-500">*</span></th>
                                        <th class="py-2 px-2">{{ __('T. Gauge (°C)') }} <span class="text-rose-500">*</span></th>
                                        <th class="py-2 px-2">{{ __('T. Prover (°C)') }} <span class="text-rose-500">*</span></th>
                                        <th class="py-2 px-2" x-show="isSvp">{{ __('T. Shaft (°C)') }}</th>
                                        <th class="py-2 px-2">{{ __('Pressure') }} (<span x-text="pressureUnit"></span>)</th>
                                        <th class="py-2 px-2 text-center font-mono">C_tdw</th>
                                        <th class="py-2 px-2 text-center font-mono">C_tsm</th>
                                        <th class="py-2 px-2 text-center font-mono">C_tsp</th>
                                        <th class="py-2 px-2 text-center font-mono">C_psp</th>
                                        <th class="py-2 px-2 text-center font-mono">C_plp</th>
                                        <th class="py-2 px-2 text-end font-bold">{{ __('V. Corrected (L)') }}</th>
                                        <th class="py-2 px-2 text-center w-10"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(fill, fIdx) in run.fills" :key="fIdx">
                                        <tr class="border-b border-gray-50 dark:border-gray-700/30">
                                            <!-- Fill # -->
                                            <td class="py-2 px-2 text-center font-mono font-bold text-gray-400" x-text="fill.fill_number"></td>

                                            <!-- Indicated Vol -->
                                            <td class="py-2 px-2">
                                                <input type="number" step="0.0001" x-model.number="fill.indicated_volume"
                                                       placeholder="400.0000"
                                                       class="input-base w-28 text-xs py-1.5 px-2 font-mono" />
                                            </td>

                                            <!-- Gauge Temp -->
                                            <td class="py-2 px-2">
                                                <input type="number" step="0.01" x-model.number="fill.gauge_temperature"
                                                       placeholder="20.00"
                                                       class="input-base w-20 text-xs py-1.5 px-2 font-mono" />
                                            </td>

                                            <!-- Prover Temp -->
                                            <td class="py-2 px-2">
                                                <input type="number" step="0.01" x-model.number="fill.prover_temperature"
                                                       placeholder="20.00"
                                                       class="input-base w-20 text-xs py-1.5 px-2 font-mono" />
                                            </td>

                                            <!-- Shaft Temp (for SVP) -->
                                            <td class="py-2 px-2" x-show="isSvp">
                                                <input type="number" step="0.01" x-model.number="fill.shaft_temperature"
                                                       placeholder="20.00"
                                                       class="input-base w-20 text-xs py-1.5 px-2 font-mono" />
                                            </td>

                                            <!-- Pressure -->
                                            <td class="py-2 px-2">
                                                <input type="number" step="0.01" x-model.number="fill.prover_pressure"
                                                       placeholder="0.00"
                                                       class="input-base w-20 text-xs py-1.5 px-2 font-mono" />
                                            </td>

                                            <!-- Calculated Factors Preview -->
                                            <td class="py-2 px-2 text-center font-mono text-[11px] text-gray-500" x-text="calculateFill(fill).c_tdw"></td>
                                            <td class="py-2 px-2 text-center font-mono text-[11px] text-gray-500" x-text="calculateFill(fill).c_tsm"></td>
                                            <td class="py-2 px-2 text-center font-mono text-[11px] text-gray-500" x-text="calculateFill(fill).c_tsp"></td>
                                            <td class="py-2 px-2 text-center font-mono text-[11px] text-gray-500" x-text="calculateFill(fill).c_psp"></td>
                                            <td class="py-2 px-2 text-center font-mono text-[11px] text-gray-500" x-text="calculateFill(fill).c_plp"></td>

                                            <!-- Corrected Volume -->
                                            <td class="py-2 px-2 text-end font-mono font-bold text-teal-600 dark:text-teal-400" x-text="calculateFill(fill).v_b"></td>

                                            <!-- Remove Fill -->
                                            <td class="py-2 px-2 text-center">
                                                <template x-if="run.fills.length > 1">
                                                    <button type="button" @click="removeFill(rIdx, fIdx)"
                                                            class="text-gray-400 hover:text-rose-500 transition">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Passes Summary Table: Individual Runs, Mean (BPV) & Result -->
            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60 space-y-4">
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
                                    <td class="px-4 py-2.5 font-bold text-teal-600 dark:text-teal-400">
                                        {{ __('RUN') }} #<span x-text="run.run_number"></span>
                                    </td>
                                    <td class="px-4 py-2.5 text-center text-gray-500 dark:text-gray-400" x-text="run.fills.length"></td>
                                    <td class="px-4 py-2.5 text-end font-bold text-gray-900 dark:text-white">
                                        <span x-text="calculateRunTotal(run) + ' L'"></span>
                                    </td>
                                    <td class="px-4 py-2.5 text-end text-gray-500 dark:text-gray-400">
                                        <span x-text="getRunDelta(run)"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="border-t-2 border-teal-500 bg-teal-500/10 dark:bg-gray-900/80 font-mono text-xs">
                            <tr class="font-bold">
                                <td colspan="2" class="px-4 py-3 text-start text-teal-900 dark:text-teal-300 uppercase tracking-wide">
                                    {{ __('Mean / Base Prover Volume (BPV)') }}:
                                </td>
                                <td class="px-4 py-3 text-end text-teal-700 dark:text-teal-300 text-sm font-black">
                                    <span x-text="summary.bpv !== '-' ? summary.bpv + ' L' : '---'"></span>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Max Spread') }}:</span>
                                    <span class="font-bold ms-1 text-gray-900 dark:text-gray-100" x-text="summary.spread !== '-' ? summary.spread + ' L' : '---'"></span>
                                </td>
                            </tr>
                            <tr class="border-t border-teal-500/20 dark:border-gray-700/70 bg-teal-500/5 dark:bg-gray-900/50">
                                <td colspan="2" class="px-4 py-2.5 text-start text-gray-700 dark:text-gray-300">
                                    {{ __('Repeatability (r %)') }}: <span class="font-bold text-teal-600 dark:text-teal-400" x-text="summary.repeatability !== '-' ? summary.repeatability + '%' : '---'"></span>
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

            <!-- Remarks Card -->
            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60 space-y-2">
                <x-input-label for="remarks" class="text-xs mb-1">
                    {{ __('Technical Remarks & Environmental Observations') }}
                </x-input-label>
                <textarea id="remarks" x-model="remarks" rows="3"
                          placeholder="{{ __('Add any observations regarding water cleanliness, ambient temperature, pressure stability...') }}"
                          class="input-base w-full text-xs p-3"></textarea>
            </div>

            <!-- Actions Bar -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700/60">
                <a href="{{ route('metrology.reports.report-prover.index') }}">
                    <x-secondary-button type="button" class="text-xs">
                        {{ __('Cancel') }}
                    </x-secondary-button>
                </a>

                <x-primary-button type="button" @click="submitForm()" class="text-xs gap-2 !bg-teal-600 hover:!bg-teal-700">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
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

                selectedSiteId: '',
                selectedProverId: '',
                selectedGaugeId: '',
                referenceNumber: '',
                calibrationDate: new Date().toISOString().split('T')[0],
                referenceTemperature: 20.00,
                pressureUnit: 'bar',
                remarks: '',

                runs: [
                    { run_number: 1, fills: [{ fill_number: 1, indicated_volume: '', gauge_temperature: '', prover_temperature: '', shaft_temperature: '', prover_pressure: 0.0 }] },
                    { run_number: 2, fills: [{ fill_number: 1, indicated_volume: '', gauge_temperature: '', prover_temperature: '', shaft_temperature: '', prover_pressure: 0.0 }] },
                    { run_number: 3, fills: [{ fill_number: 1, indicated_volume: '', gauge_temperature: '', prover_temperature: '', shaft_temperature: '', prover_pressure: 0.0 }] }
                ],

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
                    }
                },

                calculateWaterDensity(t) {
                    const a1 = -3.983035, a2 = 301.797, a3 = 522528.9, a4 = 69.34881, a5 = 999.974950;
                    const num = Math.pow(t + a1, 2) * (t + a2);
                    const den = a3 * (t + a4);
                    return a5 * (1.0 - (num / den));
                },

                calculateFill(fill) {
                    const vInd = parseFloat(fill.indicated_volume);
                    const tGauge = parseFloat(fill.gauge_temperature);
                    const tProver = parseFloat(fill.prover_temperature);
                    const pProver = parseFloat(fill.prover_pressure) || 0.0;
                    const tRef = parseFloat(this.referenceTemperature) || 20.0;

                    if (isNaN(vInd) || isNaN(tGauge) || isNaN(tProver) || vInd <= 0) {
                        return { c_tdw: '-', c_tsm: '-', c_tsp: '-', c_psp: '-', c_plp: '-', v_b: '-', val: null };
                    }

                    const prover = this.selectedProver;
                    const gauge = this.selectedGauge;

                    const gc = parseFloat(prover?.cubical_expansion_coef) || 0.00003300;
                    const gcm = parseFloat(gauge?.thermal_expansion) || 0.00005100;
                    const diameter = parseFloat(prover?.inner_diameter) || 400.0;
                    const thickness = parseFloat(prover?.wall_thickness) || 12.0;
                    const eModulus = parseFloat(prover?.elasticity_modulus) || 2068427.0;

                    const pBar = this.pressureUnit.toLowerCase() === 'kpa' ? (pProver / 100.0) : pProver;

                    // 1. C_tdw
                    const rhoMeasure = this.calculateWaterDensity(tGauge);
                    const rhoProver = this.calculateWaterDensity(tProver);
                    const c_tdw = rhoProver > 0 ? (rhoMeasure / rhoProver) : 1.0;

                    // 2. C_tsm
                    const c_tsm = 1.0 + (tGauge - tRef) * gcm;

                    // 3. C_tsp
                    let c_tsp = 1.0;
                    if (this.isSvp && fill.shaft_temperature !== '' && !isNaN(parseFloat(fill.shaft_temperature))) {
                        const ga = parseFloat(prover?.area_expansion) || 0.00003400;
                        const gl = parseFloat(prover?.linear_expansion) || 0.00000120;
                        const tShaft = parseFloat(fill.shaft_temperature);
                        c_tsp = (1.0 + (tProver - tRef) * ga) * (1.0 + (tShaft - tRef) * gl);
                    } else {
                        c_tsp = 1.0 + (tProver - tRef) * gc;
                    }

                    // 4. C_psp
                    const c_psp = (eModulus * thickness > 0 && pBar > 0)
                        ? 1.0 + ((pBar * diameter) / (eModulus * thickness))
                        : 1.0;

                    // 5. C_plp
                    const fComp = 0.0000464;
                    const c_plp = (1.0 - pBar * fComp > 0)
                        ? 1.0 / (1.0 - pBar * fComp)
                        : 1.0;

                    // 6. Corrected volume V_b
                    const ccts = c_tsp > 0 ? (c_tsm / c_tsp) : 1.0;
                    const denom = c_psp * c_plp;
                    const vb = denom > 0 ? (vInd * ccts * c_tdw / denom) : 0;

                    return {
                        c_tdw: c_tdw.toFixed(6),
                        c_tsm: c_tsm.toFixed(6),
                        c_tsp: c_tsp.toFixed(6),
                        c_psp: c_psp.toFixed(6),
                        c_plp: c_plp.toFixed(6),
                        v_b: vb.toFixed(5),
                        val: vb
                    };
                },

                calculateRunTotal(run) {
                    let sum = 0;
                    let valid = true;
                    for (let f of run.fills) {
                        const res = this.calculateFill(f);
                        if (res.val === null) { valid = false; break; }
                        sum += res.val;
                    }
                    return valid ? sum.toFixed(5) : '-';
                },

                getRunDelta(run) {
                    if (!this.summary || this.summary.bpv === '-') return '---';
                    const totalStr = this.calculateRunTotal(run);
                    if (totalStr === '-') return '---';
                    const delta = parseFloat(totalStr) - parseFloat(this.summary.bpv);
                    const sign = delta >= 0 ? '+' : '';
                    return sign + delta.toFixed(5) + ' L';
                },

                get summary() {
                    let totalSum = 0;
                    let validRuns = 0;
                    let minVol = null;
                    let maxVol = null;

                    for (let r of this.runs) {
                        const totalStr = this.calculateRunTotal(r);
                        if (totalStr === '-') continue;
                        const vol = parseFloat(totalStr);
                        validRuns++;
                        totalSum += vol;
                        if (minVol === null || vol < minVol) minVol = vol;
                        if (maxVol === null || vol > maxVol) maxVol = vol;
                    }

                    const isSessionComplete = validRuns >= 3 && validRuns === this.runs.length;
                    let bpv = '-';
                    let spread = '-';
                    let rep = '-';
                    let isConforme = false;

                    if (isSessionComplete && minVol > 0) {
                        const bpvVal = totalSum / validRuns;
                        const spreadVal = maxVol - minVol;
                        const repVal = (spreadVal / minVol) * 100.0;

                        bpv = bpvVal.toFixed(5);
                        spread = spreadVal.toFixed(5);
                        rep = repVal.toFixed(4);
                        isConforme = repVal <= 0.0200;
                    }

                    return {
                        isSessionComplete,
                        bpv,
                        spread,
                        repeatability: rep,
                        isConforme
                    };
                },

                submitForm() {
                    if (!this.selectedSiteId) {
                        alert('{{ __("Please select a calibration site.") }}');
                        return;
                    }
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
