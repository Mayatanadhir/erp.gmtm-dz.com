<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('metrology.equipment') }}" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ $equipment->full_name }}
                    </h2>
                    <div class="flex items-center gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                        @if($itemCode = $equipment->internal_code)
                            <code class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 font-mono text-[11px]">{{ $itemCode }}</code>
                            <span>•</span>
                        @endif
                        @if($sn = $equipment->serial_number)
                            <span>S/N: {{ $sn }}</span>
                            <span>•</span>
                        @endif
                        <span>{{ $equipment->category->label() }}</span>
                    </div>
                </div>
            </div>

            @include('metrology.equipment.partials._header-actions')
        </div>
    </x-slot>

    @php
        $isOldPut = old('_method') === 'PUT';
        if ($isOldPut && is_array(old('params'))) {
            $specsMap = [];
            foreach (old('params') as $gid => $p) {
                if (! empty($p['selected'])) {
                    $specsMap[(int) $gid] = [
                        'selected' => true,
                        'min'      => isset($p['min']) && $p['min'] !== '' ? (float) $p['min'] : '',
                        'max'      => isset($p['max']) && $p['max'] !== '' ? (float) $p['max'] : '',
                        'acc'      => isset($p['acc']) && $p['acc'] !== '' ? (float) $p['acc'] : '',
                        'acc_type' => $p['acc_type'] ?? '%',
                    ];
                }
            }
            $specsJson = json_encode((object) $specsMap);
        } else {
            $specsJson = json_encode($equipment->specifications->keyBy('grandeur_id')->map(fn($s) => [
                'selected' => true,
                'min'      => (float) $s->range_min,
                'max'      => (float) $s->range_max,
                'acc'      => (float) $s->accuracy_value,
                'acc_type' => $s->accuracy_type->value ?? '%',
            ])->all() ?: (object)[]);
        }
    @endphp

    <div class="py-8"
        x-data="{
            activeTab: (function() {
                const validTabs = ['overview', 'specifications', 'certificates', 'audit'];
                const h = window.location.hash ? window.location.hash.replace('#', '') : '';
                return validTabs.includes(h) ? h : 'overview';
            })(),
            showEditModal: {{ ($errors->any() && old('_method') === 'PUT') ? 'true' : 'false' }} || window.location.hash === '#edit',
            editFullName: {{ json_encode(old('_method') === 'PUT' ? (string) old('full_name', '') : (string) $equipment->full_name) }},
            editShortName: {{ json_encode(old('_method') === 'PUT' ? (string) old('short_name', '') : (string) $equipment->short_name) }},
            editInternalCode: {{ json_encode(old('_method') === 'PUT' ? (string) old('internal_code', '') : (string) $equipment->internal_code) }},
            editSerialNumber: {{ json_encode(old('_method') === 'PUT' ? (string) old('serial_number', '') : (string) $equipment->serial_number) }},
            editCategory: {{ json_encode(old('_method') === 'PUT' ? (string) old('category', 'measuring_instrument') : $equipment->category->value) }},
            editPackage: {{ json_encode(old('_method') === 'PUT' ? (string) old('package', 'none') : $equipment->package->value) }},
            editStatus: {{ json_encode(old('_method') === 'PUT' ? (string) old('status', 'active') : $equipment->status->value) }},
            editRequiresCalibration: {{ old('_method') === 'PUT' ? (old('requires_calibration') ? 'true' : 'false') : ($equipment->requires_calibration ? 'true' : 'false') }},
            editDesignation: {{ json_encode(old('_method') === 'PUT' ? (string) old('designation', '') : (string) ($equipment->designation ?? '')) }},
            editNotes: {{ json_encode(old('_method') === 'PUT' ? (string) old('notes', '') : (string) ($equipment->notes ?? '')) }},
            editImageUrl: {{ json_encode($equipment->image_url ?? '') }},
            editCertificateUrl: {{ json_encode($equipment->certificate_url ?? '') }},
            editImagePreview: null,
            editRemoveImage: false,
            editRemoveCertificate: false,
            editSpecs: {{ $specsJson }},
            handleImageChange(e) {
                const file = e.target.files[0];
                if (file) {
                    this.editImagePreview = URL.createObjectURL(file);
                    this.editRemoveImage = false;
                }
            },
            init() {
                window.addEventListener('open-edit-equipment-modal', () => { this.showEditModal = true; });
            }
        }"
    >
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                {{-- Sidebar Navigation --}}
                <aside class="w-full lg:w-64 shrink-0">
                    <x-metrology-tabs active="equipment" />
                </aside>

                {{-- Main Content --}}
                <main class="flex-1 w-full min-w-0 space-y-6">
                    @include('metrology.equipment.partials._profile-card')
                    @include('metrology.equipment.partials._tab-overview')
                    @include('metrology.equipment.partials._tab-specifications')
                    @include('metrology.equipment.partials._tab-certificates')
                    @include('metrology.equipment.partials._tab-audit')
                </main>
            </div>
        </div>

        @include('metrology.equipment.partials._modal-edit')
    </div>
</x-app-layout>
