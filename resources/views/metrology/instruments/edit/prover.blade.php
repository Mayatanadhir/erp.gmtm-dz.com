<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('metrology.instruments.show', $instrument) }}" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition" title="{{ __('Back to Instrument Details') }}">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div class="w-10 h-10 rounded-xl bg-cyan-100 dark:bg-cyan-950/60 text-cyan-700 dark:text-cyan-400 border border-cyan-200 dark:border-cyan-800/70 flex items-center justify-center shrink-0">
                    <i class="fas fa-tachometer-alt text-lg"></i>
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
                            <i class="fas fa-tachometer-alt me-1"></i>
                            {{ __('Prover (Pipe / SVP)') }}
                        </x-badge>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Unidirectional, bidirectional pipe provers and small volume provers (SVP) per API MPMS Ch. 4') }}
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
        $proverSpec = $instrument->proverSpecification;
    @endphp

    <div class="py-8"
        x-data="{
            siteId: '{{ old('site_id', $instrument->site_id ?? '') }}',
            proverType: '{{ old('prover_spec.type', $proverSpec?->type?->value ?? 'bidirectional_pipe') }}',
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
                        <input type="hidden" name="measurement_type" value="Volumetric_Displacement">
                        <input type="hidden" name="technology" value="Conventional">

                        <!-- Section 1: Basic Identity -->
                        @include('metrology.instruments.edit.partials._identity', [
                            'instrument' => $instrument,
                            'type' => 'prover',
                            'typeLabel' => __('Prover (Pipe / SVP)'),
                            'sites' => $sites
                        ])

                        <!-- Section 2: Fixed Prover Banner -->
                        <div class="p-5 rounded-2xl border border-teal-200 dark:border-teal-800/70 bg-teal-50/60 dark:bg-teal-950/30 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-3.5 text-teal-900 dark:text-teal-200">
                                <div class="w-10 h-10 rounded-xl bg-teal-100 dark:bg-teal-900/60 flex items-center justify-center shrink-0 text-teal-600">
                                    <i class="fas fa-tachometer-alt text-lg"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-sm">{{ __('Dynamic Displacement Meter Prover') }}</div>
                                    <div class="text-xs text-teal-700 dark:text-teal-300 mt-0.5">
                                        {{ __('Process Variable: Volume') }} • {{ __('Fluid: Liquid Hydrocarbon') }} • {{ __('Measurement: Volumetric Displacement') }}
                                    </div>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-teal-100 dark:bg-teal-900/50 text-teal-800 dark:text-teal-300 border border-teal-300 dark:border-teal-700">
                                <i class="fas fa-lock text-xs"></i> API MPMS Ch. 4 / OAM
                            </span>
                        </div>

                        <!-- Section 3: Prover Metrological & Mechanical Specs -->
                        <div class="p-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm space-y-5">
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3.5">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="fas fa-tachometer-alt text-teal-600 dark:text-teal-400"></i>
                                    <span>{{ __('Prover Metrological Specifications & Secondary Classification') }}</span>
                                </h3>
                                <x-badge variant="info" size="sm">API MPMS Ch. 4 / OAM</x-badge>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
                                <!-- Prover Type -->
                                <div>
                                    <x-input-label for="prover_type" :value="__('Prover Secondary Classification')" :required="true" />
                                    <select id="prover_type" name="prover_spec[type]" x-model="proverType"
                                        class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-brand-600">
                                        @foreach(\App\Enums\ProverType::cases() as $pt)
                                            <option value="{{ $pt->value }}" @selected(old('prover_spec.type', $proverSpec?->type?->value ?? 'bidirectional_pipe') === $pt->value)>
                                                {{ $pt->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('prover_spec.type')" class="mt-1" />
                                </div>

                                <!-- Inner Diameter ID (mm) -->
                                <div>
                                    <x-input-label for="pr_inner_diam" :value="__('Internal Diameter (ID) mm')" :required="true" />
                                    <x-text-input id="pr_inner_diam" name="prover_spec[inner_diameter]" type="number" step="0.0001"
                                        class="mt-1 block w-full font-mono text-sm"
                                        value="{{ old('prover_spec.inner_diameter', $proverSpec?->inner_diameter) }}"
                                        placeholder="e.g. 406.4000" required />
                                    <x-input-error :messages="$errors->get('prover_spec.inner_diameter')" class="mt-1" />
                                </div>

                                <!-- Wall Thickness WT (mm) -->
                                <div>
                                    <x-input-label for="pr_wall_thick" :value="__('Wall Thickness (WT) mm')" :required="true" />
                                    <x-text-input id="pr_wall_thick" name="prover_spec[wall_thickness]" type="number" step="0.0001"
                                        class="mt-1 block w-full font-mono text-sm"
                                        value="{{ old('prover_spec.wall_thickness', $proverSpec?->wall_thickness) }}"
                                        placeholder="e.g. 12.7000" required />
                                    <x-input-error :messages="$errors->get('prover_spec.wall_thickness')" class="mt-1" />
                                </div>

                                <!-- Base Volume BPV (L) -->
                                <div>
                                    <x-input-label for="pr_bpv" :value="__('Nominal Base Volume (Previous BPV) Liters')" />
                                    <x-text-input id="pr_bpv" name="prover_spec[nominal_base_volume]" type="number" step="0.00001"
                                        class="mt-1 block w-full font-mono text-sm"
                                        value="{{ old('prover_spec.nominal_base_volume', $proverSpec?->nominal_base_volume) }}"
                                        placeholder="e.g. 5000.00000" />
                                    <x-input-error :messages="$errors->get('prover_spec.nominal_base_volume')" class="mt-1" />
                                </div>

                                <!-- Thermal Expansion Gc (1/°C) -->
                                <div>
                                    <x-input-label for="pr_gc" :value="__('Thermal Expansion Coef Gc (1/°C)')" />
                                    <x-text-input id="pr_gc" name="prover_spec[cubical_expansion_coef]" type="number" step="0.00000001"
                                        class="mt-1 block w-full font-mono text-sm"
                                        value="{{ old('prover_spec.cubical_expansion_coef', $proverSpec?->cubical_expansion_coef ?? \App\Constants\MetrologyConstants::G_C_MILD_STEEL) }}"
                                        placeholder="0.00003300" />
                                    <x-input-error :messages="$errors->get('prover_spec.cubical_expansion_coef')" class="mt-1" />
                                </div>

                                <!-- Elasticity Modulus E (bar) -->
                                <div>
                                    <x-input-label for="pr_e" :value="__('Elasticity Modulus E (bar)')" />
                                    <x-text-input id="pr_e" name="prover_spec[elasticity_modulus]" type="number" step="0.0001"
                                        class="mt-1 block w-full font-mono text-sm"
                                        value="{{ old('prover_spec.elasticity_modulus', $proverSpec?->elasticity_modulus ?? \App\Constants\MetrologyConstants::E_MODULUS_STEEL_BAR) }}"
                                        placeholder="2068427.0000" />
                                    <x-input-error :messages="$errors->get('prover_spec.elasticity_modulus')" class="mt-1" />
                                </div>

                                <!-- Compact SVP: Area Expansion Ga -->
                                <div x-show="proverType === 'compact_svp'" x-transition>
                                    <x-input-label for="pr_ga" :value="__('Area Expansion Coef Ga (1/°C) [SVP]')" />
                                    <x-text-input id="pr_ga" name="prover_spec[area_expansion_coef]" type="number" step="0.00000001"
                                        class="mt-1 block w-full font-mono text-sm"
                                        value="{{ old('prover_spec.area_expansion_coef', $proverSpec?->area_expansion_coef ?? \App\Constants\MetrologyConstants::G_A_SVP_CYLINDER) }}"
                                        placeholder="0.00003400" />
                                </div>

                                <!-- Compact SVP: Linear Expansion Gl -->
                                <div x-show="proverType === 'compact_svp'" x-transition>
                                    <x-input-label for="pr_gl" :value="__('Linear Expansion Coef Gl (1/°C) [SVP]')" />
                                    <x-text-input id="pr_gl" name="prover_spec[linear_expansion_coef]" type="number" step="0.00000001"
                                        class="mt-1 block w-full font-mono text-sm"
                                        value="{{ old('prover_spec.linear_expansion_coef', $proverSpec?->linear_expansion_coef ?? \App\Constants\MetrologyConstants::G_L_SVP_SHAFT) }}"
                                        placeholder="0.00000120" />
                                </div>

                                <!-- Material Description -->
                                <div>
                                    <x-input-label for="pr_mat" :value="__('Prover Wall Material')" />
                                    <x-text-input id="pr_mat" name="prover_spec[material]" type="text"
                                        class="mt-1 block w-full text-sm"
                                        value="{{ old('prover_spec.material', $proverSpec?->material ?? 'Carbon Steel / Mild Steel') }}"
                                        placeholder="Mild Steel / Stainless 316" />
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
