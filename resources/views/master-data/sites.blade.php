<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <x-tool-icon name="sites" class="w-11 h-11 sm:w-12 sm:h-12 shrink-0" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Sites') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Work locations, client facilities, and operational sites directory') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="info" size="md">
                    {{ $sites->total() }} {{ __('Sites') }}
                </x-badge>
                @can('create sites')
                    <x-primary-button
                        type="button"
                        x-data
                        @click="$dispatch('open-create-site-modal')"
                        onclick="window.dispatchEvent(new CustomEvent('open-create-site-modal'))"
                        class="flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>{{ __('New Site') }}</span>
                    </x-primary-button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8"
        @open-create-site-modal.window="openCreateModal()"
        x-init="window.addEventListener('open-create-site-modal', () => { showCreateModal = true; })"
        x-data="{
        showCreateModal: {{ ($errors->has('customer_id') || $errors->has('site_code') || $errors->has('full_name') || $errors->has('short_name') || $errors->has('location') || $errors->has('map_link')) && !old('_method') ? 'true' : 'false' }},
        showEditModal: {{ ($errors->has('customer_id') || $errors->has('site_code') || $errors->has('full_name') || $errors->has('short_name') || $errors->has('location') || $errors->has('map_link')) && old('_method') === 'PUT' ? 'true' : 'false' }},
        showDeleteModal: false,

        // Edit state
        editSiteId: {{ old('_method') === 'PUT' ? (int) old('edit_site_id', 0) : 'null' }},
        editSiteCustomerId: '{{ old('_method') === 'PUT' ? (string) old('customer_id', '') : '' }}',
        editSiteCode: '{{ old('_method') === 'PUT' ? addslashes((string) old('site_code', '')) : '' }}',
        editSiteFullName: '{{ old('_method') === 'PUT' ? addslashes((string) old('full_name', '')) : '' }}',
        editSiteShortName: '{{ old('_method') === 'PUT' ? addslashes((string) old('short_name', '')) : '' }}',
        editSiteLocation: '{{ old('_method') === 'PUT' ? addslashes((string) old('location', '')) : '' }}',
        editSiteMapLink: '{{ old('_method') === 'PUT' ? addslashes((string) old('map_link', '')) : '' }}',
        editSiteActionUrl: '{{ old('_method') === 'PUT' && old('edit_site_id') ? route('master-data.sites.update', (int) old('edit_site_id')) : '' }}',

        // Delete state
        deleteSiteName: '',
        deleteSiteActionUrl: '',

        openCreateModal() {
            this.showCreateModal = true;
        },

        openEditModal(id, customerId, siteCode, fullName, shortName, location, mapLink, actionUrl) {
            this.editSiteId = id;
            this.editSiteCustomerId = customerId || '';
            this.editSiteCode = siteCode || '';
            this.editSiteFullName = fullName || '';
            this.editSiteShortName = shortName || '';
            this.editSiteLocation = location || '';
            this.editSiteMapLink = mapLink || '';
            this.editSiteActionUrl = actionUrl;
            this.showEditModal = true;
        },

        openDeleteModal(name, actionUrl) {
            this.deleteSiteName = name;
            this.deleteSiteActionUrl = actionUrl;
            this.showDeleteModal = true;
        }
    }">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-master-data-tabs active="sites" />
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
                                    <x-tool-icon name="sites" class="w-6 h-6 shrink-0" />
                                    <span>{{ __('Sites Directory') }}</span>
                                </h3>

                                <div class="flex flex-wrap items-center gap-3">
                                    <x-global-filter
                                        :action="route('master-data.sites')"
                                        :search="true"
                                        :search-placeholder="__('Search by site code, name, location...')"
                                        :search-value="request('search')"
                                        :submit-text="__('Search')"
                                    >
                                        <select name="customer_id" onchange="this.form.submit()" class="py-1.5 ps-2.5 pe-8 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600 shadow-sm">
                                            <option value="">{{ __('All Clients') }}</option>
                                            @foreach($customers as $c)
                                                <option value="{{ $c->id }}" @selected((string) request('customer_id') === (string) $c->id)>
                                                    {{ $c->short_name ?: $c->company_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </x-global-filter>
                                </div>
                            </div>
                        </x-slot:toolbar>

                        <x-slot:header>
                            <x-table.th>ID</x-table.th>
                            <x-table.th>{{ __('Site Code') }}</x-table.th>
                            <x-table.th>{{ __('Site Name') }}</x-table.th>
                            <x-table.th>{{ __('Client / Customer') }}</x-table.th>
                            <x-table.th>{{ __('Location') }}</x-table.th>
                            <x-table.th>{{ __('Map Link') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                        </x-slot:header>

                        @forelse($sites as $site)
                            <x-table.tr>
                                <x-table.td class="font-mono text-xs font-semibold text-gray-500 dark:text-gray-400">
                                    #{{ $site->id }}
                                </x-table.td>

                                <x-table.td>
                                    @if($site->site_code)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-mono font-semibold bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-800/50">
                                            {{ $site->site_code }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-600 text-xs">—</span>
                                    @endif
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-indigo-600/10 dark:bg-indigo-600/20 border border-indigo-500/20 flex items-center justify-center font-bold text-xs text-indigo-700 dark:text-indigo-400 shrink-0">
                                            {{ $site->initials }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-gray-900 dark:text-white truncate">
                                                {{ $site->full_name ?: ($site->short_name ?: $site->site_code) }}
                                            </div>
                                            @if($site->short_name && $site->full_name)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                                        {{ $site->short_name }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    @if($site->customer)
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-lg bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-bold text-[10px] flex items-center justify-center shrink-0">
                                                {{ $site->customer->initials }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-xs font-semibold text-gray-900 dark:text-white truncate">
                                                    {{ $site->customer->company_name }}
                                                </div>
                                                @if($site->customer->short_name)
                                                    <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                                        {{ $site->customer->short_name }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500 text-xs italic">{{ __('Unassigned') }}</span>
                                    @endif
                                </x-table.td>

                                <x-table.td>
                                    @if($site->location)
                                        <div class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300">
                                            <svg class="w-3.5 h-3.5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span class="truncate max-w-[200px]" title="{{ $site->location }}">{{ $site->location }}</span>
                                        </div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-600 text-xs">—</span>
                                    @endif
                                </x-table.td>

                                <x-table.td>
                                    @if($site->map_link)
                                        <a href="{{ $site->map_link }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-teal-50 text-teal-700 dark:bg-teal-500/15 dark:text-teal-300 hover:bg-teal-100 dark:hover:bg-teal-500/25 border border-teal-200/60 dark:border-teal-500/30 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                            <span>{{ __('Google Maps') }}</span>
                                            <svg class="w-3 h-3 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-600 text-xs">—</span>
                                    @endif
                                </x-table.td>

                                <x-table.td class="text-end">
                                    <x-table.actions>
                                        @can('edit sites')
                                            <x-table.action-edit
                                                type="button"
                                                :title="__('Edit Site')"
                                                @click="openEditModal({{ $site->id }}, '{{ (string) $site->customer_id }}', '{{ addslashes((string) $site->site_code) }}', '{{ addslashes((string) $site->full_name) }}', '{{ addslashes((string) $site->short_name) }}', '{{ addslashes((string) $site->location) }}', '{{ addslashes((string) $site->map_link) }}', '{{ route('master-data.sites.update', array_merge(['site' => $site->id], request()->query())) }}')"
                                            />
                                        @endcan

                                        @can('delete sites')
                                            <x-table.action-delete
                                                type="button"
                                                :title="__('Delete Site')"
                                                @click="openDeleteModal('{{ addslashes((string) ($site->full_name ?: ($site->short_name ?: $site->site_code))) }}', '{{ route('master-data.sites.destroy', array_merge(['site' => $site->id], request()->query())) }}')"
                                            />
                                        @endcan
                                    </x-table.actions>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty :colspan="7" :message="__('No sites found.')" />
                        @endforelse

                        @if($sites->hasPages())
                            <x-slot:pagination>
                                {{ $sites->links() }}
                            </x-slot:pagination>
                        @endif
                    </x-table>
                </main>
            </div>
        </div>

        {{-- Create Site Modal --}}
        <x-crud-modal.form
            show="showCreateModal"
            :action-url="route('master-data.sites.store', request()->query())"
            method="POST"
            :title="__('New Site')"
            :description="__('Add an operational facility, station, or client site to the directory.')"
            icon-color="indigo"
            max-width="2xl"
            :submit-text="__('Create')"
        >
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Client / Customer --}}
                    <div class="sm:col-span-2">
                        <x-input-label for="create_customer_id" :value="__('Client / Customer')" />
                        <select id="create_customer_id" name="customer_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-600 dark:focus:border-brand-500 focus:ring-brand-600 dark:focus:ring-brand-500 shadow-sm text-sm">
                            <option value="">{{ __('None / Unassigned') }}</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" @selected((string) old('customer_id') === (string) $c->id)>
                                    {{ $c->company_name }} @if($c->short_name)({{ $c->short_name }})@endif
                                </option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-1" :messages="$errors->get('customer_id')" />
                    </div>

                    {{-- Site Code --}}
                    <div>
                        <x-input-label for="create_site_code" :value="__('Site Code / Identifier')" />
                        <x-text-input id="create_site_code" name="site_code" type="text" class="mt-1 block w-full font-mono" :value="old('site_code')" placeholder="GTIM-001" />
                        <x-input-error class="mt-1" :messages="$errors->get('site_code')" />
                    </div>

                    {{-- Short Name --}}
                    <div>
                        <x-input-label for="create_short_name" :value="__('Short Name / Abbreviation')" />
                        <x-text-input id="create_short_name" name="short_name" type="text" class="mt-1 block w-full" :value="old('short_name')" placeholder="GTIM" />
                        <x-input-error class="mt-1" :messages="$errors->get('short_name')" />
                    </div>

                    {{-- Full Name --}}
                    <div class="sm:col-span-2">
                        <x-input-label for="create_full_name" :value="__('Full Facility / Site Name')" />
                        <x-text-input id="create_full_name" name="full_name" type="text" class="mt-1 block w-full" :value="old('full_name')" placeholder="GROUPEMENT - TIMIMOUN" />
                        <x-input-error class="mt-1" :messages="$errors->get('full_name')" />
                    </div>

                    {{-- Location --}}
                    <div class="sm:col-span-2">
                        <x-input-label for="create_location" :value="__('Location / Wilaya / Region')" />
                        <x-text-input id="create_location" name="location" type="text" class="mt-1 block w-full" :value="old('location')" placeholder="{{ __('e.g. Hassi Barouda – Adrar') }}" />
                        <x-input-error class="mt-1" :messages="$errors->get('location')" />
                    </div>

                    {{-- Map Link --}}
                    <div class="sm:col-span-2">
                        <x-input-label for="create_map_link" :value="__('Google Maps URL / Coordinates Link')" />
                        <x-text-input id="create_map_link" name="map_link" type="text" class="mt-1 block w-full font-mono dir-ltr" :value="old('map_link')" placeholder="https://maps.app.goo.gl/..." />
                        <x-input-error class="mt-1" :messages="$errors->get('map_link')" />
                    </div>
                </div>
            </div>
        </x-crud-modal.form>

        {{-- Edit Site Modal --}}
        <x-crud-modal.form
            show="showEditModal"
            :alpine-action="'editSiteActionUrl'"
            method="PUT"
            :title="__('Edit Site')"
            :description="__('Update facility name, site identifier, geographical location, and coordinates.')"
            icon-color="amber"
            max-width="2xl"
            :submit-text="__('Save Changes')"
        >
            <input type="hidden" name="edit_site_id" :value="editSiteId">

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Client / Customer --}}
                    <div class="sm:col-span-2">
                        <x-input-label for="edit_customer_id" :value="__('Client / Customer')" />
                        <select id="edit_customer_id" name="customer_id" x-model="editSiteCustomerId" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-600 dark:focus:border-brand-500 focus:ring-brand-600 dark:focus:ring-brand-500 shadow-sm text-sm">
                            <option value="">{{ __('None / Unassigned') }}</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">
                                    {{ $c->company_name }} @if($c->short_name)({{ $c->short_name }})@endif
                                </option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-1" :messages="$errors->get('customer_id')" />
                    </div>

                    {{-- Site Code --}}
                    <div>
                        <x-input-label for="edit_site_code" :value="__('Site Code / Identifier')" />
                        <x-text-input id="edit_site_code" name="site_code" type="text" class="mt-1 block w-full font-mono" x-model="editSiteCode" />
                        <x-input-error class="mt-1" :messages="$errors->get('site_code')" />
                    </div>

                    {{-- Short Name --}}
                    <div>
                        <x-input-label for="edit_short_name" :value="__('Short Name / Abbreviation')" />
                        <x-text-input id="edit_short_name" name="short_name" type="text" class="mt-1 block w-full" x-model="editSiteShortName" />
                        <x-input-error class="mt-1" :messages="$errors->get('short_name')" />
                    </div>

                    {{-- Full Name --}}
                    <div class="sm:col-span-2">
                        <x-input-label for="edit_full_name" :value="__('Full Facility / Site Name')" />
                        <x-text-input id="edit_full_name" name="full_name" type="text" class="mt-1 block w-full" x-model="editSiteFullName" />
                        <x-input-error class="mt-1" :messages="$errors->get('full_name')" />
                    </div>

                    {{-- Location --}}
                    <div class="sm:col-span-2">
                        <x-input-label for="edit_location" :value="__('Location / Wilaya / Region')" />
                        <x-text-input id="edit_location" name="location" type="text" class="mt-1 block w-full" x-model="editSiteLocation" />
                        <x-input-error class="mt-1" :messages="$errors->get('location')" />
                    </div>

                    {{-- Map Link --}}
                    <div class="sm:col-span-2">
                        <x-input-label for="edit_map_link" :value="__('Google Maps URL / Coordinates Link')" />
                        <x-text-input id="edit_map_link" name="map_link" type="text" class="mt-1 block w-full font-mono dir-ltr" x-model="editSiteMapLink" />
                        <x-input-error class="mt-1" :messages="$errors->get('map_link')" />
                    </div>
                </div>
            </div>
        </x-crud-modal.form>

        {{-- Delete Confirmation Modal --}}
        <x-crud-modal.delete
            show="showDeleteModal"
            action-url="deleteSiteActionUrl"
            item-name="deleteSiteName"
            :title="__('Delete Site')"
        />
    </div>
</x-app-layout>
