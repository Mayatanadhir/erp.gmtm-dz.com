@php
    $defaults = old('default_calibrators', $defaultCalibrators ?? ($report->default_calibrators ?? []));
    $allCals = $calibratorsData['all'] ?? collect();
    $pressCals = $calibratorsData['pressure'] ?? $allCals;
    $dpPressCals = $calibratorsData['dp_pressure'] ?? $pressCals;
    $elecCals = $calibratorsData['electrical'] ?? $allCals;
    $transTempCals = $calibratorsData['transmitter_temperature'] ?? $allCals;
    $probeThermalCals = $calibratorsData['probe_thermal'] ?? $allCals;
    $probeResistCals = $calibratorsData['probe_resistance'] ?? $allCals;
    $hideHeader = $hideHeader ?? false;

    $placeholderSelect = '-- '.__('Select a Standard Calibrator').' --';
    $placeholderNoneDeployed = __('No calibrator deployed on this mission');
    $placeholderNoMission = __('Select a mission first to load its deployed calibrators');
    $hasMissionContext = filled(old('mission_id')) || isset($report);
    $calibratorPlaceholder = match (true) {
        $allCals->isNotEmpty() => $placeholderSelect,
        $hasMissionContext => $placeholderNoneDeployed,
        default => $placeholderNoMission,
    };
@endphp

<div id="calibrators_card"
     data-placeholder-select="{{ $placeholderSelect }}"
     data-placeholder-none="{{ $placeholderNoneDeployed }}"
     data-placeholder-no-mission="{{ $placeholderNoMission }}"
     class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
    @if(!$hideHeader)
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-2 border-b border-gray-100 dark:border-gray-700/60">
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>{{ __('Default Calibration Reference Standards') }}</span>
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('Centralized configuration of master calibrators mobilized for this verification report.') }}
                </p>
            </div>
            <x-badge variant="primary" size="sm">
                {{ __('Centralized Setup') }}
            </x-badge>
        </div>
    @endif

    {{-- Automatic Cascade Notice --}}
    <div class="flex items-start gap-2.5 p-3 rounded-lg bg-brand-50/70 dark:bg-brand-950/30 border border-brand-200/70 dark:border-brand-800/40 text-brand-900 dark:text-brand-200 text-xs mb-5">
        <svg class="w-4 h-4 text-brand-600 dark:text-brand-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
        </svg>
        <span class="leading-relaxed">
            {{ __('Standards selected below will automatically cascade to all individual instruments attached to this report.') }}
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- 1. Pression Relative & Absolue --}}
        <div class="rounded-xl bg-gray-50/60 dark:bg-gray-900/40 p-4 border border-gray-200/80 dark:border-gray-700/70 shadow-xs">
            <div class="flex items-center gap-2.5 pb-2 mb-3 border-b border-gray-200/60 dark:border-gray-700/60">
                <span class="w-7 h-7 rounded-lg bg-brand-50 dark:bg-brand-500/20 text-brand-600 dark:text-brand-400 border border-brand-500/20 flex items-center justify-center text-xs shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <div>
                    <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ __('Relative & Absolute Pressure Transmitters') }}</h4>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('Standard calibrators for PT transmitters') }}</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Standard Pressure Calibrator') }}
                    </label>
                    <select name="default_calibrators[pressure_calibrator_1]" data-calibrator-family="pressure" class="input-base w-full text-xs py-1.5 px-2.5 rounded-lg">
                        <option value="">{{ $calibratorPlaceholder }}</option>
                        @foreach($pressCals as $cal)
                            <option value="{{ $cal->id }}" @selected((string) ($defaults['pressure_calibrator_1'] ?? '') === (string) $cal->id)>
                                {{ $cal->full_name ?? ($cal->designation ?? $cal->internal_code) }} ({{ $cal->serial_number ?? $cal->internal_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Multimeter / Current Loop') }}
                    </label>
                    <select name="default_calibrators[pressure_calibrator_2]" data-calibrator-family="electrical" class="input-base w-full text-xs py-1.5 px-2.5 rounded-lg">
                        <option value="">{{ $calibratorPlaceholder }}</option>
                        @foreach($elecCals as $cal)
                            <option value="{{ $cal->id }}" @selected((string) ($defaults['pressure_calibrator_2'] ?? '') === (string) $cal->id)>
                                {{ $cal->full_name ?? ($cal->designation ?? $cal->internal_code) }} ({{ $cal->serial_number ?? $cal->internal_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- 2. Pression Différentielle (ΔP) --}}
        <div class="rounded-xl bg-gray-50/60 dark:bg-gray-900/40 p-4 border border-gray-200/80 dark:border-gray-700/70 shadow-xs">
            <div class="flex items-center gap-2.5 pb-2 mb-3 border-b border-gray-200/60 dark:border-gray-700/60">
                <span class="w-7 h-7 rounded-lg bg-brand-50 dark:bg-brand-500/20 text-brand-600 dark:text-brand-400 border border-brand-500/20 flex items-center justify-center text-xs shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </span>
                <div>
                    <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ __('Differential Pressure Transmitters (ΔP)') }}</h4>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('Standard calibrators for PDT transmitters') }}</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Standard ΔP Calibrator') }}
                    </label>
                    <select name="default_calibrators[dp_calibrator_1]" data-calibrator-family="dp_pressure" class="input-base w-full text-xs py-1.5 px-2.5 rounded-lg">
                        <option value="">{{ $calibratorPlaceholder }}</option>
                        @foreach($dpPressCals as $cal)
                            <option value="{{ $cal->id }}" @selected((string) ($defaults['dp_calibrator_1'] ?? '') === (string) $cal->id)>
                                {{ $cal->full_name ?? ($cal->designation ?? $cal->internal_code) }} ({{ $cal->serial_number ?? $cal->internal_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Multimeter / Current Loop') }}
                    </label>
                    <select name="default_calibrators[dp_calibrator_2]" data-calibrator-family="electrical" class="input-base w-full text-xs py-1.5 px-2.5 rounded-lg">
                        <option value="">{{ $calibratorPlaceholder }}</option>
                        @foreach($elecCals as $cal)
                            <option value="{{ $cal->id }}" @selected((string) ($defaults['dp_calibrator_2'] ?? '') === (string) $cal->id)>
                                {{ $cal->full_name ?? ($cal->designation ?? $cal->internal_code) }} ({{ $cal->serial_number ?? $cal->internal_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- 3. Température (Transmetteurs) --}}
        <div class="rounded-xl bg-gray-50/60 dark:bg-gray-900/40 p-4 border border-gray-200/80 dark:border-gray-700/70 shadow-xs">
            <div class="flex items-center gap-2.5 pb-2 mb-3 border-b border-gray-200/60 dark:border-gray-700/60">
                <span class="w-7 h-7 rounded-lg bg-brand-50 dark:bg-brand-500/20 text-brand-600 dark:text-brand-400 border border-brand-500/20 flex items-center justify-center text-xs shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </span>
                <div>
                    <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ __('Temperature Transmitters (TT)') }}</h4>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('Standard calibrators for TT transmitters') }}</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Temperature Simulator / Dry Well') }}
                    </label>
                    <select name="default_calibrators[temperature_calibrator_1]" data-calibrator-family="transmitter_temperature" class="input-base w-full text-xs py-1.5 px-2.5 rounded-lg">
                        <option value="">{{ $calibratorPlaceholder }}</option>
                        @foreach($transTempCals as $cal)
                            <option value="{{ $cal->id }}" @selected((string) ($defaults['temperature_calibrator_1'] ?? '') === (string) $cal->id)>
                                {{ $cal->full_name ?? ($cal->designation ?? $cal->internal_code) }} ({{ $cal->serial_number ?? $cal->internal_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Multimeter / Current Loop') }}
                    </label>
                    <select name="default_calibrators[temperature_calibrator_2]" data-calibrator-family="electrical" class="input-base w-full text-xs py-1.5 px-2.5 rounded-lg">
                        <option value="">{{ $calibratorPlaceholder }}</option>
                        @foreach($elecCals as $cal)
                            <option value="{{ $cal->id }}" @selected((string) ($defaults['temperature_calibrator_2'] ?? '') === (string) $cal->id)>
                                {{ $cal->full_name ?? ($cal->designation ?? $cal->internal_code) }} ({{ $cal->serial_number ?? $cal->internal_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- 4. Sondes PT100 --}}
        <div class="rounded-xl bg-gray-50/60 dark:bg-gray-900/40 p-4 border border-gray-200/80 dark:border-gray-700/70 shadow-xs">
            <div class="flex items-center gap-2.5 pb-2 mb-3 border-b border-gray-200/60 dark:border-gray-700/60">
                <span class="w-7 h-7 rounded-lg bg-brand-50 dark:bg-brand-500/20 text-brand-600 dark:text-brand-400 border border-brand-500/20 flex items-center justify-center text-xs shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </span>
                <div>
                    <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ __('Standalone Pt100 RTD Probes') }}</h4>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('Reference standards for Pt100 RTD probes') }}</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Thermal Calibration Bath / Block') }}
                    </label>
                    <select name="default_calibrators[probe_calibrator_1]" data-calibrator-family="probe_thermal" class="input-base w-full text-xs py-1.5 px-2.5 rounded-lg">
                        <option value="">{{ $calibratorPlaceholder }}</option>
                        @foreach($probeThermalCals as $cal)
                            <option value="{{ $cal->id }}" @selected((string) ($defaults['probe_calibrator_1'] ?? '') === (string) $cal->id)>
                                {{ $cal->full_name ?? ($cal->designation ?? $cal->internal_code) }} ({{ $cal->serial_number ?? $cal->internal_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('High Precision Ohm Meter') }}
                    </label>
                    <select name="default_calibrators[probe_calibrator_2]" data-calibrator-family="probe_resistance" class="input-base w-full text-xs py-1.5 px-2.5 rounded-lg">
                        <option value="">{{ $calibratorPlaceholder }}</option>
                        @foreach($probeResistCals as $cal)
                            <option value="{{ $cal->id }}" @selected((string) ($defaults['probe_calibrator_2'] ?? '') === (string) $cal->id)>
                                {{ $cal->full_name ?? ($cal->designation ?? $cal->internal_code) }} ({{ $cal->serial_number ?? $cal->internal_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- 5. Calculateurs de Débit (Flow Computers / Multi-Canaux ADC) --}}
        <div class="rounded-xl bg-gray-50/60 dark:bg-gray-900/40 p-4 border border-gray-200/80 dark:border-gray-700/70 shadow-xs lg:col-span-2">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 mb-3 border-b border-gray-200/60 dark:border-gray-700/60">
                <div class="flex items-center gap-2.5">
                    <span class="w-7 h-7 rounded-lg bg-brand-50 dark:bg-brand-500/20 text-brand-600 dark:text-brand-400 border border-brand-500/20 flex items-center justify-center text-xs shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                        </svg>
                    </span>
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ __('Flow Computers / Multi-Channel ADC') }}</h4>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('Reference standards for flow computer acquisition loops') }}</p>
                    </div>
                </div>
                <x-badge variant="primary" size="sm">
                    {{ __('Multi-Channel: Pressure (PT), Differential (ΔP), Temperature (TT) & 4-20mA Loop') }}
                </x-badge>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                {{-- 1. Injecteur 4-20 mA --}}
                <div>
                    <label class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('4-20 mA Signal Generator') }}
                    </label>
                    <select name="default_calibrators[flow_computer_calibrator_1]" data-calibrator-family="electrical" class="input-base w-full text-xs py-1.5 px-2.5 rounded-lg">
                        <option value="">{{ $calibratorPlaceholder }}</option>
                        @foreach($elecCals as $cal)
                            <option value="{{ $cal->id }}" @selected((string) ($defaults['flow_computer_calibrator_1'] ?? '') === (string) $cal->id)>
                                {{ $cal->full_name ?? ($cal->designation ?? $cal->internal_code) }} ({{ $cal->serial_number ?? $cal->internal_code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 2. Étalon Canaux Pression (PT) --}}
                <div>
                    <label class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Pressure Channel Standard (PT)') }}
                    </label>
                    <select name="default_calibrators[flow_computer_pressure_calibrator]" data-calibrator-family="pressure" class="input-base w-full text-xs py-1.5 px-2.5 rounded-lg">
                        <option value="">{{ $calibratorPlaceholder }}</option>
                        @foreach($pressCals as $cal)
                            <option value="{{ $cal->id }}" @selected((string) ($defaults['flow_computer_pressure_calibrator'] ?? '') === (string) $cal->id)>
                                {{ $cal->full_name ?? ($cal->designation ?? $cal->internal_code) }} ({{ $cal->serial_number ?? $cal->internal_code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 3. Étalon Canaux Pression Différentielle (ΔP / PDT) --}}
                <div>
                    <label class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Differential Pressure Channel Standard (ΔP)') }}
                    </label>
                    <select name="default_calibrators[flow_computer_dp_calibrator]" data-calibrator-family="dp_pressure" class="input-base w-full text-xs py-1.5 px-2.5 rounded-lg">
                        <option value="">{{ $calibratorPlaceholder }}</option>
                        @foreach($dpPressCals as $cal)
                            <option value="{{ $cal->id }}" @selected((string) ($defaults['flow_computer_dp_calibrator'] ?? '') === (string) $cal->id)>
                                {{ $cal->full_name ?? ($cal->designation ?? $cal->internal_code) }} ({{ $cal->serial_number ?? $cal->internal_code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 4. Étalon Canaux Température (TT) --}}
                <div>
                    <label class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Temperature Channel Standard (TT)') }}
                    </label>
                    <select name="default_calibrators[flow_computer_temp_calibrator]" data-calibrator-family="transmitter_temperature" class="input-base w-full text-xs py-1.5 px-2.5 rounded-lg">
                        <option value="">{{ $calibratorPlaceholder }}</option>
                        @foreach($transTempCals as $cal)
                            <option value="{{ $cal->id }}" @selected((string) ($defaults['flow_computer_temp_calibrator'] ?? '') === (string) $cal->id)>
                                {{ $cal->full_name ?? ($cal->designation ?? $cal->internal_code) }} ({{ $cal->serial_number ?? $cal->internal_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
