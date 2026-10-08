<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('metrology.instruments.create') }}" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition" title="{{ __('Back to Type Selection') }}">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/70 flex items-center justify-center shrink-0">
                    <i class="fas fa-flask text-lg"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ __('Register New Standard Gauge (Jauge Étalon)') }}
                        </h2>
                        <x-badge variant="warning" size="sm">OAM Ch. 8 / ISO 17025</x-badge>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Volumetric capacity test measure with neck scale resolution, thermal expansion Gcm, and ISO 17025 traceability') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('metrology.instruments.create') }}">
                    <x-secondary-button type="button" class="flex items-center gap-1.5 text-xs">
                        <i class="fas fa-th-large"></i>
                        <span>{{ __('Change Type') }}</span>
                    </x-secondary-button>
                </a>
                <a href="{{ route('metrology.instruments') }}">
                    <x-secondary-button type="button" class="flex items-center gap-1.5 text-xs">
                        <i class="fas fa-times"></i>
                        <span>{{ __('Cancel') }}</span>
                    </x-secondary-button>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8"
        x-data="{
            siteId: '{{ old('site_id', '') }}',
            imagePreview: null,
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

                    <form action="{{ route('metrology.instruments.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <!-- Section 1: Basic Identity -->
                        @include('metrology.instruments.create.partials._identity', [
                            'type' => 'standard_gauge',
                            'typeLabel' => __('Standard Gauge / Jauge Étalon'),
                            'sites' => $sites
                        ])

                        <input type="hidden" name="process_variable" value="volume">
                        <input type="hidden" name="measurement_type" value="Volumetric_Standard">
                        <input type="hidden" name="fluid_type" value="liquid">
                        <input type="hidden" name="technology" value="Conventional">

                        <!-- Section 2: Fixed Volume Standards Banner -->
                        <div class="p-4 rounded-xl border border-amber-200 dark:border-amber-800/70 bg-amber-50/60 dark:bg-amber-950/30 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-3 text-amber-900 dark:text-amber-200">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/60 flex items-center justify-center shrink-0 text-amber-600">
                                    <i class="fas fa-flask"></i>
                                </div>
                                <div>
                                    <div class="font-bold">{{ __('Volumetric Capacity Standard (Jauge étalon)') }}</div>
                                    <div class="text-[11px] text-amber-700 dark:text-amber-300">
                                        {{ __('Process Variable: Volume') }} • {{ __('Fluid: Liquid (Phase Liquide)') }} • {{ __('Measurement: Volumetric Standard (BMV)') }}
                                    </div>
                                </div>
                            </div>
                            <x-badge variant="warning">
                                <i class="fas fa-flask me-1"></i>
                                {{ __('Standard Gauge / Jauge Étalon') }}
                            </x-badge>
                        </div>

                        <!-- Section 3: Standard Gauge Physical & Metrological Specs -->
                        <div class="p-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm space-y-5">
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3.5">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="fas fa-flask text-emerald-600 dark:text-emerald-400"></i>
                                    <span>{{ __('Standard Volumetric Test Measure (Jauge étalon) Specifications') }}</span>
                                </h3>
                                <x-badge variant="success" size="sm">ISO 17025 Standard</x-badge>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
                                <!-- Nominal Capacity BMV -->
                                <div>
                                    <x-input-label for="sg_nominal_capacity" :value="__('Certified Nominal Capacity BMV (Liters)')" :required="true" />
                                    <x-text-input id="sg_nominal_capacity" name="standard_gauge_spec[nominal_capacity_liters]" type="number" step="0.00001"
                                        class="mt-1 block w-full font-mono text-sm"
                                        value="{{ old('standard_gauge_spec.nominal_capacity_liters') }}"
                                        placeholder="e.g. 500.00000" required />
                                    <x-input-error :messages="$errors->get('standard_gauge_spec.nominal_capacity_liters')" class="mt-1" />
                                </div>

                                <!-- Neck Scale Sensitivity -->
                                <div>
                                    <x-input-label for="sg_neck_sensitivity" :value="__('Neck Scale Sensitivity (L/mm)')" :required="true" />
                                    <x-text-input id="sg_neck_sensitivity" name="standard_gauge_spec[neck_scale_sensitivity]" type="number" step="0.00001"
                                        class="mt-1 block w-full font-mono text-sm"
                                        value="{{ old('standard_gauge_spec.neck_scale_sensitivity') }}"
                                        placeholder="e.g. 0.05000" />
                                </div>

                                <!-- Metal Cubical Expansion Coef Gcm -->
                                <div>
                                    <x-input-label for="sg_gcm" :value="__('Metal Cubical Expansion Coef Gcm (1/°C)')" />
                                    <x-text-input id="sg_gcm" name="standard_gauge_spec[cubical_expansion_coef_gcm]" type="number" step="0.00000001"
                                        class="mt-1 block w-full font-mono text-sm"
                                        value="{{ old('standard_gauge_spec.cubical_expansion_coef_gcm', \App\Constants\MetrologyConstants::G_CM_STAINLESS_STEEL) }}"
                                        placeholder="0.00005100" />
                                </div>

                                <!-- ISO 17025 Certificate Number -->
                                <div>
                                    <x-input-label for="sg_cert_no" :value="__('ISO 17025 Certificate Number')" />
                                    <x-text-input id="sg_cert_no" name="standard_gauge_spec[calibration_certificate_number]" type="text"
                                        class="mt-1 block w-full font-mono text-sm"
                                        value="{{ old('standard_gauge_spec.calibration_certificate_number') }}"
                                        placeholder="e.g. CERT-ISO-2026-001" />
                                </div>

                                <!-- Calibration Date -->
                                <div>
                                    <x-input-label for="sg_cal_date" :value="__('Calibration Date')" />
                                    <x-text-input id="sg_cal_date" name="standard_gauge_spec[calibration_date]" type="date"
                                        class="mt-1 block w-full text-sm"
                                        value="{{ old('standard_gauge_spec.calibration_date') }}" />
                                </div>

                                <!-- Calibration Expiry Date -->
                                <div>
                                    <x-input-label for="sg_exp_date" :value="__('Calibration Expiry Date')" />
                                    <x-text-input id="sg_exp_date" name="standard_gauge_spec[calibration_expiry_date]" type="date"
                                        class="mt-1 block w-full text-sm"
                                        value="{{ old('standard_gauge_spec.calibration_expiry_date') }}" />
                                </div>

                                <!-- Vessel Material -->
                                <div>
                                    <x-input-label for="sg_mat" :value="__('Vessel Material')" />
                                    <x-text-input id="sg_mat" name="standard_gauge_spec[vessel_material]" type="text"
                                        class="mt-1 block w-full text-sm"
                                        value="{{ old('standard_gauge_spec.vessel_material', 'Stainless Steel 304 / 316') }}"
                                        placeholder="Stainless Steel 316" />
                                </div>

                                <!-- Base Reference Temperature -->
                                <div>
                                    <x-input-label for="sg_base_temp" :value="__('Base Reference Temperature (°C)')" />
                                    <x-text-input id="sg_base_temp" name="standard_gauge_spec[base_reference_temperature]" type="number" step="0.1"
                                        class="mt-1 block w-full font-mono text-sm"
                                        value="{{ old('standard_gauge_spec.base_reference_temperature', 15.0) }}"
                                        placeholder="15.0" />
                                </div>
                            </div>
                        </div>

                        <!-- Submission & Actions -->
                        <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-5">
                            <a href="{{ route('metrology.instruments.create') }}">
                                <x-secondary-button type="button" class="flex items-center gap-1.5 text-xs">
                                    <i class="fas fa-arrow-right rtl:rotate-180"></i>
                                    <span>{{ __('Back to Type Selection') }}</span>
                                </x-secondary-button>
                            </a>

                            <div class="flex items-center gap-3">
                                <x-primary-button type="submit" class="flex items-center gap-2">
                                    <i class="fas fa-plus"></i>
                                    <span>{{ __('Create Measuring Instrument') }}</span>
                                </x-primary-button>
                            </div>
                        </div>
                    </form>
                </main>
            </div>
        </div>
    </div>
</x-app-layout>
