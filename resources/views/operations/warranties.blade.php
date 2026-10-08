<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
             x-data
             @open-create-warranty-modal.window="window.dispatchEvent(new CustomEvent('open-create-warranty-modal-inner'))">
            <div class="flex items-center gap-3.5">
                <x-tool-icon name="warranties" class="w-11 h-11 sm:w-12 sm:h-12 shrink-0" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Bank Guarantees') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Track bank guarantee obligations, coverage periods, and financial commitments') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="info" size="md">
                    {{ $warranties->total() }} {{ __('Guarantees') }}
                </x-badge>
                @can('create warranties')
                    <x-primary-button
                        type="button"
                        x-data
                        @click="$dispatch('open-create-warranty-modal')"
                        onclick="window.dispatchEvent(new CustomEvent('open-create-warranty-modal'))"
                        class="flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>{{ __('New Guarantee') }}</span>
                    </x-primary-button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8"
        x-data="{
        showCreateModal: {{ ($errors->has('reference') || $errors->has('bank_name') || $errors->has('amount') || $errors->has('started_at') || $errors->has('status') || $errors->has('type')) && !old('_method') ? 'true' : 'false' }},
        showEditModal: {{ ($errors->has('reference') || $errors->has('bank_name') || $errors->has('amount') || $errors->has('started_at') || $errors->has('status') || $errors->has('type')) && old('_method') === 'PUT' ? 'true' : 'false' }},
        showDeleteModal: false,

        // Edit state
        editWarrantyId: {{ old('_method') === 'PUT' ? (int) old('edit_warranty_id', 0) : 'null' }},
        editReference: '{{ old('_method') === 'PUT' ? addslashes((string) old('reference', '')) : '' }}',
        editBankName:  '{{ old('_method') === 'PUT' ? addslashes((string) old('bank_name', '')) : '' }}',
        editAmount:    '{{ old('_method') === 'PUT' ? addslashes((string) old('amount', '')) : '' }}',
        editStartedAt: '{{ old('_method') === 'PUT' ? addslashes((string) old('started_at', '')) : '' }}',
        editStatus:    '{{ old('_method') === 'PUT' ? old('status', 'active') : '' }}',
        editType:      '{{ old('_method') === 'PUT' ? old('type', 'other') : '' }}',
        editFormAction: '',

        // Delete state
        deleteWarrantyId: null,
        deleteWarrantyRef: '',
        deleteFormAction: '',

        openCreateModal() { this.showCreateModal = true; },
        openEditModal(id, reference, bankName, amount, startedAt, status, type, action) {
            this.editWarrantyId  = id;
            this.editReference   = reference;
            this.editBankName    = bankName;
            this.editAmount      = amount;
            this.editStartedAt   = startedAt;
            this.editStatus      = status;
            this.editType        = type;
            this.editFormAction  = action;
            this.showEditModal   = true;
        },
        openDeleteModal(id, ref, action) {
            this.deleteWarrantyId  = id;
            this.deleteWarrantyRef = ref;
            this.deleteFormAction  = action;
            this.showDeleteModal   = true;
        },
    }"
        x-init="window.addEventListener('open-create-warranty-modal', () => { showCreateModal = true; })">

        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">

                {{-- Sidebar --}}
                <aside class="w-full lg:w-64 shrink-0">
                    <x-financial-tabs active="warranties" />
                </aside>

                {{-- Main Content --}}
                <main class="flex-1 w-full min-w-0 space-y-6">

                    {{-- Alerts --}}
                    @if(session('success'))
                        <x-alert variant="success">{{ session('success') }}</x-alert>
                    @endif
                    @if(session('error'))
                        <x-alert variant="danger">{{ session('error') }}</x-alert>
                    @endif
                    @if(session('warning'))
                        <x-alert variant="warning">{{ session('warning') }}</x-alert>
                    @endif
                    @if(session('info'))
                        <x-alert variant="info">{{ session('info') }}</x-alert>
                    @endif

                    <x-table>
                        <x-slot:toolbar>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                                    <x-tool-icon name="warranties" class="w-6 h-6 shrink-0" />
                                    <span>{{ __('Bank Guarantees Directory') }}</span>
                                </h3>

                                <div class="flex flex-wrap items-center gap-3">
                                    <x-global-filter
                                        :action="request()->routeIs('operations.*') ? route('operations.warranties') : route('financial.warranties')"
                                        :search="true"
                                        :search-placeholder="__('Search by reference, bank...')"
                                        :search-value="request('search')"
                                        :submit-text="__('Search')"
                                    >
                                        <select name="type" onchange="this.form.submit()" class="py-1.5 ps-2.5 pe-8 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600 shadow-sm">
                                            <option value="">{{ __('All Types') }}</option>
                                            @foreach(\App\Enums\WarrantyType::cases() as $tp)
                                                <option value="{{ $tp->value }}" @selected(request('type') === $tp->value)>{{ __($tp->label()) }}</option>
                                            @endforeach
                                        </select>

                                        <select name="status" onchange="this.form.submit()" class="py-1.5 ps-2.5 pe-8 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600 shadow-sm">
                                            <option value="">{{ __('All Statuses') }}</option>
                                            @foreach(\App\Enums\WarrantyStatus::cases() as $st)
                                                <option value="{{ $st->value }}" @selected(request('status') === $st->value)>{{ __($st->label()) }}</option>
                                            @endforeach
                                        </select>
                                    </x-global-filter>
                                </div>
                            </div>
                        </x-slot:toolbar>

                        <x-slot:header>
                            <x-table.th>ID</x-table.th>
                            <x-table.th>{{ __('Reference') }}</x-table.th>
                            <x-table.th>{{ __('Bank Name') }}</x-table.th>
                            <x-table.th>{{ __('Amount') }}</x-table.th>
                            <x-table.th>{{ __('Start Date') }}</x-table.th>
                            <x-table.th>{{ __('Type') }}</x-table.th>
                            <x-table.th>{{ __('Status') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                        </x-slot:header>

                        @forelse($warranties as $warranty)
                            <x-table.tr>
                                <x-table.td class="font-mono text-xs font-semibold text-gray-500 dark:text-gray-400">
                                    <bdi>#{{ $warranty->id }}</bdi>
                                </x-table.td>

                                <x-table.td>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded font-mono text-xs font-semibold bg-brand-50 dark:bg-emerald-500/20 text-brand-700 dark:text-emerald-300 border border-brand-200 dark:border-emerald-500/40">
                                        <bdi>{{ $warranty->reference }}</bdi>
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-blue-500 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $warranty->bank_name }}</span>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <span class="font-mono text-sm font-semibold text-gray-900 dark:text-white">
                                        <bdi>{{ number_format((float) $warranty->amount, 2, '.', ' ') }}</bdi>
                                        <span class="text-xs font-normal text-gray-500 dark:text-gray-400 ms-1">DZD</span>
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    @if($warranty->started_at)
                                        <div class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300">
                                            <svg class="w-3.5 h-3.5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span class="font-mono text-xs"><bdi>{{ $warranty->started_at->format('d/m/Y') }}</bdi></span>
                                        </div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-600 text-xs">—</span>
                                    @endif
                                </x-table.td>

                                <x-table.td>
                                    <x-badge :variant="$warranty->type->badgeVariant()">
                                        {{ __($warranty->type->label()) }}
                                    </x-badge>
                                </x-table.td>

                                <x-table.td>
                                    <x-badge :variant="$warranty->status->badgeVariant()" :dot="true">
                                        {{ __($warranty->status->label()) }}
                                    </x-badge>
                                </x-table.td>

                                <x-table.td class="text-end">
                                    <x-table.actions>
                                        @can('edit warranties')
                                            <x-table.action-edit
                                                type="button"
                                                :title="__('Edit Guarantee')"
                                                @click="openEditModal(
                                                    {{ $warranty->id }},
                                                    '{{ addslashes((string) $warranty->reference) }}',
                                                    '{{ addslashes((string) $warranty->bank_name) }}',
                                                    '{{ $warranty->amount }}',
                                                    '{{ $warranty->started_at?->format('Y-m-d') ?? '' }}',
                                                    '{{ $warranty->status->value }}',
                                                    '{{ $warranty->type->value }}',
                                                    '{{ route('financial.warranties.update', array_merge(['warranty' => $warranty->id], request()->query())) }}'
                                                )"
                                            />
                                        @endcan

                                        @can('delete warranties')
                                            <x-table.action-delete
                                                type="button"
                                                :title="__('Delete Guarantee')"
                                                @click="openDeleteModal(
                                                    {{ $warranty->id }},
                                                    '{{ addslashes((string) $warranty->reference) }}',
                                                    '{{ route('financial.warranties.destroy', array_merge(['warranty' => $warranty->id], request()->query())) }}'
                                                )"
                                            />
                                        @endcan
                                    </x-table.actions>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty :colspan="8" :message="__('No bank guarantees found.')" />
                        @endforelse

                        <x-slot:pagination>
                            {{ $warranties->links() }}
                        </x-slot:pagination>
                    </x-table>
                </main>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- CREATE MODAL                                           --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <div x-show="showCreateModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showCreateModal = false"></div>

                <div x-show="showCreateModal"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     class="relative inline-block w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl text-left shadow-xl border border-gray-100 dark:border-gray-700/60 overflow-hidden z-10">

                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-brand-600/10 dark:bg-brand-500/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('New Bank Guarantee') }}</h3>
                        </div>
                        <button type="button" @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Modal Form --}}
                    <form method="POST" action="{{ route('financial.warranties.store', request()->query()) }}">
                        @csrf
                        <div class="px-6 py-5 space-y-4">

                            {{-- Reference --}}
                            <div>
                                <x-input-label for="create_reference" :value="__('Reference') . ' *'" />
                                <x-text-input id="create_reference" name="reference" type="text"
                                    class="mt-1 block w-full" :value="old('reference')"
                                    placeholder="GRT-2024-001" required />
                                <x-input-error :messages="$errors->get('reference')" class="mt-1" />
                            </div>

                            {{-- Bank Name --}}
                            <div>
                                <x-input-label for="create_bank_name" :value="__('Bank Name') . ' *'" />
                                <x-text-input id="create_bank_name" name="bank_name" type="text"
                                    class="mt-1 block w-full" :value="old('bank_name')"
                                    placeholder="{{ __('e.g. BNA, CPA, CNEP...') }}" required />
                                <x-input-error :messages="$errors->get('bank_name')" class="mt-1" />
                            </div>

                            {{-- Amount & Start Date --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="create_amount" :value="__('Amount (DZD)') . ' *'" />
                                    <x-text-input id="create_amount" name="amount" type="number" step="0.01" min="0"
                                        class="mt-1 block w-full" :value="old('amount', '0')" required />
                                    <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="create_started_at" :value="__('Start Date')" />
                                    <x-text-input id="create_started_at" name="started_at" type="date"
                                        class="mt-1 block w-full" :value="old('started_at')" />
                                    <x-input-error :messages="$errors->get('started_at')" class="mt-1" />
                                </div>
                            </div>

                            {{-- Type & Status --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="create_type" :value="__('Type') . ' *'" />
                                    <select id="create_type" name="type" required
                                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-brand-600 focus:ring-brand-600">
                                        @foreach(\App\Enums\WarrantyType::cases() as $case)
                                            <option value="{{ $case->value }}" {{ old('type') === $case->value ? 'selected' : '' }}>
                                                {{ __($case->label()) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('type')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="create_status" :value="__('Status') . ' *'" />
                                    <select id="create_status" name="status" required
                                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-brand-600 focus:ring-brand-600">
                                        @foreach(\App\Enums\WarrantyStatus::cases() as $case)
                                            <option value="{{ $case->value }}" {{ old('status', 'active') === $case->value ? 'selected' : '' }}>
                                                {{ __($case->label()) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('status')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700/60">
                            <x-secondary-button type="button" @click="showCreateModal = false">
                                {{ __('Cancel') }}
                            </x-secondary-button>
                            <x-primary-button type="submit" class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ __('Create Guarantee') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- EDIT MODAL                                             --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <div x-show="showEditModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showEditModal = false"></div>

                <div x-show="showEditModal"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     class="relative inline-block w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl text-left shadow-xl border border-gray-100 dark:border-gray-700/60 overflow-hidden z-10">

                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 dark:bg-amber-500/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Edit Bank Guarantee') }}</h3>
                        </div>
                        <button type="button" @click="showEditModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Modal Form --}}
                    <form method="POST" :action="editFormAction">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="edit_warranty_id" :value="editWarrantyId" />

                        <div class="px-6 py-5 space-y-4">

                            {{-- Reference --}}
                            <div>
                                <x-input-label for="edit_reference" :value="__('Reference') . ' *'" />
                                <x-text-input id="edit_reference" name="reference" type="text"
                                    class="mt-1 block w-full" x-model="editReference" required />
                                <x-input-error :messages="$errors->get('reference')" class="mt-1" />
                            </div>

                            {{-- Bank Name --}}
                            <div>
                                <x-input-label for="edit_bank_name" :value="__('Bank Name') . ' *'" />
                                <x-text-input id="edit_bank_name" name="bank_name" type="text"
                                    class="mt-1 block w-full" x-model="editBankName" required />
                                <x-input-error :messages="$errors->get('bank_name')" class="mt-1" />
                            </div>

                            {{-- Amount & Start Date --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="edit_amount" :value="__('Amount (DZD)') . ' *'" />
                                    <x-text-input id="edit_amount" name="amount" type="number" step="0.01" min="0"
                                        class="mt-1 block w-full" x-model="editAmount" required />
                                    <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="edit_started_at" :value="__('Start Date')" />
                                    <x-text-input id="edit_started_at" name="started_at" type="date"
                                        class="mt-1 block w-full" x-model="editStartedAt" />
                                    <x-input-error :messages="$errors->get('started_at')" class="mt-1" />
                                </div>
                            </div>

                            {{-- Type & Status --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="edit_type" :value="__('Type') . ' *'" />
                                    <select id="edit_type" name="type" x-model="editType" required
                                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-brand-600 focus:ring-brand-600">
                                        @foreach(\App\Enums\WarrantyType::cases() as $case)
                                            <option value="{{ $case->value }}">{{ __($case->label()) }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('type')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="edit_status" :value="__('Status') . ' *'" />
                                    <select id="edit_status" name="status" x-model="editStatus" required
                                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-brand-600 focus:ring-brand-600">
                                        @foreach(\App\Enums\WarrantyStatus::cases() as $case)
                                            <option value="{{ $case->value }}">{{ __($case->label()) }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('status')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700/60">
                            <x-secondary-button type="button" @click="showEditModal = false">
                                {{ __('Cancel') }}
                            </x-secondary-button>
                            <x-primary-button type="submit" class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ __('Save Changes') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- DELETE MODAL                                           --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <x-crud-modal.delete
            show="showDeleteModal"
            action-url="deleteFormAction"
            item-name="deleteWarrantyRef"
            :title="__('Delete Guarantee')"
        />
    </div>
</x-app-layout>
