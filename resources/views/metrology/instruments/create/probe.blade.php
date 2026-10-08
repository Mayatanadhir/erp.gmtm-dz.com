<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('metrology.instruments.create') }}" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition" title="{{ __('Back to Type Selection') }}">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/70 flex items-center justify-center shrink-0">
                    <i class="fas fa-thermometer-half text-lg"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ __('Register New Temperature Probe (RTD / Pt100)') }}
                        </h2>
                        <x-badge variant="success" size="sm">IEC 60751 / Callendar-Van Dusen</x-badge>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Industrial RTD Pt100 and thermocouple temperature sensing elements with Callendar-Van Dusen coefficients') }}
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
            probeTechnology: '{{ old('technology', 'RTD_PT100_4W') }}',
            fluidType: '{{ old('fluid_type', 'liquid') }}',
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
                            'type' => 'probe',
                            'typeLabel' => __('Probe (RTD)'),
                            'sites' => $sites
                        ])

                        <input type="hidden" name="process_variable" value="temperature">
                        <input type="hidden" name="measurement_type" value="RTD_PT100">

                        <!-- Section 2: Sensor Technology & Specifications -->
                        <div class="p-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm space-y-5">
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3.5">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="fas fa-microchip text-brand-600 dark:text-brand-400"></i>
                                    <span>{{ __('Sensor Technology & Wiring Classification') }}</span>
                                </h3>
                                <span class="text-xs font-mono text-gray-500">IEC 60751 / ASTM E 1137</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <!-- Sensor Type -->
                                <div>
                                    <x-input-label for="technology" :value="__('Sensor Technology / Architecture')" :required="true" />
                                    <select id="technology" name="technology" x-model="probeTechnology" required
                                        class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-brand-600">
                                        <option value="RTD_PT100_4W">RTD Pt100 (4-Wire Precision)</option>
                                        <option value="RTD_PT100_3W">RTD Pt100 (3-Wire)</option>
                                        <option value="RTD_PT100_2W">RTD Pt100 (2-Wire)</option>
                                        <option value="Thermocouple_K">Thermocouple Type K (Chromel / Alumel)</option>
                                        <option value="Thermocouple_J">Thermocouple Type J (Iron / Constantan)</option>
                                        <option value="Thermocouple_T">Thermocouple Type T (Copper / Constantan)</option>
                                    </select>
                                </div>

                                <!-- Fluid Type -->
                                <div>
                                    <x-input-label for="fluid_type" :value="__('Fluid Type')" />
                                    <select id="fluid_type" name="fluid_type" x-model="fluidType"
                                        class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-brand-600">
                                        @foreach(\App\Enums\FluidType::cases() as $ft)
                                            <option value="{{ $ft->value }}" @selected(old('fluid_type', 'liquid') === $ft->value)>
                                                {{ $ft->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Sensor Class / Tolerance -->
                                <div>
                                    <x-input-label for="tolerance_class" :value="__('Tolerance / Class')" />
                                    <select id="tolerance_class" name="measurement_type"
                                        class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-brand-600">
                                        <option value="Class_A">Class A (±0.15 + 0.002·|t| °C)</option>
                                        <option value="Class_B">Class B (±0.30 + 0.005·|t| °C)</option>
                                        <option value="Class_1_3_DIN">1/3 DIN (±0.05 + 0.001·|t| °C)</option>
                                        <option value="Class_1_10_DIN">1/10 DIN Ultra-Precision</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Physical Quantities & Calibration Ranges -->
                        <div class="p-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm space-y-6">
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3.5">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="fas fa-sliders-h text-brand-600 dark:text-brand-400"></i>
                                    <span>{{ __('Physical Quantities, Calibration Ranges & Precision') }}</span>
                                </h3>
                                <span class="text-xs text-gray-500 font-mono">°C / Ω (Resistance)</span>
                            </div>

                            @if($measurementGrandeurs->count() > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach($measurementGrandeurs->where('symbol', '°C')->concat($measurementGrandeurs->where('symbol', 'Ω')) as $g)
                                        <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 space-y-3"
                                             x-data="{ active: {{ old("params.{$g->id}.selected") ? 'true' : ($g->symbol === '°C' ? 'true' : 'false') }} }">
                                            <div class="flex items-center justify-between">
                                                <label class="flex items-center gap-2 cursor-pointer font-bold text-xs text-gray-900 dark:text-white">
                                                    <input type="checkbox" name="params[{{ $g->id }}][selected]" value="1"
                                                           x-model="active"
                                                           class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                                    <span>{{ $g->name }}</span>
                                                    <span class="font-mono text-gray-500 text-[11px]">({{ $g->symbol }})</span>
                                                </label>
                                                <span x-show="active" class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-1.5 py-0.5 rounded">
                                                    {{ __('Active') }}
                                                </span>
                                            </div>

                                            <div class="grid grid-cols-4 gap-2 text-xs" x-show="active" x-transition>
                                                <div>
                                                    <label class="text-[10px] text-gray-500 dark:text-gray-400">{{ __('Min') }}</label>
                                                    <input type="number" step="any" name="params[{{ $g->id }}][min]"
                                                        value="{{ old("params.{$g->id}.min", $g->symbol === '°C' ? '-50' : '0') }}"
                                                        class="mt-0.5 block w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-2">
                                                </div>
                                                <div>
                                                    <label class="text-[10px] text-gray-500 dark:text-gray-400">{{ __('Max') }}</label>
                                                    <input type="number" step="any" name="params[{{ $g->id }}][max]"
                                                        value="{{ old("params.{$g->id}.max", $g->symbol === '°C' ? '200' : '400') }}"
                                                        class="mt-0.5 block w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-2">
                                                </div>
                                                <div>
                                                    <label class="text-[10px] text-gray-500 dark:text-gray-400">{{ __('Accuracy') }}</label>
                                                    <input type="number" step="any" name="params[{{ $g->id }}][acc]"
                                                        value="{{ old("params.{$g->id}.acc", '0.05') }}"
                                                        class="mt-0.5 block w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-2">
                                                </div>
                                                <div>
                                                    <label class="text-[10px] text-gray-500 dark:text-gray-400">{{ __('Type') }}</label>
                                                    <select name="params[{{ $g->id }}][acc_type]"
                                                        class="mt-0.5 block w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-1">
                                                        <option value="abs">Abs</option>
                                                        <option value="%">%</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
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
