<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('metrology.instruments.show', $instrument) }}" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition" title="{{ __('Back to Instrument Details') }}">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/70 flex items-center justify-center shrink-0">
                    <i class="fas fa-flask text-lg"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight font-mono">
                            {{ __('Edit') }}: {{ $instrument->tag_number }}
                        </h2>
                        <x-badge :variant="$instrument->status->badgeVariant()" :dot="true">
                            {{ $instrument->status->label() }}
                        </x-badge>
                        <x-badge variant="warning">
                            <i class="fas fa-flask me-1"></i>
                            {{ __('Standard Gauge / Jauge Étalon') }}
                        </x-badge>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Volumetric test measure standards with base volume, neck scale sensitivity, and thermal coefficients') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('metrology.instruments.show', $instrument) }}">
                    <x-secondary-button type="button" class="flex items-center gap-1.5 text-xs">
                        <i class="fas fa-times"></i>
                        <span>{{ __('Cancel') }}</span>
                    </x-secondary-button>
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $standardGaugeSpec = $instrument->standardGaugeSpecification;
    @endphp

    <div class="py-8"
        x-data="{
            siteId: '{{ old('site_id', $instrument->site_id ?? '') }}',
            imagePreview: null,
            removeImage: false,
        }"
    >
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <aside class="w-full lg:w-64 shrink-0">
                    <x-metrology-tabs active="instruments" />
                </aside>

                <main class="flex-1 w-full min-w-0 space-y-6">
                    @if ($errors->any())
                        <x-alert variant="danger">
                            <div class="font-bold mb-1">{{ __('Please correct the errors below:') }}</div>
                            <ul class="list-disc list-inside text-xs space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    @endif

                    <form action="{{ route('metrology.instruments.update', $instrument) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_redirect" value="{{ route('metrology.instruments.show', $instrument) }}">
                        <input type="hidden" name="process_variable" value="volume">
                        <input type="hidden" name="fluid_type" value="liquid">
                        <input type="hidden" name="measurement_type" value="Volumetric_Standard">
                        <input type="hidden" name="technology" value="Conventional">

                        <!-- Section 1: Basic Identity -->
                        @include('metrology.instruments.edit.partials._identity', [
                            'instrument' => $instrument,
                            'type' => 'standard_gauge',
                            'typeLabel' => __('Standard Gauge / Jauge Étalon'),
                            'sites' => $sites
                        ])

                        <!-- Section 2: Fixed Standard Gauge Banner -->
                        <div class="p-5 rounded-2xl border border-emerald-200 dark:border-emerald-800/70 bg-emerald-50/60 dark:bg-emerald-950/30 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-3.5 text-emerald-900 dark:text-emerald-200">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/60 flex items-center justify-center shrink-0 text-emerald-600">
                                    <i class="fas fa-flask text-lg"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-sm">{{ __('Volumetric Capacity Standard (Jauge étalon)') }}</div>
                                    <div class="text-xs text-emerald-700 dark:text-emerald-300 mt-0.5">
                                        {{ __('Process Variable: Volume') }} • {{ __('Fluid: Liquid (Phase Liquide)') }} • {{ __('Measurement: Volumetric Standard (BMV)') }}
                                    </div>
                                </div>
                            </div>
                            <x-badge variant="warning">
                                <i class="fas fa-flask me-1"></i>
                                {{ __('Standard Gauge / Jauge Étalon') }}
                            </x-badge>
                        </div>

                        <!-- Section 3: Standard Gauge Metrological Specs -->
                        <div class="p-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm space-y-5">
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3.5">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="fas fa-flask text-emerald-600 dark:text-emerald-400"></i>
                                    <span>{{ __('Standard Volumetric Test Measure (Jauge étalon) Specifications') }}</span>
                                </h3>
                                <x-badge variant="success" size="sm">ISO 17025 Standard</x-badge>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
                                <!-- Nominal Capacity BMV (L) -->
                                <div>
                                    <x-input-label for="sg_nominal_capacity" :value="__('Certified Nominal Capacity BMV (Liters)')" :required="true" />
                                    <x-text-input id="sg_nominal_capacity" name="standard_gauge_spec[nominal_capacity_liters]" type="number" step="0.00001"
                                        class="mt-1 block w-full font-mono text-sm"
                                        value="{{ old('standard_gauge_spec.nominal_capacity_liters', $standardGaugeSpec?->nominal_capacity_liters) }}"
                                        placeholder="e.g. 500.00000" required />
                                    <x-input-error :messages="$errors->get('standard_gauge_spec.nominal_capacity_liters')" class="mt-1" />
                                </div>

                                <!-- Neck Sensitivity (L/mm) -->
                                <div>
                                    <x-input-label for="sg_neck_sensitivity" :value="__('Neck Scale Sensitivity (L/mm)')" :required="true" />
                                    <x-text-input id="sg_neck_sensitivity" name="standard_gauge_spec[neck_scale_sensitivity]" type="number" step="0.00001"
                                        class="mt-1 block w-full font-mono text-sm"
                                        value="{{ old('standard_gauge_spec.neck_scale_sensitivity', $standardGaugeSpec?->neck_scale_sensitivity) }}"
                                        placeholder="e.g. 0.05000" required />
                                    <x-input-error :messages="$errors->get('standard_gauge_spec.neck_scale_sensitivity')" class="mt-1" />
                                </div>

                                <!-- Cubical Expansion Gcm (1/°C) -->
                                <div>
                                    <x-input-label for="sg_gcm" :value="__('Metal Cubical Expansion Coef Gcm (1/°C)')" />
                                    <x-text-input id="sg_gcm" name="standard_gauge_spec[cubical_expansion_coef_gcm]" type="number" step="0.00000001"
                                        class="mt-1 block w-full font-mono text-sm"
                                        value="{{ old('standard_gauge_spec.cubical_expansion_coef_gcm', $standardGaugeSpec?->cubical_expansion_coef_gcm ?? \App\Constants\MetrologyConstants::G_CM_STAINLESS_STEEL) }}"
                                        placeholder="0.00005100" />
                                    <x-input-error :messages="$errors->get('standard_gauge_spec.cubical_expansion_coef_gcm')" class="mt-1" />
                                </div>

                                <!-- ISO Certificate Number -->
                                <div>
                                    <x-input-label for="sg_cert_no" :value="__('ISO 17025 Certificate Number')" />
                                    <x-text-input id="sg_cert_no" name="standard_gauge_spec[calibration_certificate_number]" type="text"
                                        class="mt-1 block w-full font-mono text-sm"
                                        value="{{ old('standard_gauge_spec.calibration_certificate_number', $standardGaugeSpec?->calibration_certificate_number) }}"
                                        placeholder="e.g. CERT-ISO-2026-001" />
                                    <x-input-error :messages="$errors->get('standard_gauge_spec.calibration_certificate_number')" class="mt-1" />
                                </div>

                                <!-- Calibration Date -->
                                <div>
                                    <x-input-label for="sg_cal_date" :value="__('Calibration Date')" />
                                    <x-text-input id="sg_cal_date" name="standard_gauge_spec[calibration_date]" type="date"
                                        class="mt-1 block w-full text-sm"
                                        value="{{ old('standard_gauge_spec.calibration_date', $standardGaugeSpec?->calibration_date?->format('Y-m-d')) }}" />
                                    <x-input-error :messages="$errors->get('standard_gauge_spec.calibration_date')" class="mt-1" />
                                </div>

                                <!-- Expiry Date -->
                                <div>
                                    <x-input-label for="sg_exp_date" :value="__('Calibration Expiry Date')" />
                                    <x-text-input id="sg_exp_date" name="standard_gauge_spec[calibration_expiry_date]" type="date"
                                        class="mt-1 block w-full text-sm"
                                        value="{{ old('standard_gauge_spec.calibration_expiry_date', $standardGaugeSpec?->calibration_expiry_date?->format('Y-m-d')) }}" />
                                    <x-input-error :messages="$errors->get('standard_gauge_spec.calibration_expiry_date')" class="mt-1" />
                                </div>

                                <!-- Vessel Material -->
                                <div>
                                    <x-input-label for="sg_mat" :value="__('Vessel Material')" />
                                    <x-text-input id="sg_mat" name="standard_gauge_spec[vessel_material]" type="text"
                                        class="mt-1 block w-full text-sm"
                                        value="{{ old('standard_gauge_spec.vessel_material', $standardGaugeSpec?->vessel_material ?? 'Stainless Steel 304 / 316') }}"
                                        placeholder="Stainless Steel 316" />
                                    <x-input-error :messages="$errors->get('standard_gauge_spec.vessel_material')" class="mt-1" />
                                </div>

                                <!-- Base Reference Temperature (Temp. Ref) -->
                                <div>
                                    <x-input-label for="sg_base_temp" :value="__('Base Reference Temperature (°C)')" />
                                    <x-text-input id="sg_base_temp" name="standard_gauge_spec[base_reference_temperature]" type="number" step="0.1"
                                        class="mt-1 block w-full font-mono text-sm"
                                        value="{{ old('standard_gauge_spec.base_reference_temperature', $standardGaugeSpec?->base_reference_temperature ?? 15.0) }}"
                                        placeholder="15.0" />
                                    <x-input-error :messages="$errors->get('standard_gauge_spec.base_reference_temperature')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-5">
                            <a href="{{ route('metrology.instruments.show', $instrument) }}">
                                <x-secondary-button type="button" class="flex items-center gap-1.5 text-xs">
                                    <i class="fas fa-arrow-right rtl:rotate-180"></i>
                                    <span>{{ __('Back to Instrument Details') }}</span>
                                </x-secondary-button>
                            </a>

                            <div class="flex items-center gap-3">
                                <x-primary-button type="submit" class="flex items-center gap-2">
                                    <i class="fas fa-save"></i>
                                    <span>{{ __('Save & Update Instrument') }}</span>
                                </x-primary-button>
                            </div>
                        </div>
                    </form>
                </main>
            </div>
        </div>
    </div>
</x-app-layout>
