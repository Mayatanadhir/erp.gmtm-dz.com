<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('metrology.instruments.show', $instrument) }}" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition" title="{{ __('Back to Instrument Details') }}">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/70 flex items-center justify-center shrink-0">
                    <i class="fas fa-satellite-dish text-lg"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight font-mono">
                            {{ __('Edit') }}: {{ $instrument->tag_number }}
                        </h2>
                        <x-badge :variant="$instrument->status->badgeVariant()" :dot="true">
                            {{ $instrument->status->label() }}
                        </x-badge>
                        <x-badge variant="info">
                            <i class="fas fa-satellite-dish me-1"></i>
                            {{ __('Transmitter') }}
                        </x-badge>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Pressure, Differential Pressure, Flow, and Level measurement transmitters with sensing & loop capabilities') }}
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
            processVariable: '{{ old('process_variable', $instrument->process_variable?->value ?? 'pressure') }}',
            measurementType: '{{ old('measurement_type', $instrument->measurement_type ?? 'Relative') }}',
            imagePreview: null,
            removeImage: false,

            measurementOptions: {
                pressure: [
                    { value: 'Relative', label: '{{ __('Pression Relative / Gauge') }}' },
                    { value: 'Absolute', label: '{{ __('Pression Absolue') }}' },
                    { value: 'Differential', label: '{{ __('Pression Différentielle (ΔP)') }}' }
                ],
                flow: [
                    { value: 'Mass', label: '{{ __('Mass Flow (Massique)') }}' },
                    { value: 'Volumetric', label: '{{ __('Volumetric Flow (Volumique)') }}' }
                ],
                level: [
                    { value: 'Hydrostatic', label: '{{ __('Hydrostatic Level (Hydrostatique)') }}' },
                    { value: 'Radar', label: '{{ __('Radar Level') }}' },
                    { value: 'Ultrasonic', label: '{{ __('Ultrasonic Level (Ultrasonique)') }}' }
                ],
                temperature: [
                    { value: 'RTD_PT100', label: '{{ __('RTD / Pt100 (Résistance)') }}' },
                    { value: 'Thermocouple', label: '{{ __('Thermocouple') }}' }
                ]
            },

            get currentMeasurementTypeList() {
                return this.measurementOptions[this.processVariable] || [];
            }
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

                        <!-- Section 1: Basic Identity -->
                        @include('metrology.instruments.edit.partials._identity', [
                            'instrument' => $instrument,
                            'type' => 'transmitter',
                            'typeLabel' => __('Transmitter'),
                            'sites' => $sites
                        ])

                        <!-- Section 2: Process & Measurement Dynamics -->
                        <div class="p-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm space-y-5">
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3.5">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="fas fa-sliders-h text-brand-600 dark:text-brand-400"></i>
                                    <span>{{ __('Process Variable & Technology Classification') }}</span>
                                </h3>
                                <x-badge variant="info" size="sm">4-20mA / HART</x-badge>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <!-- Process Variable -->
                                <div>
                                    <x-input-label for="process_variable" :value="__('Process Variable')" :required="true" />
                                    <select id="process_variable" name="process_variable" x-model="processVariable" required
                                        class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-brand-600">
                                        <option value="pressure">{{ __('Pressure') }}</option>
                                        <option value="temperature">{{ __('Temperature') }}</option>
                                        <option value="flow">{{ __('Flow') }}</option>
                                        <option value="level">{{ __('Level') }}</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('process_variable')" class="mt-1" />
                                </div>

                                <!-- Dynamic Measurement Type -->
                                <div>
                                    <x-input-label for="measurement_type" :value="__('Measurement Type / Principle')" />
                                    <select id="measurement_type" name="measurement_type" x-model="measurementType"
                                        class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-brand-600">
                                        <template x-for="opt in currentMeasurementTypeList" :key="opt.value">
                                            <option :value="opt.value" x-text="opt.label" :selected="opt.value === measurementType"></option>
                                        </template>
                                    </select>
                                    <x-input-error :messages="$errors->get('measurement_type')" class="mt-1" />
                                </div>

                                <!-- Fluid Type -->
                                <div>
                                    <x-input-label for="fluid_type" :value="__('Fluid Type')" />
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

                                <!-- Sensor Architecture -->
                                <div>
                                    <x-input-label for="technology" :value="__('Sensor Technology / Architecture')" />
                                    <select id="technology" name="technology" x-model="technology"
                                        class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-brand-600">
                                        <option value="Conventional">{{ __('Conventional (Analog / 4-20mA)') }}</option>
                                        <option value="SMART">{{ __('SMART (HART / Fieldbus / Digital)') }}</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('technology')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Physical Grandeurs (Measurement & Source) -->
                        <div class="p-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm space-y-6">
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3.5">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="fas fa-wave-square text-brand-600 dark:text-brand-400"></i>
                                    <span>{{ __('Physical Quantities, Calibration Ranges & Precision') }}</span>
                                </h3>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('Activate quantities supported by this instrument') }}
                                </span>
                            </div>

                            <!-- Measurement Capabilities -->
                            @if($measurementGrandeurs->count() > 0)
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                                            <i class="fas fa-tachometer-alt"></i>
                                            <span>{{ __('Measurement / Sensor Input Capabilities') }}</span>
                                        </h4>
                                        <span class="text-[11px] px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300 font-medium">Input / Sensor</span>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @foreach($measurementGrandeurs as $g)
                                            @php
                                                $spec = $mappedSpecs[$g->id] ?? null;
                                                $isSelected = old("params.{$g->id}.selected", $spec ? true : false);
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
                                                            value="{{ old("params.{$g->id}.min", $spec->min_value ?? '') }}"
                                                            placeholder="0"
                                                            class="mt-0.5 block w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-2">
                                                    </div>
                                                    <div>
                                                        <label class="text-[10px] text-gray-500 dark:text-gray-400">{{ __('Max') }}</label>
                                                        <input type="number" step="any" name="params[{{ $g->id }}][max]"
                                                            value="{{ old("params.{$g->id}.max", $spec->max_value ?? '') }}"
                                                            placeholder="100"
                                                            class="mt-0.5 block w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-2">
                                                    </div>
                                                    <div>
                                                        <label class="text-[10px] text-gray-500 dark:text-gray-400">{{ __('EMT / Acc.') }}</label>
                                                        <input type="number" step="any" name="params[{{ $g->id }}][acc]"
                                                            value="{{ old("params.{$g->id}.acc", $spec->accuracy_value ?? '') }}"
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

                            <!-- Source / Generation Capabilities -->
                            @if($sourceGrandeurs->count() > 0)
                                <div class="space-y-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                                            <i class="fas fa-broadcast-tower"></i>
                                            <span>{{ __('Source / Generation Capabilities') }}</span>
                                        </h4>
                                        <span class="text-[11px] px-2 py-0.5 rounded bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 font-medium">Source / Generation</span>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @foreach($sourceGrandeurs as $g)
                                            @php
                                                $spec = $mappedSpecs[$g->id] ?? null;
                                                $isSelected = old("params.{$g->id}.selected", $spec ? true : false);
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
                                                    <span x-show="active" class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 px-1.5 py-0.5 rounded">
                                                        {{ __('Active') }}
                                                    </span>
                                                </div>

                                                <div class="grid grid-cols-4 gap-2 text-xs" x-show="active" x-transition>
                                                    <div>
                                                        <label class="text-[10px] text-gray-500 dark:text-gray-400">{{ __('Min') }}</label>
                                                        <input type="number" step="any" name="params[{{ $g->id }}][min]"
                                                            value="{{ old("params.{$g->id}.min", $spec->min_value ?? '') }}"
                                                            placeholder="0"
                                                            class="mt-0.5 block w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-2">
                                                    </div>
                                                    <div>
                                                        <label class="text-[10px] text-gray-500 dark:text-gray-400">{{ __('Max') }}</label>
                                                        <input type="number" step="any" name="params[{{ $g->id }}][max]"
                                                            value="{{ old("params.{$g->id}.max", $spec->max_value ?? '') }}"
                                                            placeholder="20"
                                                            class="mt-0.5 block w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-2">
                                                    </div>
                                                    <div>
                                                        <label class="text-[10px] text-gray-500 dark:text-gray-400">{{ __('EMT / Acc.') }}</label>
                                                        <input type="number" step="any" name="params[{{ $g->id }}][acc]"
                                                            value="{{ old("params.{$g->id}.acc", $spec->accuracy_value ?? '') }}"
                                                            placeholder="0.02"
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
                        </div>

                        <!-- Submission & Actions -->
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
