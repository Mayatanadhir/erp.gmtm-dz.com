<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <x-tool-icon name="contracts" class="w-11 h-11 sm:w-12 sm:h-12 shrink-0" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Contracts Management') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Manage customer contracts, service agreements, and commercial commitments') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="info" size="md">
                    {{ $contracts->total() }} {{ __('Contracts') }}
                </x-badge>
                @can('create contracts')
                    <a href="{{ route('operations.contracts.create') }}" class="btn-primary flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>{{ __('Create Contract') }}</span>
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-operations-tabs active="contracts" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-6">
                    @if (session('success'))
                        <x-alert variant="success" class="mb-6">
                            {{ session('success') }}
                        </x-alert>
                    @endif

                    @if (session('error'))
                        <x-alert variant="danger" class="mb-6">
                            {{ session('error') }}
                        </x-alert>
                    @endif

                    @if (session('warning'))
                        <x-alert variant="warning" class="mb-6">
                            {{ session('warning') }}
                        </x-alert>
                    @endif

                    <!-- Filter Bar -->
                    <x-global-filter
                        :action="route('operations.contracts')"
                        :search-placeholder="__('Reference or object...')"
                        :search-value="request('search')"
                        :search-width="'w-64 sm:w-80'"
                    >
                        {{-- Customer Filter --}}
                        <x-global-filter.select
                            name="customer_id"
                            :placeholder="__('All Customers')"
                            :value="request('customer_id')"
                        >
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(request('customer_id') == $customer->id)>
                                    {{ $customer->short_name ?? $customer->company_name }}
                                </option>
                            @endforeach
                        </x-global-filter.select>
                    </x-global-filter>

                    <!-- Contracts Table -->
                    <x-table>
                        <x-slot:toolbar>
                            <div class="flex items-center justify-between w-full">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                                    <x-tool-icon name="contracts" class="w-5 h-5 shrink-0" />
                                    <span>{{ __('Contracts Catalog') }}</span>
                                    <span class="ms-1.5 px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/70">
                                        {{ $contracts->total() }}
                                    </span>
                                </h3>
                            </div>
                        </x-slot:toolbar>

                        <x-slot:header>
                            <x-table.th>{{ __('Reference') }}</x-table.th>
                            <x-table.th>{{ __('Customer') }}</x-table.th>
                            <x-table.th>{{ __('Timeline') }}</x-table.th>
                            <x-table.th>{{ __('Phase') }}</x-table.th>
                            <x-table.th>{{ __('Planned Amount') }}</x-table.th>
                            <x-table.th>{{ __('Guarantee') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                        </x-slot:header>

                        @forelse ($contracts as $contract)
                            <x-table.tr>
                                <!-- Reference -->
                                <x-table.td class="font-semibold">
                                    <div class="flex flex-col">
                                        <a href="{{ route('operations.contracts.show', $contract->id) }}" class="text-brand-600 dark:text-brand-400 hover:underline flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span class="font-mono"><bdi>{{ $contract->reference }}</bdi></span>
                                        </a>                                     
                                        <span class="text-xs text-gray-400 dark:text-gray-500 font-normal mt-0.5">
                                            <bdi class="font-mono">{{ $contract->items_count }}</bdi> {{ __('items') }}
                                        </span>
                                    </div>
                                </x-table.td>

                                <!-- Customer -->
                                <x-table.td>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ $contract->customer?->short_name ?? $contract->customer?->company_name ?? '—' }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                            <bdi>{{ $contract->customer?->code ?? '' }}</bdi>
                                        </span>
                                    </div>
                                </x-table.td>

                                <!-- Timeline -->
                                <x-table.td>
                                    <div class="flex flex-col text-xs text-gray-600 dark:text-gray-300">
                                        <span class="font-mono"><bdi>{{ $contract->date_signature?->format('d/m/Y') ?? '—' }}</bdi></span>
                                        <span class="text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ $contract->duree ? $contract->duree . ' ' . __('months') : '—' }}
                                        </span>
                                    </div>
                                </x-table.td>

                                <!-- Phase / Status -->
                                <x-table.td>
                                    @php
                                        $variant = match ($contract->expiry_status) {
                                            'start' => 'success',
                                            'mid' => 'info',
                                            'end' => 'warning',
                                            'archive' => 'neutral',
                                            default => 'neutral',
                                        };
                                        $phaseLabel = match ($contract->expiry_status) {
                                            'start' => __('Start'),
                                            'mid' => __('Mid-term'),
                                            'end' => __('Near Expiry'),
                                            'archive' => __('Archived/Expired'),
                                            default => __('Unknown'),
                                        };
                                    @endphp
                                    <x-badge :variant="$variant" :dot="true">
                                        {{ $phaseLabel }}
                                    </x-badge>
                                    @if ($contract->remain_days !== null)
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                            @if ($contract->remain_days >= 0)
                                                <bdi class="font-mono">{{ $contract->remain_days }}</bdi> {{ __('days left') }}
                                            @else
                                                {{ __('Expired') }}
                                            @endif
                                        </div>
                                    @endif
                                </x-table.td>

                                <!-- Planned Amount -->
                                <x-table.td>
                                    <div class="font-medium text-xs text-gray-900 dark:text-white font-mono">
                                        <bdi>{{ number_format((float) $contract->montant_global_prevu, 2, '.', ' ') }}</bdi> DA
                                    </div>
                                </x-table.td>

                                <!-- Guarantee -->
                                <x-table.td>
                                    @if ($contract->warranty)
                                        <div class="flex flex-col text-xs">
                                            <span class="font-medium text-gray-900 dark:text-white flex items-center gap-1 font-mono">
                                                <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                <bdi>{{ $contract->warranty->reference }}</bdi>
                                            </span>
                                            <span class="text-gray-500 dark:text-gray-400 font-mono">
                                                <bdi>{{ number_format((float) $contract->warranty->amount, 2, '.', ' ') }}</bdi> DA
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </x-table.td>

                                <!-- Actions -->
                                <x-table.td class="text-end">
                                    <x-table.actions>
                                        <x-table.action-view
                                            href="{{ route('operations.contracts.show', $contract->id) }}"
                                            :title="__('View Details')"
                                        />

                                        @can('edit contracts')
                                            <x-table.action-edit
                                                href="{{ route('operations.contracts.edit', $contract->id) }}"
                                                :title="__('Edit')"
                                            />
                                        @endcan

                                        <x-table.action-stats
                                            href="{{ route('operations.contracts.statistics', $contract->id) }}"
                                            :title="__('Financial Statistics')"
                                        />

                                        @can('delete contracts')
                                            <x-table.action-delete
                                                :action-url="route('operations.contracts.destroy', $contract->id)"
                                                :item-name="$contract->contract_number ?? $contract->reference"
                                                :title="__('Delete Contract')"
                                            />
                                        @endcan
                                    </x-table.actions>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty :colspan="7" :message="__('No contracts found matching the selected criteria.')" />
                        @endforelse
                    </x-table>

                    <!-- Pagination -->
                    @if ($contracts->hasPages())
                        <div class="mt-4">
                            {{ $contracts->links() }}
                        </div>
                    @endif
                </main>
            </div>
        </div>
    </div>
</x-app-layout>
