<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('metrology.reports.show', $report->id) }}"
                   class="p-2.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition shadow-2xs">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <x-tool-icon name="instruments" class="w-12 h-12 sm:w-14 sm:h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ __('Transmitter Verification Saisie') }} &bull; <span dir="ltr" class="font-mono text-brand-600 dark:text-brand-400">{{ $instrument->tag_number }}</span>
                        </h2>
                        <x-badge variant="neutral" size="sm">{{ __('Transmitter') }}</x-badge>
                        @if($report->status === 'completed')
                            <x-badge variant="success" size="sm" :dot="true">{{ __('Report Completed') }}</x-badge>
                        @else
                            <x-badge variant="warning" size="sm" :dot="true">{{ __('In Progress') }}</x-badge>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex flex-wrap items-center gap-2">
                        <span>{{ __('Report:') }} <strong class="font-mono text-gray-700 dark:text-gray-300">{{ $report->report_number }}</strong></span>
                        <span>&bull;</span>
                        <span>{{ $report->mission?->site?->full_name ?? $report->mission?->site?->short_name ?? __('Site Unassigned') }}</span>
                        @if($specifications?->grandeur)
                            <span>&bull;</span>
                            <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $specifications->grandeur->name }}</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('metrology.reports.show', $report->id) }}">
                    <x-secondary-button type="button" class="gap-1.5 text-xs">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>{{ __('Back to Report') }}</span>
                    </x-secondary-button>
                </a>
                <x-primary-button type="submit" form="calibration-form" class="gap-1.5 text-xs shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    <span>{{ __('Save Calibration Data') }}</span>
                </x-primary-button>
            </div>
        </div>
    </x-slot>

    @php
        $unitSymbol = $specifications?->grandeur?->symbol ?? ($specifications?->measurementUnit?->symbol ?? 'bar');
        $span = (float)(($specifications?->range_max ?? 100) - ($specifications?->range_min ?? 0));
        $min = (float)($specifications?->range_min ?? 0);
        $measurand = $specifications?->grandeur?->name ?? 'Pression';
        $pressureType = $instrument->measurement_type ?? 'Relative';
        
        $isTemp = (stripos($measurand, 'Temp') !== false) || in_array(strtolower($unitSymbol), ['°c', 'c', 'k']);
        $isAbsolutePressure = (!$isTemp) && ($pressureType === 'Absolute');

        $oamService = app(\App\Services\OamMetrologyService::class);
        $fluidVal = $instrument->fluid_type instanceof \BackedEnum ? $instrument->fluid_type->value : (string) ($instrument->fluid_type ?? 'Liquid');
        $dummyEval = $oamService->evaluateTransmitter($span, $min, $min, 4.0, $min, $fluidVal, $instrument->technology, $measurand, $pressureType, 0.0);
        
        $emtApproved = $dummyEval['emt'] ?? ($specifications?->accuracy_value ?? 0.5);
        $isRelativeEmt = (!$isTemp) && (($fluidVal === 'Gas' || $instrument->technology === 'SMART') || ($span > 10 && $span <= 40));
        $emtUnitDisplay = $isTemp ? '°C' : ($isRelativeEmt ? '%' : $unitSymbol);
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

                    @if(session('success'))
                        <x-alert variant="success" :title="session('success')" :dismissible="true" />
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

                    <form id="calibration-form" action="{{ route('metrology.reports.saisie.transmitter', ['report' => $report->id, 'instrument' => $instrument->id]) }}" method="POST" class="space-y-6">
                        @csrf
                        
                        <!-- Hidden calculation engine flags for JavaScript -->
                        <input type="hidden" id="bas_echelle" value="{{ $specifications?->range_min ?? 0 }}">
                        <input type="hidden" id="fond_echelle" value="{{ $specifications?->range_max ?? 100 }}">
                        <input type="hidden" id="emt_limit" value="{{ $emtApproved }}">
                        <input type="hidden" id="is_relative_emt" value="{{ $isRelativeEmt ? '1' : '0' }}">
                        <input type="hidden" id="is_absolute_pressure" value="{{ $isAbsolutePressure ? '1' : '0' }}">
                        <input type="hidden" id="is_temp_sensor" value="{{ $isTemp ? '1' : '0' }}">

                        <!-- Card 1: General Information & EMT -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 mb-5 border-b border-gray-100 dark:border-gray-700/60">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>{{ __('General Information & Calibration Session') }}</span>
                                </h3>
                                <div class="flex flex-wrap items-center gap-2">
                                    @if($isTemp)
                                        <x-badge variant="info" size="sm">{{ __('Temperature (IEC 60751)') }}</x-badge>
                                    @elseif($isAbsolutePressure)
                                        <x-badge variant="warning" size="sm">{{ __('Absolute Pressure') }}</x-badge>
                                    @endif
                                    <x-badge variant="success" size="sm" :dot="true">
                                        {{ __('Approved EMT:') }} ±{{ $emtApproved }} {{ $emtUnitDisplay }}
                                    </x-badge>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ $isAbsolutePressure ? '5' : '4' }} gap-4 text-xs">
                                <!-- Tag -->
                                <div>
                                    <x-input-label class="text-xs mb-1">{{ __('Tag Number') }}</x-input-label>
                                    <input type="text" value="{{ $instrument->tag_number }}" disabled
                                           class="w-full text-xs rounded-md border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60 text-gray-900 dark:text-white font-mono font-bold py-2 px-3 cursor-not-allowed" />
                                </div>

                                <!-- Serial Number -->
                                <div>
                                    <x-input-label class="text-xs mb-1">{{ __('Serial Number (S/N)') }}</x-input-label>
                                    <input type="text" value="{{ $instrument->serial_number }}" disabled
                                           class="w-full text-xs rounded-md border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60 text-gray-600 dark:text-gray-300 font-mono py-2 px-3 cursor-not-allowed" />
                                </div>

                                <!-- EMT Limit Display -->
                                <div>
                                    <x-input-label class="text-xs mb-1">{{ __('EMT Limit') }}</x-input-label>
                                    <input type="text" value="±{{ $emtApproved }} {{ $emtUnitDisplay }}" disabled
                                           class="w-full text-xs rounded-md border-gray-200 dark:border-gray-700 bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-300 font-mono font-bold py-2 px-3 cursor-not-allowed" />
                                </div>

                                <!-- Verification Date -->
                                <div>
                                    <x-input-label for="verification_date" class="text-xs mb-1">
                                        {{ __('Verification Date') }} <span class="text-rose-500">*</span>
                                    </x-input-label>
                                    @php
                                        $formattedDate = old('verification_date', isset($verification->verification_date) ? (is_string($verification->verification_date) ? \Carbon\Carbon::parse($verification->verification_date)->format('Y-m-d') : $verification->verification_date->format('Y-m-d')) : date('Y-m-d'));
                                    @endphp
                                    <input type="date" id="verification_date" name="verification_date" value="{{ $formattedDate }}" required
                                           class="input-base w-full text-xs py-2 px-3 focus:border-brand-600 focus:ring-brand-600" />
                                </div>

                                @if($isAbsolutePressure)
                                    <!-- Atmospheric Pressure -->
                                    <div>
                                        <x-input-label for="ambient_pressure" class="text-xs mb-1">
                                            {{ __('Atmospheric Pressure') }} ({{ $unitSymbol }}) <span class="text-rose-500">*</span>
                                        </x-input-label>
                                        <input type="number" step="0.0001" name="ambient_pressure" id="ambient_pressure"
                                               value="{{ old('ambient_pressure', $verification->ambient_pressure ?? '0.9600') }}" required
                                               class="input-base w-full text-xs font-mono font-bold py-2 px-3 focus:border-brand-600 focus:ring-brand-600"
                                               placeholder="0.9600" />
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Card 2: Scale Parameters & Master Calibrators -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 pb-3 mb-5 border-b border-gray-100 dark:border-gray-700/60">
                                <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                                <span>{{ __('Scale Parameters & Master Calibrators') }}</span>
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                                <!-- Lower Range Value (LRV) -->
                                <div>
                                    <x-input-label class="text-xs mb-1">{{ __('Lower Range Value (LRV)') }} ({{ $unitSymbol }})</x-input-label>
                                    <input type="text" value="{{ floatval($specifications?->range_min ?? 0) }} {{ $unitSymbol }}" disabled
                                           class="w-full text-xs rounded-md border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60 text-gray-700 dark:text-gray-300 font-mono font-bold py-2 px-3 cursor-not-allowed" />
                                </div>

                                <!-- Upper Range Value (URV) -->
                                <div>
                                    <x-input-label class="text-xs mb-1">{{ __('Upper Range Value (URV)') }} ({{ $unitSymbol }})</x-input-label>
                                    <input type="text" value="{{ floatval($specifications?->range_max ?? 100) }} {{ $unitSymbol }}" disabled
                                           class="w-full text-xs rounded-md border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60 text-gray-700 dark:text-gray-300 font-mono font-bold py-2 px-3 cursor-not-allowed" />
                                </div>

                                <!-- Calibrator 1: Generator -->
                                <!-- Calibrator 1: Generator -->
                                <div>
                                    <x-input-label for="calibrator_1" class="text-xs mb-1">
                                        {{ __('Calibrator 1') }} ({{ __('Generator') }} {{ $unitSymbol }})
                                    </x-input-label>
                                    <select name="calibrator_1" id="calibrator_1" class="input-base w-full text-xs py-2 px-3">
                                        <option value="">{{ __('Select standard generator...') }}</option>
                                        @foreach($options1 as $cal)
                                            <option value="{{ $cal->id }}" {{ (string)$selectedCal1 === (string)$cal->id ? 'selected' : '' }}>
                                                {{ $cal->full_name ?? ($cal->designation ?? ($cal->name ?? $cal->internal_code)) }} ({{ $cal->serial_number ?? $cal->internal_code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div id="cal1-status-info" class="mt-1 text-[11px] min-h-[16px]"></div>
                                </div>

                                <!-- Calibrator 2: Multimeter mA -->
                                <div>
                                    <x-input-label for="calibrator_2" class="text-xs mb-1">
                                        {{ __('Calibrator 2') }} ({{ __('Multimeter mA') }})
                                    </x-input-label>
                                    <select name="calibrator_2" id="calibrator_2" class="input-base w-full text-xs py-2 px-3">
                                        <option value="">{{ __('Select standard multimeter...') }}</option>
                                        @foreach($options2 as $cal)
                                            <option value="{{ $cal->id }}" {{ (string)$selectedCal2 === (string)$cal->id ? 'selected' : '' }}>
                                                {{ $cal->full_name ?? ($cal->designation ?? ($cal->name ?? $cal->internal_code)) }} ({{ $cal->serial_number ?? $cal->internal_code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div id="cal2-status-info" class="mt-1 text-[11px] min-h-[16px]"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Calibration Points Table (Hysteresis Cycle) -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60 space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-700/60">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span>{{ __('Measurement Points (Hysteresis Cycle)') }}</span>
                                </h3>
                                <div class="flex items-center gap-2">
                                    <x-badge variant="neutral" size="sm">
                                        {{ __('Calibrator 1 → Applied') }}
                                    </x-badge>
                                    <x-badge variant="info" size="sm">
                                        {{ __('Calibrator 2 → Measured mA') }}
                                    </x-badge>
                                </div>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-2xs">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                                    <thead class="bg-gray-50/90 dark:bg-gray-900/60 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        <tr>
                                            <th class="px-3 py-3 text-center w-12">#</th>
                                            <th class="px-3 py-3 text-center">{{ __('Applied (%)') }}</th>
                                            <th class="px-3 py-3 text-center">{{ __('Applied Value') }} ({{ $unitSymbol }})</th>
                                            <th class="px-3 py-3 text-center text-brand-700 dark:text-brand-400 bg-brand-50/60 dark:bg-brand-950/50 border-x border-brand-200/40 dark:border-brand-900/50">
                                                {{ __('correction (Interpolated)') }} ({{ $unitSymbol }})
                                            </th>
                                            @if($isTemp)
                                                <th class="px-3 py-3 text-center">{{ __('Theoretical R(T) (Ω)') }}</th>
                                            @endif
                                            <th class="px-3 py-3 text-center">{{ __('Measured Signal') }} (mA)</th>
                                            <th class="px-3 py-3 text-center text-emerald-700 dark:text-emerald-400 bg-emerald-50/60 dark:bg-emerald-950/50 border-x border-emerald-200/40 dark:border-emerald-900/50">
                                                {{ __('correction (Interpolated)') }} (mA)
                                            </th>
                                            <th class="px-3 py-3 text-center">{{ __('Measured Value') }} ({{ $unitSymbol }})</th>
                                            <th class="px-3 py-3 text-center">{{ __('Error') }} ({{ $unitSymbol }})</th>
                                            <th class="px-3 py-3 text-center">{{ __('Error (%)') }}</th>
                                            <th class="px-3 py-3 text-center">{{ __('Verdict') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="points-table-body" class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
                                        @foreach($defaultPercentages as $index => $percentage)
                                            @php
                                                $appliedValue = $specifications->range_min + ($percentage / 100) * ($specifications->range_max - $specifications->range_min);
                                                $existingPoint = isset($verification) && isset($verification->points) ? $verification->points->where('step_order', $index + 1)->first() : null;
                                                $currentRefVal = (float)($existingPoint->reference_value ?? $appliedValue);
                                            @endphp
                                            <tr class="point-row hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition-colors">
                                                <td class="px-3 py-2.5 text-center font-mono font-bold text-gray-500 dark:text-gray-400">
                                                    {{ $index + 1 }}
                                                </td>
                                                <td class="px-3 py-2.5 text-center font-mono font-bold text-brand-600 dark:text-brand-400">
                                                    <input type="hidden" name="points[{{ $index }}][applied_percentage]" value="{{ $percentage }}">
                                                    <span>{{ number_format($percentage, 1) }}%</span>
                                                </td>
                                                <!-- Applied Value Input -->
                                                <td class="px-3 py-2.5 text-center">
                                                    <input type="number" step="0.0001" name="points[{{ $index }}][reference_value]"
                                                           value="{{ $existingPoint->reference_value ?? number_format($appliedValue, 4, '.', '') }}" required
                                                           class="input-appliquee w-28 text-center text-xs font-mono font-bold rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white py-1.5 px-2 focus:ring-brand-500 focus:border-brand-500" />
                                                </td>
                                                <!-- Applied Value + Correction (Interpolated) -->
                                                <td class="px-3 py-2.5 text-center font-mono font-bold cell-ref-corrigee text-brand-700 dark:text-brand-400 bg-brand-50/30 dark:bg-brand-950/30 border-x border-brand-100/50 dark:border-brand-900/30">
                                                    <span class="ref-corrigee-val">{{ isset($existingPoint->corrected_reference_value) ? number_format($existingPoint->corrected_reference_value, 4, '.', '') : '-' }}</span>
                                                    <input type="hidden" name="points[{{ $index }}][calibrator_1_correction]" class="input-cal1-correction" value="{{ $existingPoint->calibrator_1_correction ?? '' }}">
                                                    <input type="hidden" name="points[{{ $index }}][corrected_reference_value]" class="input-corrected-reference" value="{{ $existingPoint->corrected_reference_value ?? '' }}">
                                                </td>
                                                @if($isTemp)
                                                    @php
                                                        $theoreticalR = $oamService->temperatureToResistance($currentRefVal);
                                                    @endphp
                                                    <td class="px-3 py-2.5 text-center font-mono font-bold text-indigo-600 dark:text-indigo-400 cell-res-theorique">
                                                        {{ number_format($theoreticalR, 3, '.', '') }} Ω
                                                    </td>
                                                @endif
                                                <!-- Measured Signal Input (mA) -->
                                                <td class="px-3 py-2.5 text-center">
                                                    <input type="number" step="0.0001" name="points[{{ $index }}][measured_signal]"
                                                           value="{{ $existingPoint->measured_signal ?? '' }}" required
                                                           placeholder="4.0000"
                                                           class="input-signal w-28 text-center text-xs font-mono font-bold rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white py-1.5 px-2 focus:ring-brand-500 focus:border-brand-500" />
                                                </td>
                                                <!-- Measured Signal + Correction (Interpolated) -->
                                                <td class="px-3 py-2.5 text-center font-mono font-bold cell-signal-corrige text-emerald-700 dark:text-emerald-400 bg-emerald-50/30 dark:bg-emerald-950/30 border-x border-emerald-100/50 dark:border-emerald-900/30">
                                                    <span class="signal-corrige-val">{{ isset($existingPoint->corrected_signal) ? number_format($existingPoint->corrected_signal, 4, '.', '') : '-' }}</span>
                                                    <input type="hidden" name="points[{{ $index }}][calibrator_2_correction]" class="input-cal2-correction" value="{{ $existingPoint->calibrator_2_correction ?? '' }}">
                                                    <input type="hidden" name="points[{{ $index }}][corrected_signal]" class="input-corrected-signal" value="{{ $existingPoint->corrected_signal ?? '' }}">
                                                </td>
                                                <!-- Calculated Value -->
                                                <td class="px-3 py-2.5 text-center font-mono font-semibold cell-mesuree text-gray-700 dark:text-gray-200">
                                                    -
                                                </td>
                                                <!-- Absolute Error -->
                                                <td class="px-3 py-2.5 text-center font-mono font-bold cell-erreur">
                                                    -
                                                </td>
                                                <!-- Relative Error (%) -->
                                                <td class="px-3 py-2.5 text-center font-mono font-bold cell-erreur-rel text-indigo-600 dark:text-indigo-400">
                                                    -
                                                </td>
                                                <!-- Conformity Verdict -->
                                                <td class="px-3 py-2.5 text-center cell-conformite">
                                                    -
                                                </td>
                                                <input type="hidden" name="points[{{ $index }}][is_conforme]" class="input-conformite" value="{{ $existingPoint->is_conforme ?? '' }}">
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Bottom Action Bar -->
                        <div class="flex items-center justify-between rounded-xl bg-white dark:bg-gray-800 p-4 border border-gray-100 dark:border-gray-700/60 shadow-xs">
                            <a href="{{ route('metrology.reports.show', $report->id) }}">
                                <x-secondary-button type="button" class="gap-1.5 text-xs">
                                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                    <span>{{ __('Back to Report') }}</span>
                                </x-secondary-button>
                            </a>
                            <x-primary-button type="submit" class="gap-2 text-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ __('Save Calibration Data') }}</span>
                            </x-primary-button>
                        </div>
                    </form>

                </main>
            </div>
        </div>
    </div>

    <!-- Active Calibrators Points Data -->
    <script id="calibrators-points-data" type="application/json">@json($calibratorsPointsMap ?? [])</script>

    <!-- Live Calculation & Metrological Interpolation Engine Script -->
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const basEchelle = parseFloat(document.getElementById('bas_echelle').value);
        const fondEchelle = parseFloat(document.getElementById('fond_echelle').value);
        const emtLimit = parseFloat(document.getElementById('emt_limit').value);
        const isRelativeEmt = document.getElementById('is_relative_emt').value === '1';
        const isAbsolutePressure = document.getElementById('is_absolute_pressure').value === '1';
        const ambientPressureInput = document.getElementById('ambient_pressure');
        const span = fondEchelle - basEchelle;

        const calibratorsDataEl = document.getElementById('calibrators-points-data');
        let calibratorsPointsMap = {};
        if (calibratorsDataEl) {
            try {
                calibratorsPointsMap = JSON.parse(calibratorsDataEl.textContent || '{}');
            } catch (e) {
                console.warn('Failed to parse calibrators points map', e);
            }
        }

        /**
         * خوارزمية الاستيفاء الخطي المترولوجي لنقاط شهادة المعايرة
         */
        function interpolatePoints(points, targetX) {
            if (!points || !Array.isArray(points) || points.length === 0 || isNaN(targetX)) {
                return { correction: 0, hasData: false };
            }

            // 1. فحص النقطة المعتمدة الدقيقة
            for (let i = 0; i < points.length; i++) {
                if (Math.abs(points[i].nominal - targetX) < 1e-6) {
                    return { correction: Number(points[i].correction), hasData: true, exact: true };
                }
            }

            // 2. ترتيب تصاعدي حسب القيمة الاسمية
            const sorted = [...points].sort((a, b) => a.nominal - b.nominal);
            const minNom = sorted[0].nominal;
            const maxNom = sorted[sorted.length - 1].nominal;

            // 3. حماية حدود النطاق وتثبيت أقرب نقطة معتمدة
            if (targetX <= minNom) {
                return { correction: Number(sorted[0].correction), hasData: true, clamped: true };
            }
            if (targetX >= maxNom) {
                return { correction: Number(sorted[sorted.length - 1].correction), hasData: true, clamped: true };
            }

            // 4. استيفاء خطي بين النقطتين الحاصرتين [p1, p2]
            for (let i = 0; i < sorted.length - 1; i++) {
                const p1 = sorted[i];
                const p2 = sorted[i + 1];
                if (targetX >= p1.nominal && targetX <= p2.nominal) {
                    if (p2.nominal === p1.nominal) {
                        return { correction: Number(p1.correction), hasData: true };
                    }
                    const ratio = (targetX - p1.nominal) / (p2.nominal - p1.nominal);
                    const interpCorr = Number(p1.correction) + ratio * (Number(p2.correction) - Number(p1.correction));
                    return { correction: interpCorr, hasData: true };
                }
            }

            return { correction: 0, hasData: false };
        }

        /**
         * تحديث شارات حالة نقاط المعايرة تحت قوائم اختيار الأجهزة
         */
        function updateCalibratorStatus() {
            const cal1Select = document.getElementById('calibrator_1');
            const cal2Select = document.getElementById('calibrator_2');
            const cal1Info = document.getElementById('cal1-status-info');
            const cal2Info = document.getElementById('cal2-status-info');

            if (cal1Select && cal1Info) {
                const val1 = cal1Select.value;
                const pts1 = val1 && calibratorsPointsMap[val1] ? calibratorsPointsMap[val1] : [];
                if (val1 && pts1.length > 0) {
                    cal1Info.innerHTML = `<span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-semibold"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> ${pts1.length} {{ __('points') }} ({{ __('Interpolation Active') }})</span>`;
                } else if (val1) {
                    cal1Info.innerHTML = `<span class="text-gray-400 dark:text-gray-500">{{ __('No certificate points (Correction = 0)') }}</span>`;
                } else {
                    cal1Info.innerHTML = '';
                }
            }

            if (cal2Select && cal2Info) {
                const val2 = cal2Select.value;
                const pts2 = val2 && calibratorsPointsMap[val2] ? calibratorsPointsMap[val2] : [];
                if (val2 && pts2.length > 0) {
                    cal2Info.innerHTML = `<span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-semibold"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> ${pts2.length} {{ __('points') }} ({{ __('Interpolation Active') }})</span>`;
                } else if (val2) {
                    cal2Info.innerHTML = `<span class="text-gray-400 dark:text-gray-500">{{ __('No certificate points (Correction = 0)') }}</span>`;
                } else {
                    cal2Info.innerHTML = '';
                }
            }
        }

        function tempToResistance(t) {
            const r0 = 100.0;
            const a = 3.9083e-3;
            const b = -5.775e-7;
            const c = -4.183e-12;

            if (t >= 0) {
                return r0 * (1 + a * t + b * t * t);
            }
            return r0 * (1 + a * t + b * t * t + c * (t - 100) * Math.pow(t, 3));
        }

        function getAmbientPressure() {
            if (!isAbsolutePressure || !ambientPressureInput) return 0;
            const val = parseFloat(ambientPressureInput.value);
            return isNaN(val) ? 0 : val;
        }

        const rows = document.querySelectorAll('.point-row');

        function calculateRow(row) {
            const inputSignal = row.querySelector('.input-signal');
            const inputAppliquee = row.querySelector('.input-appliquee');
            const cellRefCorrigee = row.querySelector('.cell-ref-corrigee');
            const cellSignalCorrige = row.querySelector('.cell-signal-corrige');
            const inputCal1Corr = row.querySelector('.input-cal1-correction');
            const inputCorrRef = row.querySelector('.input-corrected-reference');
            const inputCal2Corr = row.querySelector('.input-cal2-correction');
            const inputCorrSignal = row.querySelector('.input-corrected-signal');
            const cellResTheorique = row.querySelector('.cell-res-theorique');
            const cellMesuree = row.querySelector('.cell-mesuree');
            const cellErreur = row.querySelector('.cell-erreur');
            const cellErreurRel = row.querySelector('.cell-erreur-rel');
            const cellConformite = row.querySelector('.cell-conformite');
            const inputConformite = row.querySelector('.input-conformite');

            const rawSignal = inputSignal ? inputSignal.value.trim() : '';
            const rawAppliquee = inputAppliquee ? inputAppliquee.value.trim() : '';

            const signal_mA = rawSignal !== '' ? parseFloat(rawSignal) : NaN;
            const val_appliquee = rawAppliquee !== '' ? parseFloat(rawAppliquee) : NaN;
            const ambientPressure = getAmbientPressure();

            const cal1Id = document.getElementById('calibrator_1')?.value;
            const cal2Id = document.getElementById('calibrator_2')?.value;
            const cal1Points = (cal1Id && calibratorsPointsMap[cal1Id]) ? calibratorsPointsMap[cal1Id] : [];
            const cal2Points = (cal2Id && calibratorsPointsMap[cal2Id]) ? calibratorsPointsMap[cal2Id] : [];

            // 1. استيفاء وتصحيح القيمة المرجعية المطبقة (Calibrator 1)
            let val_appliquee_corrigee = val_appliquee;
            let cal1_correction = 0;
            if (!isNaN(val_appliquee)) {
                const res1 = interpolatePoints(cal1Points, val_appliquee);
                cal1_correction = res1.correction;
                val_appliquee_corrigee = val_appliquee + cal1_correction;

                if (cellRefCorrigee) {
                    const spanVal = cellRefCorrigee.querySelector('.ref-corrigee-val');
                    if (spanVal) spanVal.textContent = val_appliquee_corrigee.toFixed(4);
                    if (res1.hasData && Math.abs(cal1_correction) > 1e-6) {
                        cellRefCorrigee.title = `Correction Calibrator 1: ${(cal1_correction >= 0 ? '+' : '')}${cal1_correction.toFixed(5)}`;
                    } else {
                        cellRefCorrigee.title = '';
                    }
                }
                if (inputCal1Corr) inputCal1Corr.value = cal1_correction.toFixed(6);
                if (inputCorrRef) inputCorrRef.value = val_appliquee_corrigee.toFixed(6);
            } else {
                if (cellRefCorrigee) {
                    const spanVal = cellRefCorrigee.querySelector('.ref-corrigee-val');
                    if (spanVal) spanVal.textContent = '-';
                }
                if (inputCal1Corr) inputCal1Corr.value = '';
                if (inputCorrRef) inputCorrRef.value = '';
            }

            // 2. المقاومة النظرية لمستشعر الحرارة
            if (cellResTheorique && !isNaN(val_appliquee_corrigee)) {
                const rTheo = tempToResistance(val_appliquee_corrigee);
                cellResTheorique.textContent = rTheo.toFixed(3) + ' Ω';
            }

            // 3. استيفاء وتصحيح الإشارة المقاسة (Calibrator 2)
            let signal_corrige = signal_mA;
            let cal2_correction = 0;
            if (!isNaN(signal_mA)) {
                const res2 = interpolatePoints(cal2Points, signal_mA);
                cal2_correction = res2.correction;
                signal_corrige = signal_mA + cal2_correction;

                if (cellSignalCorrige) {
                    const spanSig = cellSignalCorrige.querySelector('.signal-corrige-val');
                    if (spanSig) spanSig.textContent = signal_corrige.toFixed(4);
                    if (res2.hasData && Math.abs(cal2_correction) > 1e-6) {
                        cellSignalCorrige.title = `Correction Calibrator 2: ${(cal2_correction >= 0 ? '+' : '')}${cal2_correction.toFixed(5)} mA`;
                    } else {
                        cellSignalCorrige.title = '';
                    }
                }
                if (inputCal2Corr) inputCal2Corr.value = cal2_correction.toFixed(6);
                if (inputCorrSignal) inputCorrSignal.value = signal_corrige.toFixed(6);
            } else {
                if (cellSignalCorrige) {
                    const spanSig = cellSignalCorrige.querySelector('.signal-corrige-val');
                    if (spanSig) spanSig.textContent = '-';
                }
                if (inputCal2Corr) inputCal2Corr.value = '';
                if (inputCorrSignal) inputCorrSignal.value = '';
            }

            // 4. الحساب المترولوجي النهائي للقيمة المقاسة والخطأ
            if (!isNaN(signal_corrige) && !isNaN(val_appliquee_corrigee) && span !== 0) {
                const val_mesuree = basEchelle + ((signal_corrige - 4) / 16) * span;
                cellMesuree.textContent = val_mesuree.toFixed(3);

                const val_ref_finale = val_appliquee_corrigee + ambientPressure;
                const erreurAbs = val_mesuree - val_ref_finale;
                cellErreur.textContent = (erreurAbs >= 0 ? '+' : '') + erreurAbs.toFixed(4);
                cellErreur.className = 'px-4 py-2.5 text-center font-mono font-bold cell-erreur ' + (Math.abs(erreurAbs) <= emtLimit + 0.000001 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400');

                const erreurRel = (span !== 0) ? ((erreurAbs / span) * 100) : 0;
                if (cellErreurRel) {
                    cellErreurRel.textContent = (erreurRel >= 0 ? '+' : '') + erreurRel.toFixed(3) + '%';
                }

                const erreurEval = isRelativeEmt ? ((Math.abs(erreurAbs) / span) * 100) : Math.abs(erreurAbs);
                if (erreurEval <= emtLimit + 0.000001) {
                    cellConformite.innerHTML = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300/60"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> OK</span>';
                    inputConformite.value = 1;
                } else {
                    cellConformite.innerHTML = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-300/60"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg> NOK</span>';
                    inputConformite.value = 0;
                }
            } else {
                cellMesuree.textContent = '-';
                cellErreur.textContent = '-';
                if (cellErreurRel) cellErreurRel.textContent = '-';
                cellConformite.innerHTML = '-';
                inputConformite.value = '';
            }
        }

        function calculateAll() {
            rows.forEach(row => calculateRow(row));
        }

        rows.forEach(row => {
            row.querySelector('.input-signal')?.addEventListener('input', () => calculateRow(row));
            row.querySelector('.input-appliquee')?.addEventListener('input', () => calculateRow(row));
        });

        const cal1Select = document.getElementById('calibrator_1');
        const cal2Select = document.getElementById('calibrator_2');

        if (cal1Select) {
            cal1Select.addEventListener('change', function() {
                updateCalibratorStatus();
                calculateAll();
            });
        }

        if (cal2Select) {
            cal2Select.addEventListener('change', function() {
                updateCalibratorStatus();
                calculateAll();
            });
        }

        if (ambientPressureInput) {
            ambientPressureInput.addEventListener('input', calculateAll);
        }

        updateCalibratorStatus();
        calculateAll();
    });
    </script>
    @endpush
</x-app-layout>