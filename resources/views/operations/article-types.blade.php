<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
             x-data
             @open-create-item-type-modal.window="window.dispatchEvent(new CustomEvent('open-create-item-type-modal-inner'))">
            <div class="flex items-center gap-3.5">
                <x-tool-icon name="article-types" class="w-11 h-11 sm:w-12 sm:h-12 shrink-0" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Classification of Articles') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Contract product and item classifications') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="info" size="md">
                    {{ $itemTypes->total() }} {{ __('Article Types') }}
                </x-badge>
                @can('create article types')
                    <x-primary-button
                        type="button"
                        x-data
                        @click="$dispatch('open-create-item-type-modal')"
                        onclick="window.dispatchEvent(new CustomEvent('open-create-item-type-modal'))"
                        class="flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>{{ __('New Article Type') }}</span>
                    </x-primary-button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8"
        x-data="{
        showCreateModal: {{ $errors->has('designation') && !old('_method') ? 'true' : 'false' }},
        showEditModal: {{ $errors->has('designation') && old('_method') === 'PUT' ? 'true' : 'false' }},
        showDeleteModal: false,

        editItemTypeId: {{ old('_method') === 'PUT' ? (int) old('edit_item_type_id', 0) : 'null' }},
        editDesignation: '{{ old('_method') === 'PUT' ? addslashes((string) old('designation', '')) : '' }}',
        editFormAction: '',

        deleteItemTypeId: null,
        deleteItemTypeDesignation: '',
        deleteFormAction: '',

        openEditModal(id, designation, action) {
            this.editItemTypeId    = id;
            this.editDesignation   = designation;
            this.editFormAction    = action;
            this.showEditModal     = true;
        },
        openDeleteModal(id, designation, action) {
            this.deleteItemTypeId          = id;
            this.deleteItemTypeDesignation = designation;
            this.deleteFormAction          = action;
            this.showDeleteModal           = true;
        },
    }"
        x-init="window.addEventListener('open-create-item-type-modal', () => { showCreateModal = true; })">

        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">

                {{-- Sidebar --}}
                <aside class="w-full lg:w-64 shrink-0">
                    <x-operations-tabs active="article-types" />
                </aside>

                {{-- Main Content --}}
                <main class="flex-1 w-full min-w-0 space-y-6">

                    {{-- Alerts --}}
                    @if(session('success'))
                        <x-alert variant="success">{{ session('success') }}</x-alert>
                    @endif
                    @if(session('error'))
                        <x-alert variant="danger">{{ session('error') }}</x-alert>
                    @endif
                    @if(session('warning'))
                        <x-alert variant="warning">{{ session('warning') }}</x-alert>
                    @endif
                    @if(session('info'))
                        <x-alert variant="info">{{ session('info') }}</x-alert>
                    @endif

                    <x-table>
                        <x-slot:toolbar>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                                    <x-tool-icon name="article-types" class="w-6 h-6 shrink-0" />
                                    <span>{{ __('Classification of Articles') }}</span>
                                </h3>

                                <x-global-filter
                                    :action="route('operations.article-types')"
                                    :search="true"
                                    :search-placeholder="__('Search by designation...')"
                                    :search-value="request('search')"
                                    :submit-text="__('Search')"
                                />
                            </div>
                        </x-slot:toolbar>

                        <x-slot:header>
                            <x-table.th>ID</x-table.th>
                            <x-table.th>{{ __('Designation') }}</x-table.th>
                            <x-table.th>{{ __('Used in Contracts') }}</x-table.th>
                            <x-table.th>{{ __('Created At') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                        </x-slot:header>

                        @forelse($itemTypes as $itemType)
                            <x-table.tr>
                                <x-table.td class="font-mono text-xs font-semibold text-gray-500 dark:text-gray-400">
                                    <bdi>#{{ $itemType->id }}</bdi>
                                </x-table.td>

                                <x-table.td>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $itemType->designation }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    @if($itemType->contract_items_count > 0)
                                        <x-badge variant="info">
                                            <bdi class="font-mono">{{ $itemType->contract_items_count }}</bdi> {{ __('items') }}
                                        </x-badge>
                                    @else
                                        <x-badge variant="neutral">
                                            {{ __('Unused') }}
                                        </x-badge>
                                    @endif
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span class="font-mono"><bdi>{{ $itemType->created_at->format('d/m/Y') }}</bdi></span>
                                    </div>
                                </x-table.td>

                                <x-table.td class="text-end">
                                    <x-table.actions>
                                        @can('edit article types')
                                            <x-table.action-edit
                                                type="button"
                                                :title="__('Edit Article Type')"
                                                @click="openEditModal(
                                                    {{ $itemType->id }},
                                                    '{{ addslashes((string) $itemType->designation) }}',
                                                    '{{ route('operations.article-types.update', array_merge(['item_type' => $itemType->id], request()->query())) }}'
                                                )"
                                            />
                                        @endcan

                                        @can('delete article types')
                                            <x-table.action-delete
                                                type="button"
                                                :title="__('Delete Article Type')"
                                                @click="openDeleteModal(
                                                    {{ $itemType->id }},
                                                    '{{ addslashes((string) $itemType->designation) }}',
                                                    '{{ route('operations.article-types.destroy', array_merge(['item_type' => $itemType->id], request()->query())) }}'
                                                )"
                                            />
                                        @endcan
                                    </x-table.actions>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty :colspan="5" :message="__('No article types found.')" />
                        @endforelse

                        <x-slot:pagination>
                            {{ $itemTypes->links() }}
                        </x-slot:pagination>
                    </x-table>
                </main>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- CREATE MODAL                                           --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <div x-show="showCreateModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showCreateModal = false"></div>

                <div x-show="showCreateModal"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     class="relative inline-block w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl text-left shadow-xl border border-gray-100 dark:border-gray-700/60 overflow-hidden z-10">

                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-brand-600/10 dark:bg-brand-500/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('New Article Type') }}</h3>
                        </div>
                        <button type="button" @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Modal Form --}}
                    <form method="POST" action="{{ route('operations.article-types.store', request()->query()) }}">
                        @csrf
                        <div class="px-6 py-5 space-y-4">
                            <div>
                                <x-input-label for="create_designation" :value="__('Designation') . ' *'" />
                                <x-text-input id="create_designation" name="designation" type="text"
                                    class="mt-1 block w-full" :value="old('designation')"
                                    placeholder="{{ __('e.g. Instrumentation, Calibration, Maintenance...') }}"
                                    required autofocus />
                                <x-input-error :messages="$errors->get('designation')" class="mt-1" />
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700/60">
                            <x-secondary-button type="button" @click="showCreateModal = false">
                                {{ __('Cancel') }}
                            </x-secondary-button>
                            <x-primary-button type="submit" class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ __('Create Article Type') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- EDIT MODAL                                             --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <div x-show="showEditModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showEditModal = false"></div>

                <div x-show="showEditModal"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     class="relative inline-block w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl text-left shadow-xl border border-gray-100 dark:border-gray-700/60 overflow-hidden z-10">

                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 dark:bg-amber-500/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Edit Article Type') }}</h3>
                        </div>
                        <button type="button" @click="showEditModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Modal Form --}}
                    <form method="POST" :action="editFormAction">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="edit_item_type_id" :value="editItemTypeId" />

                        <div class="px-6 py-5 space-y-4">
                            <div>
                                <x-input-label for="edit_designation" :value="__('Designation') . ' *'" />
                                <x-text-input id="edit_designation" name="designation" type="text"
                                    class="mt-1 block w-full" x-model="editDesignation" required />
                                <x-input-error :messages="$errors->get('designation')" class="mt-1" />
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700/60">
                            <x-secondary-button type="button" @click="showEditModal = false">
                                {{ __('Cancel') }}
                            </x-secondary-button>
                            <x-primary-button type="submit" class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ __('Save Changes') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- DELETE MODAL                                           --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <x-crud-modal.delete
            show="showDeleteModal"
            action-url="deleteFormAction"
            item-name="deleteItemTypeDesignation"
            :title="__('Delete Article Type')"
        >
            <div class="px-6 pb-2">
                <p class="text-xs text-amber-600 dark:text-amber-400">
                    {{ __('Deletion will be blocked if this type is linked to contract items.') }}
                </p>
            </div>
        </x-crud-modal.delete>
    </div>
</x-app-layout>
