<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('operations.contracts.show', $contract->id) }}" class="p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Edit Contract') }}: <span class="text-brand-600 dark:text-brand-400">{{ $contract->reference }}</span>
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Update commercial terms, items schedule, and bank guarantee linkages') }}
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <x-alert variant="danger" class="mb-6">
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            <form action="{{ route('operations.contracts.update', $contract->id) }}" method="POST" id="contractForm" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- 1. Contract Information Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>{{ __('Contract Information') }}</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                        <!-- Reference -->
                        <div>
                            <label for="reference" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Reference') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="reference" name="reference" value="{{ old('reference', $contract->reference) }}" required class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                        </div>

                        <!-- Customer -->
                        <div>
                            <label for="customer_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Customer') }}
                            </label>
                            <select id="customer_id" name="customer_id" class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                                <option value="">— {{ __('Select Customer') }} —</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(old('customer_id', $contract->customer_id) == $customer->id)>
                                        {{ $customer->short_name ?? $customer->company_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Date Signature -->
                        <div>
                            <label for="date_signature" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Date Signature') }}
                            </label>
                            <input type="date" id="date_signature" name="date_signature" value="{{ old('date_signature', $contract->date_signature?->format('Y-m-d')) }}" class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                        </div>

                        <!-- Duration (Months) -->
                        <div>
                            <label for="duree" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Duration (Months)') }}
                            </label>
                            <input type="number" id="duree" name="duree" value="{{ old('duree', $contract->duree) }}" min="1" class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                        </div>

                        <!-- Planned Total Amount -->
                        <div>
                            <label for="montant_global_prevu" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Planned Total Amount (DA)') }}
                            </label>
                            <input type="number" step="0.01" id="montant_global_prevu" name="montant_global_prevu" value="{{ old('montant_global_prevu', $contract->montant_global_prevu) }}" class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                        </div>

                        <!-- Object / Description -->
                        <div class="md:col-span-2 lg:col-span-3">
                            <label for="object" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Object / Scope of Work') }}
                            </label>
                            <input type="text" id="object" name="object" value="{{ old('object', $contract->object) }}" class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                        </div>
                    </div>
                </div>

                <!-- 2. Bank Guarantee Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>{{ __('Bank Guarantee Link') }}</span>
                    </h3>

                    <div>
                        <label for="garantie_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ __('Linked Performance Bond') }}
                        </label>
                        <select id="garantie_id" name="garantie_id" class="w-full py-2.5 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                            <option value="">— {{ __('No Bank Guarantee Linked') }} —</option>
                            @foreach ($warranties as $warranty)
                                <option value="{{ $warranty->id }}" @selected(old('garantie_id', $contract->garantie_id) == $warranty->id)>
                                    {{ $warranty->reference }} ({{ number_format((float) $warranty->amount, 2, '.', ' ') }} DA — {{ $warranty->bank ?? __('Guarantee') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- 3. Contract Items Card (Dynamic) -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <span>{{ __('Contract Items Schedule') }}</span>
                        </h3>
                        <button type="button" onclick="addItem()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-emerald-800 dark:text-emerald-300 bg-emerald-100 dark:bg-gray-700 hover:bg-emerald-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>{{ __('Add Item') }}</span>
                        </button>
                    </div>

                    <div id="itemsContainer" class="space-y-4">
                        <!-- Items rendered via JS -->
                    </div>
                </div>

                <!-- Submit / Cancel Actions -->
                <div class="flex items-center justify-end gap-3 pt-4">
                    <a href="{{ route('operations.contracts.show', $contract->id) }}" class="px-5 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-semibold transition-colors">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="btn-primary flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ __('Update Contract') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        const itemTypes = @json($itemTypes);
        const existingItems = @json($contract->items);
        let itemIndex = 0;

        function buildItemTypeOptions(selected = '') {
            let opts = `<option value="">— {{ __('Type') }} —</option>`;
            itemTypes.forEach(t => {
                opts += `<option value="${t.id}" ${t.id == selected ? 'selected' : ''}>${t.designation}</option>`;
            });
            return opts;
        }

        function addItem(data = {}) {
            const i = itemIndex++;
            const row = document.createElement('div');
            row.className = 'item-row p-4 rounded-xl border border-gray-200 dark:border-gray-700/80 bg-gray-50/50 dark:bg-gray-900/40 relative space-y-3';
            
            const isExisting = Boolean(data.id);
            const frequencyVal = data.frequency ? (typeof data.frequency === 'object' ? data.frequency.value : data.frequency) : '';

            row.innerHTML = `
                <input type="hidden" name="items[${i}][id]" value="${data.id ?? ''}">
                <div class="flex items-center justify-between pb-2 border-b border-gray-200/60 dark:border-gray-700/60">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                        #${i + 1} ${isExisting ? '<span class="ms-1 px-1.5 py-0.5 rounded text-[10px] bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ __("Existing") }}</span>' : '<span class="ms-1 px-1.5 py-0.5 rounded text-[10px] bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/70">{{ __("New") }}</span>'}
                    </span>
                    <button type="button" onclick="this.closest('.item-row').remove()" class="p-1 text-gray-400 hover:text-rose-500 dark:hover:text-rose-400 transition-colors" title="{{ __('Remove Item') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Designation') }} <span class="text-rose-500">*</span></label>
                        <input type="text" name="items[${i}][designation]" class="w-full py-1.5 px-3 text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500" value="${data.designation ?? ''}" required placeholder="{{ __('Item title or service description') }}">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Classification') }}</label>
                        <select name="items[${i}][item_type_id]" class="w-full py-1.5 px-2.5 text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500">
                            ${buildItemTypeOptions(data.item_type_id ?? '')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Nature') }}</label>
                        <select name="items[${i}][type]" class="w-full py-1.5 px-2.5 text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500">
                            <option value="">—</option>
                            <option value="service" ${data.type == 'service' ? 'selected' : ''}>{{ __('Service') }}</option>
                            <option value="supply" ${data.type == 'supply' ? 'selected' : ''}>{{ __('Supply') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Quantity') }}</label>
                        <input type="number" name="items[${i}][quantity]" min="1" class="w-full py-1.5 px-3 text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500" value="${data.quantity ?? 1}">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Unit Price (DA)') }}</label>
                        <input type="number" step="0.01" name="items[${i}][unit_price]" class="w-full py-1.5 px-3 text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500" value="${data.unit_price ?? ''}" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Unit Cost (DA)') }}</label>
                        <input type="number" step="0.01" name="items[${i}][unit_cost]" class="w-full py-1.5 px-3 text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500" value="${data.unit_cost ?? ''}" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Billing Cycle') }}</label>
                        <select name="items[${i}][frequency]" class="w-full py-1.5 px-2.5 text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500">
                            <option value="">—</option>
                            <option value="annuelle" ${frequencyVal == 'annuelle' ? 'selected' : ''}>{{ __('Annual') }}</option>
                            <option value="semestrielle" ${frequencyVal == 'semestrielle' ? 'selected' : ''}>{{ __('Semi-annual') }}</option>
                        </select>
                    </div>
                </div>
            `;
            document.getElementById('itemsContainer').appendChild(row);
        }

        // Initialize with existing items or 1 empty row
        if (existingItems && existingItems.length > 0) {
            existingItems.forEach(item => addItem(item));
        } else {
            addItem();
        }
    </script>
    @endpush
</x-app-layout>
