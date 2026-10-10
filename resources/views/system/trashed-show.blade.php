<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-300">
                        {{ __('Danger Zone') }}
                    </span>
                    <a href="{{ route('system-tables.trashed') }}" class="text-xs text-brand-600 dark:text-brand-400 hover:underline flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        <span>{{ __('Back to Trashed Tables') }}</span>
                    </a>
                </div>
                <h2 class="font-extrabold text-2xl text-gray-900 dark:text-white leading-tight flex items-center gap-2.5">
                    <svg class="w-7 h-7 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    <span>{{ __('Preview Trashed Records') }}: <span class="font-mono text-rose-600 dark:text-rose-400">{{ $trashedData['table'] }}</span></span>
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('Inspecting soft-deleted rows from table :table (:model). You can examine record values or permanently purge them.', ['table' => $trashedData['table'], 'model' => $trashedData['model'] ?? $trashedData['table']]) }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if(($trashedData['total_trashed'] ?? 0) > 0)
                    <x-badge variant="danger" size="md" :dot="true" :dot-ping="true">
                        {{ number_format($trashedData['total_trashed']) }} {{ __('Soft-Deleted Records') }}
                    </x-badge>
                @else
                    <x-badge variant="success" size="md" :dot="true">
                        {{ __('0 Trashed Records (Clean)') }}
                    </x-badge>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        records: @js($trashedData['records'] ?? []),
        columns: @js($trashedData['columns'] ?? []),
        tableName: @js($trashedData['table']),
        totalCount: @js((int) ($trashedData['total_trashed'] ?? 0)),
        primaryKey: @js($trashedData['primary_key'] ?? 'id'),

        searchQuery: '',
        purgeSingleLoadingId: null,
        showPurgeTableModal: false,
        purgeConfirmationInput: '',
        isSubmitting: false,

        purgeSingleRoute: @js(route('system-tables.trashed.purge-single', ['table' => '___TBL___', 'id' => '___ID___'], false)),
        purgeTableRoute: @js(route('system-tables.trashed.purge-table', ['table' => '___TBL___'], false)),
        csrfToken: @js(csrf_token()),

        msgPurgeSingleConfirm: @js(__('Are you sure you want to permanently delete this record? This action cannot be undone.')),
        msgDeleteError: @js(__('Error deleting record')),
        msgRequestFailed: @js(__('Request failed. Check server logs.')),

        // Single place that resolves a row's key (handles key = 0 correctly)
        keyOf(record) {
            return record[this.primaryKey] ?? record.id;
        },

        get filteredRecords() {
            const q = this.searchQuery.trim().toLowerCase();
            if (!q) {
                return this.records;
            }
            return this.records.filter(r =>
                Object.values(r).some(val => val !== null && val !== undefined && String(val).toLowerCase().includes(q))
            );
        },

        get purgeTableAction() {
            return this.purgeTableRoute.replace('___TBL___', encodeURIComponent(this.tableName));
        },

        get canPurgeTable() {
            return !this.isSubmitting && this.purgeConfirmationInput.trim().toUpperCase() === 'PURGE';
        },

        init() {
            this.$watch('showPurgeTableModal', (open) => document.body.classList.toggle('overflow-hidden', open));
        },

        openPurgeTableModal() {
            this.purgeConfirmationInput = '';
            this.isSubmitting = false;
            this.showPurgeTableModal = true;
        },

        async purgeSingleRecord(id) {
            if (!confirm(this.msgPurgeSingleConfirm)) {
                return;
            }
            this.purgeSingleLoadingId = id;
            try {
                const url = this.purgeSingleRoute
                    .replace('___TBL___', encodeURIComponent(this.tableName))
                    .replace('___ID___', encodeURIComponent(id));
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const result = await res.json().catch(() => ({}));
                if (res.ok && result.success) {
                    this.records = this.records.filter(r => String(this.keyOf(r)) !== String(id));
                    this.totalCount = Math.max(0, this.totalCount - 1);
                } else {
                    alert(result.message || this.msgDeleteError);
                }
            } catch (err) {
                console.error(err);
                alert(this.msgRequestFailed);
            } finally {
                this.purgeSingleLoadingId = null;
            }
        }
    }">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-system-tabs active="trashed" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-6">

                    @if ($errors->any())
                        <x-alert variant="danger">
                            {{ $errors->first() }}
                        </x-alert>
                    @endif

                    @if (session('status'))
                        <x-alert variant="success">
                            {{ session('status') }}
                        </x-alert>
                    @endif

                    <!-- Safety Warning Card -->
                    <div class="rounded-2xl p-4 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/60 flex flex-col sm:flex-row sm:items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div class="flex-1 text-xs">
                            <h4 class="font-bold text-rose-800 dark:text-rose-200">{{ __('Critical Preview Screen') }}</h4>
                            <p class="text-rose-700/80 dark:text-rose-300/80 mt-0.5">
                                {{ __('These records are currently invisible in normal ERP screens because deleted_at is set. Permanent deletion directly deletes them from the SQL database.') }}
                            </p>
                        </div>
                        <div class="shrink-0 flex items-center gap-2">
                            <a href="{{ route('system-tables.trashed') }}"
                               class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 transition-colors">
                                {{ __('Back to Tables') }}
                            </a>
                            <template x-if="totalCount > 0">
                                <x-danger-button type="button" @click="openPurgeTableModal()">
                                    <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    {{ __('Hard Purge Table') }}
                                </x-danger-button>
                            </template>
                        </div>
                    </div>

                    <!-- Records Table Card -->
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-lg font-mono text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200" x-text="tableName"></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    <span class="font-bold text-rose-600 dark:text-rose-400" x-text="filteredRecords.length"></span>
                                    <span>{{ __('records loaded') }}</span>
                                </span>
                            </div>

                            <div class="w-full sm:w-64">
                                <input type="text"
                                       x-model="searchQuery"
                                       placeholder="{{ __('Search loaded records...') }}"
                                       class="w-full rounded-xl text-xs border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-brand-600">
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="mt-4">
                            <template x-if="filteredRecords.length === 0">
                                <div class="text-center py-16 bg-gray-50 dark:bg-gray-900/40 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                                    <svg class="w-12 h-12 mx-auto text-emerald-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('No soft-deleted records match your criteria.') }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ __('This table has no trashed records waiting for permanent purge.') }}</p>
                                </div>
                            </template>

                            <template x-if="filteredRecords.length > 0">
                                <div class="overflow-x-auto border border-gray-100 dark:border-gray-700/60 rounded-xl">
                                    <table class="w-full text-xs text-start">
                                        <thead class="bg-gray-50 dark:bg-gray-900/80 sticky top-0 text-gray-600 dark:text-gray-300 font-semibold border-b border-gray-100 dark:border-gray-700 font-mono">
                                            <tr>
                                                <template x-for="col in columns" :key="col">
                                                    <th class="p-3 text-start" x-text="col"></th>
                                                </template>
                                                <th class="p-3 text-end font-sans">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 font-mono">
                                            <template x-for="record in filteredRecords" :key="keyOf(record)">
                                                <tr class="hover:bg-rose-50/30 dark:hover:bg-rose-950/20 transition-colors">
                                                    <template x-for="col in columns" :key="col">
                                                        <td class="p-3 text-gray-800 dark:text-gray-200 truncate max-w-xs" :title="record[col]">
                                                            <span :class="col === 'deleted_at' ? 'text-rose-600 dark:text-rose-400 font-bold' : ''"
                                                                  x-text="record[col] !== null && record[col] !== undefined ? record[col] : 'NULL'"></span>
                                                        </td>
                                                    </template>
                                                    <td class="p-3 text-end whitespace-nowrap font-sans">
                                                        <button type="button"
                                                                @click="purgeSingleRecord(keyOf(record))"
                                                                :disabled="purgeSingleLoadingId !== null"
                                                                class="inline-flex items-center px-2.5 py-1 rounded bg-rose-50 dark:bg-rose-950/40 text-rose-600 hover:bg-rose-600 hover:text-white dark:hover:bg-rose-600 transition-colors text-xs font-semibold disabled:opacity-50"
                                                                title="{{ __('Permanently delete this row') }}">
                                                            <template x-if="purgeSingleLoadingId === keyOf(record)">
                                                                <span class="inline-block animate-spin w-3 h-3 border-2 border-rose-600 border-t-transparent rounded-full me-1"></span>
                                                            </template>
                                                            <span>{{ __('Force Delete') }}</span>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <!-- Purge Table Modal -->
        <div x-show="showPurgeTableModal"
             x-cloak
             @keydown.escape.window="showPurgeTableModal = false"
             class="fixed inset-0 overflow-y-auto"
             style="z-index: 99999;"
             aria-labelledby="purge-table-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showPurgeTableModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-gray-900/70 backdrop-blur-sm transition-opacity"
                     @click="showPurgeTableModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showPurgeTableModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-start overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border-2 border-rose-500/50 p-6 relative z-10"
                     @click.stop>
                    <div class="flex items-center gap-3 pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div class="w-10 h-10 rounded-xl bg-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div>
                            <h3 id="purge-table-modal-title" class="text-base font-bold text-gray-900 dark:text-white">
                                {{ __('Confirm Permanent Table Purge') }}
                            </h3>
                            <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold font-mono">
                                {{ __('Target:') }} <span x-text="tableName"></span> (<span x-text="totalCount.toLocaleString()"></span> {{ __('records') }})
                            </p>
                        </div>
                    </div>

                    <div class="my-4 space-y-3">
                        <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 text-xs text-rose-800 dark:text-rose-200 leading-relaxed">
                            <p class="font-bold mb-1">{{ __('WARNING: This action is permanent and irreversible!') }}</p>
                            <p>{{ __('All soft-deleted records in this table will be deleted from the database. They cannot be restored.') }}</p>
                        </div>

                        <div>
                            <label for="purge-confirm-input" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Type "PURGE" below to confirm:') }}
                            </label>
                            <input type="text"
                                   id="purge-confirm-input"
                                   autocomplete="off"
                                   x-model="purgeConfirmationInput"
                                   placeholder="PURGE"
                                   class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white text-sm focus:border-rose-600 focus:ring-rose-600 font-mono text-center uppercase tracking-widest font-bold">
                        </div>
                    </div>

                    <form method="POST"
                          :action="purgeTableAction"
                          @submit="if (!canPurgeTable) { $event.preventDefault(); return; } isSubmitting = true;"
                          class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                        @csrf
                        <input type="hidden" name="confirmation" :value="purgeConfirmationInput">
                        <x-secondary-button type="button" @click="showPurgeTableModal = false">
                            {{ __('Cancel') }}
                        </x-secondary-button>
                        <x-danger-button type="submit" x-bind:disabled="!canPurgeTable">
                            {{ __('Purge Table Now') }}
                        </x-danger-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>