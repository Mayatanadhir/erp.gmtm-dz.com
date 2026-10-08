<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('financial.expenses') }}" class="p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ __('Expense Details') }}
                        </h2>
                        <x-badge :variant="$expense->type->badgeVariant()" size="sm">
                            {{ $expense->type->label() }}
                        </x-badge>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('ID') }}: <span dir="ltr" class="font-mono font-semibold">#{{ $expense->id }}</span> • {{ __('Recorded on') }} <span dir="ltr" class="font-mono">{{ $expense->created_at?->format('d/m/Y H:i') }}</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @can('edit expenses')
                    <a href="{{ route('financial.expenses.edit', $expense->id) }}" class="btn-primary flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>{{ __('Edit Expense') }}</span>
                    </a>
                @endcan

                @can('delete expenses')
                    <button
                        type="button"
                        x-data
                        @click="$dispatch('open-delete-modal', {
                            action: '{{ route('financial.expenses.destroy', $expense->id) }}',
                            name: '{{ addslashes($expense->reference ?? $expense->description ?? '') }}',
                            title: '{{ __('Delete Expense') }}'
                        })"
                        class="py-2 px-3 text-sm font-medium rounded-lg border border-rose-300 dark:border-rose-800 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors flex items-center gap-1.5 shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>{{ __('Delete') }}</span>
                    </button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Details Card -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                            <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ __('Financial Overview') }}</span>
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <!-- Amount -->
                            <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/50">
                                <span class="text-xs uppercase tracking-wider font-semibold text-rose-600 dark:text-rose-400 block mb-1">
                                    {{ __('Amount') }}
                                </span>
                                <div class="text-2xl font-bold font-mono text-rose-700 dark:text-rose-300">
                                    <span dir="ltr">{{ number_format((float) $expense->amount, 2, '.', ' ') }}</span> <span class="text-sm font-sans font-medium text-rose-500">DA</span>
                                </div>
                            </div>

                            <!-- Date -->
                            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-800">
                                <span class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 block mb-1">
                                    {{ __('Transaction Date') }}
                                </span>
                                <div class="text-lg font-bold font-mono text-gray-900 dark:text-white">
                                    <span dir="ltr">{{ $expense->date?->format('d/m/Y') ?? '—' }}</span>
                                </div>
                            </div>

                            <!-- Affiliation Type -->
                            <div>
                                <span class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 block mb-1">
                                    {{ __('Affiliation Type') }}
                                </span>
                                <div class="flex items-center gap-2 mt-1">
                                    <x-badge :variant="$expense->type->badgeVariant()" size="md">
                                        {{ $expense->type->label() }}
                                    </x-badge>
                                    @if ($expense->charge_type)
                                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                            ({{ $expense->charge_type->label() }})
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Charge Category (If applicable) -->
                            @if ($expense->charge_type)
                                <div>
                                    <span class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 block mb-1">
                                        {{ __('Charge Category') }}
                                    </span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $expense->charge_type->label() }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Description -->
                        <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-700">
                            <span class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 block mb-2">
                                {{ __('Description / Justification') }}
                            </span>
                            <div class="p-3.5 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-sm text-gray-800 dark:text-gray-200 leading-relaxed">
                                {{ $expense->description }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Affiliation Association Sidecard -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                            <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            <span>{{ __('Affiliation Context') }}</span>
                        </h3>

                        @if ($expense->type === \App\Enums\ExpenseAffiliation::Mission)
                            @if ($expense->mission)
                                <div class="space-y-3">
                                    <div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ __('Mission Reference') }}</span>
                                        <a href="{{ route('operations.missions.show', $expense->mission_id) }}" class="text-sm font-bold text-brand-600 dark:text-brand-400 hover:underline font-mono flex items-center gap-1.5 mt-0.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            <span dir="ltr">{{ $expense->mission->reference }}</span>
                                        </a>
                                    </div>
                                    @if ($expense->mission->start_date)
                                        <div class="text-xs text-gray-600 dark:text-gray-400">
                                            <span>{{ __('Period') }}:</span>
                                            <span dir="ltr" class="font-mono">{{ $expense->mission->start_date?->format('d/m/Y') }}</span> — <span dir="ltr" class="font-mono">{{ $expense->mission->end_date?->format('d/m/Y') }}</span>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <p class="text-xs text-gray-500 italic">{{ __('No mission linked.') }}</p>
                            @endif

                        @elseif ($expense->type === \App\Enums\ExpenseAffiliation::Contract)
                            @if ($expense->contract)
                                <div class="space-y-3">
                                    <div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ __('Contract Reference') }}</span>
                                        <a href="{{ route('operations.contracts.show', $expense->contract_id) }}" class="text-sm font-bold text-brand-600 dark:text-brand-400 hover:underline font-mono flex items-center gap-1.5 mt-0.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span dir="ltr">{{ $expense->contract->reference }}</span>
                                        </a>
                                    </div>
                                    @if ($expense->contract->customer)
                                        <div class="text-xs text-gray-600 dark:text-gray-400">
                                            <span>{{ __('Customer') }}:</span>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $expense->contract->customer->short_name ?? $expense->contract->customer->company_name }}</span>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <p class="text-xs text-gray-500 italic">{{ __('No contract linked.') }}</p>
                            @endif

                        @elseif ($expense->type === \App\Enums\ExpenseAffiliation::Item)
                            @if ($expense->attachmentItem)
                                <div class="space-y-3">
                                    <div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ __('Attachment Item') }}</span>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white block mt-0.5">
                                            {{ $expense->attachmentItem->contractItem?->designation ?? __('Item') . ' #' . $expense->attachment_item_id }}
                                        </span>
                                    </div>
                                    @if ($expense->attachmentItem->attachment)
                                        <div class="text-xs text-gray-600 dark:text-gray-400">
                                            <span>{{ __('Attachment Reference') }}:</span>
                                            <span dir="ltr" class="font-mono font-semibold">{{ $expense->attachmentItem->attachment->code_ref }}</span>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <p class="text-xs text-gray-500 italic">{{ __('No attachment item linked.') }}</p>
                            @endif

                        @elseif ($expense->type === \App\Enums\ExpenseAffiliation::Gmtm)
                            <div class="space-y-2">
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">
                                    {{ __('Corporate Overhead') }}
                                </span>
                                <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                                    {{ __('Expenditure assigned directly to SARL GMTM operations.') }}
                                </p>
                            </div>

                        @elseif ($expense->type === \App\Enums\ExpenseAffiliation::Prisma)
                            <div class="space-y-2">
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">
                                    {{ __('PRISMA Group Overhead') }}
                                </span>
                                <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                                    {{ __('Expenditure allocated to PRISMA group operations.') }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Audit Timestamps -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm text-xs space-y-2.5">
                        <div class="flex justify-between items-center text-gray-500 dark:text-gray-400">
                            <span>{{ __('Created At') }}:</span>
                            <span dir="ltr" class="font-mono text-gray-700 dark:text-gray-300">{{ $expense->created_at?->format('d/m/Y H:i:s') ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between items-center text-gray-500 dark:text-gray-400">
                            <span>{{ __('Last Updated') }}:</span>
                            <span dir="ltr" class="font-mono text-gray-700 dark:text-gray-300">{{ $expense->updated_at?->format('d/m/Y H:i:s') ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
