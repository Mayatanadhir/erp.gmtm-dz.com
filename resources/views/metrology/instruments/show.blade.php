<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('metrology.instruments') }}" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <x-tool-icon name="instruments" class="w-10 h-10 sm:w-11 sm:h-11 shrink-0" />
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight font-mono">
                            {{ $instrument->tag_number }}
                        </h2>
                        <x-badge :variant="$instrument->status->badgeVariant()" :dot="true">
                            {{ $instrument->status->label() }}
                        </x-badge>
                        <x-badge :variant="$instrument->instrument_type->badgeVariant()">
                            <i class="fas {{ $instrument->instrument_type->icon() }} me-1"></i>
                            {{ $instrument->instrument_type->label() }}
                        </x-badge>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('S/N') }}: <span class="font-mono font-medium">{{ $instrument->serial_number }}</span>
                        @if($instrument->site)
                            • {{ __('Site') }}: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $instrument->site->short_name ?? $instrument->site->full_name }}</span>
                        @endif
                    </p>
                </div>
            </div>

            @include('metrology.instruments.partials._header-actions')
        </div>
    </x-slot>

    @php
        $specsJson = json_encode($instrument->specifications->keyBy('grandeur_id')->map(fn($s) => [
            'selected' => true,
            'min' => (float) $s->range_min,
            'max' => (float) $s->range_max,
            'acc' => (float) $s->accuracy_value,
            'acc_type' => $s->accuracy_type->value ?? '%',
        ])->all() ?: (object)[]);

        $gaugeSpecJson = json_encode($instrument->standardGaugeSpecification ? [
            'nominal_capacity_liters' => (float) $instrument->standardGaugeSpecification->nominal_capacity_liters,
            'neck_scale_sensitivity' => (float) $instrument->standardGaugeSpecification->neck_scale_sensitivity,
            'calibration_certificate_number' => $instrument->standardGaugeSpecification->calibration_certificate_number,
            'calibration_expiry_date' => $instrument->standardGaugeSpecification->calibration_expiry_date?->format('Y-m-d'),
        ] : (object)[]);

        $proverSpecJson = json_encode($instrument->proverSpecification ? [
            'type' => $instrument->proverSpecification->type?->value ?? $instrument->proverSpecification->type,
            'inner_diameter' => (float) $instrument->proverSpecification->inner_diameter,
            'wall_thickness' => (float) $instrument->proverSpecification->wall_thickness,
            'nominal_base_volume' => (float) $instrument->proverSpecification->nominal_base_volume,
            'material' => $instrument->proverSpecification->material,
        ] : (object)[]);
    @endphp

    <div class="py-8"
        x-data="{
            showEditModal: {{ ($errors->any() && old('_method') === 'PUT') ? 'true' : 'false' }} || window.location.hash === '#edit',
            editTagNumber: {{ json_encode(old('_method') === 'PUT' ? (string) old('tag_number', '') : (string) $instrument->tag_number) }},
            editSerialNumber: {{ json_encode(old('_method') === 'PUT' ? (string) old('serial_number', '') : (string) $instrument->serial_number) }},
            editSiteId: {{ json_encode(old('_method') === 'PUT' ? (string) old('site_id', '') : (string) ($instrument->site_id ?? '')) }},
            editType: {{ json_encode(old('_method') === 'PUT' ? (string) old('instrument_type', 'transmitter') : (string) $instrument->instrument_type->value) }},
            editStatus: {{ json_encode(old('_method') === 'PUT' ? (string) old('status', 'active') : (string) $instrument->status->value) }},
            editProcessVariable: {{ json_encode(old('_method') === 'PUT' ? (string) old('process_variable', '') : (string) ($instrument->process_variable?->value ?? '')) }},
            editMeasurementType: {{ json_encode(old('_method') === 'PUT' ? (string) old('measurement_type', '') : (string) ($instrument->measurement_type ?? '')) }},
            editFluidType: {{ json_encode(old('_method') === 'PUT' ? (string) old('fluid_type', '') : (string) ($instrument->fluid_type?->value ?? '')) }},
            editTechnology: {{ json_encode(old('_method') === 'PUT' ? (string) old('technology', '') : (string) ($instrument->technology ?? '')) }},
            editImageUrl: {{ json_encode($instrument->image_url ?? '') }},
            editImagePreview: null,
            editRemoveImage: false,
            editSpecs: {{ $specsJson }},
            editGaugeSpec: {{ $gaugeSpecJson }},
            editProverSpec: {{ $proverSpecJson }},
            init() {
                window.addEventListener('open-edit-instrument-modal', () => { this.showEditModal = true; });
            }
        }"
        @open-edit-instrument-modal.window="showEditModal = true"
    >
        <div class="w-full px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <x-alert variant="success" class="mb-6">
                    {{ session('success') }}
                </x-alert>
            @endif

            @if (session('error'))
                <x-alert variant="danger" class="mb-6">
                    {{ session('error') }}
                </x-alert>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <!-- Left Column: Identity & Media Card -->
                <div class="space-y-6">
                    @include('metrology.instruments.partials._identity-card')
                </div>

                <!-- Right Column: Technical Details & Specifications -->
                <div class="lg:col-span-2 space-y-6">
                    @include('metrology.instruments.partials._technical-specs')
                    @include('metrology.instruments.partials._metrological-specs')
                    @include('metrology.instruments.partials._specialized-specs')
                    @include('metrology.instruments.partials._card-audit')
                </div>
            </div>
        </div>

        @include('metrology.instruments.partials._modal-edit')
    </div>
</x-app-layout>
