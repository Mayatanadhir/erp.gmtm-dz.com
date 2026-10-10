<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('operations.attachments') }}" class="p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ $attachment->code_ref ?? $attachment->ods }}
                        </h2>
                        <x-badge :variant="$attachment->status === 'approved' ? 'success' : 'warning'" :dot="true">
                            {{ $attachment->status === 'approved' ? __('Approved') : ucfirst((string) $attachment->status) }}
                        </x-badge>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Work completion voucher and contractual line items delivery note') }}
                    </p>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Print Prestation PV -->
                <a href="{{ route('operations.attachments.print_single', $attachment->id) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>{{ __('Print PV') }}</span>
                </a>

                <!-- Print Delivery Note (BL) -->
                <a href="{{ route('operations.attachments.print_bl', $attachment->id) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 hover:bg-teal-100 dark:hover:bg-teal-900/50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>{{ __('Print BL') }}</span>
                </a>

                @can('edit attachments')
                    <!-- Status Workflow Toggle -->
                    @if($attachment->status !== 'approved')
                        <form action="{{ route('operations.attachments.update_status', $attachment->id) }}" method="POST" class="inline-block">
                            @csrf @method('PATCH')
                            <x-success-button type="submit" class="gap-1.5 text-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ __('Approve Attachment') }}</span>
                            </x-success-button>
                        </form>
                    @else
                        <form action="{{ route('operations.attachments.revert', $attachment->id) }}" method="POST" class="inline-block" x-data>
                            @csrf @method('PATCH')
                            <x-secondary-button type="button" @click="if (confirm({{ json_encode(__('Are you sure you want to revert this attachment to draft?')) }})) { $el.closest('form').submit(); }" class="gap-1.5 text-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                <span>{{ __('Revert to Draft') }}</span>
                            </x-secondary-button>
                        </form>
                    @endif

                    <x-edit-button href="{{ route('operations.attachments.edit', $attachment->id) }}" class="gap-1.5 text-xs" />
                @endcan

                @can('delete attachments')
                    <x-danger-button
                        type="button"
                        x-data
                        @click="$dispatch('open-delete-modal', {
                            action: '{{ route('operations.attachments.destroy', $attachment->id) }}',
                            name: '{{ addslashes($attachment->attachment_number ?? $attachment->title ?? '') }}',
                            title: '{{ __('Delete Attachment') }}'
                        })"
                        class="gap-1.5 text-xs"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>{{ __('Delete') }}</span>
                    </x-danger-button>
                @endcan
            </div>
        </div>
    </x-slot>

    @php
        $mission = $attachment->mission;
        $contract = $mission?->contract;
        $customer = $contract?->customer;
        $site = $mission?->site;
        $totalAmount = $attachment->total_amount;
        $totalPlannedAmount = $attachment->total_planned_amount;
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
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

            <!-- 1. Top KPI Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Invoiced Amount -->
                <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60">
                    <div class="text-xs font-semibold text-brand-600 dark:text-brand-400 uppercase tracking-wider">
                        {{ __('Actual Invoiced Total') }}
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        <span dir="ltr">{{ number_format((float) $totalAmount, 2, '.', ' ') }} DA</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('Planned') }}: <span dir="ltr">{{ number_format((float) $totalPlannedAmount, 2, '.', ' ') }} DA</span>
                    </div>
                </div>

                <!-- Total Quantity Consumed -->
                <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60">
                    <div class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                        {{ __('Consumed Units') }}
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ (float) $attachment->total_actual_quantity }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('Planned') }}: {{ (float) $attachment->total_planned_quantity }}
                    </div>
                </div>

                <!-- Number of Items -->
                <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('Line Items') }}
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ $attachment->items->count() }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('Distinct contract lines') }}
                    </div>
                </div>

                <!-- Nature & Frequency -->
                <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60">
                    <div class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                        {{ __('Contractual Terms') }}
                    </div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white mt-1 capitalize">
                        {{ $attachment->type === 'supply' ? __('Supply') : __('Service') }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 capitalize">
                        {{ $attachment->frequency?->label() ?? ($attachment->frequency ?? '—') }}
                    </div>
                </div>
            </div>

            <!-- 2. Dual Metadata Cards (Attachment & Contract) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Attachment Details Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>{{ __('Attachment Details') }}</span>
                    </h3>

                    <dl class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Reference Code') }}</dt>
                            <dd class="font-bold text-gray-900 dark:text-white text-sm mt-0.5">{{ $attachment->code_ref ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('ODS Reference') }}</dt>
                            <dd class="font-bold text-gray-900 dark:text-white text-sm mt-0.5">{{ $attachment->ods ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Execution Date') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white mt-0.5">{{ $attachment->date?->format('d/m/Y') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Validation Status') }}</dt>
                            <dd class="mt-0.5">
                                <x-badge :variant="$attachment->status === 'approved' ? 'success' : 'warning'" size="sm">
                                    {{ $attachment->status === 'approved' ? __('Approved') : ucfirst((string) $attachment->status) }}
                                </x-badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Nature of Works') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white mt-0.5 capitalize">{{ $attachment->type === 'supply' ? __('Supply (Fourniture)') : __('Service (Prestation)') }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Billing Frequency') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white mt-0.5 capitalize">{{ $attachment->frequency?->label() ?? ($attachment->frequency ?? '—') }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Contract & Mission Association Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>{{ __('Contract & Mission Origin') }}</span>
                    </h3>

                    <dl class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Commercial Contract') }}</dt>
                            <dd class="mt-0.5">
                                @if($contract)
                                    <a href="{{ route('operations.contracts.show', $contract->id) }}" class="font-bold text-brand-600 dark:text-brand-400 hover:underline">
                                        {{ $contract->reference }}
                                    </a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Customer / Client') }}</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white mt-0.5 truncate" title="{{ $customer?->company_name }}">
                                {{ $customer?->company_name ?? ($customer?->short_name ?? '—') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Field Mission') }}</dt>
                            <dd class="mt-0.5">
                                @if($mission)
                                    <a href="{{ route('operations.missions.show', $mission->id) }}" class="font-bold text-brand-600 dark:text-brand-400 hover:underline">
                                        {{ $mission->reference }}
                                    </a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Industrial Site') }}</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white mt-0.5">
                                {{ $site?->short_name ?? ($site?->full_name ?? '—') }}
                            </dd>
                        </div>
                        @if($contract)
                            <div class="col-span-2">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Scope / Object') }}</dt>
                                <dd class="text-gray-700 dark:text-gray-300 mt-0.5">{{ $contract->object ?: __('Commercial service agreement') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- 3. Consumed Items Schedule Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <span>{{ __('Consumed Items Schedule') }}</span>
                        <span class="ms-1.5 px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">
                            {{ $attachment->items->count() }}
                        </span>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-start text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="py-3 px-4 text-start">#</th>
                                <th class="py-3 px-4 text-start">{{ __('Designation') }}</th>
                                <th class="py-3 px-4 text-start">{{ __('Classification') }}</th>
                                <th class="py-3 px-4 text-start">{{ __('Nature') }}</th>
                                <th class="py-3 px-4 text-center">{{ __('Planned Qty') }}</th>
                                <th class="py-3 px-4 text-center font-bold text-emerald-600 dark:text-emerald-400">{{ __('Actual Qty') }}</th>
                                <th class="py-3 px-4 text-end">{{ __('Unit Price') }}</th>
                                <th class="py-3 px-4 text-end">{{ __('Subtotal') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($attachment->items as $idx => $item)
                                @php
                                    $contractItem = $item->contractItem;
                                @endphp
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="py-3 px-4 text-xs font-medium text-gray-500">{{ $idx + 1 }}</td>
                                    <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">
                                        {{ $contractItem?->designation ?? '—' }}
                                    </td>
                                    <td class="py-3 px-4 text-xs text-gray-600 dark:text-gray-300">
                                        {{ $contractItem?->itemType?->designation ?? '—' }}
                                    </td>
                                    <td class="py-3 px-4 text-xs">
                                        <x-badge :variant="$contractItem?->type === 'supply' ? 'neutral' : 'info'" size="sm">
                                            {{ ucfirst((string) ($contractItem?->type ?? 'service')) }}
                                        </x-badge>
                                    </td>
                                    <td class="py-3 px-4 text-center text-xs text-gray-500 font-medium">
                                        {{ (float) $item->planned_quantity }}
                                    </td>
                                    <td class="py-3 px-4 text-center text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ (float) $item->actual_quantity }}
                                    </td>
                                    <td class="py-3 px-4 text-end text-xs text-gray-700 dark:text-gray-300">
                                        <span dir="ltr">{{ number_format((float) ($contractItem?->unit_price ?? 0), 2, '.', ' ') }} DA</span>
                                    </td>
                                    <td class="py-3 px-4 text-end font-bold text-xs text-gray-900 dark:text-white">
                                        <span dir="ltr">{{ number_format((float) $item->total, 2, '.', ' ') }} DA</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('No line items recorded for this attachment.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50/75 dark:bg-gray-900/60 font-semibold border-t border-gray-200 dark:border-gray-700 text-xs">
                            <tr>
                                <td colspan="4" class="py-3.5 px-4 text-start font-bold text-gray-900 dark:text-white">
                                    {{ __('Total Consumptions') }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-gray-700 dark:text-gray-300">
                                    {{ (float) $attachment->total_planned_quantity }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-extrabold text-emerald-600 dark:text-emerald-400">
                                    {{ (float) $attachment->total_actual_quantity }}
                                </td>
                                <td></td>
                                <td class="py-3.5 px-4 text-end font-black text-sm text-brand-700 dark:text-brand-300">
                                    <span dir="ltr">{{ number_format((float) $totalAmount, 2, '.', ' ') }} DA</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
