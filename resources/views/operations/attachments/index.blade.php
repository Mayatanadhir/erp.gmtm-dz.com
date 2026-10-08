<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <x-tool-icon name="attachments" class="w-11 h-11 sm:w-12 sm:h-12 shrink-0" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Attachments List') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Manage mission attachments, contractual annexes, and technical appendices') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <x-badge variant="info" size="md">
                    {{ $attachments->total() }} {{ __('Attachments') }}
                </x-badge>
                @can('create attachments')
                    <a href="{{ route('operations.attachments.create') }}" class="btn-primary inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>{{ __('Create Attachment') }}</span>
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
                    <x-operations-tabs active="attachments" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-6">
                    @if (session('success'))
                        <x-alert variant="success" class="mb-4">
                            {{ session('success') }}
                        </x-alert>
                    @endif

                    @if (session('error'))
                        <x-alert variant="danger" class="mb-4">
                            {{ session('error') }}
                        </x-alert>
                    @endif

                    <!-- Top KPI Summary Cards -->
                    <div x-data="{ 
                        selectedYear: '{{ in_array($stats['current_year'], $stats['yearly_years'], true) ? $stats['current_year'] : 'all' }}', 
                        yearlyInvoiced: {{ \Illuminate\Support\Js::from($stats['yearly_invoiced']) }},
                        yearlyApproved: {{ \Illuminate\Support\Js::from($stats['yearly_approved']) }},
                        yearlyTotal: {{ \Illuminate\Support\Js::from($stats['yearly_total']) }},
                        yearlyDraft: {{ \Illuminate\Support\Js::from($stats['yearly_draft']) }}
                    }" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Total Attachments -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('Total Attachments') }}
                                    </span>
                                    <span class="p-2 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </span>
                                </div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                                    <span x-text="yearlyTotal[selectedYear] ?? yearlyTotal['all']">
                                        {{ $stats['yearly_total'][$stats['current_year']] ?? $stats['total'] }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-2 flex items-center justify-between">
                                <span>{{ __('Grand Total') }}: <strong class="font-bold text-gray-700 dark:text-gray-200" x-text="yearlyTotal['all']">{{ number_format($stats['total']) }}</strong></span>
                                <span class="text-[11px] font-semibold text-blue-600 dark:text-blue-400" x-text="selectedYear === 'all' ? '{{ __('All Years') }}' : selectedYear"></span>
                            </div>
                        </div>

                        <!-- Approved Attachments -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                                        {{ __('Approved / Invoiced') }}
                                    </span>
                                    <span class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </span>
                                </div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                                    <span x-text="yearlyApproved[selectedYear] ?? yearlyApproved['all']">
                                        {{ $stats['yearly_approved'][$stats['current_year']] ?? $stats['approved'] }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 flex items-center justify-between">
                                <span x-text="((yearlyTotal[selectedYear] > 0 ? Math.round((yearlyApproved[selectedYear] / yearlyTotal[selectedYear]) * 1000) / 10 : 0) + '% {{ __('validation rate') }}')">
                                    {{ $stats['total'] > 0 ? round(($stats['approved'] / $stats['total']) * 100, 1) : 0 }}% {{ __('validation rate') }}
                                </span>
                                <span class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-300" x-text="selectedYear === 'all' ? '{{ __('All Years') }}' : selectedYear"></span>
                            </div>
                        </div>

                        <!-- Draft Attachments -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">
                                        {{ __('Draft / Pending') }}
                                    </span>
                                    <span class="p-2 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </span>
                                </div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                                    <span x-text="yearlyDraft[selectedYear] ?? yearlyDraft['all']">
                                        {{ $stats['yearly_draft'][$stats['current_year']] ?? $stats['draft'] }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-xs text-amber-600 dark:text-amber-400 mt-2 flex items-center justify-between">
                                <span>{{ __('Under review') }}</span>
                                <span class="text-[11px] font-semibold text-amber-700 dark:text-amber-300" x-text="selectedYear === 'all' ? '{{ __('All Years') }}' : selectedYear"></span>
                            </div>
                        </div>

                        <!-- Total Invoiced / Valorized -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                                        {{ __('Total Invoiced') }}
                                    </span>
                                    <div class="flex items-center gap-1.5">
                                        <!-- Inline Annual Selector -->
                                        <select x-model="selectedYear" class="text-[11px] font-semibold py-1 px-2 pe-6 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:ring-indigo-500 focus:border-indigo-500 transition-colors shadow-none cursor-pointer">
                                            @foreach($stats['yearly_years'] as $yr)
                                                <option value="{{ $yr }}">{{ $yr }}</option>
                                            @endforeach
                                            <option value="all">{{ __('All Years') }}</option>
                                        </select>
                                        <span class="p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-xl font-bold text-gray-900 dark:text-white mt-2 truncate" :title="yearlyInvoiced[selectedYear] ?? yearlyInvoiced['all']">
                                    <span dir="ltr" x-text="yearlyInvoiced[selectedYear] ?? yearlyInvoiced['all']">
                                        {{ $stats['yearly_invoiced'][$stats['current_year']] ?? $stats['yearly_invoiced']['all'] }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-2 flex items-center justify-between">
                                <span>{{ __('Approved works sum') }}</span>
                                <span class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400" x-text="selectedYear === 'all' ? '{{ __('All Years') }}' : selectedYear"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Bar -->
                    <x-global-filter
                        :action="route('operations.attachments')"
                        :search-placeholder="__('Code Ref, ODS, Mission, Site, Contract...')"
                        :search-value="request('search')"
                        :search-width="'w-64 sm:w-80'"
                    >
                        {{-- Status Filter --}}
                        <x-global-filter.select
                            name="status"
                            :placeholder="__('All Statuses')"
                            :value="request('status')"
                            :options="['approved' => __('Approved'), 'draft' => __('Draft')]"
                        />

                        {{-- Nature / Type Filter --}}
                        <x-global-filter.select
                            name="type"
                            :placeholder="__('All Natures')"
                            :value="request('type')"
                            :options="['service' => __('Service (Prestation)'), 'supply' => __('Supply (Fourniture)')]"
                        />
                    </x-global-filter>

                    <!-- Attachments Table -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                                <x-tool-icon name="attachments" class="w-6 h-6 shrink-0" />
                                <span>{{ __('Work Attachments & Consumptions') }}</span>
                                <span class="ms-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">
                                    {{ $attachments->total() }}
                                </span>
                            </h3>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-start text-xs">
                                <thead class="bg-gray-50 dark:bg-gray-900/50 text-[11px] font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 whitespace-nowrap">
                                    <tr>
                                        <th class="py-2.5 px-3 text-start whitespace-nowrap">{{ __('Reference Code') }}</th>
                                        <th class="py-2.5 px-3 text-start whitespace-nowrap">{{ __('Mission & Site') }}</th>
                                        <th class="py-2.5 px-3 text-start whitespace-nowrap">{{ __('Contract & Client') }}</th>
                                        <th class="py-2.5 px-3 text-center whitespace-nowrap">{{ __('Date') }}</th>
                                        <th class="py-2.5 px-3 text-center whitespace-nowrap">{{ __('Nature / Cycle') }}</th>
                                        <th class="py-2.5 px-3 text-center whitespace-nowrap">{{ __('Items') }}</th>
                                        <th class="py-2.5 px-3 text-end whitespace-nowrap">{{ __('Valorization') }}</th>
                                        <th class="py-2.5 px-3 text-center whitespace-nowrap">{{ __('Status') }}</th>
                                        <th class="py-2.5 px-3 text-end whitespace-nowrap">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @forelse($attachments as $attachment)
                                        @php
                                            $mission = $attachment->mission;
                                            $contract = $mission?->contract;
                                            $customer = $contract?->customer;
                                            $site = $mission?->site;
                                            $totalAmount = $attachment->total_amount;
                                        @endphp
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors whitespace-nowrap">
                                            <!-- Code Ref & ODS -->
                                            <td class="py-2.5 px-3 whitespace-nowrap">
                                                <a href="{{ route('operations.attachments.show', $attachment->id) }}" class="font-bold text-xs text-gray-900 dark:text-white hover:text-brand-600 dark:hover:text-brand-400">
                                                    {{ $attachment->code_ref ?? '—' }}
                                                </a>
                                                @if($attachment->ods)
                                                    <div class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5 whitespace-nowrap">
                                                        <span class="font-semibold">ODS:</span> {{ $attachment->ods }}
                                                    </div>
                                                @endif
                                            </td>

                                            <!-- Mission & Site -->
                                            <td class="py-2.5 px-3 text-xs whitespace-nowrap">
                                                @if($mission)
                                                    <a href="{{ route('operations.missions.show', $mission->id) }}" class="font-semibold text-brand-600 dark:text-brand-400 hover:underline">
                                                        {{ $mission->reference }}
                                                    </a>
                                                    @if($site)
                                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 whitespace-nowrap">
                                                            {{ $site->short_name ?? $site->full_name }}
                                                        </div>
                                                    @endif
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>

                                            <!-- Contract & Client -->
                                            <td class="py-2.5 px-3 text-xs whitespace-nowrap">
                                                @if($contract)
                                                    <a href="{{ route('operations.contracts.show', $contract->id) }}" class="font-medium text-gray-900 dark:text-white hover:text-brand-600 dark:hover:text-brand-400 hover:underline">
                                                        {{ $contract->reference }}
                                                    </a>
                                                    @if($customer)
                                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 whitespace-nowrap" title="{{ $customer->company_name }}">
                                                            {{ $customer->short_name ?? $customer->company_name }}
                                                        </div>
                                                    @endif
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>

                                            <!-- Date -->
                                            <td class="py-2.5 px-3 text-center text-xs text-gray-600 dark:text-gray-300 font-medium whitespace-nowrap">
                                                <span dir="ltr">{{ $attachment->date?->format('d/m/Y') ?? '—' }}</span>
                                            </td>

                                            <!-- Nature & Cycle -->
                                            <td class="py-2.5 px-3 text-center text-xs whitespace-nowrap">
                                                <div class="flex flex-col items-center gap-0.5">
                                                    <x-badge :variant="$attachment->type === 'supply' ? 'neutral' : 'info'" size="sm">
                                                        {{ $attachment->type === 'supply' ? __('Supply') : __('Service') }}
                                                    </x-badge>
                                                    @if($attachment->frequency)
                                                        <span class="text-[10px] text-gray-500 dark:text-gray-400 capitalize whitespace-nowrap">
                                                            {{ $attachment->frequency?->label() ?? $attachment->frequency }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>

                                            <!-- Items Count -->
                                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                                    {{ $attachment->items->count() }} {{ __('items') }}
                                                </span>
                                            </td>

                                            <!-- Total Valorized Amount -->
                                            <td class="py-2.5 px-3 text-end whitespace-nowrap">
                                                <div class="font-bold text-xs text-gray-900 dark:text-white">
                                                    <span dir="ltr">{{ number_format((float) $totalAmount, 2, '.', ' ') }} DA</span>
                                                </div>
                                               
                                            </td>

                                            <!-- Status -->
                                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                                <x-badge :variant="$attachment->status === 'approved' ? 'success' : 'warning'" :dot="true" size="sm">
                                                    {{ $attachment->status === 'approved' ? __('Approved') : ucfirst((string) $attachment->status) }}
                                                </x-badge>
                                            </td>

                                            <!-- Actions -->
                                            <td class="py-2.5 px-3 text-end whitespace-nowrap">
                                                <x-table.actions>
                                                    <!-- Show Page Link -->
                                                    <x-table.action-view
                                                        href="{{ route('operations.attachments.show', $attachment->id) }}"
                                                        :title="__('View Full Details')"
                                                    />

                                                    <!-- Print PV -->
                                                    <x-table.action-print
                                                        href="{{ route('operations.attachments.print_single', $attachment->id) }}"
                                                        target="_blank"
                                                        :title="__('Print Attachement (PV)')"
                                                    />

                                                    @can('edit attachments')
                                                        <x-table.action-edit
                                                            href="{{ route('operations.attachments.edit', $attachment->id) }}"
                                                            :title="__('Edit Attachment')"
                                                        />
                                                    @endcan
                                                </x-table.actions>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="py-12 text-center">
                                                <div class="flex flex-col items-center justify-center">
                                                    <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 mb-3">
                                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    </div>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('No attachments found.') }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Try adjusting your search criteria or resetting filters.') }}</p>
                                                    @if (request()->hasAny(['search', 'status', 'type', 'frequency', 'contract_id']))
                                                        <a href="{{ route('operations.attachments') }}" class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 transition-colors">
                                                            {{ __('Reset Filters') }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if ($attachments->hasPages())
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $attachments->links() }}
                            </div>
                        @endif
                    </div>
                </main>
            </div>
        </div>
    </div>
</x-app-layout>
