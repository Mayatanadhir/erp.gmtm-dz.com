<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                    {{ __('Cache & Locks') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('Inspect cached memory stores, TTL expiration, and atomic race-condition locks') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="primary" size="md">
                    {{ $cacheEntries->total() }} {{ __('Cached Items') }}
                </x-badge>
                <x-badge variant="info" size="md">
                    {{ $cacheLocks->total() }} {{ __('Active Locks') }}
                </x-badge>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-system-tabs active="cache" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-6">

            <!-- Cache Entries Section -->
            <x-table>
                <x-slot:toolbar>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm0 5h16M9 4v16"></path></svg>
                        <span>{{ __('Cache Entries') }} (<code>cache</code>)</span>
                    </h3>
                    <span class="text-xs text-gray-500 font-mono">{{ $cacheEntries->total() }} {{ __('keys') }}</span>
                </x-slot:toolbar>

                <x-slot:header>
                    <x-table.th>{{ __('Cache Key') }}</x-table.th>
                    <x-table.th>{{ __('Expiration (TTL)') }}</x-table.th>
                    <x-table.th>{{ __('Status') }}</x-table.th>
                    <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                </x-slot:header>

                @forelse($cacheEntries as $c)
                    @php
                        $isExpired = $c->expiration < now()->timestamp;
                    @endphp
                    <x-table.tr>
                        <x-table.td class="font-mono font-semibold text-gray-900 dark:text-white">
                            {{ $c->key }}
                        </x-table.td>
                        <x-table.td class="font-mono text-gray-600 dark:text-gray-400 whitespace-nowrap">
                            {{ \Carbon\Carbon::createFromTimestamp($c->expiration)->diffForHumans() }}
                            <span class="text-gray-400">(<x-date :value="\Carbon\Carbon::createFromTimestamp($c->expiration)" format="timestamp" />)</span>
                        </x-table.td>
                        <x-table.td>
                            @if($isExpired)
                                <x-badge variant="danger" :dot="true">
                                    {{ __('Expired') }}
                                </x-badge>
                            @else
                                <x-badge variant="success" :dot="true">
                                    {{ __('Active') }}
                                </x-badge>
                            @endif
                        </x-table.td>
                        <x-table.td class="whitespace-nowrap text-end">
                            <x-table.actions class="justify-end">
                                <x-table.action-delete :title="__('Forget Key')" />
                            </x-table.actions>
                        </x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.empty colspan="4" />
                @endforelse

                @if($cacheEntries->hasPages())
                    <x-slot:pagination>
                        {{ $cacheEntries->links() }}
                    </x-slot:pagination>
                @endif
            </x-table>

            <!-- Cache Locks Section -->
            <x-table>
                <x-slot:toolbar>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        <span>{{ __('Active Atomic Locks') }} (<code>cache_locks</code>)</span>
                    </h3>
                    <span class="text-xs text-gray-500 font-mono">{{ $cacheLocks->total() }} {{ __('locks') }}</span>
                </x-slot:toolbar>

                <x-slot:header>
                    <x-table.th>{{ __('Lock Key') }}</x-table.th>
                    <x-table.th>{{ __('Owner Token') }}</x-table.th>
                    <x-table.th>{{ __('Expiration') }}</x-table.th>
                    <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                </x-slot:header>

                @forelse($cacheLocks as $lock)
                    <x-table.tr>
                        <x-table.td class="font-mono font-semibold text-gray-900 dark:text-white">{{ $lock->key }}</x-table.td>
                        <x-table.td class="font-mono text-gray-500">{{ $lock->owner }}</x-table.td>
                        <x-table.td class="font-mono text-gray-600 dark:text-gray-400 whitespace-nowrap">
                            {{ \Carbon\Carbon::createFromTimestamp($lock->expiration)->diffForHumans() }}
                        </x-table.td>
                        <x-table.td class="whitespace-nowrap text-end">
                            <x-table.actions class="justify-end">
                                <x-table.action-delete :title="__('Release Lock')" />
                            </x-table.actions>
                        </x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.empty colspan="4" :message="__('No active locks at this moment.')" />
                @endforelse

                @if($cacheLocks->hasPages())
                    <x-slot:pagination>
                        {{ $cacheLocks->links() }}
                    </x-slot:pagination>
                @endif
            </x-table>
                </main>
            </div>
        </div>
    </div>
</x-app-layout>
