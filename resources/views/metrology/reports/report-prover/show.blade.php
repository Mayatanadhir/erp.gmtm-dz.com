<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-tool-icon name="prover" class="w-14 h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight font-mono">
                            {{ $proverVerification->reference_number ?: 'VERIF-PRV-'.$proverVerification->id }}
                        </h2>
                        <x-badge :variant="$proverVerification->is_conforme ? 'success' : 'danger'" size="md" :dot="true">
                            {{ $proverVerification->is_conforme ? __('Compliant (Conforme)') : __('Non-Compliant (Non-Conforme)') }}
                        </x-badge>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Official Water Draw Verification Session per API MPMS Ch. 4 / ISO 7278 / ISO 8222') }} &bull; {{ $proverVerification->calibration_date?->format('d/m/Y') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center flex-wrap gap-2">
                <x-secondary-button href="{{ route('metrology.reports.report-prover.index') }}" class="gap-1.5 text-xs">
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>{{ __('Prover Hub') }}</span>
                </x-secondary-button>

                @can('edit reports')
                    <x-edit-button href="{{ route('metrology.reports.report-prover.edit', $proverVerification->id) }}" class="gap-1.5 text-xs">
                        {{ __('Edit Session') }}
                    </x-edit-button>
                @endcan

                <x-primary-button href="{{ route('metrology.reports.report-prover.pdf', $proverVerification->id) }}" target="_blank" class="gap-1.5 text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>{{ __('Official Report (PDF)') }}</span>
                </x-primary-button>

                <x-secondary-button href="{{ route('metrology.reports.report-prover.pdf-not-emt', $proverVerification->id) }}" target="_blank" class="gap-1.5 text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span>{{ __('Report Without EMT (PDF)') }}</span>
                </x-secondary-button>

                @can('delete reports')
                    <form action="{{ route('metrology.reports.report-prover.destroy', $proverVerification->id) }}" method="POST"
                          onsubmit="return confirm('{{ __('Are you sure you want to permanently delete this prover verification session?') }}');" class="inline">
                        @csrf
                        @method('DELETE')
                        <x-danger-button type="submit" class="gap-1.5 text-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>{{ __('Delete') }}</span>
                        </x-danger-button>
                    </form>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash feedback alerts -->
            @if(session('success'))
                <x-alert variant="success">{{ session('success') }}</x-alert>
            @endif
            @if(session('error'))
                <x-alert variant="danger">{{ session('error') }}</x-alert>
            @endif

            <!-- Metrological KPI Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Base Prover Volume (BPV) -->
                <div class="rounded-xl bg-white dark:bg-gray-800 p-5 border border-teal-200 dark:border-teal-800/40 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-teal-700 dark:text-teal-400 uppercase tracking-wider">
                            {{ __('Base Prover Volume (BPV)') }}
                        </p>
                        <h3 class="text-2xl font-black text-teal-900 dark:text-teal-100 mt-1 font-mono">
                            {{ number_format((float) $proverVerification->base_prover_volume, 5) }} <span class="text-xs font-normal text-gray-500">L</span>
                        </h3>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                            {{ __('Reference Temperature') }}: {{ number_format((float) $proverVerification->reference_temperature, 2) }}°C
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center border border-teal-500/20 shrink-0">
                        <x-tool-icon name="prover" class="w-6 h-6" />
                    </div>
                </div>

                <!-- Card 2: Max Run Volume -->
                <div class="rounded-xl bg-white dark:bg-gray-800 p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('Max Run Volume (CPV max)') }}
                        </p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1 font-mono">
                            {{ $proverVerification->max_run_volume !== null ? number_format((float) $proverVerification->max_run_volume, 5) : '---' }} <span class="text-xs font-normal text-gray-500">L</span>
                        </h3>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                            {{ __('Highest corrected pass') }}
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-sky-50 dark:bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center border border-sky-500/20 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                </div>

                <!-- Card 3: Min Run Volume -->
                <div class="rounded-xl bg-white dark:bg-gray-800 p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('Min Run Volume (CPV min)') }}
                        </p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1 font-mono">
                            {{ $proverVerification->min_run_volume !== null ? number_format((float) $proverVerification->min_run_volume, 5) : '---' }} <span class="text-xs font-normal text-gray-500">L</span>
                        </h3>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                            {{ __('Lowest corrected pass') }}
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center border border-indigo-500/20 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                    </div>
                </div>

                <!-- Card 4: Repeatability (r%) -->
                <div class="rounded-xl bg-white dark:bg-gray-800 p-5 border {{ $proverVerification->is_conforme ? 'border-emerald-200 dark:border-emerald-800/40' : 'border-rose-200 dark:border-rose-800/40' }} shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold {{ $proverVerification->is_conforme ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }} uppercase tracking-wider">
                            {{ __('Repeatability (r%)') }}
                        </p>
                        <h3 class="text-2xl font-black {{ $proverVerification->is_conforme ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} mt-1 font-mono">
                            {{ number_format((float) $proverVerification->repeatability_percent, 4) }}%
                        </h3>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                            {{ __('Legal Limit') }}: &le; 0.0200%
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl {{ $proverVerification->is_conforme ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20' }} flex items-center justify-center shrink-0">
                        @if($proverVerification->is_conforme)
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @else
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Device Specifications Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Prover Tube Specifications -->
                @php
                    $proverInst = $proverVerification->prover;
                    $proverSpec = $proverInst?->proverSpecification;
                @endphp
                <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center border border-teal-500/20 shrink-0">
                                <x-tool-icon name="prover" class="w-5 h-5" />
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ __('Prover Tube Under Calibration') }}
                                </h4>
                                <p class="text-xs font-mono text-gray-500 dark:text-gray-400">
                                    {{ $proverInst?->tag_number ?: '---' }} &bull; S/N: {{ $proverInst?->serial_number ?: '---' }}
                                </p>
                            </div>
                        </div>
                        @if($proverInst)
                            <a href="{{ route('metrology.instruments.show', $proverInst->id) }}" class="text-xs text-brand-600 dark:text-brand-400 hover:underline">
                                {{ __('View Instrument') }} &rarr;
                            </a>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                            <span class="text-gray-500 dark:text-gray-400 block">{{ __('Prover Type') }}:</span>
                            <span class="font-bold text-gray-900 dark:text-white">
                                {{ $proverSpec?->type instanceof \BackedEnum ? $proverSpec->type->label() : ($proverSpec?->type ?? 'Bidirectional Pipe') }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                            <span class="text-gray-500 dark:text-gray-400 block">{{ __('Site / Location') }}:</span>
                            <span class="font-bold text-gray-900 dark:text-white">
                                {{ $proverInst?->site?->short_name ?? $proverInst?->site?->full_name ?? '---' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                            <span class="text-gray-500 dark:text-gray-400 block">{{ __('Inner Diameter (ID)') }}:</span>
                            <span class="font-mono font-bold text-gray-900 dark:text-white">
                                {{ $proverSpec?->inner_diameter ? number_format((float) $proverSpec->inner_diameter, 2).' mm' : '---' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                            <span class="text-gray-500 dark:text-gray-400 block">{{ __('Wall Thickness (WT)') }}:</span>
                            <span class="font-mono font-bold text-gray-900 dark:text-white">
                                {{ $proverSpec?->wall_thickness ? number_format((float) $proverSpec->wall_thickness, 2).' mm' : '---' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                            <span class="text-gray-500 dark:text-gray-400 block">{{ __('Cubical Thermal Expansion (Gc)') }}:</span>
                            <span class="font-mono font-bold text-gray-900 dark:text-white">
                                {{ $proverSpec?->cubical_expansion_coef ?? '0.00003300' }} /°C
                            </span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                            <span class="text-gray-500 dark:text-gray-400 block">{{ __('Modulus of Elasticity (E)') }}:</span>
                            <span class="font-mono font-bold text-gray-900 dark:text-white">
                                {{ number_format((float) ($proverSpec?->elasticity_modulus ?? 2068427), 0) }} bar
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Standard Gauge Specifications -->
                @php
                    $gaugeInst = $proverVerification->jauge;
                    $gaugeSpec = $gaugeInst?->standardGaugeSpecification;
                @endphp
                <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center border border-amber-500/20 shrink-0">
                                <x-tool-icon name="units" class="w-5 h-5" />
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ __('Reference Standard Gauge (Jauge Étalon)') }}
                                </h4>
                                <p class="text-xs font-mono text-gray-500 dark:text-gray-400">
                                    {{ $gaugeInst?->tag_number ?: '---' }} &bull; S/N: {{ $gaugeInst?->serial_number ?: '---' }}
                                </p>
                            </div>
                        </div>
                        @if($gaugeInst)
                            <a href="{{ route('metrology.instruments.show', $gaugeInst->id) }}" class="text-xs text-brand-600 dark:text-brand-400 hover:underline">
                                {{ __('View Instrument') }} &rarr;
                            </a>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                            <span class="text-gray-500 dark:text-gray-400 block">{{ __('Nominal Base Capacity (BMV)') }}:</span>
                            <span class="font-mono font-bold text-gray-900 dark:text-white">
                                {{ $gaugeSpec?->nominal_capacity_liters ? number_format((float) $gaugeSpec->nominal_capacity_liters, 2).' L' : '400.00 L' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                            <span class="text-gray-500 dark:text-gray-400 block">{{ __('Neck Scale Sensitivity') }}:</span>
                            <span class="font-mono font-bold text-gray-900 dark:text-white">
                                {{ $gaugeSpec?->neck_scale_sensitivity ?? '0.01000' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                            <span class="text-gray-500 dark:text-gray-400 block">{{ __('Thermal Expansion (Gcm)') }}:</span>
                            <span class="font-mono font-bold text-gray-900 dark:text-white">
                                {{ $gaugeSpec?->cubical_expansion_coef_gcm ?? '0.00005100' }} /°C
                            </span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                            <span class="text-gray-500 dark:text-gray-400 block">{{ __('Vessel Material') }}:</span>
                            <span class="font-bold text-gray-900 dark:text-white">
                                {{ $gaugeSpec?->vessel_material ?? 'Stainless Steel 304/316' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50 col-span-2">
                            <span class="text-gray-500 dark:text-gray-400 block">{{ __('Calibration Certificate') }}:</span>
                            <span class="font-mono font-bold text-gray-900 dark:text-white">
                                {{ $gaugeSpec?->calibration_certificate_number ?: '---' }}
                                @if($gaugeSpec?->calibration_date)
                                    ({{ __('Date') }}: {{ \Carbon\Carbon::parse($gaugeSpec->calibration_date)->format('d/m/Y') }})
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comprehensive Run Measurements & Calculation Results Table -->
            <x-table>
                <x-slot:toolbar>
                    <div class="flex items-center justify-between gap-3 w-full">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-brand-50 dark:bg-brand-500/20 text-brand-600 dark:text-brand-400 flex items-center justify-center shrink-0 border border-brand-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">
                                {{ __('Detailed Measurement Passes & Correction Factors') }}
                            </h3>
                        </div>
                        <span class="text-xs font-mono text-gray-500 dark:text-gray-400">
                            {{ count($proverVerification->runs) }} {{ __('measurement fills recorded') }}
                        </span>
                    </div>
                </x-slot:toolbar>

                <x-slot:header>
                    <x-table.th class="text-center w-12">{{ __('Run') }}</x-table.th>
                    <x-table.th class="text-center w-12">{{ __('Fill') }}</x-table.th>
                    <x-table.th class="text-end">{{ __('V. Indicated (L)') }}</x-table.th>
                    <x-table.th class="text-center">{{ __('T. Gauge (°C)') }}</x-table.th>
                    <x-table.th class="text-center">{{ __('T. Prover (°C)') }}</x-table.th>
                    <x-table.th class="text-center">{{ __('Pressure (bar)') }}</x-table.th>
                    <x-table.th class="text-center font-mono">C_tdw</x-table.th>
                    <x-table.th class="text-center font-mono">C_tsm</x-table.th>
                    <x-table.th class="text-center font-mono">C_tsp</x-table.th>
                    <x-table.th class="text-center font-mono">C_psp</x-table.th>
                    <x-table.th class="text-center font-mono">C_plp</x-table.th>
                    <x-table.th class="text-end font-bold">{{ __('V. Corrected (L)') }}</x-table.th>
                </x-slot:header>

                @php
                    $runsByNumber = $proverVerification->runs->groupBy('run_number');
                @endphp

                @foreach($runsByNumber as $runNum => $fills)
                    @php
                        $runTotalVolume = $fills->sum(fn($f) => (float)$f->correctedVolume);
                    @endphp
                    @foreach($fills as $fill)
                        <x-table.tr>
                            <x-table.td class="text-center font-bold font-mono text-brand-600 dark:text-brand-400">
                                #{{ $fill->run_number }}
                            </x-table.td>
                            <x-table.td class="text-center font-mono text-gray-500 dark:text-gray-400">
                                {{ $fill->fill_number }}
                            </x-table.td>
                            <x-table.td class="text-end font-mono font-semibold">
                                {{ number_format((float) $fill->indicated_volume, 4) }}
                            </x-table.td>
                            <x-table.td class="text-center font-mono">
                                {{ number_format((float) $fill->gauge_temperature, 2) }}
                            </x-table.td>
                            <x-table.td class="text-center font-mono">
                                {{ number_format((float) $fill->prover_temperature, 2) }}
                            </x-table.td>
                            <x-table.td class="text-center font-mono">
                                {{ number_format((float) $fill->prover_pressure, 2) }}
                            </x-table.td>
                            <x-table.td class="text-center font-mono text-xs text-gray-600 dark:text-gray-400">
                                {{ $fill->c_tdw ? number_format((float) $fill->c_tdw, 6) : '---' }}
                            </x-table.td>
                            <x-table.td class="text-center font-mono text-xs text-gray-600 dark:text-gray-400">
                                {{ $fill->c_tsm ? number_format((float) $fill->c_tsm, 6) : '---' }}
                            </x-table.td>
                            <x-table.td class="text-center font-mono text-xs text-gray-600 dark:text-gray-400">
                                {{ $fill->c_tsp ? number_format((float) $fill->c_tsp, 6) : '---' }}
                            </x-table.td>
                            <x-table.td class="text-center font-mono text-xs text-gray-600 dark:text-gray-400">
                                {{ $fill->c_psp ? number_format((float) $fill->c_psp, 6) : '---' }}
                            </x-table.td>
                            <x-table.td class="text-center font-mono text-xs text-gray-600 dark:text-gray-400">
                                {{ $fill->c_plp ? number_format((float) $fill->c_plp, 6) : '---' }}
                            </x-table.td>
                            <x-table.td class="text-end font-mono font-bold text-teal-700 dark:text-teal-300">
                                {{ number_format((float) $fill->corrected_volume, 5) }}
                            </x-table.td>
                        </x-table.tr>
                    @endforeach

                    <!-- Run Aggregated Subtotal Row -->
                    <tr class="bg-teal-500/10 dark:bg-gray-900/60 font-bold border-y border-teal-500/20 dark:border-teal-800/40">
                        <td colspan="11" class="py-2 px-4 text-start text-xs text-teal-900 dark:text-teal-200">
                            {{ __('Total Corrected Volume for Run') }} #{{ $runNum }} (CPV_{{ $runNum }}):
                        </td>
                        <td class="py-2 px-4 text-end font-mono text-sm text-teal-900 dark:text-teal-100 font-black">
                            {{ number_format($runTotalVolume, 5) }} L
                        </td>
                    </tr>
                @endforeach

                <!-- Overall Mean & Verification Summary Footer -->
                <tr class="bg-brand-500/10 dark:bg-gray-900/80 font-bold border-t-2 border-brand-500">
                    <td colspan="11" class="py-3 px-4 text-start text-xs text-brand-900 dark:text-brand-300 uppercase tracking-wide">
                        {{ __('Mean / Base Prover Volume (BPV)') }}:
                    </td>
                    <td class="py-3 px-4 text-end font-mono text-base text-brand-700 dark:text-brand-300 font-black">
                        {{ number_format((float) $proverVerification->base_prover_volume, 5) }} L
                    </td>
                </tr>
                <tr class="bg-brand-500/5 dark:bg-gray-900/50 border-t border-brand-500/20 dark:border-gray-700/60 text-xs">
                    <td colspan="6" class="py-2.5 px-4 text-start font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Repeatability (r %)') }}: <span class="font-bold text-brand-600 dark:text-brand-400 font-mono">{{ number_format((float) $proverVerification->repeatability_percent, 4) }}%</span>
                        <span class="text-[11px] text-gray-400 ms-1">(≤ 0.020%)</span>
                    </td>
                    <td colspan="6" class="py-2.5 px-4 text-end">
                        <span class="text-gray-500 dark:text-gray-400 me-2">{{ __('Result') }}:</span>
                        <span class="font-bold font-mono {{ $proverVerification->is_conforme ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            {{ $proverVerification->is_conforme ? '✓ ' . __('CONFORME (Pass)') : '✗ ' . __('NON-CONFORME (Fail)') }}
                        </span>
                    </td>
                </tr>
            </x-table>

            <!-- Legal Decision Banner -->
            <div class="rounded-xl p-6 border {{ $proverVerification->is_conforme ? 'bg-emerald-500/10 dark:bg-emerald-950/30 border-emerald-500/30 dark:border-emerald-800/50 text-emerald-900 dark:text-emerald-100' : 'bg-rose-500/10 dark:bg-rose-950/30 border-rose-500/30 dark:border-rose-800/50 text-rose-900 dark:text-rose-100' }} flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl {{ $proverVerification->is_conforme ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white' }} flex items-center justify-center shrink-0 shadow-sm">
                        @if($proverVerification->is_conforme)
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        @endif
                    </div>
                    <div>
                        <h4 class="text-base font-extrabold">
                            {{ $proverVerification->is_conforme ? __('LEGAL VERDICT: CONFORME (COMPLIANT)') : __('LEGAL VERDICT: NON-CONFORME (NON-COMPLIANT)') }}
                        </h4>
                        <p class="text-xs mt-0.5 opacity-90">
                            {{ $proverVerification->is_conforme
                                ? __('The meter prover fulfills all metrological repeatability criteria according to Algerian OAM regulations and API MPMS Chapter 4 (r ≤ 0.020%).')
                                : __('The meter prover repeatability exceeds the maximum permissible limit (r > 0.020%). Recalibration or mechanical inspection is required.') }}
                        </p>
                    </div>
                </div>
                <div class="text-end shrink-0">
                    <span class="text-xs uppercase font-semibold block opacity-75">{{ __('Calculated BPV') }}</span>
                    <span class="text-2xl font-black font-mono">{{ number_format((float) $proverVerification->base_prover_volume, 5) }} L</span>
                </div>
            </div>

            @if($proverVerification->remarks)
                <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
                    <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                        {{ __('Technical Remarks & Observations') }}
                    </h4>
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line font-medium">
                        {{ $proverVerification->remarks }}
                    </p>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
