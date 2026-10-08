<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                    {{ __('Notifications') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('Internal database notifications, recipient targeting, and delivery payloads') }}
                </p>
            </div>
            <x-badge variant="primary" size="md">
                {{ $notifications->total() }} {{ __('Total Notifications') }}
            </x-badge>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ modalOpen: false, modalTitle: '', modalPayload: null }">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-system-tabs active="notifications" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-6">

            <x-table>
                <!-- Toolbar -->
                <x-slot:toolbar>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        <span>{{ __('Database Notifications') }} (<code class="text-xs font-mono px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700/80 text-gray-700 dark:text-gray-200">notifications</code>)</span>
                    </h3>

                    <!-- Status Filter -->
                    <x-global-filter
                        :action="route('system-tables.notifications')"
                        :search="false"
                        :show-submit="false">
                        <x-global-filter.select
                            name="status"
                            :placeholder="__('All Statuses')"
                            :value="$status"
                            :auto-submit="true"
                            :options="[
                                'unread' => __('Unread'),
                                'read' => __('Read')
                            ]"
                        />
                    </x-global-filter>
                </x-slot:toolbar>

                <x-slot:header>
                    <x-table.th>{{ __('UUID') }}</x-table.th>
                    <x-table.th>{{ __('Type') }}</x-table.th>
                    <x-table.th>{{ __('Recipient') }}</x-table.th>
                    <x-table.th>{{ __('Status') }}</x-table.th>
                    <x-table.th>{{ __('Date') }}</x-table.th>
                    <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                </x-slot:header>

                @forelse($notifications as $notif)
                    @php
                        $data = json_decode($notif->data ?? '{}', true);
                    @endphp
                    @php
                        $typeBasename = class_basename($notif->type);
                        $translatedType = match($typeBasename) {
                            'SystemActivityAlert' => __('System Activity Alert'),
                            'ResetPassword', 'ResetPasswordNotification' => __('Password Reset Notification'),
                            'VerifyEmail' => __('Email Verification Notification'),
                            default => __($typeBasename),
                        };
                        $actionType = $data['type'] ?? null;
                        $notificationTitle = $data['title'] ?? null;
                    @endphp
                    <x-table.tr>
                        <x-table.td class="font-mono text-gray-500 dark:text-gray-400">
                            {{ substr($notif->id, 0, 8) }}...
                        </x-table.td>
                        <x-table.td>
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        {{ $translatedType }}
                                    </span>
                                    @if($actionType)
                                        @php
                                            $actionVariant = match($actionType) {
                                                'created', 'success' => 'success',
                                                'updated', 'warning' => 'warning',
                                                'deleted', 'danger' => 'danger',
                                                default => 'info'
                                            };
                                        @endphp
                                        <x-badge :variant="$actionVariant" size="sm" class="uppercase text-[10px]">
                                            {{ __($actionType) }}
                                        </x-badge>
                                    @endif
                                </div>
                                @if($notificationTitle)
                                    <span class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs" title="{{ __($notificationTitle) }}">
                                        {{ __($notificationTitle) }}
                                    </span>
                                @else
                                    <span class="font-mono text-[11px] text-gray-500 dark:text-gray-400">
                                        {{ $typeBasename }}
                                    </span>
                                @endif
                            </div>
                        </x-table.td>
                        <x-table.td class="font-mono text-xs text-gray-700 dark:text-gray-300">
                            {{ __(class_basename($notif->notifiable_type)) }} #{{ $notif->notifiable_id }}
                        </x-table.td>
                        <x-table.td>
                            @if($notif->read_at)
                                <x-badge variant="neutral">
                                    {{ __('Read') }} ({{ \Carbon\Carbon::parse($notif->read_at)->diffForHumans() }})
                                </x-badge>
                            @else
                                <x-badge variant="success" :dot="true">
                                    {{ __('Unread') }}
                                </x-badge>
                            @endif
                        </x-table.td>
                        <x-table.td class="whitespace-nowrap text-gray-600 dark:text-gray-300">
                            <x-date :value="\Carbon\Carbon::parse($notif->created_at)" format="datetime" />
                        </x-table.td>
                        <x-table.td class="whitespace-nowrap text-end">
                            <x-table.actions class="justify-end">
                                <x-table.action-view
                                    @click="modalOpen = true; modalTitle = '{{ addslashes($translatedType) }}'; modalPayload = {{ json_encode($data) }}"
                                    :title="__('View Payload')">
                                    {{ __('View Payload') }}
                                </x-table.action-view>
                                <x-table.action-delete :title="__('Delete Notification')" />
                            </x-table.actions>
                        </x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.empty colspan="6" />
                @endforelse

                @if($notifications->hasPages())
                    <x-slot:pagination>
                        {{ $notifications->links() }}
                    </x-slot:pagination>
                @endif
            </x-table>
                </main>
            </div>
        </div>

        <!-- Alpine Payload Modal -->
        <div x-show="modalOpen"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
             @keydown.escape.window="modalOpen = false">
            <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-xl border border-gray-200 dark:border-gray-700"
                 @click.outside="modalOpen = false">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                    <h4 class="text-base font-bold text-gray-900 dark:text-white" x-text="modalTitle"></h4>
                    <button type="button" @click="modalOpen = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-gray-700/50 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="py-4 max-h-80 overflow-y-auto">
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-900/80 p-4 border border-gray-200 dark:border-gray-700/80 shadow-inner">
                        <pre class="text-xs font-mono text-gray-800 dark:text-gray-200 overflow-x-auto whitespace-pre-wrap" x-text="JSON.stringify(modalPayload || {}, null, 2)"></pre>
                    </div>
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
