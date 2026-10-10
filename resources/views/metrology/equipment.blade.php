<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <x-tool-icon name="equipment" class="w-11 h-11 sm:w-12 sm:h-12 shrink-0" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Equipment') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Manage calibration equipment, measuring instruments, and work tools') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="info" size="md">
                    {{ $equipment->total() }} {{ __('Records') }}
                </x-badge>
                @can('create equipment')
                    <x-primary-button
                        type="button"
                        x-data
                        @click="$dispatch('open-create-equipment-modal')"
                        class="flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>{{ __('New Equipment') }}</span>
                    </x-primary-button>
                @endcan
            </div>
        </div>
    </x-slot>

    @php
        $oldPutSpecs = [];
        if (old('_method') === 'PUT') {
            if (is_array(old('params'))) {
                foreach (old('params') as $gid => $p) {
                    if (! empty($p['selected'])) {
                        $oldPutSpecs[(int) $gid] = [
                            'selected' => true,
                            'min'      => isset($p['min']) && $p['min'] !== '' ? (float) $p['min'] : '',
                            'max'      => isset($p['max']) && $p['max'] !== '' ? (float) $p['max'] : '',
                            'acc'      => isset($p['acc']) && $p['acc'] !== '' ? (float) $p['acc'] : '',
                            'acc_type' => $p['acc_type'] ?? '%',
                        ];
                    }
                }
            } elseif (old('edit_id')) {
                $prevEq = \App\Models\Equipment::with('specifications')->find(old('edit_id'));
                if ($prevEq) {
                    $oldPutSpecs = $prevEq->specifications->keyBy('grandeur_id')->map(fn($s) => [
                        'selected' => true,
                        'min'      => (float) $s->range_min,
                        'max'      => (float) $s->range_max,
                        'acc'      => (float) $s->accuracy_value,
                        'acc_type' => $s->accuracy_type->value ?? '%',
                    ])->all();
                }
            }
        }
    @endphp

    <div class="py-8"
        @open-create-equipment-modal.window="openCreateModal()"
        x-data="{
            showCreateModal: {{ ($errors->any() && !old('_method')) ? 'true' : 'false' }},
            showEditModal: {{ ($errors->any() && old('_method') === 'PUT') ? 'true' : 'false' }},
            showDeleteModal: false,

            // Create state
            createRequiresCalibration: {{ old('requires_calibration') ? 'true' : 'false' }},
            createCategory: '{{ old('category', 'measuring_instrument') }}',
            createImagePreview: null,

            // Edit state
            editId: {{ old('_method') === 'PUT' ? (int) old('edit_id', 0) : 'null' }},
            editFullName: {{ Js::from(old('_method') === 'PUT' ? (string) old('full_name', '') : '') }},
            editShortName: {{ Js::from(old('_method') === 'PUT' ? (string) old('short_name', '') : '') }},
            editInternalCode: {{ Js::from(old('_method') === 'PUT' ? (string) old('internal_code', '') : '') }},
            editSerialNumber: {{ Js::from(old('_method') === 'PUT' ? (string) old('serial_number', '') : '') }},
            editCategory: {{ Js::from(old('_method') === 'PUT' ? (string) old('category', 'measuring_instrument') : 'measuring_instrument') }},
            editPackage: {{ Js::from(old('_method') === 'PUT' ? (string) old('package', 'none') : 'none') }},
            editStatus: {{ Js::from(old('_method') === 'PUT' ? (string) old('status', 'active') : 'active') }},
            editRequiresCalibration: {{ old('_method') === 'PUT' ? (old('requires_calibration') ? 'true' : 'false') : 'false' }},
            editDesignation: {{ Js::from(old('_method') === 'PUT' ? (string) old('designation', '') : '') }},
            editNotes: {{ Js::from(old('_method') === 'PUT' ? (string) old('notes', '') : '') }},
            editImageUrl: '',
            editCertificateUrl: '',
            editImagePreview: null,
            editRemoveImage: false,
            editRemoveCertificate: false,
            editActionUrl: '{{ old('_method') === 'PUT' && old('edit_id') ? route('metrology.equipment.update', (int) old('edit_id')) : '' }}',
            editSpecs: {{ json_encode((object) $oldPutSpecs) }},

            // Delete state
            deleteName: '',
            deleteActionUrl: '',

            openCreateModal() {
                this.createImagePreview = null;
                this.createRequiresCalibration = this.createCategory === 'measuring_instrument';
                this.showCreateModal = true;
            },

            openEditModal(item, actionUrl) {
                this.editId = item.id;
                this.editFullName = item.full_name || '';
                this.editShortName = item.short_name || '';
                this.editInternalCode = item.internal_code || '';
                this.editSerialNumber = item.serial_number || '';
                this.editCategory = item.category || 'measuring_instrument';
                this.editPackage = item.package || 'none';
                this.editStatus = item.status || 'active';
                this.editRequiresCalibration = Boolean(item.requires_calibration);
                this.editDesignation = item.designation || '';
                this.editNotes = item.notes || '';
                this.editImageUrl = item.image_url || '';
                this.editCertificateUrl = item.certificate_url || '';
                this.editImagePreview = null;
                this.editRemoveImage = false;
                this.editRemoveCertificate = false;
                this.editActionUrl = actionUrl;

                // Load specs map
                this.editSpecs = {};
                if (item.specifications && Array.isArray(item.specifications)) {
                    item.specifications.forEach(s => {
                        this.editSpecs[s.grandeur_id] = {
                            selected: true,
                            min: s.range_min,
                            max: s.range_max,
                            acc: s.accuracy_value,
                            acc_type: s.accuracy_type || '%'
                        };
                    });
                }

                this.showEditModal = true;
            },

            openDeleteModal(name, actionUrl) {
                this.deleteName = name;
                this.deleteActionUrl = actionUrl;
                this.showDeleteModal = true;
            },

            handleImageChange(e, type) {
                const file = e.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (ev) => {
                    if (type === 'create') this.createImagePreview = ev.target.result;
                    if (type === 'edit') this.editImagePreview = ev.target.result;
                };
                reader.readAsDataURL(file);
            }
        }">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-metrology-tabs active="equipment" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-6">
                    @if (session('success'))
                        <x-alert variant="success">
                            {{ session('success') }}
                        </x-alert>
                    @endif

                    @if (session('error'))
                        <x-alert variant="danger">
                            {{ session('error') }}
                        </x-alert>
                    @endif

                    <!-- KPI Statistics Grid (1: Has Certificate, 2: Work Tools, 3: Vehicles, 4: Inactive) -->
                    @php
                        $isCertifiedActive = request('category') === 'measuring_instrument' || request('requires_calibration') === '1';
                        $isWorkToolActive = request('category') === 'work_tool';
                        $isVehicleActive = request('category') === 'vehicle';
                        $isInactiveActive = request('status') === 'inactive';
                    @endphp
                    <div class="kpi-grid-4 grid grid-cols-4 gap-2.5 sm:gap-4 w-full">
                        <!-- 1. Has Certificate -->
                        <a href="{{ route('metrology.equipment', $isCertifiedActive ? request()->except(['category', 'requires_calibration', 'page']) : array_merge(request()->except(['page']), ['category' => 'measuring_instrument'])) }}"
                           class="p-3 sm:p-4 rounded-xl border bg-white dark:bg-gray-800 shadow-sm flex items-center justify-between gap-2 sm:gap-3 hover:shadow-md transition-all duration-150 group min-w-0 {{ $isCertifiedActive ? 'ring-2 ring-emerald-500 border-emerald-500 dark:border-emerald-500 bg-emerald-50/20 dark:bg-emerald-500/10' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                                <div class="p-2 sm:p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 shrink-0 group-hover:scale-105 transition-transform">
                                    <i class="fas fa-certificate text-base sm:text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 font-medium truncate">{{ __('Has Certificate') }}</div>
                                    <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-white leading-tight mt-0.5">{{ $stats['has_certificate'] ?? 0 }}</div>
                                </div>
                            </div>
                            <span class="shrink-0 text-[10px] sm:text-[11px] font-mono font-semibold px-1.5 sm:px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-500/30">1</span>
                        </a>

                        <!-- 2. Work Tools -->
                        <a href="{{ route('metrology.equipment', $isWorkToolActive ? request()->except(['category', 'page']) : array_merge(request()->except(['page']), ['category' => 'work_tool'])) }}"
                           class="p-3 sm:p-4 rounded-xl border bg-white dark:bg-gray-800 shadow-sm flex items-center justify-between gap-2 sm:gap-3 hover:shadow-md transition-all duration-150 group min-w-0 {{ $isWorkToolActive ? 'ring-2 ring-amber-500 border-amber-500 dark:border-amber-500 bg-amber-50/20 dark:bg-amber-500/10' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                                <div class="p-2 sm:p-2.5 rounded-lg bg-amber-50 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 shrink-0 group-hover:scale-105 transition-transform">
                                    <i class="fas fa-tools text-base sm:text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 font-medium truncate">{{ __('Work Tools') }}</div>
                                    <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-white leading-tight mt-0.5">{{ $stats['work_tools'] ?? 0 }}</div>
                                </div>
                            </div>
                            <span class="shrink-0 text-[10px] sm:text-[11px] font-mono font-semibold px-1.5 sm:px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300 border border-amber-200/60 dark:border-amber-500/30">2</span>
                        </a>

                        <!-- 3. Vehicles -->
                        <a href="{{ route('metrology.equipment', $isVehicleActive ? request()->except(['category', 'page']) : array_merge(request()->except(['page']), ['category' => 'vehicle'])) }}"
                           class="p-3 sm:p-4 rounded-xl border bg-white dark:bg-gray-800 shadow-sm flex items-center justify-between gap-2 sm:gap-3 hover:shadow-md transition-all duration-150 group min-w-0 {{ $isVehicleActive ? 'ring-2 ring-indigo-500 border-indigo-500 dark:border-indigo-500 bg-indigo-50/20 dark:bg-indigo-500/10' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                                <div class="p-2 sm:p-2.5 rounded-lg bg-indigo-50 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 shrink-0 group-hover:scale-105 transition-transform">
                                    <i class="fas fa-truck-pickup text-base sm:text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 font-medium truncate">{{ __('Vehicles') }}</div>
                                    <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-white leading-tight mt-0.5">{{ $stats['vehicles'] ?? 0 }}</div>
                                </div>
                            </div>
                            <span class="shrink-0 text-[10px] sm:text-[11px] font-mono font-semibold px-1.5 sm:px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-500/30">3</span>
                        </a>

                        <!-- 4. Inactive -->
                        <a href="{{ route('metrology.equipment', $isInactiveActive ? request()->except(['status', 'page']) : array_merge(request()->except(['page']), ['status' => 'inactive'])) }}"
                           class="p-3 sm:p-4 rounded-xl border bg-white dark:bg-gray-800 shadow-sm flex items-center justify-between gap-2 sm:gap-3 hover:shadow-md transition-all duration-150 group min-w-0 {{ $isInactiveActive ? 'ring-2 ring-rose-500 border-rose-500 dark:border-rose-500 bg-rose-50/20 dark:bg-rose-500/10' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                                <div class="p-2 sm:p-2.5 rounded-lg bg-rose-50 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 shrink-0 group-hover:scale-105 transition-transform">
                                    <i class="fas fa-ban text-base sm:text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 font-medium truncate">{{ __('Inactive') }}</div>
                                    <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-white leading-tight mt-0.5">{{ $stats['inactive'] ?? 0 }}</div>
                                </div>
                            </div>
                            <span class="shrink-0 text-[10px] sm:text-[11px] font-mono font-semibold px-1.5 sm:px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300 border border-rose-200/60 dark:border-rose-500/30">4</span>
                        </a>
                    </div>

                    <x-table>
                        <x-slot:toolbar>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                                    <x-tool-icon name="equipment" class="w-6 h-6 shrink-0" />
                                    <span>{{ __('Equipment Inventory') }}</span>
                                </h3>

                                <div class="flex flex-wrap items-center gap-3">
                                    <x-global-filter
                                        :action="route('metrology.equipment')"
                                        :search="true"
                                        :search-placeholder="__('Search by name, code, serial...')"
                                        :search-value="request('search')"
                                        :submit-text="__('Search')"
                                    >
                                        <select name="category" onchange="this.form.submit()" class="py-1.5 ps-2.5 pe-8 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600 shadow-sm">
                                            <option value="">{{ __('All Categories') }}</option>
                                            @foreach(\App\Enums\EquipmentCategory::cases() as $cat)
                                                <option value="{{ $cat->value }}" @selected(request('category') === $cat->value)>{{ $cat->label() }}</option>
                                            @endforeach
                                        </select>

                                        <select name="package" onchange="this.form.submit()" class="py-1.5 ps-2.5 pe-8 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600 shadow-sm">
                                            <option value="">{{ __('All Packages') }}</option>
                                            @foreach(\App\Enums\EquipmentPackage::cases() as $pkg)
                                                <option value="{{ $pkg->value }}" @selected(request('package') === $pkg->value)>{{ $pkg->label() }}</option>
                                            @endforeach
                                        </select>

                                        <select name="status" onchange="this.form.submit()" class="py-1.5 ps-2.5 pe-8 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600 shadow-sm">
                                            <option value="">{{ __('All Statuses') }}</option>
                                            @foreach(\App\Enums\EquipmentStatus::cases() as $st)
                                                <option value="{{ $st->value }}" @selected(request('status') === $st->value)>{{ $st->label() }}</option>
                                            @endforeach
                                        </select>
                                    </x-global-filter>
                                </div>
                            </div>
                        </x-slot:toolbar>

                        <x-slot:header>
                            <x-table.th class="w-16">{{ __('Photo') }}</x-table.th>
                            <x-table.th>{{ __('Equipment Information') }}</x-table.th>
                            <x-table.th>{{ __('Category') }}</x-table.th>
                            <x-table.th>{{ __('Package / Lot') }}</x-table.th>
                            <x-table.th>{{ __('Calibration') }}</x-table.th>
                            <x-table.th>{{ __('Status') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                        </x-slot:header>

                        @forelse($equipment as $item)
                            <x-table.tr>
                                <x-table.td>
                                    @if($item->image_url)
                                        <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="w-11 h-11 object-contain rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm bg-white dark:bg-gray-800 p-0.5">
                                    @else
                                        <div class="w-11 h-11 rounded-lg bg-gray-100 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-400">
                                            <i class="fas {{ $item->category->icon() }}"></i>
                                        </div>
                                    @endif
                                </x-table.td>

                                <x-table.td>
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $item->full_name }}</div>
                                    <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                        @if($item->short_name)
                                            <span>{{ $item->short_name }}</span>
                                            <span>•</span>
                                        @endif
                                        @if($item->internal_code)
                                            <code class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 font-mono text-[11px]">{{ $item->internal_code }}</code>
                                        @endif
                                        @if($item->serial_number)
                                            <span class="text-gray-400">S/N: {{ $item->serial_number }}</span>
                                        @endif
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <x-badge :variant="$item->category->badgeVariant()" size="sm">
                                        <i class="fas {{ $item->category->icon() }} me-1"></i>
                                        {{ $item->category->label() }}
                                    </x-badge>
                                </x-table.td>

                                <x-table.td>
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                        {{ $item->package->label() }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    @if($item->requires_calibration)
                                        <div class="flex items-center gap-1.5">
                                            <x-badge variant="info" size="sm" :dot="true">
                                                {{ __('Yes') }}
                                            </x-badge>
                                          
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">
                                            {{ __('No') }}
                                        </span>
                                    @endif
                                </x-table.td>

                                <x-table.td>
                                    <x-badge :variant="$item->status->badgeVariant()" size="sm" :dot="true">
                                        {{ $item->status->label() }}
                                    </x-badge>
                                </x-table.td>

                                <x-table.td class="text-end">
                                    <x-table.actions>
                                        <x-table.action-view
                                            href="{{ route('metrology.equipment.show', $item->id) }}"
                                            :title="__('View Details')"
                                        />

                                       

                                        @can('edit equipment')
                                            @php
                                                $editPayload = [
                                                    'id' => $item->id,
                                                    'full_name' => $item->full_name,
                                                    'short_name' => $item->short_name,
                                                    'internal_code' => $item->internal_code,
                                                    'serial_number' => $item->serial_number,
                                                    'category' => $item->category->value,
                                                    'package' => $item->package->value,
                                                    'status' => $item->status->value,
                                                    'requires_calibration' => (bool) $item->requires_calibration,
                                                    'designation' => $item->designation,
                                                    'notes' => $item->notes,
                                                    'image_url' => $item->image_url,
                                                    'certificate_url' => $item->certificate_url,
                                                    'specifications' => $item->specifications->map(fn($s) => [
                                                        'grandeur_id' => $s->grandeur_id,
                                                        'range_min' => (float) $s->range_min,
                                                        'range_max' => (float) $s->range_max,
                                                        'accuracy_value' => (float) $s->accuracy_value,
                                                        'accuracy_type' => $s->accuracy_type->value ?? '%',
                                                    ])->values()->all(),
                                                ];
                                                $editJson = base64_encode(json_encode($editPayload));
                                                $updateUrl = route('metrology.equipment.update', array_merge(['equipment' => $item->id], request()->query()));
                                            @endphp
                                            <x-table.action-edit
                                                type="button"
                                                data-item="{{ $editJson }}"
                                                @click="openEditModal(JSON.parse(atob($el.dataset.item)), '{{ $updateUrl }}')"
                                                :title="__('Edit Equipment')"
                                            />
                                        @endcan

                                        @can('delete equipment')
                                            <x-table.action-delete
                                                type="button"
                                                @click="openDeleteModal('{{ addslashes($item->full_name) }}', '{{ route('metrology.equipment.destroy', array_merge(['equipment' => $item->id], request()->query())) }}')"
                                                :title="__('Delete Equipment')"
                                            />
                                        @endcan
                                    </x-table.actions>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty :colspan="7" :message="__('No equipment records found.')" />
                        @endforelse

                        @if($equipment->hasPages())
                            <x-slot:pagination>
                                {{ $equipment->links() }}
                            </x-slot:pagination>
                        @endif
                    </x-table>
                </main>
            </div>
        </div>

        <!-- ================= CREATE EQUIPMENT MODAL ================= -->
        <div
            x-show="showCreateModal"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div
                    x-show="showCreateModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="showCreateModal = false"
                    class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                ></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div
                    x-show="showCreateModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-start overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-gray-100 dark:border-gray-700"
                >
                    <form action="{{ route('metrology.equipment.store', request()->query()) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-orange-50 dark:bg-orange-500/20 text-orange-600 dark:text-orange-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ __('Add New Equipment') }}
                                </h3>
                            </div>
                            <button type="button" @click="showCreateModal = false" class="text-gray-400 hover:text-gray-500 text-xl font-bold">
                                &times;
                            </button>
                        </div>

                        <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                            <!-- Basic Information -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <x-input-label for="create_full_name" :value="__('Full Name')" required />
                                    <x-text-input id="create_full_name" name="full_name" type="text" class="mt-1 block w-full" :value="old('full_name')" required placeholder="e.g. Manometre Digital ADT672" />
                                    <x-input-error :messages="$errors->get('full_name')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="create_short_name" :value="__('Short / Acronym Name')" />
                                    <x-text-input id="create_short_name" name="short_name" type="text" class="mt-1 block w-full" :value="old('short_name')" placeholder="e.g. ADT672 100 bar" />
                                    <x-input-error :messages="$errors->get('short_name')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="create_internal_code" :value="__('Storage / Internal Code')" />
                                    <x-text-input id="create_internal_code" name="internal_code" type="text" class="mt-1 block w-full font-mono" :value="old('internal_code')" placeholder="e.g. LAB-WC-002" />
                                    <x-input-error :messages="$errors->get('internal_code')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="create_serial_number" :value="__('Serial Number (S/N)')" />
                                    <x-text-input id="create_serial_number" name="serial_number" type="text" class="mt-1 block w-full font-mono" :value="old('serial_number')" placeholder="e.g. 27316200007" />
                                    <x-input-error :messages="$errors->get('serial_number')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="create_category" :value="__('Equipment Category')" required />
                                    <select
                                        id="create_category"
                                        name="category"
                                        x-model="createCategory"
                                        @change="if(createCategory === 'measuring_instrument') createRequiresCalibration = true; else if(createCategory === 'vehicle') createRequiresCalibration = false;"
                                        class="mt-1 block w-full py-2 px-3 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-500 focus:border-brand-500"
                                        required
                                    >
                                        @foreach(\App\Enums\EquipmentCategory::cases() as $cat)
                                            <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('category')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="create_package" :value="__('Logistics Package / Lot')" />
                                    <select
                                        id="create_package"
                                        name="package"
                                        class="mt-1 block w-full py-2 px-3 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-500 focus:border-brand-500"
                                    >
                                        @foreach(\App\Enums\EquipmentPackage::cases() as $pkg)
                                            <option value="{{ $pkg->value }}" @selected(old('package') === $pkg->value)>{{ $pkg->label() }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('package')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="create_status" :value="__('Operational Status')" />
                                    <select
                                        id="create_status"
                                        name="status"
                                        class="mt-1 block w-full py-2 px-3 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-500 focus:border-brand-500"
                                    >
                                        @foreach(\App\Enums\EquipmentStatus::cases() as $st)
                                            <option value="{{ $st->value }}" @selected(old('status', 'active') === $st->value)>{{ $st->label() }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('status')" class="mt-1" />
                                </div>
                            </div>

                            <!-- Calibration Switch -->
                            <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/40">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        name="requires_calibration"
                                        value="1"
                                        x-model="createRequiresCalibration"
                                        class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-orange-500 shadow-sm focus:ring-orange-500 dark:focus:ring-offset-gray-800 w-4 h-4"
                                    >
                                    <div>
                                        <div class="font-semibold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                                            <i class="fas fa-certificate text-orange-500"></i>
                                            <span>{{ __('Requires Periodic Metrological Calibration') }}</span>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ __('Activate physical measurement ranges and generate accuracy specifications') }}
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- Physical Specifications Section -->
                            <div x-show="createRequiresCalibration" x-transition class="space-y-4">
                                <h4 class="font-bold text-sm text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center gap-2">
                                    <i class="fas fa-wave-square text-indigo-500"></i>
                                    <span>{{ __('Measurement & Generation Capabilities') }}</span>
                                </h4>

                                <!-- Measurement Parameters -->
                                @if($measurementGrandeurs->count() > 0)
                                    <div>
                                        <div class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-2">
                                            <i class="fas fa-signal me-1"></i> {{ __('Measurement Capabilities (Sensors / In)') }}
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            @foreach($measurementGrandeurs as $g)
                                                <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm space-y-2">
                                                    <label class="flex items-center justify-between cursor-pointer">
                                                        <span class="font-semibold text-xs text-gray-900 dark:text-white flex items-center gap-1.5">
                                                            <span>{{ $g->name }}</span>
                                                            <span class="text-[11px] px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20 font-bold">({{ $g->symbol }})</span>
                                                        </span>
                                                        <input type="checkbox" name="params[{{ $g->id }}][selected]" value="1" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-brand-600 focus:ring-brand-500 dark:focus:ring-offset-gray-800">
                                                    </label>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <input type="number" step="any" name="params[{{ $g->id }}][min]" placeholder="{{ __('Min') }}" class="py-1 px-2 text-xs rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                        <input type="number" step="any" name="params[{{ $g->id }}][max]" placeholder="{{ __('Max') }}" class="py-1 px-2 text-xs rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <input type="number" step="any" name="params[{{ $g->id }}][acc]" placeholder="{{ __('Accuracy') }}" class="py-1 px-2 text-xs rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                        <select name="params[{{ $g->id }}][acc_type]" class="py-1 px-2 text-xs rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                            <option value="%">%</option>
                                                            <option value="abs">Abs</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Source / Generation Parameters -->
                                @if($sourceGrandeurs->count() > 0)
                                    <div>
                                        <div class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider mb-2 mt-4">
                                            <i class="fas fa-bolt me-1"></i> {{ __('Source Capabilities (Generators / Out)') }}
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            @foreach($sourceGrandeurs as $g)
                                                <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm space-y-2">
                                                    <label class="flex items-center justify-between cursor-pointer">
                                                        <span class="font-semibold text-xs text-gray-900 dark:text-white flex items-center gap-1.5">
                                                            <span>{{ $g->name }}</span>
                                                            <span class="text-[11px] px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20 font-bold">({{ $g->symbol }})</span>
                                                        </span>
                                                        <input type="checkbox" name="params[{{ $g->id }}][selected]" value="1" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-brand-600 focus:ring-brand-500 dark:focus:ring-offset-gray-800">
                                                    </label>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <input type="number" step="any" name="params[{{ $g->id }}][min]" placeholder="{{ __('Min') }}" class="py-1 px-2 text-xs rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                        <input type="number" step="any" name="params[{{ $g->id }}][max]" placeholder="{{ __('Max') }}" class="py-1 px-2 text-xs rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <input type="number" step="any" name="params[{{ $g->id }}][acc]" placeholder="{{ __('Accuracy') }}" class="py-1 px-2 text-xs rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                        <select name="params[{{ $g->id }}][acc_type]" class="py-1 px-2 text-xs rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                            <option value="%">%</option>
                                                            <option value="abs">Abs</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Media Uploads: Image & Certificate -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100 dark:border-gray-700">
                                <div>
                                    <x-input-label for="create_image" :value="__('Equipment Photo (Optimized to WebP)')" />
                                    <input
                                        id="create_image"
                                        name="image"
                                        type="file"
                                        accept="image/*"
                                        @change="handleImageChange($event, 'create')"
                                        class="mt-1 block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 dark:file:bg-gray-700 dark:file:text-gray-300"
                                    >
                                    <template x-if="createImagePreview">
                                        <div class="mt-2">
                                            <img :src="createImagePreview" class="w-16 h-16 object-contain p-0.5 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                        </div>
                                    </template>
                                    <x-input-error :messages="$errors->get('image')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="create_certificate" :value="__('Calibration Certificate (PDF)')" />
                                    <input
                                        id="create_certificate"
                                        name="certificate"
                                        type="file"
                                        accept=".pdf"
                                        class="mt-1 block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-300"
                                    >
                                    <x-input-error :messages="$errors->get('certificate')" class="mt-1" />
                                </div>

                                <div class="sm:col-span-2">
                                    <x-input-label for="create_designation" :value="__('Designation / Technical Notes')" />
                                    <textarea id="create_designation" name="designation" rows="2" class="mt-1 block w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm">{{ old('designation') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                            <x-secondary-button type="button" @click="showCreateModal = false">
                                {{ __('Cancel') }}
                            </x-secondary-button>
                            <x-primary-button type="submit">
                                {{ __('Save Equipment') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ================= EDIT EQUIPMENT MODAL ================= -->
        <div
            x-show="showEditModal"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div
                    x-show="showEditModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="showEditModal = false"
                    class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                ></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div
                    x-show="showEditModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-start overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-gray-100 dark:border-gray-700"
                >
                    <form :action="editActionUrl" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="edit_id" :value="editId">

                        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ __('Edit Equipment') }}
                                </h3>
                            </div>
                            <button type="button" @click="showEditModal = false" class="text-gray-400 hover:text-gray-500 text-xl font-bold">
                                &times;
                            </button>
                        </div>

                        <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                            @if($errors->any() && old('_method') === 'PUT')
                                <x-alert variant="danger">
                                    <ul class="list-disc list-inside text-xs space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </x-alert>
                            @endif

                            <!-- Basic Information -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <x-input-label for="edit_full_name" :value="__('Full Name')" required />
                                    <x-text-input id="edit_full_name" name="full_name" type="text" class="mt-1 block w-full" x-model="editFullName" required />
                                    <x-input-error :messages="$errors->get('full_name')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="edit_short_name" :value="__('Short / Acronym Name')" />
                                    <x-text-input id="edit_short_name" name="short_name" type="text" class="mt-1 block w-full" x-model="editShortName" />
                                </div>

                                <div>
                                    <x-input-label for="edit_internal_code" :value="__('Storage / Internal Code')" />
                                    <x-text-input id="edit_internal_code" name="internal_code" type="text" class="mt-1 block w-full font-mono" x-model="editInternalCode" />
                                </div>

                                <div>
                                    <x-input-label for="edit_serial_number" :value="__('Serial Number (S/N)')" />
                                    <x-text-input id="edit_serial_number" name="serial_number" type="text" class="mt-1 block w-full font-mono" x-model="editSerialNumber" />
                                    <x-input-error :messages="$errors->get('serial_number')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="edit_category" :value="__('Equipment Category')" required />
                                    <select
                                        id="edit_category"
                                        name="category"
                                        x-model="editCategory"
                                        class="mt-1 block w-full py-2 px-3 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                        required
                                    >
                                        @foreach(\App\Enums\EquipmentCategory::cases() as $cat)
                                            <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <x-input-label for="edit_package" :value="__('Logistics Package / Lot')" />
                                    <select
                                        id="edit_package"
                                        name="package"
                                        x-model="editPackage"
                                        class="mt-1 block w-full py-2 px-3 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    >
                                        @foreach(\App\Enums\EquipmentPackage::cases() as $pkg)
                                            <option value="{{ $pkg->value }}">{{ $pkg->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <x-input-label for="edit_status" :value="__('Operational Status')" />
                                    <select
                                        id="edit_status"
                                        name="status"
                                        x-model="editStatus"
                                        class="mt-1 block w-full py-2 px-3 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    >
                                        @foreach(\App\Enums\EquipmentStatus::cases() as $st)
                                            <option value="{{ $st->value }}">{{ $st->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Calibration Switch -->
                            <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/40">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        name="requires_calibration"
                                        value="1"
                                        x-model="editRequiresCalibration"
                                        class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-orange-500 shadow-sm focus:ring-orange-500 dark:focus:ring-offset-gray-800 w-4 h-4"
                                    >
                                    <div>
                                        <div class="font-semibold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                                            <i class="fas fa-certificate text-orange-500"></i>
                                            <span>{{ __('Requires Periodic Metrological Calibration') }}</span>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- Physical Specifications Section -->
                            <div x-show="editRequiresCalibration" x-transition class="space-y-4">
                                <h4 class="font-bold text-sm text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center gap-2">
                                    <i class="fas fa-wave-square text-indigo-500"></i>
                                    <span>{{ __('Measurement & Generation Capabilities') }}</span>
                                </h4>

                                <!-- Measurement Parameters -->
                                @if($measurementGrandeurs->count() > 0)
                                    <div>
                                        <div class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-2 flex items-center justify-between">
                                            <span class="flex items-center gap-1.5">
                                                <i class="fas fa-signal"></i>
                                                <span>{{ __('Measurement Capabilities') }}</span>
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            @foreach($measurementGrandeurs as $g)
                                                <div
                                                    :class="editSpecs[{{ $g->id }}]?.selected
                                                        ? 'p-3 rounded-xl border-2 border-emerald-500 bg-emerald-50/75 dark:bg-emerald-950/30 shadow-md ring-2 ring-emerald-500/20'
                                                        : 'p-3 rounded-xl border border-gray-200 dark:border-gray-700/80 bg-gray-50/40 dark:bg-gray-800/40 shadow-xs opacity-75 hover:opacity-100'"
                                                    class="space-y-2.5 transition-all duration-200"
                                                >
                                                    <label class="flex items-center justify-between cursor-pointer select-none">
                                                        <span class="font-semibold text-xs flex items-center gap-1.5 flex-wrap"
                                                              :class="editSpecs[{{ $g->id }}]?.selected ? 'text-emerald-950 dark:text-emerald-100 font-bold' : 'text-gray-700 dark:text-gray-300'">
                                                            <span>{{ $g->name }}</span>
                                                            <span :class="editSpecs[{{ $g->id }}]?.selected
                                                                      ? 'bg-emerald-600 text-white border-emerald-600 font-black shadow-xs'
                                                                      : 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/20 font-bold'"
                                                                  class="text-[11px] px-1.5 py-0.5 rounded border transition-colors">
                                                                ({{ $g->symbol }})
                                                            </span>
                                                            <template x-if="editSpecs[{{ $g->id }}]?.selected">
                                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-200/90 dark:bg-emerald-900/90 text-emerald-900 dark:text-emerald-100 border border-emerald-300 dark:border-emerald-700 shadow-xs">
                                                                    <svg class="w-2.5 h-2.5 text-emerald-700 dark:text-emerald-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                                    <span>{{ __('Active') }}</span>
                                                                </span>
                                                            </template>
                                                        </span>
                                                        <input type="checkbox" name="params[{{ $g->id }}][selected]" value="1"
                                                            :checked="editSpecs[{{ $g->id }}]?.selected"
                                                            @change="editSpecs = { ...editSpecs, [{{ $g->id }}]: { ...(editSpecs[{{ $g->id }}] || {}), selected: $el.checked } }"
                                                            class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-emerald-600 focus:ring-emerald-500 dark:focus:ring-offset-gray-800 w-4 h-4 cursor-pointer">
                                                    </label>
                                                    <div class="grid grid-cols-2 gap-2" :class="editSpecs[{{ $g->id }}]?.selected ? 'opacity-100' : 'opacity-65'">
                                                        <input type="number" step="any" name="params[{{ $g->id }}][min]" placeholder="{{ __('Min') }}" :value="editSpecs[{{ $g->id }}]?.min ?? ''"
                                                            :class="editSpecs[{{ $g->id }}]?.selected ? 'bg-white dark:bg-gray-900 border-emerald-300 dark:border-emerald-700 focus:ring-emerald-500 font-medium' : 'bg-gray-100/60 dark:bg-gray-800/60 border-gray-300 dark:border-gray-600'"
                                                            class="py-1 px-2 text-xs rounded border dark:text-white transition-colors">
                                                        <input type="number" step="any" name="params[{{ $g->id }}][max]" placeholder="{{ __('Max') }}" :value="editSpecs[{{ $g->id }}]?.max ?? ''"
                                                            :class="editSpecs[{{ $g->id }}]?.selected ? 'bg-white dark:bg-gray-900 border-emerald-300 dark:border-emerald-700 focus:ring-emerald-500 font-medium' : 'bg-gray-100/60 dark:bg-gray-800/60 border-gray-300 dark:border-gray-600'"
                                                            class="py-1 px-2 text-xs rounded border dark:text-white transition-colors">
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2" :class="editSpecs[{{ $g->id }}]?.selected ? 'opacity-100' : 'opacity-65'">
                                                        <input type="number" step="any" name="params[{{ $g->id }}][acc]" placeholder="{{ __('Accuracy') }}" :value="editSpecs[{{ $g->id }}]?.acc ?? ''"
                                                            :class="editSpecs[{{ $g->id }}]?.selected ? 'bg-white dark:bg-gray-900 border-emerald-300 dark:border-emerald-700 focus:ring-emerald-500 font-medium' : 'bg-gray-100/60 dark:bg-gray-800/60 border-gray-300 dark:border-gray-600'"
                                                            class="py-1 px-2 text-xs rounded border dark:text-white transition-colors">
                                                        <select name="params[{{ $g->id }}][acc_type]"
                                                            :class="editSpecs[{{ $g->id }}]?.selected ? 'bg-white dark:bg-gray-900 border-emerald-300 dark:border-emerald-700 focus:ring-emerald-500 font-medium' : 'bg-gray-100/60 dark:bg-gray-800/60 border-gray-300 dark:border-gray-600'"
                                                            class="py-1 px-2 text-xs rounded border dark:text-white transition-colors">
                                                            <option value="%" :selected="editSpecs[{{ $g->id }}]?.acc_type === '%'">%</option>
                                                            <option value="abs" :selected="editSpecs[{{ $g->id }}]?.acc_type === 'abs'">Abs</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Source Parameters -->
                                @if($sourceGrandeurs->count() > 0)
                                    <div>
                                        <div class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider mb-2 mt-4 flex items-center justify-between">
                                            <span class="flex items-center gap-1.5">
                                                <i class="fas fa-bolt"></i>
                                                <span>{{ __('Source Capabilities') }}</span>
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            @foreach($sourceGrandeurs as $g)
                                                <div
                                                    :class="editSpecs[{{ $g->id }}]?.selected
                                                        ? 'p-3 rounded-xl border-2 border-amber-500 bg-amber-50/75 dark:bg-amber-950/30 shadow-md ring-2 ring-amber-500/20'
                                                        : 'p-3 rounded-xl border border-gray-200 dark:border-gray-700/80 bg-gray-50/40 dark:bg-gray-800/40 shadow-xs opacity-75 hover:opacity-100'"
                                                    class="space-y-2.5 transition-all duration-200"
                                                >
                                                    <label class="flex items-center justify-between cursor-pointer select-none">
                                                        <span class="font-semibold text-xs flex items-center gap-1.5 flex-wrap"
                                                              :class="editSpecs[{{ $g->id }}]?.selected ? 'text-amber-950 dark:text-amber-100 font-bold' : 'text-gray-700 dark:text-gray-300'">
                                                            <span>{{ $g->name }}</span>
                                                            <span :class="editSpecs[{{ $g->id }}]?.selected
                                                                      ? 'bg-amber-600 text-white border-amber-600 font-black shadow-xs'
                                                                      : 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/20 font-bold'"
                                                                  class="text-[11px] px-1.5 py-0.5 rounded border transition-colors">
                                                                ({{ $g->symbol }})
                                                            </span>
                                                            <template x-if="editSpecs[{{ $g->id }}]?.selected">
                                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-200/90 dark:bg-amber-900/90 text-amber-900 dark:text-amber-100 border border-amber-300 dark:border-amber-700 shadow-xs">
                                                                    <svg class="w-2.5 h-2.5 text-amber-700 dark:text-amber-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                                    <span>{{ __('Active') }}</span>
                                                                </span>
                                                            </template>
                                                        </span>
                                                        <input type="checkbox" name="params[{{ $g->id }}][selected]" value="1"
                                                            :checked="editSpecs[{{ $g->id }}]?.selected"
                                                            @change="editSpecs = { ...editSpecs, [{{ $g->id }}]: { ...(editSpecs[{{ $g->id }}] || {}), selected: $el.checked } }"
                                                            class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-amber-600 focus:ring-amber-500 dark:focus:ring-offset-gray-800 w-4 h-4 cursor-pointer">
                                                    </label>
                                                    <div class="grid grid-cols-2 gap-2" :class="editSpecs[{{ $g->id }}]?.selected ? 'opacity-100' : 'opacity-65'">
                                                        <input type="number" step="any" name="params[{{ $g->id }}][min]" placeholder="{{ __('Min') }}" :value="editSpecs[{{ $g->id }}]?.min ?? ''"
                                                            :class="editSpecs[{{ $g->id }}]?.selected ? 'bg-white dark:bg-gray-900 border-amber-300 dark:border-amber-700 focus:ring-amber-500 font-medium' : 'bg-gray-100/60 dark:bg-gray-800/60 border-gray-300 dark:border-gray-600'"
                                                            class="py-1 px-2 text-xs rounded border dark:text-white transition-colors">
                                                        <input type="number" step="any" name="params[{{ $g->id }}][max]" placeholder="{{ __('Max') }}" :value="editSpecs[{{ $g->id }}]?.max ?? ''"
                                                            :class="editSpecs[{{ $g->id }}]?.selected ? 'bg-white dark:bg-gray-900 border-amber-300 dark:border-amber-700 focus:ring-amber-500 font-medium' : 'bg-gray-100/60 dark:bg-gray-800/60 border-gray-300 dark:border-gray-600'"
                                                            class="py-1 px-2 text-xs rounded border dark:text-white transition-colors">
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2" :class="editSpecs[{{ $g->id }}]?.selected ? 'opacity-100' : 'opacity-65'">
                                                        <input type="number" step="any" name="params[{{ $g->id }}][acc]" placeholder="{{ __('Accuracy') }}" :value="editSpecs[{{ $g->id }}]?.acc ?? ''"
                                                            :class="editSpecs[{{ $g->id }}]?.selected ? 'bg-white dark:bg-gray-900 border-amber-300 dark:border-amber-700 focus:ring-amber-500 font-medium' : 'bg-gray-100/60 dark:bg-gray-800/60 border-gray-300 dark:border-gray-600'"
                                                            class="py-1 px-2 text-xs rounded border dark:text-white transition-colors">
                                                        <select name="params[{{ $g->id }}][acc_type]"
                                                            :class="editSpecs[{{ $g->id }}]?.selected ? 'bg-white dark:bg-gray-900 border-amber-300 dark:border-amber-700 focus:ring-amber-500 font-medium' : 'bg-gray-100/60 dark:bg-gray-800/60 border-gray-300 dark:border-gray-600'"
                                                            class="py-1 px-2 text-xs rounded border dark:text-white transition-colors">
                                                            <option value="%" :selected="editSpecs[{{ $g->id }}]?.acc_type === '%'">%</option>
                                                            <option value="abs" :selected="editSpecs[{{ $g->id }}]?.acc_type === 'abs'">Abs</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Media Uploads: Image & Certificate -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100 dark:border-gray-700">
                                <div>
                                    <x-input-label for="edit_image" :value="__('Change Equipment Photo')" />
                                    <input
                                        id="edit_image"
                                        name="image"
                                        type="file"
                                        accept="image/*"
                                        @change="handleImageChange($event, 'edit')"
                                        class="mt-1 block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 dark:file:bg-gray-700 dark:file:text-gray-300"
                                    >
                                    <template x-if="editImagePreview || editImageUrl">
                                        <div class="mt-2 flex items-center gap-3">
                                            <img :src="editImagePreview || editImageUrl" class="w-16 h-16 object-contain p-0.5 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                            <label class="text-xs text-rose-600 dark:text-rose-400 flex items-center gap-1 cursor-pointer">
                                                <input type="checkbox" name="remove_image" value="1" x-model="editRemoveImage" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-rose-600 focus:ring-rose-500 dark:focus:ring-offset-gray-800">
                                                <span>{{ __('Remove image') }}</span>
                                            </label>
                                        </div>
                                    </template>
                                </div>

                                <div>
                                    <x-input-label for="edit_certificate" :value="__('Change Certificate (PDF)')" />
                                    <input
                                        id="edit_certificate"
                                        name="certificate"
                                        type="file"
                                        accept=".pdf"
                                        class="mt-1 block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-300"
                                    >
                                    <template x-if="editCertificateUrl">
                                        <div class="mt-2 flex items-center gap-3">
                                            <a :href="editCertificateUrl" target="_blank" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                                                <i class="fas fa-file-pdf"></i>
                                                <span>{{ __('View Current Certificate') }}</span>
                                            </a>
                                            <label class="text-xs text-rose-600 dark:text-rose-400 flex items-center gap-1 cursor-pointer">
                                                <input type="checkbox" name="remove_certificate" value="1" x-model="editRemoveCertificate" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-rose-600 focus:ring-rose-500 dark:focus:ring-offset-gray-800">
                                                <span>{{ __('Remove') }}</span>
                                            </label>
                                        </div>
                                    </template>
                                </div>

                                <div class="sm:col-span-2">
                                    <x-input-label for="edit_designation" :value="__('Designation / Technical Notes')" />
                                    <textarea id="edit_designation" name="designation" rows="2" x-model="editDesignation" class="mt-1 block w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                            <x-secondary-button type="button" @click="showEditModal = false">
                                {{ __('Cancel') }}
                            </x-secondary-button>
                            <x-primary-button type="submit">
                                {{ __('Update Equipment') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ================= DELETE CONFIRMATION MODAL ================= -->
        <x-crud-modal.delete
            show="showDeleteModal"
            action-url="deleteActionUrl"
            item-name="deleteName"
            :title="__('Delete Equipment')"
        />
    </div>
</x-app-layout>
