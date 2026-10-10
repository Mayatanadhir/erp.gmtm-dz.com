@php
    // Shared icon paths (used several times below)
    $trashPath = 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16';
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-300">
                        {{ __('Danger Zone') }}
                    </span>
                    <a href="{{ route('system-tables.pruning') }}" class="text-xs text-brand-600 dark:text-brand-400 hover:underline flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        <span>{{ __('Back to Data Pruning') }}</span>
                    </a>
                </div>
                <h2 class="font-extrabold text-2xl text-gray-900 dark:text-white leading-tight flex items-center gap-2.5">
                    <svg class="w-7 h-7 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $trashPath }}"></path></svg>
                    <span>{{ __('Deep Trashed Inspector & Permanent Purge') }}</span>
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('Isolate, inspect, and permanently hard-delete soft-deleted database records to reclaim storage and eliminate residual data.') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if(($totalTrashedCount ?? 0) > 0)
                    <x-badge variant="danger" size="md" :dot="true" :dot-ping="true">
                        {{ number_format($totalTrashedCount) }} {{ __('Trashed Records in DB') }}
                    </x-badge>
                @else
                    <x-badge variant="success" size="md" :dot="true">
                        {{ __('0 Trashed Records (Clean)') }}
                    </x-badge>
                @endif
            </div>
        </div>
    </x-slot>

    {{--
        This page only lists tables and handles the two bulk purges (single table / system-wide)
        through ONE shared confirmation modal. Row inspection and single-row deletion live
        exclusively on the dedicated "show" page (system-tables.trashed.show).
    --}}
    <div class="py-8" x-data="{
        showPurgeModal: false,
        purgeMode: 'table',      // 'table' | 'all'
        purgeTable: '',
        purgeCount: 0,
        purgeInput: '',
        isSubmitting: false,

        totalTrashed: @js((int) ($totalTrashedCount ?? 0)),
        purgeTableRoute: @js(route('system-tables.trashed.purge-table', ['table' => '___TBL___'], false)),
        purgeAllRoute: @js(route('system-tables.trashed.purge-all', [], false)),

        texts: @js([
            'table' => [
                'title'    => __('Confirm Permanent Table Purge'),
                'target'   => __('Target:'),
                'records'  => __('records'),
                'warning'  => __('WARNING: This action is permanent and irreversible!'),
                'detail'   => __('All soft-deleted records in this table will be deleted from the database. They cannot be restored.'),
                'note'     => '',
                'label'    => __('Type "PURGE" below to confirm:'),
                'word'     => 'PURGE',
                'submit'   => __('Purge Table Now'),
            ],
            'all' => [
                'title'    => __('CRITICAL: System-Wide Hard Purge'),
                'subtitle' => __('Permanent erasure across all database tables.'),
                'warning'  => __('DANGER: Total Data Destruction!'),
                'detail'   => __('This will permanently delete all :count soft-deleted records across EVERY table in the system.'),
                'note'     => __('Sovereign tables like users, roles and permissions are always protected.'),
                'label'    => __('Type "FORCE DELETE" to confirm total wipeout:'),
                'word'     => 'FORCE DELETE',
                'submit'   => __('Execute System-Wide Purge'),
            ],
        ]),

        get t() { return this.texts[this.purgeMode]; },
        get isAll() { return this.purgeMode === 'all'; },
        get subtitle() {
            return this.isAll
                ? this.t.subtitle
                : this.t.target + ' ' + this.purgeTable + ' (' + this.purgeCount.toLocaleString() + ' ' + this.t.records + ')';
        },
        get detail() { return this.t.detail.replace(':count', this.purgeCount.toLocaleString()); },
        get purgeAction() {
            return this.isAll
                ? this.purgeAllRoute
                : this.purgeTableRoute.replace('___TBL___', encodeURIComponent(this.purgeTable));
        },
        get canSubmit() {
            return !this.isSubmitting && this.purgeInput.trim().toUpperCase() === this.t.word;
        },

        init() {
            this.$watch('showPurgeModal', (open) => document.body.classList.toggle('overflow-hidden', open));
        },

        openPurge(mode, table = '', count = 0) {
            this.purgeMode = mode;
            this.purgeTable = table;
            this.purgeCount = mode === 'all' ? this.totalTrashed : Number(count);
            this.purgeInput = '';
            this.isSubmitting = false;
            this.showPurgeModal = true;
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

                    <!-- Stat Summary Cards Row -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-rose-100 dark:border-rose-950/40 relative overflow-hidden">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">{{ __('Total Trashed Records') }}</span>
                                <div class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $trashPath }}"></path></svg>
                                </div>
                            </div>
                            <div class="mt-3 flex items-baseline gap-2">
                                <span class="text-3xl font-extrabold font-mono text-gray-900 dark:text-white">{{ number_format($totalTrashedCount ?? 0) }}</span>
                                <span class="text-xs text-gray-400">{{ __('rows awaiting purge') }}</span>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ __('Soft-Delete Tables') }}</span>
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm0 5h16M9 4v16"></path></svg>
                                </div>
                            </div>
                            <div class="mt-3 flex items-baseline gap-2">
                                <span class="text-3xl font-extrabold font-mono text-gray-900 dark:text-white">{{ count($trashedTables ?? []) }}</span>
                                <span class="text-xs text-gray-400">{{ __('detected database entities') }}</span>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-emerald-100 dark:border-emerald-950/40">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">{{ __('Protection Shield') }}</span>
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                </div>
                            </div>
                            <div class="mt-3 flex items-baseline gap-2">
                                <span class="text-sm font-bold text-emerald-700 dark:text-emerald-400">{{ __('Sovereign Guard Active') }}</span>
                                <span class="text-xs text-gray-400">{{ __('(Users & Roles Protected)') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Main Trashed Management Table Card -->
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-sm border-2 border-rose-200 dark:border-rose-900/60 relative overflow-hidden">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-5 border-b border-rose-100 dark:border-rose-900/40">
                            <div>
                                <h3 class="text-lg font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $trashPath }}"></path></svg>
                                    <span>{{ __('Target Database Entities') }}</span>
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ __('These tables hold records deleted via user actions in the ERP interfaces but retained inside database tables.') }}
                                </p>
                            </div>

                            @if(($totalTrashedCount ?? 0) > 0)
                                <div class="flex items-center gap-2 shrink-0">
                                    <x-danger-button type="button" @click="openPurge('all')">
                                        <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                        {{ __('Purge All Trashed System-Wide') }} ({{ number_format($totalTrashedCount) }})
                                    </x-danger-button>
                                </div>
                            @endif
                        </div>

                        <!-- Trashed Tables List -->
                        <div class="mt-5 overflow-x-auto">
                            <table class="w-full text-xs text-start">
                                <thead class="bg-gray-50 dark:bg-gray-900/60 text-gray-600 dark:text-gray-300 font-semibold border-b border-gray-100 dark:border-gray-700">
                                    <tr>
                                        <th class="p-3 text-start">{{ __('Database Table / Entity') }}</th>
                                        <th class="p-3 text-start">{{ __('Soft-Deleted Count') }}</th>
                                        <th class="p-3 text-start">{{ __('Total Records') }}</th>
                                        <th class="p-3 text-start">{{ __('Oldest Deleted Date') }}</th>
                                        <th class="p-3 text-start">{{ __('Newest Deleted Date') }}</th>
                                        <th class="p-3 text-end">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 font-mono">
                                    @forelse($trashedTables as $item)
                                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/20 transition-colors">
                                            <td class="p-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-gray-900 dark:text-white">{{ $item['table'] }}</span>
                                                    @if(!empty($item['model']))
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300">
                                                            {{ $item['model'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="p-3">
                                                @if($item['trashed_count'] > 0)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-300 animate-pulse">
                                                        {{ number_format($item['trashed_count']) }} {{ __('rows trashed') }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs text-gray-400 dark:text-gray-500">
                                                        0 {{ __('trashed') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="p-3 text-gray-600 dark:text-gray-400">
                                                {{ number_format($item['total_count']) }}
                                            </td>
                                            <td class="p-3 text-gray-500 dark:text-gray-400">
                                                {{ $item['oldest_deleted_at'] ? \Carbon\Carbon::parse($item['oldest_deleted_at'])->diffForHumans() : '-' }}
                                            </td>
                                            <td class="p-3 text-gray-500 dark:text-gray-400">
                                                {{ $item['newest_deleted_at'] ? \Carbon\Carbon::parse($item['newest_deleted_at'])->diffForHumans() : '-' }}
                                            </td>
                                            <td class="p-3 text-end whitespace-nowrap">
                                                <div class="inline-flex items-center gap-2">
                                                    <a href="{{ route('system-tables.trashed.show', ['table' => $item['table']]) }}"
                                                       class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 transition-colors"
                                                       title="{{ __('Open dedicated inspection page') }}">
                                                        <svg class="w-3.5 h-3.5 me-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                        {{ __('Inspect') }}
                                                    </a>

                                                    @if($item['trashed_count'] > 0)
                                                        <x-danger-button type="button" @click="openPurge('table', @js($item['table']), {{ (int) $item['trashed_count'] }})">
                                                            <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $trashPath }}"></path></svg>
                                                            {{ __('Hard Purge') }}
                                                        </x-danger-button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="p-6 text-center text-gray-500">
                                                {{ __('No soft-deleted tables detected in the database.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </main>
            </div>
        </div>

        <!-- Shared Purge Confirmation Modal (single table OR system-wide) -->
        <div x-show="showPurgeModal"
             x-cloak
             @keydown.escape.window="showPurgeModal = false"
             class="fixed inset-0 overflow-y-auto"
             style="z-index: 100000;"
             aria-labelledby="purge-modal-title" role="dialog" aria-modal="true">

            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- Backdrop --}}
                <div x-show="showPurgeModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-gray-900/80 backdrop-blur-md transition-opacity"
                     @click="showPurgeModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                {{-- Modal Card --}}
                <div x-show="showPurgeModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     :class="isAll ? 'border-rose-600' : 'border-rose-500/50'"
                     class="inline-block align-bottom sm:align-middle relative my-8 mx-auto w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl text-start overflow-hidden shadow-2xl transform transition-all border-2 p-6 z-10"
                     @click.stop>

                    <div class="flex items-center gap-3 pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div :class="isAll ? 'w-12 h-12 rounded-2xl bg-rose-600 text-white shadow-lg shadow-rose-600/30' : 'w-10 h-10 rounded-xl bg-rose-500/20 text-rose-600 dark:text-rose-400'"
                             class="flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div>
                            <h3 id="purge-modal-title"
                                :class="isAll ? 'font-extrabold text-rose-600 dark:text-rose-400' : 'font-bold text-gray-900 dark:text-white'"
                                class="text-base" x-text="t.title"></h3>
                            <p :class="isAll ? 'text-gray-500 dark:text-gray-400' : 'text-rose-600 dark:text-rose-400 font-semibold font-mono'"
                               class="text-xs" x-text="subtitle"></p>
                        </div>
                    </div>

                    <div class="my-4 space-y-3">
                        <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 text-xs text-rose-800 dark:text-rose-200 leading-relaxed">
                            <p class="font-bold mb-1" x-text="t.warning"></p>
                            <p x-text="detail"></p>
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400" x-show="t.note" x-text="t.note"></p>
                        </div>

                        <div>
                            <label for="purge-confirm-input" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1" x-text="t.label"></label>
                            <input type="text"
                                   id="purge-confirm-input"
                                   autocomplete="off"
                                   x-model="purgeInput"
                                   :placeholder="t.word"
                                   class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white text-sm focus:border-rose-600 focus:ring-rose-600 font-mono text-center uppercase tracking-widest font-bold">
                        </div>
                    </div>

                    <form method="POST"
                          :action="purgeAction"
                          @submit="if (!canSubmit) { $event.preventDefault(); return; } isSubmitting = true;"
                          class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                        @csrf
                        <input type="hidden" name="confirmation" :value="purgeInput">
                        <x-secondary-button type="button" @click="showPurgeModal = false">
                            {{ __('Cancel') }}
                        </x-secondary-button>
                        <x-danger-button type="submit" x-bind:disabled="!canSubmit">
                            <span x-text="t.submit"></span>
                        </x-danger-button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>