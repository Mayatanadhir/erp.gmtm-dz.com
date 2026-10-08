<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <x-tool-icon name="clients" class="w-11 h-11 sm:w-12 sm:h-12 shrink-0" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Clients') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Client directory, partner records, and corporate directory') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="info" size="md">
                    {{ $customers->total() }} {{ __('Clients') }}
                </x-badge>
                @can('create clients')
                    <x-primary-button
                        type="button"
                        x-data
                        @click="$dispatch('open-create-customer-modal')"
                        onclick="window.dispatchEvent(new CustomEvent('open-create-customer-modal'))"
                        class="flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>{{ __('New Client') }}</span>
                    </x-primary-button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8"
        @open-create-customer-modal.window="openCreateModal()"
        x-init="window.addEventListener('open-create-customer-modal', () => { showCreateModal = true; })"
        x-data="{
        showCreateModal: {{ ($errors->has('company_name') || $errors->has('reference') || $errors->has('short_name') || $errors->has('phone') || $errors->has('email') || $errors->has('website') || $errors->has('registration_number') || $errors->has('address') || $errors->has('notes')) && !old('_method') ? 'true' : 'false' }},
        showEditModal: {{ ($errors->has('company_name') || $errors->has('reference') || $errors->has('short_name') || $errors->has('phone') || $errors->has('email') || $errors->has('website') || $errors->has('registration_number') || $errors->has('address') || $errors->has('notes')) && old('_method') === 'PUT' ? 'true' : 'false' }},
        showDeleteModal: false,

        // Edit state
        editCustomerId: {{ old('_method') === 'PUT' ? (int) old('edit_customer_id', 0) : 'null' }},
        editCustomerReference: '{{ old('_method') === 'PUT' ? addslashes((string) old('reference', '')) : '' }}',
        editCustomerCompanyName: '{{ old('_method') === 'PUT' ? addslashes((string) old('company_name', '')) : '' }}',
        editCustomerShortName: '{{ old('_method') === 'PUT' ? addslashes((string) old('short_name', '')) : '' }}',
        editCustomerAddress: '{{ old('_method') === 'PUT' ? addslashes((string) old('address', '')) : '' }}',
        editCustomerPhone: '{{ old('_method') === 'PUT' ? addslashes((string) old('phone', '')) : '' }}',
        editCustomerEmail: '{{ old('_method') === 'PUT' ? addslashes((string) old('email', '')) : '' }}',
        editCustomerWebsite: '{{ old('_method') === 'PUT' ? addslashes((string) old('website', '')) : '' }}',
        editCustomerRegistrationNumber: '{{ old('_method') === 'PUT' ? addslashes((string) old('registration_number', '')) : '' }}',
        editCustomerNotes: '{{ old('_method') === 'PUT' ? addslashes((string) old('notes', '')) : '' }}',
        editCustomerActionUrl: '{{ old('_method') === 'PUT' && old('edit_customer_id') ? route('master-data.clients.update', (int) old('edit_customer_id')) : '' }}',

        // Delete state
        deleteCustomerName: '',
        deleteCustomerActionUrl: '',

        openCreateModal() {
            this.showCreateModal = true;
        },

        openEditModal(id, reference, companyName, shortName, address, phone, email, website, registrationNumber, notes, actionUrl) {
            this.editCustomerId = id;
            this.editCustomerReference = reference || '';
            this.editCustomerCompanyName = companyName || '';
            this.editCustomerShortName = shortName || '';
            this.editCustomerAddress = address || '';
            this.editCustomerPhone = phone || '';
            this.editCustomerEmail = email || '';
            this.editCustomerWebsite = website || '';
            this.editCustomerRegistrationNumber = registrationNumber || '';
            this.editCustomerNotes = notes || '';
            this.editCustomerActionUrl = actionUrl;
            this.showEditModal = true;
        },

        openDeleteModal(name, actionUrl) {
            this.deleteCustomerName = name;
            this.deleteCustomerActionUrl = actionUrl;
            this.showDeleteModal = true;
        }
    }">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-master-data-tabs active="clients" />
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

                    <x-table>
                        <x-slot:toolbar>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                                    <x-tool-icon name="clients" class="w-6 h-6 shrink-0" />
                                    <span>{{ __('Clients Directory') }}</span>
                                </h3>

                                <div class="flex flex-wrap items-center gap-3">
                                    <x-global-filter
                                        :action="route('master-data.clients')"
                                        :search="true"
                                        :search-placeholder="__('Search by company, short name, reference, RC...')"
                                        :search-value="request('search')"
                                        :submit-text="__('Search')"
                                    />

                                   
                                </div>
                            </div>
                        </x-slot:toolbar>

                        <x-slot:header>
                            <x-table.th>ID</x-table.th>
                            <x-table.th>{{ __('Client / Company') }}</x-table.th>
                            <x-table.th>{{ __('Reference') }}</x-table.th>
                            <x-table.th>{{ __('Registration Number') }}</x-table.th>
                            <x-table.th>{{ __('Contact Details') }}</x-table.th>
                            <x-table.th>{{ __('Website / Address') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                        </x-slot:header>

                        @forelse($customers as $customer)
                            <x-table.tr>
                                <x-table.td class="font-mono text-xs font-semibold text-gray-500 dark:text-gray-400">
                                    #{{ $customer->id }}
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-emerald-600/10 dark:bg-emerald-600/20 border border-emerald-500/20 flex items-center justify-center font-bold text-xs text-emerald-700 dark:text-emerald-400 shrink-0">
                                            {{ $customer->initials }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-gray-900 dark:text-white truncate">
                                                {{ $customer->company_name }}
                                            </div>
                                            @if($customer->short_name)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                                        {{ $customer->short_name }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    @if($customer->reference)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded font-mono text-xs font-semibold bg-brand-50 dark:bg-emerald-500/20 text-brand-700 dark:text-emerald-300 border border-brand-200 dark:border-emerald-500/40">
                                            {{ $customer->reference }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-600 text-xs">—</span>
                                    @endif
                                </x-table.td>

                                <x-table.td>
                                    @if($customer->registration_number)
                                        <div class="flex items-center gap-1.5 font-mono text-xs text-gray-700 dark:text-gray-300">
                                            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span>{{ $customer->registration_number }}</span>
                                        </div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-600 text-xs">—</span>
                                    @endif
                                </x-table.td>

                                <x-table.td>
                                    <div class="space-y-1">
                                        @if($customer->phone)
                                            <div class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300">
                                                <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                                <a href="tel:{{ $customer->phone }}" class="hover:underline font-mono text-[11px] dir-ltr text-left">{{ $customer->phone }}</a>
                                            </div>
                                        @endif
                                        @if($customer->email)
                                            <div class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                                                <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                <a href="mailto:{{ $customer->email }}" class="hover:underline text-[11px] truncate max-w-[170px]">{{ $customer->email }}</a>
                                            </div>
                                        @endif
                                        @if(!$customer->phone && !$customer->email)
                                            <span class="text-gray-400 dark:text-gray-600 text-xs">—</span>
                                        @endif
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="space-y-1">
                                        @if($customer->website)
                                            <div class="flex items-center gap-1.5 text-xs text-brand-600 dark:text-brand-400">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                                <a href="{{ str_starts_with($customer->website, 'http') ? $customer->website : 'https://' . $customer->website }}" target="_blank" rel="noopener noreferrer" class="hover:underline text-[11px] truncate max-w-[160px]">
                                                    {{ preg_replace('#^https?://#', '', $customer->website) }}
                                                </a>
                                            </div>
                                        @endif
                                        @if($customer->address)
                                            <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400" title="{{ $customer->address }}">
                                                <svg class="w-3.5 h-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                <span class="truncate max-w-[160px] text-[11px]">{{ $customer->address }}</span>
                                            </div>
                                        @endif
                                        @if(!$customer->website && !$customer->address)
                                            <span class="text-gray-400 dark:text-gray-600 text-xs">—</span>
                                        @endif
                                    </div>
                                </x-table.td>

                                <x-table.td class="text-end">
                                    <x-table.actions>
                                        @can('edit clients')
                                            <x-table.action-edit
                                                type="button"
                                                :title="__('Edit Client')"
                                                @click="openEditModal({{ $customer->id }}, '{{ addslashes((string) $customer->reference) }}', '{{ addslashes((string) $customer->company_name) }}', '{{ addslashes((string) $customer->short_name) }}', '{{ addslashes((string) $customer->address) }}', '{{ addslashes((string) $customer->phone) }}', '{{ addslashes((string) $customer->email) }}', '{{ addslashes((string) $customer->website) }}', '{{ addslashes((string) $customer->registration_number) }}', '{{ addslashes((string) $customer->notes) }}', '{{ route('master-data.clients.update', array_merge(['customer' => $customer->id], request()->query())) }}')"
                                            />
                                        @endcan

                                        @can('delete clients')
                                            <x-table.action-delete
                                                type="button"
                                                :title="__('Delete Client')"
                                                @click="openDeleteModal('{{ addslashes((string) $customer->company_name) }}', '{{ route('master-data.clients.destroy', array_merge(['customer' => $customer->id], request()->query())) }}')"
                                            />
                                        @endcan
                                    </x-table.actions>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty :colspan="7" :message="__('No clients found.')" />
                        @endforelse

                        @if($customers->hasPages())
                            <x-slot:pagination>
                                {{ $customers->links() }}
                            </x-slot:pagination>
                        @endif
                    </x-table>
                </main>
            </div>
        </div>

        {{-- Create Client Modal --}}
        <x-crud-modal.form
            show="showCreateModal"
            :action-url="route('master-data.clients.store', request()->query())"
            method="POST"
            :title="__('New Client')"
            :description="__('Add a new customer or business partner to the corporate registry.')"
            icon-color="emerald"
            max-width="2xl"
            :submit-text="__('Create')"
        >
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Company Name --}}
                    <div class="sm:col-span-2">
                        <x-input-label for="create_company_name" :value="__('Company Name')" />
                        <x-text-input id="create_company_name" name="company_name" type="text" class="mt-1 block w-full" :value="old('company_name')" placeholder="{{ __('e.g. Sonatrach Spa') }}" required />
                        <x-input-error class="mt-1" :messages="$errors->get('company_name')" />
                    </div>

                    {{-- Short Name --}}
                    <div>
                        <x-input-label for="create_short_name" :value="__('Short Name / Abbreviation')" />
                        <x-text-input id="create_short_name" name="short_name" type="text" class="mt-1 block w-full" :value="old('short_name')" placeholder="{{ __('e.g. SH / DP') }}" />
                        <x-input-error class="mt-1" :messages="$errors->get('short_name')" />
                    </div>

                    {{-- Reference --}}
                    <div>
                        <x-input-label for="create_reference" :value="__('Reference Code')" />
                        <x-text-input id="create_reference" name="reference" type="text" class="mt-1 block w-full font-mono" :value="old('reference')" placeholder="CLI-2026-001" />
                        <x-input-error class="mt-1" :messages="$errors->get('reference')" />
                    </div>

                    {{-- Registration Number (RC/NIF/NIS) --}}
                    <div>
                        <x-input-label for="create_registration_number" :value="__('Registration Number (RC/NIF)')" />
                        <x-text-input id="create_registration_number" name="registration_number" type="text" class="mt-1 block w-full font-mono" :value="old('registration_number')" placeholder="16/00-1234567B18" />
                        <x-input-error class="mt-1" :messages="$errors->get('registration_number')" />
                    </div>

                    {{-- Phone --}}
                    <div>
                        <x-input-label for="create_phone" :value="__('Phone Number')" />
                        <x-text-input id="create_phone" name="phone" type="text" class="mt-1 block w-full font-mono dir-ltr" :value="old('phone')" placeholder="+213 21 00 00 00" />
                        <x-input-error class="mt-1" :messages="$errors->get('phone')" />
                    </div>

                    {{-- Email --}}
                    <div>
                        <x-input-label for="create_email" :value="__('Email Address')" />
                        <x-text-input id="create_email" name="email" type="email" class="mt-1 block w-full font-mono" :value="old('email')" placeholder="contact@company.com" />
                        <x-input-error class="mt-1" :messages="$errors->get('email')" />
                    </div>

                    {{-- Website --}}
                    <div>
                        <x-input-label for="create_website" :value="__('Website')" />
                        <x-text-input id="create_website" name="website" type="text" class="mt-1 block w-full font-mono" :value="old('website')" placeholder="https://www.company.com" />
                        <x-input-error class="mt-1" :messages="$errors->get('website')" />
                    </div>

                    {{-- Address --}}
                    <div class="sm:col-span-2">
                        <x-input-label for="create_address" :value="__('Address')" />
                        <x-text-input id="create_address" name="address" type="text" class="mt-1 block w-full" :value="old('address')" placeholder="{{ __('Headquarters, Industrial Zone, City, Country') }}" />
                        <x-input-error class="mt-1" :messages="$errors->get('address')" />
                    </div>

                    {{-- Notes --}}
                    <div class="sm:col-span-2">
                        <x-input-label for="create_notes" :value="__('Notes & Observations')" />
                        <textarea id="create_notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-600 dark:focus:border-brand-500 focus:ring-brand-600 dark:focus:ring-brand-500 shadow-sm text-sm" placeholder="{{ __('Additional corporate notes, contact person, or specifications...') }}">{{ old('notes') }}</textarea>
                        <x-input-error class="mt-1" :messages="$errors->get('notes')" />
                    </div>
                </div>
            </div>
        </x-crud-modal.form>

        {{-- Edit Client Modal --}}
        <x-crud-modal.form
            show="showEditModal"
            :alpine-action="'editCustomerActionUrl'"
            method="PUT"
            :title="__('Edit Client')"
            :description="__('Update customer details, contact channels, and registration records.')"
            icon-color="amber"
            max-width="2xl"
            :submit-text="__('Save Changes')"
        >
            <input type="hidden" name="edit_customer_id" :value="editCustomerId">

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Company Name --}}
                    <div class="sm:col-span-2">
                        <x-input-label for="edit_company_name" :value="__('Company Name')" />
                        <x-text-input id="edit_company_name" name="company_name" type="text" class="mt-1 block w-full" x-model="editCustomerCompanyName" required />
                        <x-input-error class="mt-1" :messages="$errors->get('company_name')" />
                    </div>

                    {{-- Short Name --}}
                    <div>
                        <x-input-label for="edit_short_name" :value="__('Short Name / Abbreviation')" />
                        <x-text-input id="edit_short_name" name="short_name" type="text" class="mt-1 block w-full" x-model="editCustomerShortName" />
                        <x-input-error class="mt-1" :messages="$errors->get('short_name')" />
                    </div>

                    {{-- Reference --}}
                    <div>
                        <x-input-label for="edit_reference" :value="__('Reference Code')" />
                        <x-text-input id="edit_reference" name="reference" type="text" class="mt-1 block w-full font-mono" x-model="editCustomerReference" />
                        <x-input-error class="mt-1" :messages="$errors->get('reference')" />
                    </div>

                    {{-- Registration Number (RC/NIF/NIS) --}}
                    <div>
                        <x-input-label for="edit_registration_number" :value="__('Registration Number (RC/NIF)')" />
                        <x-text-input id="edit_registration_number" name="registration_number" type="text" class="mt-1 block w-full font-mono" x-model="editCustomerRegistrationNumber" />
                        <x-input-error class="mt-1" :messages="$errors->get('registration_number')" />
                    </div>

                    {{-- Phone --}}
                    <div>
                        <x-input-label for="edit_phone" :value="__('Phone Number')" />
                        <x-text-input id="edit_phone" name="phone" type="text" class="mt-1 block w-full font-mono dir-ltr" x-model="editCustomerPhone" />
                        <x-input-error class="mt-1" :messages="$errors->get('phone')" />
                    </div>

                    {{-- Email --}}
                    <div>
                        <x-input-label for="edit_email" :value="__('Email Address')" />
                        <x-text-input id="edit_email" name="email" type="email" class="mt-1 block w-full font-mono" x-model="editCustomerEmail" />
                        <x-input-error class="mt-1" :messages="$errors->get('email')" />
                    </div>

                    {{-- Website --}}
                    <div>
                        <x-input-label for="edit_website" :value="__('Website')" />
                        <x-text-input id="edit_website" name="website" type="text" class="mt-1 block w-full font-mono" x-model="editCustomerWebsite" />
                        <x-input-error class="mt-1" :messages="$errors->get('website')" />
                    </div>

                    {{-- Address --}}
                    <div class="sm:col-span-2">
                        <x-input-label for="edit_address" :value="__('Address')" />
                        <x-text-input id="edit_address" name="address" type="text" class="mt-1 block w-full" x-model="editCustomerAddress" />
                        <x-input-error class="mt-1" :messages="$errors->get('address')" />
                    </div>

                    {{-- Notes --}}
                    <div class="sm:col-span-2">
                        <x-input-label for="edit_notes" :value="__('Notes & Observations')" />
                        <textarea id="edit_notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-amber-500 dark:focus:border-amber-600 focus:ring-amber-500 dark:focus:ring-amber-600 shadow-sm text-sm" x-model="editCustomerNotes"></textarea>
                        <x-input-error class="mt-1" :messages="$errors->get('notes')" />
                    </div>
                </div>
            </div>
        </x-crud-modal.form>

        {{-- Delete Client Confirmation Modal --}}
        <x-crud-modal.delete
            show="showDeleteModal"
            action-url="deleteCustomerActionUrl"
            item-name="deleteCustomerName"
            :title="__('Delete Client')"
        />
    </div>
</x-app-layout>
