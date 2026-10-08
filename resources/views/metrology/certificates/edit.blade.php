<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/70 flex items-center justify-center shrink-0">
                    <i class="fas fa-edit text-lg"></i>
                </div>
                <div>
                    <h2 class="font-bold text-xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Edit Calibration Certificate') }} &bull; {{ $certificate->reference ?: ('CERT-#' . $certificate->id) }}
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Update certificate parameters and measurement points prior to official approval') }}
                    </p>
                </div>
            </div>
            <a href="{{ route('metrology.calibration-certificates.show', $certificate) }}" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-lg transition">
                <i class="fas fa-times mr-1"></i> {{ __('Cancel') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        equipmentsData: {{ Js::from($equipmentsData ?? []) }},
        selectedEquipmentId: '{{ old('equipment_id', $certificate->equipment_id) }}',
        activeSpecId: null,
        points: {{ Js::from(old('points', $certificate->calibrationPoints->map(fn($p) => [
            'nominal_value' => $p->nominal_value,
            'correction' => $p->correction,
            'uncertainty' => $p->uncertainty,
            'equipment_specification_id' => $p->equipment_specification_id,
        ])->values())) }},
        calDate: '{{ old('calibration_date', $certificate->calibration_date ? $certificate->calibration_date->format('Y-m-d') : '') }}',
        validityMonths: {{ old('validity_period_months', $certificate->validity_period_months ?: 12) }},
        expiryDate: '{{ old('expiry_date', $certificate->expiry_date ? $certificate->expiry_date->format('Y-m-d') : '') }}',

        aiState: {
            loading: false,
            step: 1,
            stepMessage: '',
            progressPercent: 0,
            fileName: null,
            extractionId: null,
            pollTimer: null,
            extractedData: null,
            errorMessage: null,
            previewModalOpen: false,
            historyModalOpen: false,
            historyLoading: false,
            historyList: [],
            appliedSuccess: false,
        },

        get currentSpecs() {
            if (!this.selectedEquipmentId || !this.equipmentsData[this.selectedEquipmentId]) {
                return [];
            }
            return this.equipmentsData[this.selectedEquipmentId].specifications || [];
        },

        get activeSpec() {
            if (!this.activeSpecId || this.activeSpecId === 'all') {
                return null;
            }
            return this.currentSpecs.find(s => String(s.id) === String(this.activeSpecId)) || null;
        },

        get measurementSpecs() {
            return this.currentSpecs.filter(s => s.type === 'measurement');
        },

        get sourceSpecs() {
            return this.currentSpecs.filter(s => s.type === 'source');
        },

        get activeUnit() {
            return this.activeSpec ? this.activeSpec.symbol : '';
        },

        get activeRangeText() {
            if (!this.activeSpec) return '';
            if (this.activeSpec.range_min !== null && this.activeSpec.range_max !== null) {
                return this.activeSpec.range_min + ' → ' + this.activeSpec.range_max + ' ' + (this.activeSpec.symbol || '');
            }
            return '';
        },

        get activeAccuracyText() {
            if (!this.activeSpec || this.activeSpec.accuracy_value === null) return '';
            return '±' + this.activeSpec.accuracy_value + ' ' + (this.activeSpec.accuracy_type || '%');
        },

        getPointsCountForSpec(specId) {
            return this.points.filter(p => String(p.equipment_specification_id) === String(specId)).length;
        },

        isRowVisible(pt) {
            if (this.currentSpecs.length === 0 || this.activeSpecId === 'all') {
                return true;
            }
            return String(pt.equipment_specification_id) === String(this.activeSpecId);
        },

        get visiblePointsCount() {
            if (this.currentSpecs.length === 0 || this.activeSpecId === 'all') {
                return this.points.length;
            }
            return this.points.filter(p => String(p.equipment_specification_id) === String(this.activeSpecId)).length;
        },

        onEquipmentChange() {
            const specs = this.currentSpecs;
            if (specs.length > 0) {
                this.activeSpecId = specs[0].id;
                this.points.forEach(p => {
                    if (!p.equipment_specification_id || !specs.some(s => String(s.id) === String(p.equipment_specification_id))) {
                        p.equipment_specification_id = specs[0].id;
                    }
                });
            } else {
                this.activeSpecId = null;
                this.points.forEach(p => {
                    p.equipment_specification_id = '';
                });
            }
        },

        addPoint(targetSpecId = null) {
            const specId = targetSpecId || (this.activeSpecId !== 'all' ? this.activeSpecId : (this.currentSpecs[0]?.id || ''));
            this.points.push({
                nominal_value: '',
                correction: '',
                uncertainty: '',
                equipment_specification_id: specId
            });
        },

        addPointsBatch(count = 5, targetSpecId = null) {
            for (let i = 0; i < count; i++) {
                this.addPoint(targetSpecId);
            }
        },

        removePoint(index) {
            if (this.points.length > 1) {
                this.points.splice(index, 1);
            }
        },

        clearCurrentSpecPoints() {
            if (this.activeSpecId === 'all' || this.currentSpecs.length === 0) {
                this.points = [{
                    nominal_value: '',
                    correction: '',
                    uncertainty: '',
                    equipment_specification_id: this.currentSpecs[0]?.id || ''
                }];
            } else {
                this.points = this.points.filter(p => String(p.equipment_specification_id) !== String(this.activeSpecId));
                this.addPoint(this.activeSpecId);
            }
        },

        updateExpiry() {
            if (!this.calDate) return;
            const d = new Date(this.calDate);
            d.setMonth(d.getMonth() + parseInt(this.validityMonths || 12));
            this.expiryDate = d.toISOString().split('T')[0];
        },

        init() {
            this.updateExpiry();
            if (this.selectedEquipmentId) {
                const specs = this.currentSpecs;
                if (specs.length > 0) {
                    const firstPtSpec = this.points.find(p => p.equipment_specification_id)?.equipment_specification_id;
                    this.activeSpecId = firstPtSpec || specs[0].id;
                }
            }
            if (this.points.length === 0) {
                this.addPoint();
            }
        }
    }">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-metrology.ai-extractor />

            <form method="POST" action="{{ route('metrology.calibration-certificates.update', $certificate) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Section 1: General & Equipment Context -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200/80 dark:border-gray-700/80 p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2 border-b border-gray-100 dark:border-gray-700 pb-3">
                        <i class="fas fa-info-circle text-brand-600"></i>
                        <span>{{ __('Certificate & Equipment Specification') }}</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Target Equipment / Standard') }} <span class="text-rose-500">*</span>
                            </label>
                            <select name="equipment_id" x-model="selectedEquipmentId" @change="onEquipmentChange()" required class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-500 focus:border-brand-500">
                                @foreach($equipments as $eq)
                                    <option value="{{ $eq->id }}" @selected(old('equipment_id', $certificate->equipment_id) == $eq->id)>
                                        {{ $eq->short_name ?: $eq->full_name }}{{ $eq->internal_code ? ' ('.$eq->internal_code.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('equipment_id') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Certificate Reference / Number') }}
                            </label>
                            <input type="text" name="reference" value="{{ old('reference', $certificate->reference) }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-500 focus:border-brand-500">
                            @error('reference') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Certificate Type') }}
                            </label>
                            <select name="certificate_type" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-500 focus:border-brand-500">
                                <option value="periodic" @selected($certificate->certificate_type === 'periodic')>{{ __('Periodic Calibration (Annual)') }}</option>
                                <option value="initial" @selected($certificate->certificate_type === 'initial')>{{ __('Initial Calibration') }}</option>
                                <option value="after_repair" @selected($certificate->certificate_type === 'after_repair')>{{ __('Recalibration After Maintenance') }}</option>
                                <option value="intermediate" @selected($certificate->certificate_type === 'intermediate')>{{ __('Intermediate Verification') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Accredited Laboratory') }}
                            </label>
                            <input type="text" name="laboratory_name" value="{{ old('laboratory_name', $certificate->laboratory_name) }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-500 focus:border-brand-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Calibration Date') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" name="calibration_date" x-model="calDate" @change="updateExpiry()" required class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-500 focus:border-brand-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Validity Period (Months)') }}
                            </label>
                            <input type="number" name="validity_period_months" x-model="validityMonths" @change="updateExpiry()" min="1" max="60" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-500 focus:border-brand-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Calculated Expiry Date') }}
                            </label>
                            <input type="date" name="expiry_date" x-model="expiryDate" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-500 focus:border-brand-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Service Cost / Price (DZD)') }}
                            </label>
                            <input type="number" name="price" step="0.01" min="0" value="{{ old('price', $certificate->price) }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-500 focus:border-brand-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Replace Certificate PDF (Optional)') }}
                            </label>
                            <input type="file" name="certificate_file" accept="application/pdf" class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-gray-700 dark:file:text-gray-300">
                            @if($certificate->certificate_path)
                                <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-1 flex items-center gap-1">
                                    <i class="fas fa-check-circle"></i>
                                    <span>{{ __('Current Document') }}: {{ $certificate->file_name ?: basename($certificate->certificate_path) }}</span>
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Section 2: Ambient Environmental Conditions -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200/80 dark:border-gray-700/80 p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2 border-b border-gray-100 dark:border-gray-700 pb-3">
                        <i class="fas fa-cloud-sun text-brand-600"></i>
                        <span>{{ __('Ambient Environmental Conditions & Remarks') }}</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Temperature (°C)') }}</label>
                            <input type="number" step="0.1" name="environmental_conditions[temperature_celsius]" value="{{ old('environmental_conditions.temperature_celsius', $certificate->environmental_conditions['temperature_celsius'] ?? '') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Relative Humidity (%)') }}</label>
                            <input type="number" step="0.1" min="0" max="100" name="environmental_conditions[humidity_percent]" value="{{ old('environmental_conditions.humidity_percent', $certificate->environmental_conditions['humidity_percent'] ?? '') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Atmospheric Pressure (hPa)') }}</label>
                            <input type="number" step="0.1" name="environmental_conditions[atmospheric_pressure_hpa]" value="{{ old('environmental_conditions.atmospheric_pressure_hpa', $certificate->environmental_conditions['atmospheric_pressure_hpa'] ?? '') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Metrologist Remarks') }}</label>
                        <textarea name="remarks" rows="2" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white">{{ old('remarks', $certificate->remarks) }}</textarea>
                    </div>
                </div>

                <!-- Section 3: Metrological Calibration Points Grid (Multi-Standard Aware) -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200/80 dark:border-gray-700/80 p-6 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-list-ol text-brand-600"></i>
                                <span>{{ __('Multi-Standard Capabilities & Calibration Points') }}</span>
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ __('Record calibrated nominal points, corrections, and expanded uncertainties per standard') }}
                            </p>
                        </div>

                        <!-- Global Points Summary Pill -->
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                <span>{{ __('Total Recorded Points') }}:</span>
                                <strong class="font-mono text-brand-600 dark:text-brand-400" x-text="points.length"></strong>
                            </span>
                        </div>
                    </div>

                    <!-- Standard Switcher Bar (If multiple standards exist) -->
                    <div x-show="selectedEquipmentId && currentSpecs.length > 1" class="space-y-3 p-4 bg-gray-50/80 dark:bg-gray-900/40 rounded-xl border border-gray-200/80 dark:border-gray-700/70">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center gap-1.5">
                                <i class="fas fa-layer-group text-brand-600"></i>
                                <span>{{ __('Select Standard / Parameter') }}:</span>
                            </span>
                            <button type="button" @click="activeSpecId = 'all'"
                                    :class="activeSpecId === 'all'
                                        ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 shadow-sm font-semibold'
                                        : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:text-gray-900 dark:hover:text-white'"
                                    class="px-2.5 py-1 rounded-lg text-xs transition inline-flex items-center gap-1.5">
                                <i class="fas fa-th-list text-[10px]"></i>
                                <span>{{ __('All Standards Overview') }}</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded-full font-mono bg-black/10 dark:bg-white/10" x-text="points.length"></span>
                            </button>
                        </div>

                        <!-- Measurement Standards Pills -->
                        <div x-show="measurementSpecs.length > 0" class="space-y-1.5">
                            <span class="text-[11px] font-semibold text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5">
                                <i class="fas fa-sign-in-alt text-[10px] text-emerald-600"></i>
                                <span>{{ __('Measurement Standards & Capabilities (Sensors / In)') }}</span>
                            </span>
                            <div class="flex flex-wrap items-center gap-2">
                                <template x-for="spec in measurementSpecs" :key="spec.id">
                                    <button type="button" @click="activeSpecId = spec.id"
                                            :class="activeSpecId == spec.id
                                                ? 'bg-emerald-600 text-white shadow-sm ring-2 ring-emerald-500/20 font-bold'
                                                : 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border border-emerald-500/20 hover:bg-emerald-500/20'"
                                            class="px-3 py-1.5 rounded-lg text-xs transition inline-flex items-center gap-1.5">
                                        <i :class="'fas ' + (spec.icon || 'fa-sign-in-alt') + ' text-[10px]'"></i>
                                        <span x-text="spec.name + (spec.symbol ? ' (' + spec.symbol + ')' : '')"></span>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full font-mono"
                                              :class="activeSpecId == spec.id ? 'bg-white/20 text-white' : 'bg-emerald-500/20 text-emerald-900 dark:text-emerald-200'"
                                              x-text="getPointsCountForSpec(spec.id)"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Source Standards Pills -->
                        <div x-show="sourceSpecs.length > 0" class="space-y-1.5 pt-1">
                            <span class="text-[11px] font-semibold text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                                <i class="fas fa-bolt text-[10px] text-amber-600"></i>
                                <span>{{ __('Source Standards & Capabilities (Generators / Out)') }}</span>
                            </span>
                            <div class="flex flex-wrap items-center gap-2">
                                <template x-for="spec in sourceSpecs" :key="spec.id">
                                    <button type="button" @click="activeSpecId = spec.id"
                                            :class="activeSpecId == spec.id
                                                ? 'bg-amber-600 text-white shadow-sm ring-2 ring-amber-500/20 font-bold'
                                                : 'bg-amber-500/10 text-amber-800 dark:text-amber-300 border border-amber-500/20 hover:bg-amber-500/20'"
                                            class="px-3 py-1.5 rounded-lg text-xs transition inline-flex items-center gap-1.5">
                                        <i :class="'fas ' + (spec.icon || 'fa-bolt') + ' text-[10px]'"></i>
                                        <span x-text="spec.name + (spec.symbol ? ' (' + spec.symbol + ')' : '')"></span>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full font-mono"
                                              :class="activeSpecId == spec.id ? 'bg-white/20 text-white' : 'bg-amber-500/20 text-amber-900 dark:text-amber-200'"
                                              x-text="getPointsCountForSpec(spec.id)"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Active Standard Details & Action Toolbar -->
                    <div x-show="selectedEquipmentId" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 rounded-xl border"
                         :class="activeSpec?.type === 'source'
                            ? 'bg-amber-500/5 border-amber-200 dark:border-amber-800/60'
                            : (activeSpec?.type === 'measurement'
                                ? 'bg-emerald-500/5 border-emerald-200 dark:border-emerald-800/60'
                                : 'bg-gray-50 dark:bg-gray-800/60 border-gray-200 dark:border-gray-700')">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <template x-if="activeSpec">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-md flex items-center justify-center text-xs shrink-0"
                                         :class="activeSpec.icon_container || 'bg-gray-100 text-gray-600'">
                                        <i :class="'fas ' + (activeSpec.icon || 'fa-sliders-h')"></i>
                                    </div>
                                    <span class="text-xs font-bold text-gray-900 dark:text-white" x-text="activeSpec.name"></span>
                                    <span x-show="activeSpec.symbol" class="px-2 py-0.5 rounded font-mono text-xs font-bold border"
                                          :class="activeSpec.badge_class || 'border-gray-200'"
                                          x-text="activeSpec.symbol"></span>
                                    <x-badge :variant="'success'" x-show="activeSpec.type === 'measurement'" class="text-[10px]">
                                        {{ __('Measurement / In') }}
                                    </x-badge>
                                    <x-badge :variant="'warning'" x-show="activeSpec.type === 'source'" class="text-[10px]">
                                        {{ __('Source / Out') }}
                                    </x-badge>
                                </div>
                            </template>
                            <template x-if="activeSpecId === 'all'">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-900 dark:text-white">{{ __('All Standards Overview') }}</span>
                                    <x-badge variant="neutral" class="text-[10px]">
                                        <span x-text="currentSpecs.length + ' {{ __('Standards') }}'"></span>
                                    </x-badge>
                                </div>
                            </template>

                            <!-- Range & Accuracy badges -->
                            <template x-if="activeRangeText">
                                <span class="inline-flex items-center gap-1 text-[11px] font-mono text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 px-2 py-0.5 rounded border border-gray-200 dark:border-gray-700">
                                    <i class="fas fa-arrows-alt-h text-gray-400"></i>
                                    <span class="text-gray-400">{{ __('Range') }}:</span>
                                    <strong class="text-gray-700 dark:text-gray-200" x-text="activeRangeText"></strong>
                                </span>
                            </template>
                            <template x-if="activeAccuracyText">
                                <span class="inline-flex items-center gap-1 text-[11px] font-mono text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 px-2 py-0.5 rounded border border-gray-200 dark:border-gray-700">
                                    <i class="fas fa-bullseye text-gray-400"></i>
                                    <span class="text-gray-400">{{ __('Accuracy') }}:</span>
                                    <strong class="text-gray-700 dark:text-gray-200" x-text="activeAccuracyText"></strong>
                                </span>
                            </template>
                        </div>

                        <!-- Action buttons for points -->
                        <div class="flex items-center gap-2">
                            <button type="button" @click="addPoint()" class="px-3 py-1.5 bg-emerald-100 hover:bg-emerald-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-emerald-800 dark:text-emerald-300 text-xs font-semibold rounded-lg transition inline-flex items-center gap-1">
                                <i class="fas fa-plus"></i>
                                <span>{{ __('Add Point') }}</span>
                            </button>
                            <button type="button" @click="addPointsBatch(5)" class="px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-lg transition inline-flex items-center gap-1" title="{{ __('Add 5 Points') }}">
                                <i class="fas fa-layer-group"></i>
                                <span>{{ __('Add 5 Points') }}</span>
                            </button>
                            <button type="button" @click="clearCurrentSpecPoints()" class="px-2.5 py-1.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-xs font-semibold rounded-lg transition inline-flex items-center gap-1" title="{{ __('Clear Standard Points') }}">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Points Table -->
                    <div x-show="selectedEquipmentId" class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-700/60 shadow-xs">
                        <table class="w-full text-xs text-left text-gray-700 dark:text-gray-200">
                            <thead class="bg-gray-50 dark:bg-gray-700/50 uppercase font-semibold text-gray-500 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="py-2.5 px-3 w-12 text-center">#</th>
                                    <th class="py-2.5 px-3">
                                        <span>{{ __('Nominal Value (Setpoint)') }}</span>
                                        <span class="text-rose-500">*</span>
                                        <span x-show="activeUnit" class="text-xs font-normal text-brand-600 dark:text-brand-400 ms-1" x-text="'[' + activeUnit + ']'"></span>
                                    </th>
                                    <th class="py-2.5 px-3">
                                        <span>{{ __('Correction (C)') }}</span>
                                        <span class="text-rose-500">*</span>
                                        <span x-show="activeUnit" class="text-xs font-normal text-brand-600 dark:text-brand-400 ms-1" x-text="'[' + activeUnit + ']'"></span>
                                    </th>
                                    <th class="py-2.5 px-3">
                                        <span>{{ __('Uncertainty (U)') }}</span>
                                        <span class="text-rose-500">*</span>
                                        <span x-show="activeUnit" class="text-xs font-normal text-brand-600 dark:text-brand-400 ms-1" x-text="'[' + activeUnit + ']'"></span>
                                    </th>
                                    <th x-show="activeSpecId === 'all'" class="py-2.5 px-3">
                                        <span>{{ __('Standard / Capability') }}</span>
                                    </th>
                                    <th class="py-2.5 px-3 w-16 text-center">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <template x-for="(pt, idx) in points" :key="idx">
                                    <tr x-show="isRowVisible(pt)" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                        <td class="py-2 px-3 text-center font-mono text-gray-400" x-text="idx + 1"></td>
                                        <td class="py-2 px-3">
                                            <input type="hidden" :name="'points[' + idx + '][equipment_specification_id]'" x-model="pt.equipment_specification_id">
                                            <input type="number" step="any" :name="'points[' + idx + '][nominal_value]'" x-model="pt.nominal_value" required placeholder="0.0" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white font-mono font-bold focus:ring-brand-500 focus:border-brand-500">
                                        </td>
                                        <td class="py-2 px-3">
                                            <input type="number" step="any" :name="'points[' + idx + '][correction]'" x-model="pt.correction" required placeholder="0.0" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white font-mono focus:ring-brand-500 focus:border-brand-500">
                                        </td>
                                        <td class="py-2 px-3">
                                            <input type="number" step="any" min="0" :name="'points[' + idx + '][uncertainty]'" x-model="pt.uncertainty" required placeholder="0.0" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white font-mono focus:ring-brand-500 focus:border-brand-500">
                                        </td>
                                        <td x-show="activeSpecId === 'all'" class="py-2 px-3">
                                            <select x-model="pt.equipment_specification_id" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white">
                                                <template x-for="s in currentSpecs" :key="s.id">
                                                    <option :value="s.id" x-text="s.name + (s.symbol ? ' (' + s.symbol + ')' : '') + ' - ' + s.type_label"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="py-2 px-3 text-center">
                                            <button type="button" @click="removePoint(idx)" :disabled="points.length === 1" class="p-1 text-rose-500 hover:text-rose-700 disabled:opacity-30 transition">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty state if current standard has 0 points -->
                    <div x-show="selectedEquipmentId && visiblePointsCount === 0" class="p-6 text-center text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/40 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                        <p>{{ __('No points added for this standard yet.') }}</p>
                        <button type="button" @click="addPoint()" class="mt-2 px-3 py-1.5 bg-emerald-100 hover:bg-emerald-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-emerald-800 dark:text-emerald-300 text-xs font-semibold rounded-lg transition">
                            <i class="fas fa-plus mr-1"></i> {{ __('Add Point') }}
                        </button>
                    </div>
                </div>

                <!-- Form Bottom Actions -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('metrology.calibration-certificates.show', $certificate) }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-lg transition">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                        <i class="fas fa-save mr-1.5"></i>
                        <span>{{ __('Update Certificate') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
