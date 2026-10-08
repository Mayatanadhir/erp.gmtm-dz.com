<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('metrology.instruments.create') }}" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition" title="{{ __('Back to Type Selection') }}">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-400 border border-purple-200 dark:border-purple-800/70 flex items-center justify-center shrink-0">
                    <i class="fas fa-vial text-lg"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ __('Register New Gas Chromatograph (GC)') }}
                        </h2>
                        <x-badge variant="neutral" size="sm">ISO 6974 / ASTM D 1945</x-badge>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Online natural gas analytical composition analyzer with TCD/FID thermal conductivity detectors') }}
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
            detectorTechnology: '{{ old('technology', 'TCD') }}',
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
                            'type' => 'chromatograph',
                            'typeLabel' => __('Gas Chromatograph'),
                            'sites' => $sites
                        ])

                        <!-- Section 2: Fixed Gas Chromatography Metrological Standards Banner -->
                        <div class="p-4 rounded-xl border border-purple-200 dark:border-purple-800/70 bg-purple-50/60 dark:bg-purple-950/30 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-3 text-purple-900 dark:text-purple-200">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/60 flex items-center justify-center shrink-0 text-purple-600">
                                    <i class="fas fa-vial"></i>
                                </div>
                                <div>
                                    <div class="font-bold">{{ __('Fixed Gas Chromatography Parameters') }}</div>
                                    <div class="text-[11px] text-purple-700 dark:text-purple-300">
                                        {{ __('Process Variable: Quality / Composition') }} • {{ __('Fluid: Gas (Phase Gazeuse)') }} • {{ __('Measurement: Gas Chromatography (GC)') }}
                                    </div>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300 border border-purple-300 dark:border-purple-700">
                                <i class="fas fa-lock"></i> ISO 6974 / ASTM D 1945
                            </span>
                        </div>

                        <!-- Section 3: Detector Architecture & Hardware Configuration -->
                        <div class="p-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm space-y-5">
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3.5">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="fas fa-microchip text-brand-600 dark:text-brand-400"></i>
                                    <span>{{ __('Gas Chromatograph Architecture & Detector Configuration') }}</span>
                                </h3>
                                <span class="text-xs font-mono text-gray-500">Dual-Bridge Sensor</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <x-input-label for="technology" :value="__('Chromatography Detectors')" :required="true" />
                                    <select id="technology" name="technology" x-model="detectorTechnology" required
                                        class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-brand-600">
                                        <option value="TCD">TCD (Thermal Conductivity Detector)</option>
                                        <option value="FID">FID (Flame Ionization Detector)</option>
                                        <option value="TCD_FID">Dual TCD / FID Detectors</option>
                                    </select>
                                </div>

                                <div>
                                    <x-input-label for="carrier_gas" :value="__('Carrier Gas Type')" />
                                    <select id="carrier_gas" name="carrier_gas"
                                        class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-brand-600">
                                        <option value="Helium">Helium (He - 99.999% Purity)</option>
                                        <option value="Hydrogen">Hydrogen (H2 - Ultra Pure)</option>
                                        <option value="Nitrogen">Nitrogen (N2 - Carrier)</option>
                                    </select>
                                </div>

                                <div>
                                    <x-input-label for="sampling_streams" :value="__('Sampling Streams Count')" />
                                    <input type="number" id="sampling_streams" name="sampling_streams" min="1" max="12" value="1"
                                        class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-brand-600" />
                                </div>
                            </div>
                        </div>

                        <!-- Section 4: Chromatograph Dual Architecture Cards -->
                        <div class="p-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm space-y-5">
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3.5">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="fas fa-layer-group text-purple-600 dark:text-purple-400"></i>
                                    <span>{{ __('Chromatograph Dual Architecture (Analytical Reader & Signal Source)') }}</span>
                                </h3>
                                <x-badge variant="info" size="sm">ISO 6974 / ASTM D 1945</x-badge>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <!-- Mode 1: Analytical Reader -->
                                <div class="p-5 rounded-xl border border-purple-200 dark:border-purple-800/60 bg-purple-50/40 dark:bg-purple-950/20 space-y-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300 flex items-center justify-center font-bold">
                                            <i class="fas fa-flask"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-purple-950 dark:text-purple-200">{{ __('Analytical Reader Mode') }}</h4>
                                            <p class="text-[11px] text-purple-700 dark:text-purple-400">{{ __('Primary Role: Analytical Sensing') }}</p>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                                        {{ __('Receives gas samples, separates 11 components in chromatographic columns, and senses microvolt variations across TCD/FID thermal conductivity detectors.') }}
                                    </p>
                                    <ul class="text-xs text-gray-700 dark:text-gray-300 space-y-1.5 pt-1 border-t border-purple-200/60 dark:border-purple-800/40">
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check-circle text-purple-600 text-[11px]"></i>
                                            <span><strong>{{ __('Measured Quantity') }}:</strong> {{ __('11 Molar Fractions (% mol/mol)') }}</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check-circle text-purple-600 text-[11px]"></i>
                                            <span><strong>{{ __('Calibration Standard') }}:</strong> {{ __('Certified CRM Gas Cylinder') }}</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check-circle text-purple-600 text-[11px]"></i>
                                            <span><strong>{{ __('Calculations') }}:</strong> {{ __('Heating Values (PCS/PCI), Density, Z factor') }}</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Mode 2: Signal & Data Source -->
                                <div class="p-5 rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/40 dark:bg-emerald-950/20 space-y-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 flex items-center justify-center font-bold">
                                            <i class="fas fa-broadcast-tower"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-emerald-950 dark:text-emerald-200">{{ __('Signal & Data Source Mode') }}</h4>
                                            <p class="text-[11px] text-emerald-700 dark:text-emerald-400">{{ __('Secondary Role: Loop & Protocol Feeding') }}</p>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                                        {{ __('Acts as an active electrical and digital transmitter feeding real-time calculated analytical gas composition to supervisory Flow Computers.') }}
                                    </p>
                                    <ul class="text-xs text-gray-700 dark:text-gray-300 space-y-1.5 pt-1 border-t border-emerald-200/60 dark:border-emerald-800/40">
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check-circle text-emerald-600 text-[11px]"></i>
                                            <span><strong>{{ __('Analog Outputs') }}:</strong> {{ __('Active 4–20 mA Current Loops') }}</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check-circle text-emerald-600 text-[11px]"></i>
                                            <span><strong>{{ __('Digital Interface') }}:</strong> {{ __('Modbus RTU (RS-485) / Modbus TCP') }}</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check-circle text-emerald-600 text-[11px]"></i>
                                            <span><strong>{{ __('Receiving Station') }}:</strong> {{ __('Flow Computers (AGA 8 / ISO 6976)') }}</span>
                                        </li>
                                    </ul>
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
