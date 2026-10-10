<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                    {{ __('Data Pruning & Lifecycle Management') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('Configure automated data retention limits, maximum capacity thresholds, and manual maintenance triggers') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                @php
                    $activeTablesCount = collect($effectiveConfig['tables'] ?? [])->filter(fn($t) => !empty($t['enabled']))->count();
                @endphp
                @if($activeTablesCount > 0)
                    <x-badge variant="success" size="md" :dot="true" :dot-ping="true">
                        {{ $activeTablesCount }} {{ __('Active Tables') }}
                    </x-badge>
                @else
                    <x-badge variant="neutral" size="md" :dot="true">
                        {{ __('All Tables Disabled') }}
                    </x-badge>
                @endif

                @if($hasCustomSettings)
                    <x-badge variant="info" size="md">
                        {{ __('Custom Settings Active') }}
                    </x-badge>
                @else
                    <x-badge variant="neutral" size="md">
                        {{ __('Config Defaults') }}
                    </x-badge>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        showDryRunModal: false,
        showExecuteModal: false,
        showResetModal: false,
        showAddTableModal: false,
        dryRunReport: null,
        isLoadingDryRun: false,
        eligibleTables: {{ Js::from($eligibleTables ?? []) }},
        selectedTable: '',
        selectedTableColumns: [],
        primaryKey: 'id',
        dateColumn: 'created_at',
        retentionDays: 30,
        maxRecords: 10000,
        onTableSelected(tableName) {
            this.selectedTable = tableName;
            const found = this.eligibleTables.find(t => t.name === tableName);
            if (found) {
                this.selectedTableColumns = found.columns;
                this.primaryKey = found.suggested_pk;
                this.dateColumn = found.suggested_date;
            } else {
                this.selectedTableColumns = [];
                this.primaryKey = 'id';
                this.dateColumn = 'created_at';
            }
        },
        async triggerDryRun() {
            this.isLoadingDryRun = true;
            this.showDryRunModal = true;
            try {
                const res = await fetch('{{ route('system-tables.pruning.dry-run') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                this.dryRunReport = await res.json();
            } catch (err) {
                console.error(err);
            } finally {
                this.isLoadingDryRun = false;
            }
        }
    }">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-system-tabs active="pruning" />
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

                    <!-- Main Settings Form -->
                    <form method="POST" action="{{ route('system-tables.pruning.update') }}" class="space-y-6">
                        @csrf

                        <!-- Execution Parameters Card -->
                        <div class="rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60">
                            <div >
                               
 <!-- Target Tables Configuration Grid Header -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                    <span>{{ __('Target Tables Lifecycle Rules') }}</span>
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ __('Individual retention limits and capacity rules for system and custom database tables.') }}
                                </p>
                            </div>
                            @if(!empty($eligibleTables))
                                <x-primary-button type="button" @click="showAddTableModal = true">
                                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    {{ __('Add Table to Pruning') }}
                                </x-primary-button>
                            @endif
                        </div>
                                
                            </div>
                        </div>

                       

                        <!-- Target Tables Configuration Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                            <!-- Activity Log Card -->
                            @php
                                $actCfg = $effectiveConfig['tables']['activity_log'] ?? [];
                            @endphp
                            <div class="rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-brand-600/10 text-brand-700 dark:text-brand-400 text-xs font-bold font-mono">
                                            activity_log
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                            {{ number_format($counts['activity_log'] ?? 0) }} {{ __('rows') }}
                                        </span>
                                    </div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ __('Activity Audit Trail') }}
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 mb-4">
                                        {{ __('System events, security changes, and administrator mutations.') }}
                                    </p>

                                    <div class="space-y-4 pt-3 border-t border-gray-100 dark:border-gray-700/60">
                                        <div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="tables[activity_log][enabled]" value="1" class="sr-only peer" {{ !empty($actCfg['enabled']) ? 'checked' : '' }}>
                                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-brand-600"></div>
                                                <span class="ms-2.5 text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Enable Table Pruning') }}</span>
                                            </label>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                                                {{ __('Retention Days (Date)') }}
                                            </label>
                                            <input type="number" name="tables[activity_log][retention_days]" value="{{ $actCfg['retention_days'] ?? 90 }}" min="0" max="3650" class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white text-sm focus:border-brand-600 focus:ring-brand-600">
                                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">{{ __('Delete logs older than N days.') }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                                                {{ __('Max Records (Capacity)') }}
                                            </label>
                                            <input type="number" name="tables[activity_log][max_records]" value="{{ $actCfg['max_records'] ?? 100000 }}" min="0" max="10000000" class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white text-sm focus:border-brand-600 focus:ring-brand-600">
                                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">{{ __('Purge excess oldest records beyond this limit.') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Notifications Card -->
                            @php
                                $notifCfg = $effectiveConfig['tables']['notifications'] ?? [];
                            @endphp
                            <div class="rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-500/10 text-brand-700 dark:text-indigo-400 text-xs font-bold font-mono">
                                            notifications
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                            {{ number_format($counts['notifications'] ?? 0) }} {{ __('rows') }}
                                        </span>
                                    </div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ __('Database Notifications') }}
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 mb-4">
                                        {{ __('User alerts, broadcast notifications, and activity alarms.') }}
                                    </p>

                                    <div class="space-y-4 pt-3 border-t border-gray-100 dark:border-gray-700/60">
                                        <div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="tables[notifications][enabled]" value="1" class="sr-only peer" {{ !empty($notifCfg['enabled']) ? 'checked' : '' }}>
                                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-brand-600"></div>
                                                <span class="ms-2.5 text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Enable Table Pruning') }}</span>
                                            </label>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                                                {{ __('Retention Days (Date)') }}
                                            </label>
                                            <input type="number" name="tables[notifications][retention_days]" value="{{ $notifCfg['retention_days'] ?? 60 }}" min="0" max="3650" class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white text-sm focus:border-brand-600 focus:ring-brand-600">
                                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">{{ __('Delete notifications older than N days.') }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                                                {{ __('Max Records (Capacity)') }}
                                            </label>
                                            <input type="number" name="tables[notifications][max_records]" value="{{ $notifCfg['max_records'] ?? 50000 }}" min="0" max="10000000" class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white text-sm focus:border-brand-600 focus:ring-brand-600">
                                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">{{ __('Purge excess oldest records beyond this limit.') }}</p>
                                        </div>

                                        <div class="pt-2">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" name="tables[notifications][only_read]" value="1" class="rounded border-gray-300 dark:border-gray-600 text-brand-600 focus:ring-brand-600 dark:bg-gray-900" {{ !empty($notifCfg['only_read']) ? 'checked' : '' }}>
                                                <span class="text-xs text-gray-600 dark:text-gray-300 font-medium">{{ __('Prune only read notifications') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Failed Jobs Card -->
                            @php
                                $failCfg = $effectiveConfig['tables']['failed_jobs'] ?? [];
                            @endphp
                            <div class="rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 text-xs font-bold font-mono">
                                            failed_jobs
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                            {{ number_format($counts['failed_jobs'] ?? 0) }} {{ __('rows') }}
                                        </span>
                                    </div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ __('Failed Queue Jobs') }}
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 mb-4">
                                        {{ __('Unresolved exception traces and dead-letter payloads.') }}
                                    </p>

                                    <div class="space-y-4 pt-3 border-t border-gray-100 dark:border-gray-700/60">
                                        <div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="tables[failed_jobs][enabled]" value="1" class="sr-only peer" {{ !empty($failCfg['enabled']) ? 'checked' : '' }}>
                                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-brand-600"></div>
                                                <span class="ms-2.5 text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Enable Table Pruning') }}</span>
                                            </label>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                                                {{ __('Retention Days (Date)') }}
                                            </label>
                                            <input type="number" name="tables[failed_jobs][retention_days]" value="{{ $failCfg['retention_days'] ?? 30 }}" min="0" max="3650" class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white text-sm focus:border-brand-600 focus:ring-brand-600">
                                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">{{ __('Delete failures older than N days.') }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                                                {{ __('Max Records (Capacity)') }}
                                            </label>
                                            <input type="number" name="tables[failed_jobs][max_records]" value="{{ $failCfg['max_records'] ?? 10000 }}" min="0" max="10000000" class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white text-sm focus:border-brand-600 focus:ring-brand-600">
                                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">{{ __('Purge excess oldest records beyond this limit.') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dynamically Added Custom Tables -->
                            @foreach($effectiveConfig['tables'] as $tblKey => $cfg)
                                @if(!in_array($tblKey, ['activity_log', 'notifications', 'failed_jobs'], true))
                                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-indigo-100 dark:border-indigo-900/40 flex flex-col justify-between">
                                        <div>
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-500/10 text-brand-700 dark:text-indigo-400 text-xs font-bold font-mono">
                                                        {{ $tblKey }}
                                                    </span>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                                        {{ __('Custom') }}
                                                    </span>
                                                </div>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                                    {{ number_format($counts[$tblKey] ?? 0) }} {{ __('rows') }}
                                                </span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <h4 class="text-sm font-bold text-gray-900 dark:text-white font-mono">
                                                    {{ $tblKey }}
                                                </h4>
                                                <button type="button"
                                                        @click="$dispatch('open-delete-modal', {
                                                            action: '{{ route('system-tables.pruning.tables.remove', $tblKey) }}',
                                                            name: '{{ $tblKey }}',
                                                            title: '{{ __('Remove from Pruning') }}'
                                                        })"
                                                        class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
                                                        title="{{ __('Remove table from pruning') }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </div>
                                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                                <span>PK: <code class="text-brand-700 dark:text-indigo-400 font-bold">{{ $cfg['primary_key'] ?? 'id' }}</code></span>
                                                <span>&bull;</span>
                                                <span>{{ __('Date:') }} <code class="text-brand-700 dark:text-indigo-400 font-bold">{{ $cfg['date_column'] ?? 'created_at' }}</code></span>
                                            </div>

                                            <div class="space-y-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 mt-3">
                                                <div>
                                                    <label class="relative inline-flex items-center cursor-pointer">
                                                        <input type="checkbox" name="tables[{{ $tblKey }}][enabled]" value="1" class="sr-only peer" {{ !empty($cfg['enabled']) ? 'checked' : '' }}>
                                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-brand-600"></div>
                                                        <span class="ms-2.5 text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Enable Table Pruning') }}</span>
                                                    </label>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                                                        {{ __('Retention Days (Date)') }}
                                                    </label>
                                                    <input type="number" name="tables[{{ $tblKey }}][retention_days]" value="{{ $cfg['retention_days'] ?? 30 }}" min="0" max="3650" class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white text-sm focus:border-brand-600 focus:ring-brand-600">
                                                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">{{ __('Delete logs older than N days.') }}</p>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                                                        {{ __('Max Records (Capacity)') }}
                                                    </label>
                                                    <input type="number" name="tables[{{ $tblKey }}][max_records]" value="{{ $cfg['max_records'] ?? 10000 }}" min="0" max="10000000" class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white text-sm focus:border-brand-600 focus:ring-brand-600">
                                                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">{{ __('Purge excess oldest records beyond this limit.') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach

                        </div>

                        <!-- Action Controls Bar (Rule 14 Buttons) -->
                        <div class="rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="flex items-center gap-2">
                                <x-primary-button type="submit">
                                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    {{ __('Save Pruning Settings') }}
                                </x-primary-button>

                                @if($hasCustomSettings)
                                    <x-secondary-button type="button" @click="showResetModal = true">
                                        <svg class="w-4 h-4 me-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        {{ __('Reset to Defaults') }}
                                    </x-secondary-button>
                                @endif
                            </div>

                            <div class="flex items-center gap-2.5">
                                <x-info-button type="button" @click="triggerDryRun()">
                                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                    {{ __('Dry-Run Simulation') }}
                                </x-info-button>

                                <x-danger-button type="button" @click="showExecuteModal = true">
                                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    {{ __('Execute Pruning Now') }}
                                </x-danger-button>
                            </div>
                        </div>

                    </form>

                    <!-- Hidden Delete Forms for Custom Pruning Tables -->
                    @foreach($effectiveConfig['tables'] as $tblKey => $cfg)
                        @if(!in_array($tblKey, ['activity_log', 'notifications', 'failed_jobs'], true))
                            <form id="delete-table-{{ $tblKey }}" method="POST" action="{{ route('system-tables.pruning.tables.remove', $tblKey) }}" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endif
                    @endforeach

                    <!-- Recent Pruning Activity Table (Rule 15 Standard Table) -->
                    <x-table>
                        <x-slot:toolbar>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>{{ __('Pruning Audit Trail') }}</span>
                            </h3>
                            <span class="text-xs text-gray-500 font-mono">{{ $history->count() }} {{ __('events') }}</span>
                        </x-slot:toolbar>

                        <x-slot:header>
                            <x-table.th>{{ __('Operation') }}</x-table.th>
                            <x-table.th>{{ __('Scope & Numbers') }}</x-table.th>
                            <x-table.th>{{ __('Timestamp') }}</x-table.th>
                        </x-slot:header>

                        @forelse($history as $item)
                            <x-table.tr>
                                <x-table.td class="font-medium text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-brand-600 shrink-0"></span>
                                        <span>{{ $item->translated_description ?? $item->description }}</span>
                                    </div>
                                </x-table.td>
                                <x-table.td class="font-mono text-xs text-gray-600 dark:text-gray-300">
                                    @if(isset($item->properties['table']))
                                        <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 me-2">{{ $item->properties['table'] }}</span>
                                        <span>{{ __('Purged:') }} <strong class="text-rose-600 dark:text-rose-400">{{ $item->properties['total_pruned'] ?? 0 }}</strong></span>
                                        @if(isset($item->properties['date_pruned']))
                                            <span class="text-gray-400 ms-1">({{ __('Date:') }} {{ $item->properties['date_pruned'] }}, {{ __('Cap:') }} {{ $item->properties['count_pruned'] }})</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">{{ __('Configuration event') }}</span>
                                    @endif
                                </x-table.td>
                                <x-table.td class="font-mono text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ $item->created_at->diffForHumans() }} (<x-date :value="$item->created_at" format="datetime" />)
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty :colspan="3" :message="__('No automated pruning activity logged yet.')" />
                        @endforelse
                    </x-table>

                </main>
            </div>
        </div>

        <!-- 1. Dry Run Simulation Modal (Alpine.js) -->
        <div x-show="showDryRunModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showDryRunModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                     @click="showDryRunModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showDryRunModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-start overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-gray-100 dark:border-gray-700">

                    <div class="p-6">
                        <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-brand-700 dark:text-indigo-400 flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white" id="modal-title">
                                        {{ __('Dry-Run Simulation Results') }}
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('Zero database changes were made. This is an exploratory simulation.') }}
                                    </p>
                                </div>
                            </div>
                            <button @click="showDryRunModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="py-5">
                            <div x-show="isLoadingDryRun" class="text-center py-8">
                                <div class="inline-block animate-spin w-8 h-8 border-4 border-brand-500 border-t-transparent rounded-full mb-3"></div>
                                <p class="text-xs text-gray-500 font-medium">{{ __('Analyzing database tables and lifecycle rules...') }}</p>
                            </div>

                            <div x-show="!isLoadingDryRun && dryRunReport" class="space-y-4">
                                <div class="p-3.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-800 text-xs text-indigo-900 dark:text-indigo-200 flex items-center justify-between font-semibold">
                                    <span>{{ __('Total records targeted for pruning:') }}</span>
                                    <span class="text-base font-mono text-brand-700 dark:text-indigo-400" x-text="dryRunReport ? dryRunReport.total_pruned : 0"></span>
                                </div>

                                <div class="border border-gray-100 dark:border-gray-700/60 rounded-xl overflow-hidden">
                                    <table class="w-full text-xs text-start">
                                        <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-600 dark:text-gray-300 font-semibold border-b border-gray-100 dark:border-gray-700">
                                            <tr>
                                                <th class="p-3 text-start">{{ __('Table') }}</th>
                                                <th class="p-3 text-start">{{ __('By Date') }}</th>
                                                <th class="p-3 text-start">{{ __('By Capacity') }}</th>
                                                <th class="p-3 text-start">{{ __('Target Total') }}</th>
                                                <th class="p-3 text-start">{{ __('Remaining') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 font-mono">
                                            <template x-if="dryRunReport && dryRunReport.tables">
                                                <template x-for="(t, key) in dryRunReport.tables" :key="key">
                                                    <tr>
                                                        <td class="p-3 font-semibold text-gray-900 dark:text-white" x-text="t.table"></td>
                                                        <td class="p-3 text-gray-600 dark:text-gray-400" x-text="t.date_pruned"></td>
                                                        <td class="p-3 text-gray-600 dark:text-gray-400" x-text="t.count_pruned"></td>
                                                        <td class="p-3 text-rose-600 dark:text-rose-400 font-bold" x-text="t.total_pruned"></td>
                                                        <td class="p-3 text-emerald-600 dark:text-emerald-400" x-text="t.remaining_records"></td>
                                                    </tr>
                                                </template>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                            <x-secondary-button type="button" @click="showDryRunModal = false">
                                {{ __('Close Simulation') }}
                            </x-secondary-button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Manual Execution Confirmation Modal -->
        <div x-show="showExecuteModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showExecuteModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                     @click="showExecuteModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showExecuteModal"
                     class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-start overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-3 pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                {{ __('Execute Immediate Pruning?') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Permanent deletion of expired and excess records.') }}
                            </p>
                        </div>
                    </div>

                    <p class="text-xs text-gray-600 dark:text-gray-300 my-4 leading-relaxed">
                        {{ __('This action will permanently purge records matching current retention and capacity rules across all enabled tables. Sovereign tables (users, roles, permissions) are strictly protected.') }}
                    </p>

                    <form method="POST" action="{{ route('system-tables.pruning.execute') }}" class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                        @csrf
                        <x-secondary-button type="button" @click="showExecuteModal = false">
                            {{ __('Cancel') }}
                        </x-secondary-button>
                        <x-danger-button type="submit">
                            {{ __('Yes, Execute Pruning') }}
                        </x-danger-button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 3. Reset Defaults Confirmation Modal -->
        <div x-show="showResetModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showResetModal"
                     class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                     @click="showResetModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showResetModal"
                     class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-start overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-3 pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                {{ __('Reset Pruning Settings?') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Restore values from config/pruning.php.') }}
                            </p>
                        </div>
                    </div>

                    <p class="text-xs text-gray-600 dark:text-gray-300 my-4 leading-relaxed">
                        {{ __('This will discard your custom stored database overrides and restore the default configuration parameters defined in config/pruning.php.') }}
                    </p>

                    <form method="POST" action="{{ route('system-tables.pruning.reset') }}" class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                        @csrf
                        <x-secondary-button type="button" @click="showResetModal = false">
                            {{ __('Cancel') }}
                        </x-secondary-button>
                        <x-primary-button type="submit">
                            {{ __('Confirm Reset') }}
                        </x-primary-button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 4. Add Table to Pruning Modal (Alpine.js) -->
        <div x-show="showAddTableModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="add-table-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showAddTableModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                     @click="showAddTableModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showAddTableModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-start overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100 dark:border-gray-700">

                    <form method="POST" action="{{ route('system-tables.pruning.tables.add') }}" class="p-6 space-y-5">
                        @csrf

                        <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-brand-600/10 text-brand-700 dark:text-brand-400 flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white" id="add-table-modal-title">
                                        {{ __('Onboard Database Table to Pruning') }}
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('Configure automated lifecycle retention for any non-sovereign database table.') }}
                                    </p>
                                </div>
                            </div>
                            <button type="button" @click="showAddTableModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <!-- Table Select -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1">
                                {{ __('Database Table') }}
                            </label>
                            <select name="table"
                                    x-model="selectedTable"
                                    @change="onTableSelected($event.target.value)"
                                    required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white text-sm focus:border-brand-600 focus:ring-brand-600 font-mono">
                                <option value="" disabled>{{ __('Select a database table...') }}</option>
                                <template x-for="t in eligibleTables" :key="t.name">
                                    <option :value="t.name" x-text="t.name + ' (' + t.count + ' {{ __('rows') }})'"></option>
                                </template>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Primary Key -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1">
                                    {{ __('Primary Key Column') }}
                                </label>
                                <select name="primary_key"
                                        x-model="primaryKey"
                                        required
                                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white text-sm focus:border-brand-600 focus:ring-brand-600 font-mono">
                                    <template x-for="col in selectedTableColumns" :key="col">
                                        <option :value="col" x-text="col"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Date Column -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1">
                                    {{ __('Date Column (Timestamp)') }}
                                </label>
                                <select name="date_column"
                                        x-model="dateColumn"
                                        required
                                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white text-sm focus:border-brand-600 focus:ring-brand-600 font-mono">
                                    <template x-for="col in selectedTableColumns" :key="col">
                                        <option :value="col" x-text="col"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Retention Days -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1">
                                    {{ __('Retention Days (Date)') }}
                                </label>
                                <input type="number"
                                       name="retention_days"
                                       x-model="retentionDays"
                                       min="0"
                                       max="3650"
                                       required
                                       class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white text-sm focus:border-brand-600 focus:ring-brand-600">
                                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">{{ __('Delete logs older than N days.') }}</p>
                            </div>

                            <!-- Max Records -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1">
                                    {{ __('Max Records (Capacity)') }}
                                </label>
                                <input type="number"
                                       name="max_records"
                                       x-model="maxRecords"
                                       min="0"
                                       max="10000000"
                                       required
                                       class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white text-sm focus:border-brand-600 focus:ring-brand-600">
                                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">{{ __('Purge excess oldest records beyond this limit.') }}</p>
                            </div>
                        </div>

                        <!-- Enable Immediately Toggle -->
                        <div class="pt-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enabled" value="1" class="sr-only peer" checked>
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-brand-600"></div>
                                <span class="ms-2.5 text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Enable Table Pruning') }}</span>
                            </label>
                        </div>

                        <!-- Sovereign Security Notice -->
                        <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-[11px] text-amber-800 dark:text-amber-300 flex items-start gap-2">
                            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span>{{ __('Sovereign security tables (users, roles, permissions, sessions, jobs) are permanently protected and cannot be onboarded.') }}</span>
                        </div>

                        <!-- Modal Actions -->
                        <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-end gap-2">
                            <x-secondary-button type="button" @click="showAddTableModal = false">
                                {{ __('Cancel') }}
                            </x-secondary-button>
                            <x-primary-button type="submit" ::disabled="!selectedTable">
                                {{ __('Onboard Table') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
