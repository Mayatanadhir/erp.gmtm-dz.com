<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('operations.contracts') }}" class="p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight font-mono">
                            <bdi>{{ $contract->reference }}</bdi>
                        </h2>
                        <x-badge :variant="$contract->expiryBadgeVariant()" :dot="true">
                            {{ $contract->expiryPhaseLabel() }}
                        </x-badge>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $contract->object ?: __('Commercial Service Contract') }}
                    </p>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-2">
                <x-secondary-button href="{{ route('operations.contracts.statistics', $contract->id) }}" class="gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>{{ __('Unit Economics') }}</span>
                </x-secondary-button>

                @can('edit contracts')
                    <x-edit-button href="{{ route('operations.contracts.edit', $contract->id) }}">
                        {{ __('Edit') }}
                    </x-edit-button>
                @endcan

                @can('delete contracts')
                    <x-danger-button
                        type="button"
                        class="gap-2 text-xs sm:text-sm !py-2 !px-3.5"
                        x-data
                        @click="$dispatch('open-delete-modal', {
                            action: '{{ route('operations.contracts.destroy', $contract->id) }}',
                            name: '{{ addslashes($contract->contract_number ?? $contract->reference ?? '') }}',
                            title: '{{ __('Delete Contract') }}'
                        })"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>{{ __('Delete') }}</span>
                    </x-danger-button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
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

            <!-- 1. Top KPI Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Unconsumed Value (Items sum - Total Consumed) -->
                <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 flex flex-col justify-between">
                    <div class="text-xs font-semibold {{ ($rawUnconsumed ?? $contract->rawTotalUnconsumed()) < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-blue-600 dark:text-blue-400' }} uppercase tracking-wider whitespace-nowrap">
                        {{ __('Unconsumed Value') }}
                    </div>
                    <div class="text-lg sm:text-xl font-bold {{ ($rawUnconsumed ?? $contract->rawTotalUnconsumed()) < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-blue-700 dark:text-blue-300' }} mt-1 font-mono whitespace-nowrap">
                        <bdi>{{ $totalUnconsumed ?? $contract->totalUnconsumed() }}</bdi>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 whitespace-nowrap">
                        {{ __('Items sum') }}: <span class="font-mono"><bdi>{{ $totalPlanned ?? $contract->totalPlanned() }}</bdi></span>
                    </div>
                </div>

                <!-- Services (Nature: Service) -->
                <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 flex flex-col justify-between">
                    <div class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider flex items-center justify-between gap-2 whitespace-nowrap">
                        <span>{{ __('Services') }}</span>
                        <x-badge variant="info" size="sm" class="shrink-0">{{ __('Service') }}</x-badge>
                    </div>
                    <div class="text-lg sm:text-xl font-bold text-indigo-700 dark:text-indigo-300 mt-1 font-mono whitespace-nowrap">
                        <bdi>{{ $servicesTotalPlanned ?? $contract->servicesTotalPlanned() }}</bdi>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 whitespace-nowrap">
                        <span class="font-mono"><bdi>{{ $servicesCount ?? $contract->servicesCount() }}</bdi></span> {{ __('items') }}
                    </div>
                </div>

                <!-- Supplies (Nature: Supply) -->
                <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 flex flex-col justify-between">
                    <div class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider flex items-center justify-between gap-2 whitespace-nowrap">
                        <span>{{ __('Supplies') }}</span>
                        <x-badge variant="neutral" size="sm" class="shrink-0">{{ __('Supply') }}</x-badge>
                    </div>
                    <div class="text-lg sm:text-xl font-bold text-slate-700 dark:text-slate-300 mt-1 font-mono whitespace-nowrap">
                        <bdi>{{ $suppliesTotalPlanned ?? $contract->suppliesTotalPlanned() }}</bdi>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 whitespace-nowrap">
                        <span class="font-mono"><bdi>{{ $suppliesCount ?? $contract->suppliesCount() }}</bdi></span> {{ __('items') }}
                    </div>
                </div>

                <!-- Total Invoiced (Approved) -->
                <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 flex flex-col justify-between">
                    <div class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider whitespace-nowrap">
                        {{ __('Invoiced (Approved)') }}
                    </div>
                    <div class="text-lg sm:text-xl font-bold text-emerald-700 dark:text-emerald-300 mt-1 font-mono whitespace-nowrap">
                        <bdi>{{ $totalInvoiced ?? $contract->totalInvoiced() }}</bdi>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 whitespace-nowrap">
                        {{ __('Approved attachments') }}
                    </div>
                </div>

                <!-- Total Consumed -->
                <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 flex flex-col justify-between">
                    <div class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider whitespace-nowrap">
                        {{ __('Total Consumed') }}
                    </div>
                    <div class="text-lg sm:text-xl font-bold text-amber-700 dark:text-amber-300 mt-1 font-mono whitespace-nowrap">
                        <bdi>{{ $totalConsumed ?? $contract->totalConsumed() }}</bdi>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 whitespace-nowrap">
                        {{ __('All attachment states') }}
                    </div>
                </div>

                <!-- Duration & Timeline -->
                <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 flex flex-col justify-between">
                    <div class="text-xs font-semibold {{ $contract->remain_days !== null && $contract->remain_days < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-indigo-600 dark:text-indigo-400' }} uppercase tracking-wider whitespace-nowrap">
                        {{ __('Time Remaining') }}
                    </div>
                    <div class="text-lg sm:text-xl font-bold {{ $contract->remain_days !== null && $contract->remain_days < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-indigo-700 dark:text-indigo-300' }} mt-1 whitespace-nowrap">
                        @if ($contract->remain_days !== null)
                            @if ($contract->remain_days < 0)
                                {{ __('Expired') }}
                            @else
                                <span class="font-mono"><bdi>{{ $contract->remain_days }}</bdi></span> {{ __('days') }}
                            @endif
                        @else
                            —
                        @endif
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 whitespace-nowrap">
                        @if ($contract->remain_days !== null && $contract->remain_days < 0)
                            <span class="text-rose-500/90 dark:text-rose-400/90 font-medium whitespace-nowrap">
                                {{ __('Expired :days d ago', ['days' => abs($contract->remain_days)]) }}
                            </span>
                        @else
                            <span class="font-mono"><bdi>{{ $contract->percent_remaining }}%</bdi></span> {{ __('remaining of') }} <span class="font-mono"><bdi>{{ $contract->duree }}</bdi></span> {{ __('months') }}
                        @endif
                    </div>
                </div>
            </div>

            <!-- 2. Contract & Guarantee Meta Details -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Contract Info Card -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm space-y-4">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ __('Contract Specifications') }}</span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Customer') }}:</span>
                            <div class="font-semibold text-gray-900 dark:text-white mt-0.5">
                                {{ $contract->customer?->company_name ?? '—' }}
                                @if ($contract->customer?->short_name)
                                    <span class="text-xs text-gray-500 font-normal">(<bdi>{{ $contract->customer->short_name }}</bdi>)</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Date Signature') }}:</span>
                            <div class="font-semibold text-gray-900 dark:text-white mt-0.5">
                                <span class="font-mono"><bdi>{{ $contract->date_signature?->format('d/m/Y') ?? '—' }}</bdi></span>
                            </div>
                        </div>

                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Contract Duration') }}:</span>
                            <div class="font-semibold text-gray-900 dark:text-white mt-0.5">
                                @if ($contract->duree)
                                    <span class="font-mono"><bdi>{{ $contract->duree }}</bdi></span> {{ __('months') }}
                                @else
                                    —
                                @endif
                                @if ($contract->date_signature && $contract->duree)
                                    <span class="text-xs text-gray-500 font-normal">
                                        ({{ __('Ends') }}: <span class="font-mono"><bdi>{{ $contract->date_signature->copy()->addMonths($contract->duree)->format('d/m/Y') }}</bdi></span>)
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Total Items') }}:</span>
                            <div class="font-semibold text-gray-900 dark:text-white mt-0.5">
                                <span class="font-mono"><bdi>{{ $contract->items->count() }}</bdi></span> {{ __('line items') }}
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Object & Scope') }}:</span>
                            <div class="text-gray-800 dark:text-gray-200 mt-0.5 leading-relaxed">
                                {{ $contract->object ?: __('No detailed object specified.') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Guarantee Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm space-y-4">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>{{ __('Bank Guarantee') }}</span>
                    </h3>

                    @if ($contract->warranty)
                        <div class="space-y-3 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 text-xs">{{ __('Guarantee Ref') }}</span>
                                <div class="font-semibold text-gray-900 dark:text-white font-mono">
                                    <bdi>{{ $contract->warranty->reference }}</bdi>
                                </div>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 text-xs">{{ __('Bank Institution') }}</span>
                                <div class="font-medium text-gray-800 dark:text-gray-200">
                                    {{ $contract->warranty->bank ?? '—' }}
                                </div>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 text-xs">{{ __('Guarantee Amount') }}</span>
                                <div class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                                    <bdi>{{ number_format((float) $contract->warranty->amount, 2, '.', ' ') }}</bdi> DA
                                </div>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 text-xs">{{ __('Status') }}</span>
                                <div class="mt-0.5">
                                    <x-badge :variant="$contract->warranty->status->badgeVariant()" size="sm">
                                        {{ $contract->warranty->status->label() }}
                                    </x-badge>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-6">
                            <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('No bank guarantee / performance bond currently linked.') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 3. Contract Items Schedule Table -->
            <x-table>
                <x-slot:toolbar>
                    <div class="flex flex-wrap items-center justify-between gap-3 w-full">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                {{ __('Contract Items & Consumptions') }}
                            </h3>
                            <span class="ms-1.5 px-2 py-0.5 text-xs font-semibold rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-mono">
                                <bdi>{{ $contract->items->count() }}</bdi>
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            @if (($servicesCount ?? $contract->servicesCount()) > 0)
                                <x-badge variant="info" size="sm">
                                    <span class="font-mono"><bdi>{{ $servicesCount ?? $contract->servicesCount() }}</bdi></span> {{ __('Service') }}
                                </x-badge>
                            @endif
                            @if (($suppliesCount ?? $contract->suppliesCount()) > 0)
                                <x-badge variant="neutral" size="sm">
                                    <span class="font-mono"><bdi>{{ $suppliesCount ?? $contract->suppliesCount() }}</bdi></span> {{ __('Supply') }}
                                </x-badge>
                            @endif
                        </div>
                    </div>
                </x-slot:toolbar>

                <x-slot:header>
                    <x-table.th class="w-12">#</x-table.th>
                    <x-table.th>{{ __('Designation') }}</x-table.th>
                    <x-table.th>{{ __('Classification') }}</x-table.th>
                    <x-table.th>{{ __('Nature') }}</x-table.th>
                    <x-table.th>{{ __('Cycle') }}</x-table.th>
                    <x-table.th class="text-end">{{ __('Qty') }}</x-table.th>
                    <x-table.th class="text-end">{{ __('Unit Price') }}</x-table.th>
                    <x-table.th class="text-end">{{ __('Total Planned') }}</x-table.th>
                    <x-table.th class="min-w-[140px]">{{ __('Consumption') }}</x-table.th>
                </x-slot:header>

                @forelse ($contract->items as $idx => $item)
                    <x-table.tr>
                        <x-table.td class="font-medium text-gray-500 font-mono"><bdi>{{ $idx + 1 }}</bdi></x-table.td>
                        <x-table.td class="font-semibold text-gray-900 dark:text-white">
                            {{ $item->designation }}
                        </x-table.td>
                        <x-table.td>
                            {{ $item->itemType?->designation ?? '—' }}
                        </x-table.td>
                        <x-table.td>
                            <x-badge :variant="$item->type === 'service' ? 'info' : 'neutral'" size="sm">
                                {{ __(ucfirst($item->type ?? 'service')) }}
                            </x-badge>
                        </x-table.td>
                        <x-table.td>
                            {{ $item->frequency?->label() ?? '—' }}
                        </x-table.td>
                        <x-table.td class="text-end font-medium text-gray-900 dark:text-white font-mono">
                            <bdi>{{ $item->quantity }}</bdi>
                        </x-table.td>
                        <x-table.td class="text-end font-mono">
                            <bdi>{{ number_format((float) $item->unit_price, 2, '.', ' ') }}</bdi> DA
                        </x-table.td>
                        <x-table.td class="text-end font-mono font-semibold text-gray-900 dark:text-white">
                            <bdi>{{ number_format((float) $item->total, 2, '.', ' ') }}</bdi> DA
                        </x-table.td>
                        <x-table.td>
                            <div class="space-y-1">
                                <div class="flex justify-between text-[11px] text-gray-500 font-mono">
                                    <span><bdi>{{ (float) ($item->attachment_items_sum_quantity ?? 0) }}</bdi> / <bdi>{{ $item->quantity }}</bdi></span>
                                    <span><bdi>{{ $item->consumption_percentage }}%</bdi></span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                    <div class="h-1.5 rounded-full {{ $item->consumption_percentage >= 100 ? 'bg-rose-500' : ($item->consumption_percentage >= 75 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min(100, $item->consumption_percentage) }}%"></div>
                                </div>
                            </div>
                        </x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.empty :colspan="9" :message="__('No items registered in this contract.')" />
                @endforelse
            </x-table>

            <!-- 4. Linked Field Missions -->
            <x-table>
                <x-slot:toolbar>
                    <div class="flex items-center gap-2">
                        <x-tool-icon name="missions" class="w-5 h-5 shrink-0" />
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ __('Associated Field Missions') }}
                        </h3>
                        <span class="ms-1.5 px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/70 font-mono">
                            <bdi>{{ $contract->missions->count() }}</bdi>
                        </span>
                    </div>
                </x-slot:toolbar>

                <x-slot:header>
                    <x-table.th>{{ __('Mission Ref') }}</x-table.th>
                    <x-table.th>{{ __('Site') }}</x-table.th>
                    <x-table.th>{{ __('Start Date') }}</x-table.th>
                    <x-table.th>{{ __('End Date') }}</x-table.th>
                    <x-table.th>{{ __('Status') }}</x-table.th>
                    <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                </x-slot:header>

                @forelse ($contract->missions as $mission)
                    <x-table.tr>
                        <x-table.td class="font-semibold text-brand-600 dark:text-brand-400">
                            <a href="{{ route('operations.missions.show', $mission->id) }}" class="hover:underline font-mono">
                                <bdi>{{ $mission->reference }}</bdi>
                            </a>
                        </x-table.td>
                        <x-table.td>
                            {{ $mission->site?->short_name ?? $mission->site?->full_name ?? '—' }}
                        </x-table.td>
                        <x-table.td class="font-mono">
                            <bdi>{{ $mission->start_date?->format('d/m/Y') ?? '—' }}</bdi>
                        </x-table.td>
                        <x-table.td class="font-mono">
                            <bdi>{{ $mission->end_date?->format('d/m/Y') ?? '—' }}</bdi>
                        </x-table.td>
                        <x-table.td>
                            <x-badge :variant="$mission->status->badgeVariant()" :dot="true" size="sm">
                                {{ $mission->status->label() }}
                            </x-badge>
                        </x-table.td>
                        <x-table.td class="text-end">
                            <x-table.actions class="justify-end">
                                <x-table.action-view :href="route('operations.missions.show', $mission->id)">
                                    {{ __('Details') }}
                                </x-table.action-view>
                            </x-table.actions>
                        </x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.empty :colspan="6" :message="__('No missions linked to this contract yet.')" />
                @endforelse
            </x-table>
        </div>
    </div>
</x-app-layout>
