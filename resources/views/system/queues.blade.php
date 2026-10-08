<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                    {{ __('Queues & Jobs') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('Monitor background task processing, failed job diagnostics, and job batches') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="info" size="md">
                    {{ $jobs->total() }} {{ __('Pending') }}
                </x-badge>
                <x-badge :variant="$failedJobs->total() > 0 ? 'danger' : 'neutral'" size="md">
                    {{ $failedJobs->total() }} {{ __('Failed') }}
                </x-badge>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ currentTab: '{{ in_array($tab, ['jobs', 'failed', 'batches']) ? $tab : 'jobs' }}', errorModalOpen: false, errorTitle: '', errorTrace: '' }">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-system-tabs active="queues" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-6">

            <div class="space-y-4">
                <!-- Subtab Navigation Toolbar -->
                <div class="flex flex-wrap items-center gap-2 p-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 shadow-sm">
                    <button type="button"
                            @click="currentTab = 'jobs'"
                            :class="currentTab === 'jobs' ? 'bg-brand-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition">
                        {{ __('Pending Jobs') }} (<code>jobs</code>: {{ $jobs->total() }})
                    </button>
                    <button type="button"
                            @click="currentTab = 'failed'"
                            :class="currentTab === 'failed' ? 'bg-rose-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition">
                        {{ __('Failed Jobs') }} (<code>failed_jobs</code>: {{ $failedJobs->total() }})
                    </button>
                    <button type="button"
                            @click="currentTab = 'batches'"
                            :class="currentTab === 'batches' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition">
                        {{ __('Job Batches') }} (<code>job_batches</code>: {{ $batches->total() }})
                    </button>
                </div>

                <!-- 1. Pending Jobs Tab Content -->
                <div x-show="currentTab === 'jobs'">
                    <x-table>
                        <x-slot:header>
                            <x-table.th>ID</x-table.th>
                            <x-table.th>{{ __('Queue') }}</x-table.th>
                            <x-table.th>{{ __('Attempts') }}</x-table.th>
                            <x-table.th>{{ __('Reserved At') }}</x-table.th>
                            <x-table.th>{{ __('Created At') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                        </x-slot:header>

                        @forelse($jobs as $j)
                            <x-table.tr>
                                <x-table.td class="font-mono font-bold text-gray-900 dark:text-white">#{{ $j->id }}</x-table.td>
                                <x-table.td>
                                    <x-badge variant="info">
                                        {{ $j->queue }}
                                    </x-badge>
                                </x-table.td>
                                <x-table.td class="font-bold">{{ $j->attempts }}</x-table.td>
                                <x-table.td class="font-mono text-xs">
                                    {{ $j->reserved_at ? \Carbon\Carbon::createFromTimestamp($j->reserved_at)->diffForHumans() : '-' }}
                                </x-table.td>
                                <x-table.td class="whitespace-nowrap">
                                    <x-date :value="\Carbon\Carbon::createFromTimestamp($j->created_at)" format="timestamp" />
                                </x-table.td>
                                <x-table.td class="whitespace-nowrap text-end">
                                    <x-table.actions class="justify-end">
                                        <x-table.action-delete :title="__('Cancel Job')" />
                                    </x-table.actions>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty colspan="6" :message="__('No pending queue jobs in database.')" />
                        @endforelse

                        @if($jobs->hasPages())
                            <x-slot:pagination>
                                {{ $jobs->links() }}
                            </x-slot:pagination>
                        @endif
                    </x-table>
                </div>

                <!-- 2. Failed Jobs Tab Content -->
                <div x-show="currentTab === 'failed'" x-cloak>
                    <x-table>
                        <x-slot:header>
                            <x-table.th>ID</x-table.th>
                            <x-table.th>{{ __('UUID') }}</x-table.th>
                            <x-table.th>{{ __('Queue') }}</x-table.th>
                            <x-table.th>{{ __('Connection') }}</x-table.th>
                            <x-table.th>{{ __('Failed At') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                        </x-slot:header>

                        @forelse($failedJobs as $fj)
                            <x-table.tr>
                                <x-table.td class="font-mono font-bold text-gray-900 dark:text-white">#{{ $fj->id }}</x-table.td>
                                <x-table.td class="font-mono text-gray-500">{{ substr($fj->uuid, 0, 8) }}...</x-table.td>
                                <x-table.td class="font-mono text-rose-600 font-semibold">{{ $fj->queue }}</x-table.td>
                                <x-table.td>{{ $fj->connection }}</x-table.td>
                                <x-table.td class="whitespace-nowrap">{{ $fj->failed_at }}</x-table.td>
                                <x-table.td class="whitespace-nowrap text-end">
                                    <x-table.actions class="justify-end">
                                        <x-table.action-view
                                            @click="errorModalOpen = true; errorTitle = 'Job #{{ $fj->id }} Exception'; errorTrace = {{ json_encode($fj->exception) }}"
                                            :title="__('View Stack Trace')" />
                                        <x-table.action type="primary" :title="__('Retry Job')">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        </x-table.action>
                                        <x-table.action-delete :title="__('Delete Record')" />
                                    </x-table.actions>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty colspan="6" :message="__('No failed jobs recorded. Everything runs smoothly!')" />
                        @endforelse

                        @if($failedJobs->hasPages())
                            <x-slot:pagination>
                                {{ $failedJobs->links() }}
                            </x-slot:pagination>
                        @endif
                    </x-table>
                </div>

                <!-- 3. Job Batches Tab Content -->
                <div x-show="currentTab === 'batches'" x-cloak>
                    <x-table>
                        <x-slot:header>
                            <x-table.th>{{ __('Batch ID') }}</x-table.th>
                            <x-table.th>{{ __('Name') }}</x-table.th>
                            <x-table.th>{{ __('Total Jobs') }}</x-table.th>
                            <x-table.th>{{ __('Pending') }}</x-table.th>
                            <x-table.th>{{ __('Failed') }}</x-table.th>
                            <x-table.th>{{ __('Date') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                        </x-slot:header>

                        @forelse($batches as $b)
                            <x-table.tr>
                                <x-table.td class="font-mono text-gray-500">{{ substr($b->id, 0, 8) }}...</x-table.td>
                                <x-table.td class="font-semibold text-gray-900 dark:text-white">{{ $b->name }}</x-table.td>
                                <x-table.td class="font-bold">{{ $b->total_jobs }}</x-table.td>
                                <x-table.td>{{ $b->pending_jobs }}</x-table.td>
                                <x-table.td class="{{ $b->failed_jobs > 0 ? 'text-rose-600 font-bold' : '' }}">{{ $b->failed_jobs }}</x-table.td>
                                <x-table.td class="whitespace-nowrap">
                                    <x-date :value="\Carbon\Carbon::createFromTimestamp($b->created_at)" format="datetime" />
                                </x-table.td>
                                <x-table.td class="whitespace-nowrap text-end">
                                    <x-table.actions class="justify-end">
                                        <x-table.action-delete :title="__('Delete Batch')" />
                                    </x-table.actions>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty colspan="7" :message="__('No job batches recorded.')" />
                        @endforelse

                        @if($batches->hasPages())
                            <x-slot:pagination>
                                {{ $batches->links() }}
                            </x-slot:pagination>
                        @endif
                    </x-table>
                </div>
            </div>
                </main>
            </div>
        </div>

        <!-- Exception Modal -->
        <div x-show="errorModalOpen"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
             @keydown.escape.window="errorModalOpen = false">
            <div class="relative w-full max-w-3xl rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-xl border border-gray-200 dark:border-gray-700"
                 @click.outside="errorModalOpen = false">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                    <h4 class="text-base font-bold text-rose-600 dark:text-rose-400" x-text="errorTitle"></h4>
                    <button type="button" @click="errorModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="py-4 max-h-96 overflow-y-auto">
                    <div class="rounded-lg bg-gray-900 text-gray-100 p-4 border border-gray-800 font-mono text-xs overflow-x-auto whitespace-pre-wrap" x-text="errorTrace"></div>
                </div>

                <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                    <x-secondary-button type="button"
                            @click="errorModalOpen = false"
                            class="py-1.5 px-4 text-xs rounded-lg">
                        {{ __('Close') }}
                    </x-secondary-button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
