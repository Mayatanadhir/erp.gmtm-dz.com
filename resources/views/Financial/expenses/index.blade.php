<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <x-tool-icon name="expenses" class="w-11 h-11 sm:w-12 sm:h-12 shrink-0" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Expenses & Charges') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Track corporate expenditures, operational costs, and financial liabilities') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="info" size="md">
                    <span dir="ltr">{{ $expenses->total() }}</span> {{ __('Records') }}
                </x-badge>
                @can('create expenses')
                    <a href="{{ route('financial.expenses.create') }}" class="btn-primary flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>{{ __('Add Expense') }}</span>
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
                    <x-financial-tabs active="expenses" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-6">
                    @if (session('success'))
                        <x-alert variant="success">
                            {{ session('success') }}
                        </x-alert>
                    @endif

                    @if (session('error'))
                        <x-alert variant="danger">
                            {{ session('error') }}
                        </x-alert>
                    @endif

                    <!-- Filter Bar -->
                    <x-global-filter
                        :action="route('financial.expenses')"
                        :search-placeholder="__('Search in description or amount...')"
                        :search-value="$search ?? ''"
                        :search-width="'w-56 sm:w-72'"
                    >
                        {{-- Year Filter --}}
                        <x-global-filter.select
                            name="year"
                            :placeholder="false"
                            :value="$year ?? date('Y')"
                        >
                            @for ($i = (int) date('Y') + 1; $i >= 2023; $i--)
                                <option value="{{ $i }}" @selected(($year ?? date('Y')) == (string) $i)>{{ $i }}</option>
                            @endfor
                            <option value="all" @selected(($year ?? date('Y')) === 'all')>{{ __('All Years') }}</option>
                        </x-global-filter.select>

                        {{-- Affiliation Type Filter (special: shows/hides charge_type field) --}}
                        <div x-data="{ showChargeType: {{ $type === 'gmtm' ? 'true' : 'false' }} }">
                            <select name="type"
                                    onchange="this.form.submit()"
                                    x-on:change="showChargeType = (this.value === 'gmtm')"
                                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 px-3 py-1.5 pe-8 text-xs text-gray-900 dark:text-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600 dark:focus:border-brand-600 transition-colors shadow-sm cursor-pointer">
                                <option value="">— {{ __('All Types') }} —</option>
                                @foreach (\App\Enums\ExpenseAffiliation::cases() as $affiliation)
                                    <option value="{{ $affiliation->value }}" @selected($type === $affiliation->value)>
                                        {{ $affiliation->label() }}
                                    </option>
                                @endforeach
                            </select>

                            {{-- Charge Category (shown only when type = gmtm) --}}
                            <select name="charge_type"
                                    x-show="showChargeType"
                                    onchange="this.form.submit()"
                                    x-cloak
                                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 px-3 py-1.5 pe-8 text-xs text-gray-900 dark:text-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600 dark:focus:border-brand-600 transition-colors shadow-sm cursor-pointer ms-1">
                                <option value="">— {{ __('All Categories') }} —</option>
                                @foreach (\App\Enums\ExpenseChargeType::cases() as $cat)
                                    <option value="{{ $cat->value }}" @selected($chargeType === $cat->value)>
                                        {{ $cat->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </x-global-filter>

                    <!-- Summary Stat Card -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/70 shadow-sm shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <div>
                                <span class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400">
                                    {{ __('Filtered Total Amount') }} ({{ $year === 'all' ? __('All Years') : $year }})
                                </span>
                                <h3 class="text-xl sm:text-2xl font-bold font-mono text-gray-900 dark:text-white mt-0.5">
                                    <span dir="ltr">{{ number_format($yearlyTotal, 2, '.', ' ') }}</span> <span class="text-sm font-sans font-medium text-gray-500 dark:text-gray-400">DA</span>
                                </h3>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                            {{ __('Showing') }} <span dir="ltr" class="font-bold text-gray-700 dark:text-gray-300 font-mono">{{ $expenses->count() }}</span> / <span dir="ltr" class="font-bold text-gray-700 dark:text-gray-300 font-mono">{{ $expenses->total() }}</span> {{ __('expenses') }}
                        </div>
                    </div>

                    <!-- Expenses Table -->
                    <x-table>
                        <x-slot:toolbar>
                            <div class="flex items-center justify-between w-full">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                                    <x-tool-icon name="expenses" class="w-5 h-5 shrink-0" />
                                    <span>{{ __('Expenses List') }}</span>
                                </h3>
                            </div>
                        </x-slot:toolbar>

                        <x-slot:header>
                            <x-table.th class="text-center w-28">{{ __('Date') }}</x-table.th>
                            <x-table.th>{{ __('Description') }}</x-table.th>
                            <x-table.th class="text-center">{{ __('Affiliation') }}</x-table.th>
                            <x-table.th class="text-center">{{ __('Linked Entity') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Amount') }}</x-table.th>
                            <x-table.th class="text-end w-32">{{ __('Actions') }}</x-table.th>
                        </x-slot:header>

                        @forelse ($expenses as $expense)
                            <x-table.tr>
                                <!-- Date -->
                                <x-table.td class="text-center">
                                    <span dir="ltr" class="font-mono text-xs text-gray-600 dark:text-gray-400">
                                        {{ $expense->date?->format('d/m/Y') ?? '—' }}
                                    </span>
                                </x-table.td>

                                <!-- Description -->
                                <x-table.td>
                                    <div class="flex flex-col">
                                        <a href="{{ route('financial.expenses.show', $expense->id) }}" class="font-medium text-gray-900 dark:text-white hover:text-brand-600 dark:hover:text-brand-400 transition-colors">
                                            {{ $expense->description }}
                                        </a>
                                    </div>
                                </x-table.td>

                                <!-- Type / Affiliation -->
                                <x-table.td class="text-center">
                                    <div class="inline-flex flex-col items-center">
                                        <x-badge :variant="$expense->type->badgeVariant()" size="sm">
                                            {{ $expense->type->label() }}
                                        </x-badge>
                                        @if ($expense->charge_type)
                                            <span class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                                ({{ $expense->charge_type->label() }})
                                            </span>
                                        @endif
                                    </div>
                                </x-table.td>

                                <!-- Linked Entity -->
                                <x-table.td class="text-center">
                                    @if ($expense->type === \App\Enums\ExpenseAffiliation::Mission && $expense->mission)
                                        <a href="{{ route('operations.missions.show', $expense->mission_id) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 dark:text-brand-400 hover:underline font-mono">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            <span dir="ltr">{{ $expense->mission->reference }}</span>
                                        </a>
                                    @elseif ($expense->type === \App\Enums\ExpenseAffiliation::Contract && $expense->contract)
                                        <a href="{{ route('operations.contracts.show', $expense->contract_id) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 dark:text-brand-400 hover:underline font-mono">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span dir="ltr">{{ $expense->contract->reference }}</span>
                                        </a>
                                    @elseif ($expense->type === \App\Enums\ExpenseAffiliation::Item && $expense->attachmentItem)
                                        <div class="text-xs text-gray-700 dark:text-gray-300">
                                            <span class="font-semibold">{{ __('Item') }} #{{ $expense->attachment_item_id }}</span>
                                            @if ($expense->attachmentItem->attachment)
                                                <span class="text-gray-500 font-mono">({{ $expense->attachmentItem->attachment->code_ref }})</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-600 text-xs">—</span>
                                    @endif
                                </x-table.td>

                                <!-- Amount -->
                                <x-table.td class="text-end">
                                    <span dir="ltr" class="font-mono font-bold text-rose-600 dark:text-rose-400">
                                        {{ number_format((float) $expense->amount, 2, '.', ' ') }} <span class="text-xs font-sans text-gray-500 font-normal">DA</span>
                                    </span>
                                </x-table.td>

                                <!-- Actions -->
                                <x-table.td class="text-end">
                                    <x-table.actions>
                                        @can('view expenses')
                                            <x-table.action-view
                                                href="{{ route('financial.expenses.show', $expense->id) }}"
                                                :title="__('View Details')"
                                            />
                                        @endcan

                                        @can('edit expenses')
                                            <x-table.action-edit
                                                href="{{ route('financial.expenses.edit', $expense->id) }}"
                                                :title="__('Edit')"
                                            />
                                        @endcan

                                        @can('delete expenses')
                                            <x-table.action-delete
                                                :action-url="route('financial.expenses.destroy', $expense->id)"
                                                :item-name="$expense->reference ?? $expense->description"
                                                :title="__('Delete Expense')"
                                            />
                                        @endcan
                                    </x-table.actions>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty :colspan="6" :message="__('No expenses found matching the selected criteria.')" />
                        @endforelse
                    </x-table>

                    <!-- Pagination -->
                    @if ($expenses->hasPages())
                        <div class="mt-4">
                            {{ $expenses->links() }}
                        </div>
                    @endif
                </main>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function handleTypeChange() {
            const type = document.getElementById('typeFilter').value;
            const wrapper = document.getElementById('chargeTypeWrapper');
            const select = document.getElementById('chargeTypeFilter');

            if (type === 'gmtm') {
                wrapper.style.display = 'block';
            } else {
                wrapper.style.display = 'none';
                if (select) {
                    select.value = '';
                }
            }
            document.getElementById('filterForm').submit();
        }
    </script>
    @endpush
</x-app-layout>
