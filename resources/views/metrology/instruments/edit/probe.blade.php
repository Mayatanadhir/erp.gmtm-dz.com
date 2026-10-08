<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('metrology.instruments.show', $instrument) }}" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition" title="{{ __('Back to Instrument Details') }}">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/70 flex items-center justify-center shrink-0">
                    <i class="fas fa-thermometer-half text-lg"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight font-mono">
                            {{ __('Edit') }}: {{ $instrument->tag_number }}
                        </h2>
                        <x-badge :variant="$instrument->status->badgeVariant()" :dot="true">
                            {{ $instrument->status->label() }}
                        </x-badge>
                        <x-badge variant="success">
                            <i class="fas fa-thermometer-half me-1"></i>
                            {{ __('Probe (RTD)') }}
                        </x-badge>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('RTD Pt100 and thermocouple temperature sensors with Callendar-Van Dusen curve coefficients') }}
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

    <div class="py-8"
        x-data="{
            siteId: '{{ old('site_id', $instrument->site_id ?? '') }}',
            fluidType: '{{ old('fluid_type', $instrument->fluid_type?->value ?? '') }}',
            technology: '{{ old('technology', $instrument->technology ?? 'Conventional') }}',
            measurementType: '{{ old('measurement_type', $instrument->measurement_type ?? 'RTD_PT100') }}',
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
                        <input type="hidden" name="process_variable" value="temperature">

                        <!-- Section 1: Basic Identity -->
                        @include('metrology.instruments.edit.partials._identity', [
                            'instrument' => $instrument,
                            'type' => 'probe',
                            'typeLabel' => __('Probe (RTD)'),
                            'sites' => $sites
                        ])

                        <!-- Section 2: Sensor Technology & CVD Parameters -->
                        <div class="p-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm space-y-5">
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3.5">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="fas fa-microchip text-emerald-600 dark:text-emerald-400"></i>
                                    <span>{{ __('Thermal Sensing Technology & Callendar-Van Dusen Dynamics') }}</span>
                                </h3>
                                <x-badge variant="success" size="sm">IEC 60751 / ASTM E1137</x-badge>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <!-- Sensor Type -->
                                <div>
                                    <x-input-label for="measurement_type" :value="__('Probe Sensing Element')" :required="true" />
                                    <select id="measurement_type" name="measurement_type" x-model="measurementType" required
                                        class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-brand-600">
                                        <option value="RTD_PT100">RTD Pt100 (100 Ω @ 0°C)</option>
                                        <option value="RTD_PT500">RTD Pt500 (500 Ω @ 0°C)</option>
                                        <option value="RTD_PT1000">RTD Pt1000 (1000 Ω @ 0°C)</option>
                                        <option value="Thermocouple">Thermocouple (Type K / J / T / N)</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('measurement_type')" class="mt-1" />
                                </div>

                                <!-- Wiring Configuration -->
                                <div>
                                    <x-input-label for="technology" :value="__('Wiring Configuration')" />
                                    <select id="technology" name="technology" x-model="technology"
                                        class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-brand-600">
                                        <option value="4-wire">{{ __('4-Wire (High Precision RTD)') }}</option>
                                        <option value="3-wire">{{ __('3-Wire (Industrial Standard)') }}</option>
                                        <option value="2-wire">{{ __('2-Wire (Direct Connection)') }}</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('technology')" class="mt-1" />
                                </div>

                                <!-- Process Fluid Type -->
                                <div>
                                    <x-input-label for="fluid_type" :value="__('Process Fluid')" />
                                    <select id="fluid_type" name="fluid_type" x-model="fluidType"
                                        class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-brand-600">
                                        <option value="">-- {{ __('Select Fluid') }} --</option>
                                        @foreach(\App\Enums\FluidType::cases() as $ft)
                                            <option value="{{ $ft->value }}" @selected(old('fluid_type', $instrument->fluid_type?->value) === $ft->value)>
                                                {{ $ft->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('fluid_type')" class="mt-1" />
                                </div>

                                <!-- Nominal Resistance R0 -->
                                <div>
                                    <x-input-label :value="__('Nominal Resistance R₀ (Ω)')" />
                                    <x-text-input type="text" class="mt-1 block w-full text-sm bg-gray-50 dark:bg-gray-900/50" value="100.000 Ω" readonly />
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Physical Parameters (Grandeurs) -->
                        @if($measurementGrandeurs->count() > 0)
                            <div class="p-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm space-y-6">
                                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3.5">
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <i class="fas fa-wave-square text-emerald-600 dark:text-emerald-400"></i>
                                        <span>{{ __('Physical Quantities, Calibration Ranges & Precision') }}</span>
                                    </h3>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('Activate quantities supported by this instrument') }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach($measurementGrandeurs as $g)
                                        @php
                                            $spec = $mappedSpecs[$g->id] ?? null;
                                            $isSelected = old("params.{$g->id}.selected", $spec ? true : ($loop->first));
                                        @endphp
                                        <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 space-y-3"
                                             x-data="{ active: {{ $isSelected ? 'true' : 'false' }} }">
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
                                                        value="{{ old("params.{$g->id}.min", $spec->min_value ?? '-50') }}"
                                                        placeholder="-50"
                                                        class="mt-0.5 block w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-2">
                                                </div>
                                                <div>
                                                    <label class="text-[10px] text-gray-500 dark:text-gray-400">{{ __('Max') }}</label>
                                                    <input type="number" step="any" name="params[{{ $g->id }}][max]"
                                                        value="{{ old("params.{$g->id}.max", $spec->max_value ?? '250') }}"
                                                        placeholder="250"
                                                        class="mt-0.5 block w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-2">
                                                </div>
                                                <div>
                                                    <label class="text-[10px] text-gray-500 dark:text-gray-400">{{ __('EMT / Acc.') }}</label>
                                                    <input type="number" step="any" name="params[{{ $g->id }}][acc]"
                                                        value="{{ old("params.{$g->id}.acc", $spec->accuracy_value ?? '0.05') }}"
                                                        placeholder="0.05"
                                                        class="mt-0.5 block w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-2">
                                                </div>
                                                <div>
                                                    <label class="text-[10px] text-gray-500 dark:text-gray-400">{{ __('Type') }}</label>
                                                    <select name="params[{{ $g->id }}][acc_type]"
                                                        class="mt-0.5 block w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-1">
                                                        <option value="%" @selected(old("params.{$g->id}.acc_type", $spec->accuracy_type->value ?? $spec->accuracy_type ?? '%') === '%')>%</option>
                                                        <option value="abs" @selected(old("params.{$g->id}.acc_type", $spec->accuracy_type->value ?? $spec->accuracy_type ?? '') === 'abs')>Abs</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

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
