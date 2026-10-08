<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                    {{ __('Database Backups & Disaster Recovery') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('Manage database snapshots, download archives, and execute point-in-time state restoration.') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="info" size="md">
                    {{ $backupData['total_count'] }} {{ __('backups') }}
                </x-badge>
                <x-badge variant="neutral" size="md">
                    {{ $backupData['total_size_formatted'] }}
                </x-badge>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        showRestoreModal: false,
        restoreFileName: '',
        restoreDate: '',
        restoreSize: '',
        isOldestRestore: false,
        openRestoreModal(fileName, date, size, isOldest = false) {
            this.restoreFileName = fileName;
            this.restoreDate = date;
            this.restoreSize = size;
            this.isOldestRestore = isOldest;
            this.showRestoreModal = true;
        }
    }">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-system-tabs active="backups" />
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

                    <!-- Top Action & Overview Card -->
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>
                                    <span>{{ __('Database Snapshot Repository') }}</span>
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ __('Generate instant database dumps, download compressed archives, or roll back to an earlier recovery snapshot.') }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2.5">
                                @if(!empty($backupData['oldest_backup']))
                                    <x-warning-button type="button"
                                                      @click="openRestoreModal('{{ $backupData['oldest_backup']['file_name'] }}', '{{ $backupData['oldest_backup']['date']->format('d/m/Y H:i') }}', '{{ $backupData['oldest_backup']['size_formatted'] }}', true)">
                                        <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ __('Restore Oldest Snapshot') }}
                                    </x-warning-button>
                                @endif

                                <form method="POST" action="{{ route('system-tables.backups.create') }}" x-data="{ isCreating: false }" @submit="isCreating = true">
                                    @csrf
                                    <x-primary-button type="submit" ::disabled="isCreating">
                                        <svg x-show="!isCreating" class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        <svg x-show="isCreating" x-cloak class="animate-spin w-4 h-4 me-1.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                        <span x-text="isCreating ? '{{ __('Creating Backup...') }}' : '{{ __('Create Backup Now') }}'">{{ __('Create Backup Now') }}</span>
                                    </x-primary-button>
                                </form>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-4 pt-5 border-t border-gray-100 dark:border-gray-700/60 text-xs">
                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700/60">
                                <span class="text-gray-400 dark:text-gray-500 block mb-0.5">{{ __('Total Stored Backups') }}</span>
                                <span class="text-sm font-bold font-mono text-gray-900 dark:text-white">{{ $backupData['total_count'] }}</span>
                            </div>
                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700/60">
                                <span class="text-gray-400 dark:text-gray-500 block mb-0.5">{{ __('Disk Storage Used') }}</span>
                                <span class="text-sm font-bold font-mono text-gray-900 dark:text-white">{{ $backupData['total_size_formatted'] }}</span>
                            </div>
                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700/60">
                                <span class="text-gray-400 dark:text-gray-500 block mb-0.5">{{ __('Oldest Snapshot Date') }}</span>
                                <span class="text-sm font-bold font-mono text-amber-600 dark:text-amber-400">
                                    @if(!empty($backupData['oldest_backup']))
                                        <x-date :value="$backupData['oldest_backup']['date']" format="datetime" />
                                    @else
                                        {{ __('None') }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Backups Table (Rule 15 Unified Table Architecture) -->
                    <x-table>
                        <x-slot:toolbar>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm0 5h16M9 4v16"></path></svg>
                                <span>{{ __('Available Database Backups') }}</span>
                            </h3>
                            <span class="text-xs text-gray-500 font-mono">{{ $backupData['total_count'] }} {{ __('files') }}</span>
                        </x-slot:toolbar>

                        <x-slot:header>
                            <x-table.th>{{ __('Snapshot File') }}</x-table.th>
                            <x-table.th>{{ __('Created Date') }}</x-table.th>
                            <x-table.th>{{ __('Archive Size') }}</x-table.th>
                            <x-table.th>{{ __('Snapshot Role') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                        </x-slot:header>

                        @forelse($backupData['backups'] as $backup)
                            <x-table.tr>
                                <x-table.td class="font-medium text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-brand-600/10 text-brand-700 dark:text-brand-400 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path></svg>
                                        </div>
                                        <div>
                                            <span class="font-mono text-xs font-bold block">{{ $backup['file_name'] }}</span>
                                            <span class="text-[11px] text-gray-400 font-mono">{{ $backup['disk'] }}</span>
                                        </div>
                                    </div>
                                </x-table.td>

                                <x-table.td class="font-mono text-xs text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                    <span><x-date :value="$backup['date']" format="datetime" /></span>
                                    <span class="text-gray-400 block text-[11px]">{{ $backup['age'] }}</span>
                                </x-table.td>

                                <x-table.td class="font-mono text-xs font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    {{ $backup['size_formatted'] }}
                                </x-table.td>

                                <x-table.td class="whitespace-nowrap">
                                    @if($backup['is_oldest'])
                                        <x-badge variant="warning">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            {{ __('Oldest Snapshot') }}
                                        </x-badge>
                                    @elseif($backup['is_newest'])
                                        <x-badge variant="success">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            {{ __('Latest Snapshot') }}
                                        </x-badge>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">
                                            {{ __('Standard Backup') }}
                                        </span>
                                    @endif
                                </x-table.td>

                                <x-table.td class="text-end whitespace-nowrap">
                                    <x-table.actions>
                                        <!-- 1. Download Backup Button -->
                                        <x-table.action-download
                                            href="{{ route('system-tables.backups.download', $backup['file_name']) }}"
                                            :title="__('Download backup archive')"
                                        />

                                        <!-- 2. Restore Backup Button -->
                                        <x-table.action-restore
                                            @click="openRestoreModal('{{ $backup['file_name'] }}', '{{ $backup['date']->format('d/m/Y H:i') }}', '{{ $backup['size_formatted'] }}', {{ $backup['is_oldest'] ? 'true' : 'false' }})"
                                            :title="__('Restore database from this snapshot')"
                                        />

                                        <!-- 3. Delete Backup Button -->
                                        <x-table.action-delete
                                            :action-url="route('system-tables.backups.delete', $backup['file_name'])"
                                            :confirm-message="__('Are you sure you want to permanently delete backup snapshot :file?', ['file' => $backup['file_name']])"
                                            :title="__('Delete backup snapshot')"
                                        />
                                    </x-table.actions>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty :colspan="5" :message="__('No database backups available yet.')" />
                        @endforelse
                    </x-table>

                </main>
            </div>
        </div>

        <!-- Interactive Restore Confirmation Modal (Alpine.js) -->
        <div x-show="showRestoreModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="restore-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showRestoreModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                     @click="showRestoreModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showRestoreModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-start overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-rose-200 dark:border-rose-900/50 p-6">

                    <!-- Modal Header -->
                    <div class="flex items-center gap-3 pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div class="w-11 h-11 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white" id="restore-modal-title">
                                {{ __('Confirm Database Restoration') }}
                            </h3>
                            <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold">
                                {{ __('CRITICAL: Point-in-time database overwrite') }}
                            </p>
                        </div>
                    </div>

                    <!-- Danger Warning Box -->
                    <div class="my-4 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs text-rose-800 dark:text-rose-300 space-y-2 leading-relaxed">
                        <p class="font-bold flex items-center gap-1.5">
                            <svg class="w-4 h-4 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            {{ __('Warning: This operation will overwrite current database contents!') }}
                        </p>
                        <p>
                            {{ __('Restoring will replace all current tables and records with the state saved in this snapshot. Any data created or modified after this snapshot was generated will be permanently lost.') }}
                        </p>
                    </div>

                    <!-- Target Snapshot Details -->
                    <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700/60 text-xs space-y-1.5 font-mono mb-5">
                        <div class="flex justify-between items-center text-gray-600 dark:text-gray-300">
                            <span class="font-sans text-gray-400">{{ __('Target Snapshot:') }}</span>
                            <span class="font-bold text-gray-900 dark:text-white" x-text="restoreFileName"></span>
                        </div>
                        <div class="flex justify-between items-center text-gray-600 dark:text-gray-300">
                            <span class="font-sans text-gray-400">{{ __('Snapshot Date:') }}</span>
                            <span x-text="restoreDate"></span>
                        </div>
                        <div class="flex justify-between items-center text-gray-600 dark:text-gray-300">
                            <span class="font-sans text-gray-400">{{ __('Archive Size:') }}</span>
                            <span x-text="restoreSize"></span>
                        </div>
                        <template x-if="isOldestRestore">
                            <div class="pt-1 mt-1 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center text-amber-600 dark:text-amber-400 font-semibold font-sans">
                                <span>{{ __('Snapshot Role:') }}</span>
                                <span>{{ __('Oldest Available Snapshot') }}</span>
                            </div>
                        </template>
                    </div>

                    <!-- Form Actions (Rule 14 Unified Buttons) -->
                    <form method="POST" action="{{ route('system-tables.backups.restore') }}"
                          x-data="{ isRestoring: false }"
                          @submit="isRestoring = true"
                          class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                        @csrf
                        <input type="hidden" name="file" :value="restoreFileName">
                        <x-secondary-button type="button" @click="showRestoreModal = false" ::disabled="isRestoring">
                            {{ __('Cancel') }}
                        </x-secondary-button>
                        <x-danger-button type="submit" ::disabled="isRestoring">
                            <svg x-show="!isRestoring" class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            <svg x-show="isRestoring" x-cloak class="animate-spin w-4 h-4 me-1.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span x-text="isRestoring ? '{{ __('Restoring Database...') }}' : '{{ __('Yes, Restore Database State') }}'">{{ __('Yes, Restore Database State') }}</span>
                        </x-danger-button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
