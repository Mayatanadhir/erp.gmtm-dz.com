<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('financial.expenses') }}" class="p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Create Expense') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Record an operational charge, mission expense, or company overhead') }}
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <x-alert variant="danger" class="mb-6">
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            <form action="{{ route('financial.expenses.store') }}" method="POST" id="expenseForm" class="space-y-6">
                @csrf

                <!-- Basic Information Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ __('Expense Details') }}</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <!-- Amount -->
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Amount') }} (DA) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" id="amount" name="amount" value="{{ old('amount') }}" required placeholder="0.00" class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm font-mono">
                        </div>

                        <!-- Date (Day / Month in the middle / Year) -->
                        <div x-data="{
                            dateValue: '{{ old('date', now()->format('Y-m-d')) }}',
                            displayValue: '',
                            init() {
                                this.updateDisplay();
                            },
                            updateDisplay() {
                                if (!this.dateValue) {
                                    this.displayValue = '';
                                    return;
                                }
                                if (this.dateValue.includes('/')) {
                                    const slashParts = this.dateValue.split('/');
                                    if (slashParts.length === 3) {
                                        this.displayValue = this.dateValue;
                                        this.dateValue = `${slashParts[2]}-${slashParts[1]}-${slashParts[0]}`;
                                        return;
                                    }
                                }
                                const parts = this.dateValue.split('-');
                                if (parts.length === 3) {
                                    this.displayValue = `${parts[2]}/${parts[1]}/${parts[0]}`;
                                }
                            },
                            onDisplayInput(e) {
                                let val = e.target.value.replace(/[^0-9]/g, '');
                                if (val.length > 8) val = val.substring(0, 8);

                                let formatted = '';
                                if (val.length > 0) formatted += val.substring(0, 2);
                                if (val.length >= 3) formatted += '/' + val.substring(2, 4);
                                if (val.length >= 5) formatted += '/' + val.substring(4, 8);

                                this.displayValue = formatted;

                                if (val.length === 8) {
                                    const day = val.substring(0, 2);
                                    const month = val.substring(2, 4);
                                    const year = val.substring(4, 8);
                                    if (parseInt(month) >= 1 && parseInt(month) <= 12 && parseInt(day) >= 1 && parseInt(day) <= 31) {
                                        this.dateValue = `${year}-${month}-${day}`;
                                    }
                                } else if (val.length === 0) {
                                    this.dateValue = '';
                                }
                            },
                            onPickerChange(e) {
                                this.dateValue = e.target.value;
                                this.updateDisplay();
                            },
                            openPicker() {
                                try {
                                    $refs.nativePicker.showPicker();
                                } catch(err) {
                                    $refs.nativePicker.focus();
                                    $refs.nativePicker.click();
                                }
                            }
                        }">
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('Date') }} <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-[11px] font-mono text-gray-400 dark:text-gray-500 font-normal" dir="ltr">
                                    {{ __('DD/MM/YYYY') }}
                                </span>
                            </div>

                            <input type="hidden" name="date" :value="dateValue || displayValue">

                            <div class="relative flex items-center">
                                <input 
                                    type="text" 
                                    id="date" 
                                    :value="displayValue" 
                                    @input="onDisplayInput($event)"
                                    placeholder="DD/MM/YYYY" 
                                    dir="ltr"
                                    maxlength="10"
                                    class="w-full py-2 ps-3 pe-10 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm font-mono text-center tracking-wider"
                                    required>

                                <button 
                                    type="button" 
                                    @click="openPicker()" 
                                    class="absolute end-2 p-1.5 text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 rounded-md transition-colors"
                                    title="{{ __('Select Date') }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </button>

                                <input 
                                    type="date" 
                                    x-ref="nativePicker" 
                                    :value="dateValue" 
                                    @change="onPickerChange($event)" 
                                    class="sr-only absolute pointer-events-none" 
                                    tabindex="-1">
                            </div>
                        </div>

                        <!-- Type / Affiliation -->
                        <div>
                            <label for="typeSelect" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Affiliation Type') }} <span class="text-rose-500">*</span>
                            </label>
                            <select id="typeSelect" name="type" required onchange="toggleAffiliationFields()" class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                                <option value="">— {{ __('Select Type') }} —</option>
                                @foreach (\App\Enums\ExpenseAffiliation::cases() as $affiliation)
                                    <option value="{{ $affiliation->value }}" @selected(old('type', request('type', request('mission_id') ? 'mission' : '')) === $affiliation->value)>
                                        {{ $affiliation->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- GMTM Charge Category (Fixed / Variable) -->
                        <div id="chargeTypeField" style="display: none;">
                            <label for="chargeTypeSelect" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Charge Category') }} <span class="text-rose-500">*</span>
                            </label>
                            <select id="chargeTypeSelect" name="charge_type" class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                                <option value="">— {{ __('Select Category') }} —</option>
                                @foreach (\App\Enums\ExpenseChargeType::cases() as $chargeCat)
                                    <option value="{{ $chargeCat->value }}" @selected(old('charge_type') === $chargeCat->value)>
                                        {{ $chargeCat->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Description -->
                        <div class="md:col-span-2 lg:col-span-3">
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Description') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="description" name="description" value="{{ old('description') }}" required placeholder="{{ __('Enter brief description of this expense...') }}" class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                        </div>
                    </div>
                </div>

                <!-- Dynamic Affiliation Section -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm" id="affiliationSection" style="display: none;">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        <span>{{ __('Affiliation Link') }}</span>
                    </h3>

                    <div class="space-y-4">
                        <!-- Mission Selection -->
                        <div id="missionField" style="display: none;">
                            <label for="missionSelect" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Mission') }} <span class="text-rose-500">*</span>
                            </label>
                            <select id="missionSelect" name="mission_id" class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm font-mono">
                                <option value="">— {{ __('Select Mission') }} —</option>
                                @foreach ($missions as $mission)
                                    <option value="{{ $mission->id }}" @selected(old('mission_id', request('mission_id')) == $mission->id)>
                                        {{ $mission->reference }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Contract Selection -->
                        <div id="contractField" style="display: none;">
                            <label for="contractSelect" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Contract') }} <span class="text-rose-500">*</span>
                            </label>
                            <select id="contractSelect" name="contract_id" class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm font-mono">
                                <option value="">— {{ __('Select Active Contract') }} —</option>
                                @foreach ($contracts as $contract)
                                    <option value="{{ $contract->id }}" @selected(old('contract_id', request('contract_id')) == $contract->id)>
                                        {{ $contract->reference }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Attachment Item Selection -->
                        <div id="itemField" style="display: none;">
                            <label for="itemSelect" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Attachment Item') }} <span class="text-rose-500">*</span>
                            </label>
                            <select id="itemSelect" name="attachment_item_id" class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                                <option value="">— {{ __('Select Attachment Item') }} —</option>
                                @foreach ($attachmentItems as $item)
                                    <option value="{{ $item->id }}" @selected(old('attachment_item_id') == $item->id)>
                                        {{ $item->attachment?->code_ref }} — {{ $item->contractItem?->designation ?? __('Item') . ' #' . $item->id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4">
                    <a href="{{ route('financial.expenses') }}" class="py-2.5 px-4 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="py-2.5 px-5 text-sm font-semibold rounded-lg bg-brand-600 hover:bg-brand-700 text-white transition-colors flex items-center gap-2 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ __('Save Expense') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function toggleAffiliationFields() {
            const type = document.getElementById('typeSelect').value;
            const section = document.getElementById('affiliationSection');
            const missionField = document.getElementById('missionField');
            const contractField = document.getElementById('contractField');
            const itemField = document.getElementById('itemField');
            const chargeTypeField = document.getElementById('chargeTypeField');
            const chargeTypeSelect = document.getElementById('chargeTypeSelect');

            const missionSelect = document.getElementById('missionSelect');
            const contractSelect = document.getElementById('contractSelect');
            const itemSelect = document.getElementById('itemSelect');

            // Hide all first
            missionField.style.display = 'none';
            contractField.style.display = 'none';
            itemField.style.display = 'none';
            chargeTypeField.style.display = 'none';

            missionSelect.removeAttribute('required');
            contractSelect.removeAttribute('required');
            itemSelect.removeAttribute('required');
            chargeTypeSelect.removeAttribute('required');

            if (type === 'mission') {
                section.style.display = 'block';
                missionField.style.display = 'block';
                missionSelect.setAttribute('required', 'required');
            } else if (type === 'contract') {
                section.style.display = 'block';
                contractField.style.display = 'block';
                contractSelect.setAttribute('required', 'required');
            } else if (type === 'item') {
                section.style.display = 'block';
                itemField.style.display = 'block';
                itemSelect.setAttribute('required', 'required');
            } else if (type === 'gmtm') {
                section.style.display = 'none';
                chargeTypeField.style.display = 'block';
                chargeTypeSelect.setAttribute('required', 'required');
            } else {
                section.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', toggleAffiliationFields);
    </script>
    @endpush
</x-app-layout>
