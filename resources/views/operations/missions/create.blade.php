<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('operations.missions') }}" class="p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Create Field Mission') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Mobilize a field calibration operation, assign staff and equipment') }}
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8"
             x-data="{
                 contractId: '{{ old('contract_id', '') }}',
                 contracts: {{ Js::from($contracts) }},
                 allSites: {{ Js::from($sites) }},
                 selectedSiteId: '{{ old('site_id', '') }}',

                 // Vehicle
                 selectedVehicle: '{{ old('vehicle_id', '') }}',
                 vehicles: {{ Js::from($vehicles->keyBy('id')) }},

                 // Team selection
                 selectedEmployees: {{ Js::from(old('employees') ? (is_array(old('employees')) ? array_values(array_filter(array_map(fn($e) => is_array($e) ? (int)($e['id'] ?? 0) : (int)$e, old('employees')))) : []) : ($employees->count() > 0 ? [$employees->first()->id] : [])) }},
                 chiefId: {{ old('chief_id', ($employees->count() > 0 ? $employees->first()->id : 'null')) }},
                 employeeSearch: '',
                 allEmployees: {{ Js::from($employees->map(fn($e) => [
                     'id' => $e->id,
                     'name' => $e->full_name,
                     'position' => is_object($e->position) ? ($e->position->label() ?? $e->position->value) : (string) $e->position,
                     'initials' => $e->initials ?: mb_substr($e->full_name, 0, 1),
                     'photo' => $e->profile_photo_url
                 ])) }},

                 // Equipments & Lots
                 selectedEquipments: {{ Js::from(old('equipments') ? array_values(array_map('intval', (array) old('equipments'))) : []) }},
                 equipmentMap: {{ Js::from($equipments->map(fn($e) => [
                     'id' => $e->id,
                     'package' => is_object($e->package) ? $e->package->value : (string) $e->package,
                     'name' => $e->full_name,
                     'code' => $e->internal_code,
                     'image' => $e->image_url
                 ])) }},
                 equipmentSearch: '',

                 get selectedContract() {
                     return this.contracts.find(c => String(c.id) === String(this.contractId)) || null;
                 },
                 get filteredSites() {
                     if (!this.selectedContract || !this.selectedContract.customer_id) {
                         return this.allSites;
                     }
                     return this.allSites.filter(s => String(s.customer_id) === String(this.selectedContract.customer_id));
                 },
                 onContractChange() {
                     const availableIds = this.filteredSites.map(s => String(s.id));
                     if (this.selectedSiteId && !availableIds.includes(String(this.selectedSiteId))) {
                         this.selectedSiteId = '';
                     }
                 },
                 toggleEmployee(empId) {
                     const idx = this.selectedEmployees.indexOf(empId);
                     if (idx > -1) {
                         this.selectedEmployees.splice(idx, 1);
                         if (this.chiefId === empId) {
                             this.chiefId = this.selectedEmployees.length > 0 ? this.selectedEmployees[0] : null;
                         }
                     } else {
                         this.selectedEmployees.push(empId);
                         if (!this.chiefId) {
                             this.chiefId = empId;
                         }
                     }
                 },
                 isEmployeeSelected(empId) {
                     return this.selectedEmployees.includes(empId);
                 },
                 get selectedEmployeeObjects() {
                     return this.allEmployees.filter(e => this.selectedEmployees.includes(e.id));
                 },
                 selectLot(lotKey) {
                     const target = lotKey.toLowerCase().replace(/[^a-z0-9]/g, '');
                     const lotIds = this.equipmentMap
                         .filter(eq => {
                             const pkg = (eq.package || '').toLowerCase().replace(/[^a-z0-9]/g, '');
                             return pkg === target;
                         })
                         .map(eq => eq.id);

                     lotIds.forEach(id => {
                         if (!this.selectedEquipments.includes(id)) {
                             this.selectedEquipments.push(id);
                         }
                     });
                 },
                 resetEquipments() {
                     this.selectedEquipments = [];
                 },
                 toggleEquipment(eqId) {
                     const idx = this.selectedEquipments.indexOf(eqId);
                     if (idx > -1) {
                         this.selectedEquipments.splice(idx, 1);
                     } else {
                         this.selectedEquipments.push(eqId);
                     }
                 },
                 isEquipmentSelected(eqId) {
                     return this.selectedEquipments.includes(eqId);
                 }
             }">

            @if ($errors->any())
                <x-alert variant="danger" class="mb-6">
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            <form action="{{ route('operations.missions.store') }}" method="POST" id="missionForm" class="space-y-6">
                @csrf

                <!-- 1. Mission Specifications Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>{{ __('General Specifications') }}</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Reference (Auto Generated) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Mission Reference') }}
                            </label>
                            <input type="text" value="{{ __('Auto-generated upon creation (e.g. M-2026-001)') }}" disabled readonly class="w-full py-2.5 px-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900/60 text-gray-500 dark:text-gray-400 text-sm shadow-xs cursor-not-allowed">
                        </div>

                        <!-- Contract Reference (Dynamic Customer Link) -->
                        <div>
                            <label for="contract_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 flex items-center justify-between">
                                <span>{{ __('Contract Reference') }}</span>
                                <span class="text-xs text-gray-400 font-normal">({{ __('Optional') }})</span>
                            </label>
                            <select name="contract_id" id="contract_id" x-model="contractId" @change="onContractChange()" class="w-full py-2.5 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                                <option value="">{{ __('Select Contract') }}</option>
                                @if ($contracts->isEmpty())
                                    <option value="" disabled>{{ __('No active contracts available') }}</option>
                                @endif
                                @foreach ($contracts as $contract)
                                    <option value="{{ $contract->id }}">
                                        {{ $contract->reference }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Site (Filtered by Contract Customer) -->
                        <div class="md:col-span-2">
                            <label for="site_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 flex items-center justify-between">
                                <span>{{ __('Target Industrial Site') }} <span class="text-rose-500">*</span></span>
                                <template x-if="selectedContract && selectedContract.customer_id">
                                    <span class="text-xs text-brand-600 dark:text-brand-400 font-medium">
                                        {{ __('Filtered by client') }} (<span x-text="filteredSites.length"></span> {{ __('sites') }})
                                    </span>
                                </template>
                            </label>
                            <select id="site_id" name="site_id" x-model="selectedSiteId" required class="w-full py-2.5 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                                <option value="" disabled>{{ __('Select Site') }}</option>
                                <template x-for="site in filteredSites" :key="site.id">
                                    <option :value="site.id" x-text="site.short_name + (site.full_name ? ' — ' + site.full_name : '') + (site.location ? ' (' + site.location + ')' : '')" :selected="String(site.id) === String(selectedSiteId)"></option>
                                </template>
                                @foreach ($sites as $site)
                                    <option value="{{ $site->id }}" class="hidden">
                                        {{ $site->short_name }} — {{ $site->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Transport Vehicle -->
                        <div class="md:col-span-2">
                            <label for="vehicle_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Designated Transport Vehicle') }}
                            </label>
                            <div class="flex items-center gap-3">
                                <select id="vehicle_id" name="vehicle_id" x-model="selectedVehicle" class="flex-1 py-2.5 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                                    <option value="">{{ __('No Vehicle Assigned (External Transit)') }}</option>
                                    @foreach ($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}" @selected(old('vehicle_id') == $vehicle->id)>
                                            {{ $vehicle->full_name }} ({{ $vehicle->serial_number }})
                                        </option>
                                    @endforeach
                                </select>
                                <template x-if="selectedVehicle && vehicles[selectedVehicle]">
                                    <div class="w-12 h-10 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center shrink-0 p-1 overflow-hidden shadow-xs">
                                        <template x-if="vehicles[selectedVehicle]?.image_url">
                                            <img :src="vehicles[selectedVehicle].image_url" :alt="vehicles[selectedVehicle].full_name" class="w-full h-full object-contain">
                                        </template>
                                        <template x-if="!vehicles[selectedVehicle]?.image_url">
                                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-8 4h8m-8 4h4m5-9H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V8a2 2 0 00-2-2z"/></svg>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Start Date -->
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                <bdi>{{ __('Start Date (Departure)') }} <span class="text-rose-500">*</span></bdi>
                            </label>
                            <input type="date" id="start_date" name="start_date" dir="ltr" lang="en-GB" value="{{ old('start_date', date('Y-m-d')) }}" required class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 !text-left focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                        </div>

                        <!-- End Date -->
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('End Date (Estimated Return)') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" id="end_date" name="end_date" dir="ltr" lang="en-GB" value="{{ old('end_date', date('Y-m-d', strtotime('+7 days'))) }}" required class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 !text-left focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                        </div>

                        <!-- Mob/Dmob Days -->
                        <div class="md:col-span-2">
                            <label for="mob_dmob_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Mobilization / Demobilization (Days)') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" step="0.5" id="mob_dmob_days" name="mob_dmob_days" value="{{ old('mob_dmob_days', 2) }}" min="0" max="30" required class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Transit days deducted from actual operational calibration days') }}</p>
                        </div>

                        <!-- Description -->
                        <div class="md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Mission Scope & Technical Objective') }}
                            </label>
                            <textarea id="description" name="description" rows="3" placeholder="{{ __('Periodic fiscal metering loop verification, transmitter inspection...') }}" class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- 2. Team Deployment Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <span>{{ __('Field Engineering Team') }}</span>
                                <span class="text-rose-500">*</span>
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ __('Select participating engineers and designate the mission leader') }}
                            </p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800/40">
                            <span x-text="selectedEmployees.length"></span>&nbsp;{{ __('Selected') }}
                        </span>
                    </div>

                    <!-- Search filter for employees -->
                    <div class="mb-4">
                        <div class="relative">
                            <input type="text" x-model="employeeSearch" placeholder="{{ __('Search employees by name or position...') }}" class="w-full py-2 ps-9 pe-3 text-xs rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/40 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500">
                            <svg class="w-4 h-4 text-gray-400 absolute start-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>

                    <!-- Employees List -->
                    <div class="space-y-2.5 max-h-72 overflow-y-auto p-1">
                        @foreach ($employees as $employee)
                            @php
                                $empPosition = is_object($employee->position) ? ($employee->position->label() ?? $employee->position->value) : (string) ($employee->position ?? '');
                            @endphp
                            <div x-show="!employeeSearch || {{ Js::from(mb_strtolower($employee->full_name . ' ' . $empPosition)) }}.includes(employeeSearch.toLowerCase())"
                                 @click="toggleEmployee({{ $employee->id }})"
                                 class="flex items-center justify-between p-3 rounded-lg border cursor-pointer transition-all"
                                 :class="isEmployeeSelected({{ $employee->id }}) ? 'border-indigo-500 bg-indigo-50/40 dark:bg-indigo-900/20 dark:border-indigo-600 shadow-xs' : 'border-gray-200 dark:border-gray-700/80 bg-gray-50/50 dark:bg-gray-900/40 hover:bg-white dark:hover:bg-gray-700/50'">
                                <div class="flex items-center gap-3 min-w-0">
                                    <input type="checkbox"
                                           :checked="isEmployeeSelected({{ $employee->id }})"
                                           @click.stop="toggleEmployee({{ $employee->id }})"
                                           class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-brand-600 focus:ring-brand-500 shrink-0">
                                    <div class="relative shrink-0">
                                        @if ($employee->profile_photo_url)
                                            <img src="{{ $employee->profile_photo_url }}" alt="{{ $employee->full_name }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 dark:border-gray-700 shadow-xs">
                                        @else
                                            <div class="w-9 h-9 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 flex items-center justify-center font-bold text-xs border border-emerald-200 dark:border-emerald-800/70">
                                                {{ $employee->initials ?: mb_substr($employee->full_name, 0, 1) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0 truncate">
                                        <div class="font-medium text-sm text-gray-900 dark:text-white truncate">{{ $employee->full_name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $empPosition }}</div>
                                    </div>
                                </div>

                                <template x-if="chiefId === {{ $employee->id }}">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-700 shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        {{ __('Leader') }}
                                    </span>
                                </template>
                            </div>
                        @endforeach
                    </div>

                    <!-- Dynamic Chief Selection Box (Exact Legacy Logic) -->
                    <div x-show="selectedEmployees.length > 0" x-transition class="mt-5 p-4 rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50/40 dark:bg-amber-950/20">
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                <span>{{ __('Select Mission Leader') }}</span>
                                <span class="text-rose-500">*</span>
                            </label>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Designate exactly one team leader') }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                            <template x-for="emp in selectedEmployeeObjects" :key="emp.id">
                                <label class="flex items-center gap-2.5 p-2.5 rounded-lg border cursor-pointer transition-all"
                                       :class="chiefId === emp.id ? 'bg-amber-100/70 dark:bg-amber-900/40 border-amber-400 dark:border-amber-600 text-amber-900 dark:text-amber-100 font-semibold shadow-xs ring-1 ring-amber-400' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-amber-300'">
                                    <input type="radio"
                                           name="ui_chief_radio"
                                           :value="emp.id"
                                           :checked="chiefId === emp.id"
                                           @change="chiefId = emp.id"
                                           class="text-amber-600 focus:ring-amber-500">
                                    <span class="text-xs truncate" x-text="emp.name"></span>
                                </label>
                            </template>
                        </div>

                        @error('chief_id')
                            <p class="mt-2.5 text-xs text-red-600 dark:text-red-400 flex items-center gap-1.5 font-medium">
                                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Hidden Backend Form Inputs for Team & Leader --}}
                    <input type="hidden" name="chief_id" :value="chiefId">
                    <template x-for="(empId, index) in selectedEmployees" :key="empId">
                        <div>
                            <input type="hidden" :name="'employees[' + index + '][id]'" :value="empId">
                            <input type="hidden" :name="'employees[' + index + '][is_leader]'" :value="empId === chiefId ? 1 : 0">
                        </div>
                    </template>
                </div>

                <!-- 3. Logistics & Equipment Card (with Quick Lots 01 & 02) -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                <span>{{ __('Equipment & Calibration Standards') }}</span>
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ __('Mobilize standard gauges, provers, and field measurement tools') }}
                            </p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 border border-amber-100 dark:border-amber-800/40">
                            <span x-text="selectedEquipments.length"></span>&nbsp;{{ __('Selected') }}
                        </span>
                    </div>

                    <!-- Quick Actions Toolbar (Legacy Feature Ported) -->
                    <div class="flex flex-wrap items-center gap-2 mb-4 p-3 bg-gray-50 dark:bg-gray-900/60 rounded-xl border border-gray-200 dark:border-gray-700">
                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            {{ __('Quick Actions') }}:
                        </span>

                        <button type="button"
                                @click="selectLot('lot_01')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 hover:border-brand-500 transition-colors shadow-xs">
                            <svg class="w-3.5 h-3.5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ __('Lot 01') }}</span>
                        </button>

                        <button type="button"
                                @click="selectLot('lot_02')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 hover:border-brand-500 transition-colors shadow-xs">
                            <svg class="w-3.5 h-3.5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ __('Lot 02') }}</span>
                        </button>

                        <button type="button"
                                @click="resetEquipments()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 hover:bg-rose-100 dark:hover:bg-rose-900/60 transition-colors shadow-xs ms-auto">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>{{ __('Reset') }}</span>
                        </button>
                    </div>

                    <!-- Search filter for equipments -->
                    <div class="mb-4">
                        <div class="relative">
                            <input type="text" x-model="equipmentSearch" placeholder="{{ __('Search equipment by code or name...') }}" class="w-full py-2 ps-9 pe-3 text-xs rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/40 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500">
                            <svg class="w-4 h-4 text-gray-400 absolute start-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>

                    <!-- Equipment Groups (Organized by Lots) -->
                    <div class="space-y-4 max-h-96 overflow-y-auto p-1">
                        @foreach ($equipments->groupBy(fn($e) => is_object($e->package) ? $e->package->value : (string) $e->package) as $lotKey => $items)
                            <div class="border border-gray-200 dark:border-gray-700/80 rounded-xl p-3 bg-gray-50/30 dark:bg-gray-900/20">
                                <div class="flex items-center justify-between mb-2 pb-1.5 border-b border-gray-200 dark:border-gray-700/60">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                                        <span>
                                            @if($lotKey === 'lot_01')
                                                {{ __('Lot 01') }}
                                            @elseif($lotKey === 'lot_02')
                                                {{ __('Lot 02') }}
                                            @elseif($lotKey === 'vehicle_lot')
                                                {{ __('Vehicle Lot') }}
                                            @else
                                                {{ __('Uncategorized / Standard Tools') }}
                                            @endif
                                        </span>
                                    </h4>
                                    <span class="text-[11px] text-gray-400">{{ count($items) }} {{ __('tools') }}</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                                    @foreach ($items as $equipment)
                                        <div x-show="!equipmentSearch || {{ Js::from(mb_strtolower($equipment->full_name . ' ' . $equipment->internal_code)) }}.includes(equipmentSearch.toLowerCase())"
                                             @click="toggleEquipment({{ $equipment->id }})"
                                             class="flex items-center gap-2.5 p-2 rounded-lg cursor-pointer transition-all border"
                                             :class="isEquipmentSelected({{ $equipment->id }}) ? 'border-brand-500 bg-emerald-50/80 dark:bg-emerald-950/40 dark:border-brand-500 shadow-xs' : 'border-gray-200 dark:border-gray-700/70 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600'">
                                            <input type="checkbox"
                                                   :checked="isEquipmentSelected({{ $equipment->id }})"
                                                   @click.stop="toggleEquipment({{ $equipment->id }})"
                                                   class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-brand-600 focus:ring-brand-500 shrink-0">
                                            <div class="w-9 h-9 rounded-lg bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 flex items-center justify-center shrink-0 p-0.5 overflow-hidden">
                                                @if ($equipment->image_url)
                                                    <img src="{{ $equipment->image_url }}" alt="{{ $equipment->full_name }}" class="w-full h-full object-contain" loading="lazy">
                                                @else
                                                    <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0 truncate">
                                                <div class="font-medium text-xs text-gray-900 dark:text-white truncate" title="{{ $equipment->full_name }}">{{ $equipment->full_name }}</div>
                                                <div class="text-gray-500 dark:text-gray-400 font-mono text-[10px]">{{ $equipment->internal_code }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Hidden Backend Form Inputs for Equipments --}}
                    <template x-for="eqId in selectedEquipments" :key="eqId">
                        <input type="hidden" name="equipments[]" :value="eqId">
                    </template>
                </div>

                <!-- Form Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4">
                    <a href="{{ route('operations.missions') }}" class="btn-secondary">
                        {{ __('Cancel') }}
                    </a>
                    <x-primary-button type="submit" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        <span>{{ __('Initialize Mission') }}</span>
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
