<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                    {{ __('Activity Log') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('Forensic audit trail, model mutations, and actor tracking') }}
                </p>
            </div>
            <x-badge variant="info" size="md">
                {{ $activities->total() }} {{ __('Total Activities') }}
            </x-badge>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ modalOpen: false, modalTitle: '', modalChanges: null }">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-system-tabs active="activity-log" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-6">

            <x-table>
                <!-- Filters & Search Toolbar -->
                <x-slot:toolbar>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>{{ __('Audit Trail Table') }} (<code>activity_log</code>)</span>
                    </h3>

                    <!-- Global Filter Form -->
                    <x-global-filter
                        :action="route('system-tables.activity-log')"
                        :search="true"
                        :search-placeholder="__('Search description, subject, user...')"
                        :search-value="$search"
                        :submit-text="__('Filter')">
                        <x-global-filter.select
                            name="event"
                            :placeholder="__('All Events')"
                            :value="$event"
                            :options="[
                                'created' => __('Created'),
                                'updated' => __('Updated'),
                                'deleted' => __('Deleted'),
                            ]"
                        />
                    </x-global-filter>
                </x-slot:toolbar>

                <!-- Table Content -->
                <x-slot:header>
                    <x-table.th>{{ __('ID') }}</x-table.th>
                    <x-table.th>{{ __('Event') }}</x-table.th>
                    <x-table.th>{{ __('Description') }}</x-table.th>
                    <x-table.th>{{ __('Subject') }}</x-table.th>
                    <x-table.th>{{ __('Causer') }}</x-table.th>
                    <x-table.th>{{ __('Date') }}</x-table.th>
                    <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                </x-slot:header>

                @forelse($activities as $act)
                    @php
                        $eventVariant = match($act->event) {
                            'created' => 'success',
                            'updated' => 'warning',
                            'deleted' => 'danger',
                            default => 'info',
                        };
                        $changesPayload = json_encode($act->attribute_changes ?? $act->properties ?? []);
                    @endphp
                    <x-table.tr>
                        <x-table.td class="font-mono font-bold text-gray-900 dark:text-white">#{{ $act->id }}</x-table.td>
                        <x-table.td>
                            <x-badge :variant="$eventVariant" class="uppercase">
                                {{ __($act->event ?? 'event') }}
                            </x-badge>
                        </x-table.td>
                        <x-table.td class="font-medium text-gray-900 dark:text-white">
                            {{ $act->translated_description ?? $act->description }}
                        </x-table.td>
                        <x-table.td class="font-mono text-xs">
                            @if($act->subject_type)
                                <span class="text-gray-700 dark:text-gray-300">{{ class_basename($act->subject_type) }}</span>
                                <span class="text-gray-400">#{{ $act->subject_id }}</span>
                            @else
                                <span class="text-gray-400 italic">{{ __('None') }}</span>
                            @endif
                        </x-table.td>
                        <x-table.td class="whitespace-nowrap">
                            @if($act->causer)
                                <div class="flex items-center gap-2.5">
                                    @if(method_exists($act->causer, 'getProfilePhotoUrlAttribute') && $act->causer->profile_photo_url)
                                        <img src="{{ $act->causer->profile_photo_url }}"
                                             alt="{{ $act->causer->name }}"
                                             class="w-7 h-7 rounded-full object-cover border border-gray-200 dark:border-gray-700 shrink-0">
                                    @else
                                        <div class="w-7 h-7 rounded-full bg-brand-600/10 dark:bg-brand-600/20 text-brand-700 dark:text-brand-400 flex items-center justify-center font-bold text-xs shrink-0">
                                            {{ strtoupper(substr($act->causer->name ?? $act->causer->email ?? 'U', 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <span class="font-semibold text-xs text-gray-900 dark:text-white block truncate">
                                            {{ $act->causer->name ?? $act->causer->email }}
                                        </span>
                                        @if(!empty($act->causer->email) && $act->causer->name && $act->causer->email !== $act->causer->name)
                                            <span class="font-mono text-[11px] text-gray-500 dark:text-gray-400 block truncate">
                                                {{ $act->causer->email }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @elseif($act->causer_id)
                                <span class="font-mono text-xs text-gray-500">{{ __('ID') }}: {{ $act->causer_id }}</span>
                            @else
                                <span class="text-xs text-gray-400 dark:text-gray-500 italic">{{ __('System') }}</span>
                            @endif
                        </x-table.td>
                        <x-table.td class="whitespace-nowrap">
                            <x-date :value="$act->created_at" format="timestamp" />
                        </x-table.td>
                        <x-table.td class="whitespace-nowrap text-end">
                            <x-table.actions class="justify-end">
                                <x-table.action-view
                                    @click="modalOpen = true; modalTitle = '{{ addslashes($act->translated_description ?? $act->description) }}'; modalChanges = {{ $changesPayload }}"
                                    :title="__('View Changes')">
                                    {{ __('View Changes') }}
                                </x-table.action-view>
                            </x-table.actions>
                        </x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.empty colspan="7" />
                @endforelse

                @if($activities->hasPages())
                    <x-slot:pagination>
                        {{ $activities->links() }}
                    </x-slot:pagination>
                @endif
            </x-table>
                </main>
            </div>
        </div>

        <!-- Alpine.js Changes Inspection Modal -->
        <div x-show="modalOpen"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
             @keydown.escape.window="modalOpen = false">
            <div class="relative w-full max-w-2xl rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-xl border border-gray-200 dark:border-gray-700"
                 @click.outside="modalOpen = false">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                    <h4 class="text-base font-bold text-gray-900 dark:text-white" x-text="modalTitle"></h4>
                    <button type="button" @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="py-4 max-h-96 overflow-y-auto space-y-4">
                    <template x-if="modalChanges && (modalChanges.attributes || modalChanges.old)">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Old values -->
                            <div class="rounded-lg bg-rose-50 dark:bg-rose-950/20 p-3 border border-rose-200/50 dark:border-rose-900/50">
                                <p class="text-xs font-bold text-rose-700 dark:text-rose-400 uppercase tracking-wider mb-2">
                                    {{ __('Old Values') }}
                                </p>
                                <pre class="text-xs font-mono text-gray-800 dark:text-gray-200 overflow-x-auto whitespace-pre-wrap" x-text="JSON.stringify(modalChanges.old || {}, null, 2)"></pre>
                            </div>

                            <!-- New values -->
                            <div class="rounded-lg bg-emerald-50 dark:bg-emerald-950/20 p-3 border border-emerald-200/50 dark:border-emerald-900/50">
                                <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-2">
                                    {{ __('New Values') }}
                                </p>
                                <pre class="text-xs font-mono text-gray-800 dark:text-gray-200 overflow-x-auto whitespace-pre-wrap" x-text="JSON.stringify(modalChanges.attributes || {}, null, 2)"></pre>
                            </div>
                        </div>
                    </template>

                    <template x-if="!modalChanges || (!modalChanges.attributes && !modalChanges.old)">
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-900/50 p-4 border border-gray-200 dark:border-gray-700">
                            <pre class="text-xs font-mono text-gray-800 dark:text-gray-200 overflow-x-auto whitespace-pre-wrap" x-text="JSON.stringify(modalChanges || {}, null, 2)"></pre>
                        </div>
                    </template>
                </div>

                <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                    <x-secondary-button type="button"
                            @click="modalOpen = false"
                            class="py-1.5 px-4 text-xs rounded-lg">
                        {{ __('Close') }}
                    </x-secondary-button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
