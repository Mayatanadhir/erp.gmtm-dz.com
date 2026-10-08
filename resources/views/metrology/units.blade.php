<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <x-tool-icon name="units" class="w-11 h-11 sm:w-12 sm:h-12 shrink-0" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Quantities & Units') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Measurement units, conversion rates, and dimensional standards') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @can('create quantities units')
                    <x-primary-button type="button" x-data="" @click="$dispatch('open-modal', 'create-grandeur-modal')" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>{{ __('Add Quantity / Unit') }}</span>
                    </x-primary-button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        editModalOpen: false,
        deleteModalOpen: false,
        editGrandeur: { id: null, name: '', symbol: '', type: 'measurement' },
        deleteGrandeur: { id: null, name: '', symbol: '', specsCount: 0 },
        editUrl: '',
        deleteUrl: '',
        openEdit(item) {
            this.editGrandeur = { id: item.id, name: item.name, symbol: item.symbol, type: item.type };
            this.editUrl = '{{ route('metrology.units.update', array_merge(['grandeur' => ':id'], request()->query())) }}'.replace(':id', item.id);
            $dispatch('open-modal', 'edit-grandeur-modal');
        },
        openDelete(item) {
            this.deleteGrandeur = { id: item.id, name: item.name, symbol: item.symbol, specsCount: item.specifications_count || 0 };
            this.deleteUrl = '{{ route('metrology.units.destroy', array_merge(['grandeur' => ':id'], request()->query())) }}'.replace(':id', item.id);
            this.deleteModalOpen = true;
        }
    }">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Global Flash Alerts -->
            @if(session('success'))
                <x-alert variant="success">{{ session('success') }}</x-alert>
            @endif
            @if(session('error'))
                <x-alert variant="danger">{{ session('error') }}</x-alert>
            @endif
            @if(session('warning'))
                <x-alert variant="warning">{{ session('warning') }}</x-alert>
            @endif

            <!-- 4-Card KPI Counter Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Total Quantities') }}</p>
                        <p class="text-2xl font-black text-gray-900 dark:text-white mt-1.5">{{ $stats['total'] }}</p>
                        <p class="text-xs text-brand-600 dark:text-brand-400 mt-0.5 font-medium">{{ __('Standard Units Catalog') }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-brand-500/10 dark:bg-brand-500/20 text-brand-600 dark:text-brand-400 border border-brand-500/20 dark:border-brand-500/30 flex items-center justify-center shrink-0">
                        <i class="fas fa-balance-scale text-xl"></i>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Measurement Capabilities') }}</p>
                        <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1.5">{{ $stats['measurement'] }}</p>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-0.5 font-medium">{{ __('Sensors / Input') }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 dark:border-emerald-500/30 flex items-center justify-center shrink-0">
                        <i class="fas fa-compress-arrows-alt text-xl"></i>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Source Capabilities') }}</p>
                        <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1.5">{{ $stats['source'] }}</p>
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5 font-medium">{{ __('Generators / Output') }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-500/10 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/20 dark:border-amber-500/30 flex items-center justify-center shrink-0">
                        <i class="fas fa-expand-arrows-alt text-xl"></i>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Configured Specs') }}</p>
                        <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1.5">{{ $stats['linked_specs'] }}</p>
                        <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-0.5 font-medium">{{ __('Active Metrology Limits') }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 dark:border-indigo-500/30 flex items-center justify-center shrink-0">
                        <i class="fas fa-cogs text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-metrology-tabs active="units" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-6">
                    <!-- Global Filter Toolbar -->
                    <x-global-filter
                        :action="route('metrology.units')"
                        :search="request('search', request('q'))"
                        :placeholder="__('Search by quantity name, symbol...')"
                    >
                        <div>
                            <select
                                name="type"
                                onchange="this.form.submit()"
                                class="w-full sm:w-48 text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 focus:border-brand-500 focus:ring-brand-500 shadow-sm"
                            >
                                <option value="">{{ __('All Operation Types') }}</option>
                                @foreach(\App\Enums\GrandeurType::cases() as $typeCase)
                                    <option value="{{ $typeCase->value }}" @selected(request('type') === $typeCase->value)>
                                        {{ $typeCase->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </x-global-filter>

                    <!-- Table Card -->
                    <x-table>
                        <x-slot:toolbar>
                            <div class="flex items-center justify-between w-full">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                                    <x-tool-icon name="units" class="w-6 h-6 shrink-0" />
                                    <span>{{ __('Quantities & Units Catalog') }}</span>
                                    <span class="text-xs px-2 py-0.5 font-normal rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                                        {{ $grandeurs->total() }} {{ __('Records') }}
                                    </span>
                                </h3>
                            </div>
                        </x-slot:toolbar>

                        <x-slot:header>
                            <x-table.th class="w-16">ID</x-table.th>
                            <x-table.th>{{ __('Quantity Name') }}</x-table.th>
                            <x-table.th class="text-center">{{ __('Unit Symbol') }}</x-table.th>
                            <x-table.th class="text-center">{{ __('Operation Type') }}</x-table.th>
                            <x-table.th class="text-center">{{ __('Equipment Specs') }}</x-table.th>
                            <x-table.th>{{ __('Created At') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                        </x-slot:header>

                        @forelse ($grandeurs as $item)
                            <x-table.tr>
                                <x-table.td class="font-mono text-xs text-gray-500 dark:text-gray-400">
                                    #{{ $item->id }}
                                </x-table.td>
                                <x-table.td>
                                    <div class="flex items-center gap-3">
                                        <x-grandeur-icon :grandeur="$item" size="md" :withBackground="true" />
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white text-sm">
                                                {{ $item->name }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                                {{ $item->discipline()->label() }}
                                            </p>
                                        </div>
                                    </div>
                                </x-table.td>
                                <x-table.td class="text-center">
                                    <span class="inline-flex items-center font-mono font-bold text-xs px-2.5 py-1 rounded-lg border {{ $item->discipline()->badgeClass() }}">
                                        {{ $item->symbol }}
                                    </span>
                                </x-table.td>
                                <x-table.td class="text-center">
                                    <x-badge :variant="$item->type->badgeVariant()">
                                        <i class="fas {{ $item->type === \App\Enums\GrandeurType::Measurement ? 'fa-compress-arrows-alt' : 'fa-expand-arrows-alt' }} me-1"></i>
                                        {{ $item->type->label() }}
                                    </x-badge>
                                </x-table.td>
                                <x-table.td class="text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $item->specifications_count > 0 ? 'bg-indigo-50 text-indigo-700 border-indigo-200/60 dark:bg-indigo-500/20 dark:text-indigo-300 dark:border-indigo-500/30' : 'bg-gray-100 text-gray-500 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700' }}">
                                        <i class="fas fa-link text-[10px]"></i>
                                        <span>{{ $item->specifications_count }}</span>
                                    </span>
                                </x-table.td>
                                <x-table.td class="text-xs text-gray-500 dark:text-gray-400">
                                    <x-date :value="$item->created_at" />
                                </x-table.td>
                                <x-table.td class="text-end">
                                    <x-table.actions>
                                        @can('edit quantities units')
                                            @php
                                                $unitPayload = [
                                                    'id' => $item->id,
                                                    'name' => $item->name,
                                                    'symbol' => $item->symbol,
                                                    'type' => $item->type->value,
                                                ];
                                                $unitJson = base64_encode(json_encode($unitPayload));
                                            @endphp
                                            <x-table.action-edit
                                                type="button"
                                                :title="__('Edit Quantity / Unit')"
                                                data-item="{{ $unitJson }}"
                                                @click="openEdit(JSON.parse(atob($el.dataset.item)))"
                                            />
                                        @endcan

                                        @can('delete quantities units')
                                            @php
                                                $deletePayload = [
                                                    'id' => $item->id,
                                                    'name' => $item->name,
                                                    'symbol' => $item->symbol,
                                                    'specifications_count' => (int) $item->specifications_count,
                                                ];
                                                $deleteJson = base64_encode(json_encode($deletePayload));
                                            @endphp
                                            <x-table.action-delete
                                                type="button"
                                                :title="__('Delete Quantity / Unit')"
                                                data-item="{{ $deleteJson }}"
                                                @click="openDelete(JSON.parse(atob($el.dataset.item)))"
                                            />
                                        @endcan
                                    </x-table.actions>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty :colspan="7" :message="__('No physical quantities or measurement units found.')" />
                        @endforelse
                    </x-table>

                    <!-- Pagination -->
                    @if($grandeurs->hasPages())
                        <div class="mt-4">
                            {{ $grandeurs->links() }}
                        </div>
                    @endif
                </main>
            </div>
        </div>

        <!-- Create Modal -->
        <x-modal name="create-grandeur-modal" maxWidth="md" focusable>
            <form method="POST" action="{{ route('metrology.units.store', request()->query()) }}" class="p-6">
                @csrf
                <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-plus-circle text-brand-600"></i>
                        <span>{{ __('Add New Quantity / Unit') }}</span>
                    </h3>
                    <button type="button" @click="$dispatch('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div>
                        <x-input-label for="create_name" :value="__('Quantity Name')" />
                        <x-text-input id="create_name" name="name" type="text" class="mt-1 block w-full text-sm" placeholder="e.g. Pression, Température, Débit" required />
                    </div>

                    <div>
                        <x-input-label for="create_symbol" :value="__('Unit Symbol')" />
                        <x-text-input id="create_symbol" name="symbol" type="text" class="mt-1 block w-full text-sm font-mono" placeholder="e.g. bar, °C, mA, V, m³/h" required />
                    </div>

                    <div>
                        <x-input-label for="create_type" :value="__('Operation Type')" />
                        <select id="create_type" name="type" class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 focus:border-brand-500 focus:ring-brand-500 shadow-sm" required>
                            @foreach(\App\Enums\GrandeurType::cases() as $typeCase)
                                <option value="{{ $typeCase->value }}">
                                    {{ $typeCase->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <x-secondary-button type="button" @click="$dispatch('close')">
                        {{ __('Cancel') }}
                    </x-secondary-button>
                    <x-primary-button type="submit">
                        {{ __('Save Quantity') }}
                    </x-primary-button>
                </div>
            </form>
        </x-modal>

        <!-- Edit Modal -->
        <x-modal name="edit-grandeur-modal" maxWidth="md" focusable>
            <form method="POST" :action="editUrl" class="p-6">
                @csrf
                @method('PUT')
                <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-edit text-brand-600"></i>
                        <span>{{ __('Edit Quantity / Unit') }}</span>
                    </h3>
                    <button type="button" @click="$dispatch('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div>
                        <x-input-label for="edit_name" :value="__('Quantity Name')" />
                        <x-text-input id="edit_name" name="name" type="text" x-model="editGrandeur.name" class="mt-1 block w-full text-sm" required />
                    </div>

                    <div>
                        <x-input-label for="edit_symbol" :value="__('Unit Symbol')" />
                        <x-text-input id="edit_symbol" name="symbol" type="text" x-model="editGrandeur.symbol" class="mt-1 block w-full text-sm font-mono" required />
                    </div>

                    <div>
                        <x-input-label for="edit_type" :value="__('Operation Type')" />
                        <select id="edit_type" name="type" x-model="editGrandeur.type" class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 focus:border-brand-500 focus:ring-brand-500 shadow-sm" required>
                            @foreach(\App\Enums\GrandeurType::cases() as $typeCase)
                                <option value="{{ $typeCase->value }}">
                                    {{ $typeCase->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <x-secondary-button type="button" @click="$dispatch('close')">
                        {{ __('Cancel') }}
                    </x-secondary-button>
                    <x-primary-button type="submit">
                        {{ __('Update Quantity') }}
                    </x-primary-button>
                </div>
            </form>
        </x-modal>

        <!-- Delete Modal -->
        <x-crud-modal.delete
            show="deleteModalOpen"
            action-url="deleteUrl"
            item-name="deleteGrandeur.name"
            :title="__('Delete Quantity / Unit')"
        >
            <template x-if="deleteGrandeur.specsCount > 0">
                <div class="px-6 pb-2">
                    <div class="p-3 rounded-lg bg-amber-500/10 dark:bg-amber-500/20 border border-amber-500/20 dark:border-amber-500/30 text-amber-800 dark:text-amber-300 text-xs flex items-start gap-2">
                        <i class="fas fa-lock mt-0.5 shrink-0"></i>
                        <span>
                            {{ __('This quantity is currently linked to equipment specifications. Deleting it will be rejected by the system to prevent data corruption.') }}
                        </span>
                    </div>
                </div>
            </template>
        </x-crud-modal.delete>
    </div>
</x-app-layout>
