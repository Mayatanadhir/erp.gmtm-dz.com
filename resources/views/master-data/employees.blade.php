<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <x-tool-icon name="employees" class="w-11 h-11 sm:w-12 sm:h-12 shrink-0" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Employees') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Staff registry, job positions, and human resource profiles') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="info" size="md">
                    {{ $employees->total() }} {{ __('Employees') }}
                </x-badge>
                @can('create employees')
                    <x-primary-button
                        type="button"
                        x-data
                        @click="$dispatch('open-create-employee-modal')"
                        onclick="window.dispatchEvent(new CustomEvent('open-create-employee-modal'))"
                        class="flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>{{ __('New Employee') }}</span>
                    </x-primary-button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8"
        @open-create-employee-modal.window="openCreateModal()"
        x-init="window.addEventListener('open-create-employee-modal', () => { openCreateModal(); })"
        x-data="{
        showCreateModal: {{ ($errors->has('full_name') || $errors->has('registration_number') || $errors->has('position') || $errors->has('join_date') || $errors->has('salary') || $errors->has('daily_rate') || $errors->has('user_id') || $errors->has('photo')) && !old('_method') ? 'true' : 'false' }},
        showEditModal: {{ ($errors->has('full_name') || $errors->has('registration_number') || $errors->has('position') || $errors->has('join_date') || $errors->has('salary') || $errors->has('daily_rate') || $errors->has('user_id') || $errors->has('photo')) && old('_method') === 'PUT' ? 'true' : 'false' }},
        showDeleteModal: false,

        usersMap: @js($users->keyBy('id')->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'photo_url' => $u->profile_photo_url])),

        // Create state
        createUserId: '{{ old('user_id', '') }}',
        createPhotoPreview: null,
        createPhotoName: '',

        // Edit state
        editEmployeeId: {{ old('_method') === 'PUT' ? (int) old('edit_employee_id', 0) : 'null' }},
        editEmployeeFullName: '{{ old('_method') === 'PUT' ? addslashes((string) old('full_name', '')) : '' }}',
        editEmployeeRegistrationNumber: '{{ old('_method') === 'PUT' ? addslashes((string) old('registration_number', '')) : '' }}',
        editEmployeePosition: '{{ old('_method') === 'PUT' ? addslashes((string) old('position', '')) : '' }}',
        editEmployeeStatus: '{{ old('_method') === 'PUT' ? addslashes((string) old('status', 'active')) : 'active' }}',
        editEmployeeJoinDate: '{{ old('_method') === 'PUT' ? addslashes((string) old('join_date', '')) : '' }}',
        editEmployeeSalary: '{{ old('_method') === 'PUT' ? addslashes((string) old('salary', '0.00')) : '0.00' }}',
        editEmployeeDailyRate: '{{ old('_method') === 'PUT' ? addslashes((string) old('daily_rate', '0.00')) : '0.00' }}',
        editEmployeeAddress: '{{ old('_method') === 'PUT' ? addslashes((string) old('address', '')) : '' }}',
        editEmployeeUserId: '{{ old('_method') === 'PUT' ? addslashes((string) old('user_id', '')) : '' }}',
        editEmployeePhotoUrl: '',
        editEmployeePhotoPreview: null,
        editEmployeePhotoName: '',
        editEmployeeRemovePhoto: false,
        editEmployeeActionUrl: '{{ old('_method') === 'PUT' && old('edit_employee_id') ? route('master-data.employees.update', (int) old('edit_employee_id')) : '' }}',

        // Delete state
        deleteEmployeeName: '',
        deleteEmployeeActionUrl: '',

        openCreateModal() {
            this.createUserId = '';
            this.createPhotoPreview = null;
            this.createPhotoName = '';
            if (this.$refs.createFileInput) {
                this.$refs.createFileInput.value = '';
            }
            this.showCreateModal = true;
        },

        openEditModal(id, fullName, regNumber, position, status, joinDate, salary, dailyRate, address, userId, photoUrl, actionUrl) {
            this.editEmployeeId = id;
            this.editEmployeeFullName = fullName;
            this.editEmployeeRegistrationNumber = regNumber;
            this.editEmployeePosition = position;
            this.editEmployeeStatus = status || 'active';
            this.editEmployeeJoinDate = joinDate;
            this.editEmployeeSalary = salary;
            this.editEmployeeDailyRate = dailyRate;
            this.editEmployeeAddress = address || '';
            this.editEmployeeUserId = userId ? String(userId) : '';
            this.editEmployeePhotoUrl = photoUrl || '';
            this.editEmployeePhotoPreview = null;
            this.editEmployeePhotoName = '';
            this.editEmployeeRemovePhoto = false;
            this.editEmployeeActionUrl = actionUrl;
            if (this.$refs.editFileInput) {
                this.$refs.editFileInput.value = '';
            }
            this.showEditModal = true;
        },

        openDeleteModal(name, actionUrl) {
            this.deleteEmployeeName = name;
            this.deleteEmployeeActionUrl = actionUrl;
            this.showDeleteModal = true;
        },

        handleCreatePhotoChange(e) {
            const file = e.target.files[0];
            if (file) {
                this.createPhotoName = file.name;
                this.createPhotoPreview = URL.createObjectURL(file);
            }
        },

        handleEditPhotoChange(e) {
            const file = e.target.files[0];
            if (file) {
                this.editEmployeePhotoName = file.name;
                this.editEmployeePhotoPreview = URL.createObjectURL(file);
                this.editEmployeeRemovePhoto = false;
            }
        },

        getEffectiveCreatePhoto() {
            if (this.createPhotoPreview) return this.createPhotoPreview;
            if (this.createUserId && this.usersMap[this.createUserId]?.photo_url) {
                return this.usersMap[this.createUserId].photo_url;
            }
            return null;
        },

        getEffectiveEditPhoto() {
            if (this.editEmployeeRemovePhoto) return null;
            if (this.editEmployeePhotoPreview) return this.editEmployeePhotoPreview;
            if (this.editEmployeePhotoUrl) return this.editEmployeePhotoUrl;
            if (this.editEmployeeUserId && this.usersMap[this.editEmployeeUserId]?.photo_url) {
                return this.usersMap[this.editEmployeeUserId].photo_url;
            }
            return null;
        }
    }">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-master-data-tabs active="employees" />
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
                                    <x-tool-icon name="employees" class="w-6 h-6 shrink-0" />
                                    <span>{{ __('Employees') }}</span>
                                </h3>

                                <div class="flex flex-wrap items-center gap-3">
                                    <x-global-filter
                                        :action="route('master-data.employees')"
                                        :search="true"
                                        :search-placeholder="__('Search by name or code...')"
                                        :search-value="request('search')"
                                        :submit-text="__('Search')"
                                    >
                                        <select name="position" onchange="this.form.submit()" class="py-1.5 ps-2.5 pe-8 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600 shadow-sm">
                                            <option value="">{{ __('All Positions') }}</option>
                                            <optgroup label="{{ __('Management & Executive Leadership') }}">
                                                @foreach(\App\Enums\EmployeePosition::cases() as $pos)
                                                    @if($pos->isManagement())
                                                        <option value="{{ $pos->value }}" @selected(request('position') === $pos->value)>{{ $pos->label() }}</option>
                                                    @endif
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="{{ __('Engineering & Specialist Roles') }}">
                                                @foreach(\App\Enums\EmployeePosition::cases() as $pos)
                                                    @if($pos->isEngineer())
                                                        <option value="{{ $pos->value }}" @selected(request('position') === $pos->value)>{{ $pos->label() }}</option>
                                                    @endif
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="{{ __('Field Operations & Technicians') }}">
                                                @foreach(\App\Enums\EmployeePosition::cases() as $pos)
                                                    @if($pos->isTechnician())
                                                        <option value="{{ $pos->value }}" @selected(request('position') === $pos->value)>{{ $pos->label() }}</option>
                                                    @endif
                                                @endforeach
                                            </optgroup>
                                        </select>

                                        <select name="status" onchange="this.form.submit()" class="py-1.5 ps-2.5 pe-8 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600 shadow-sm">
                                            <option value="">{{ __('All Statuses') }}</option>
                                            @foreach(\App\Enums\EmployeeStatus::cases() as $st)
                                                <option value="{{ $st->value }}" @selected(request('status') === $st->value)>{{ $st->label() }}</option>
                                            @endforeach
                                        </select>
                                    </x-global-filter>

                                  
                                </div>
                            </div>
                        </x-slot:toolbar>

                        <x-slot:header>
                            <x-table.th>ID</x-table.th>
                            <x-table.th>{{ __('Employee') }}</x-table.th>
                            <x-table.th>{{ __('Position') }}</x-table.th>
                            <x-table.th>{{ __('Status') }}</x-table.th>
                            <x-table.th>{{ __('Compensation') }}</x-table.th>
                            <x-table.th>{{ __('Join Date') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                        </x-slot:header>

                        @forelse($employees as $employee)
                            <x-table.tr>
                                <x-table.td class="font-mono text-xs font-semibold text-gray-500 dark:text-gray-400">
                                    #{{ $employee->id }}
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex items-center gap-3">
                                        @if($employee->profile_photo_url)
                                            <img src="{{ $employee->profile_photo_url }}" alt="{{ $employee->full_name }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 dark:border-gray-700 shrink-0">
                                        @else
                                            <div class="w-9 h-9 rounded-full bg-brand-600/10 dark:bg-brand-600/20 border border-brand-500/20 flex items-center justify-center font-bold text-xs text-brand-700 dark:text-brand-400 shrink-0">
                                                {{ $employee->initials }}
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="font-semibold text-gray-900 dark:text-white truncate">
                                                {{ $employee->full_name }}
                                            </div>
                                            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                <span class="font-mono">{{ $employee->registration_number }}</span>
                                                @if($employee->user)
                                                    <span class="inline-flex items-center gap-1 text-[11px] text-brand-600 dark:text-brand-400 font-medium" title="{{ $employee->user->email }}">
                                                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                        <span>{{ $employee->user->name }}</span>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <x-badge :variant="$employee->position->badgeVariant()">
                                        {{ $employee->position->label() }}
                                    </x-badge>
                                </x-table.td>

                                <x-table.td>
                                    <x-badge :variant="$employee->status->badgeVariant()" :dot="true">
                                        {{ $employee->status->label() }}
                                    </x-badge>
                                </x-table.td>

                                <x-table.td>
                                    @can('view employee compensation')
                                        <div class="text-xs font-medium text-gray-900 dark:text-white font-mono">
                                            {{ number_format((float) $employee->salary, 2) }} DZD
                                        </div>
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 font-mono">
                                            {{ number_format((float) $employee->daily_rate, 2) }} DZD / {{ __('day') }}
                                        </div>
                                    @else
                                        <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 text-xs font-mono select-none" title="{{ __('Financial Compensation (Confidential)') }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            <span>•••••••• DZD</span>
                                        </div>
                                    @endcan
                                </x-table.td>

                                <x-table.td>
                                    <div class="text-xs font-mono text-gray-700 dark:text-gray-300">
                                        <x-date :value="$employee->join_date" />
                                    </div>
                                    @if($employee->address)
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 truncate max-w-[150px]">
                                            {{ $employee->address }}
                                        </div>
                                    @endif
                                </x-table.td>

                                <x-table.td class="text-end">
                                    <x-table.actions>
                                        @can('edit employees')
                                            <x-table.action-edit
                                                type="button"
                                                :title="__('Edit Employee')"
                                                @click="openEditModal({{ $employee->id }}, '{{ addslashes($employee->full_name) }}', '{{ addslashes($employee->registration_number) }}', '{{ $employee->position->value }}', '{{ $employee->status->value }}', '{{ $employee->join_date ? $employee->join_date->format('Y-m-d') : '' }}', '{{ (float) $employee->salary }}', '{{ (float) $employee->daily_rate }}', '{{ addslashes((string) $employee->address) }}', {{ $employee->user_id ? $employee->user_id : 'null' }}, '{{ addslashes((string) $employee->profile_photo_url) }}', '{{ route('master-data.employees.update', array_merge(['employee' => $employee->id], request()->query())) }}')"
                                            />
                                        @endcan

                                        @can('delete employees')
                                            <x-table.action-delete
                                                type="button"
                                                :title="__('Delete Employee')"
                                                @click="openDeleteModal('{{ addslashes($employee->full_name) }}', '{{ route('master-data.employees.destroy', array_merge(['employee' => $employee->id], request()->query())) }}')"
                                            />
                                        @endcan
                                    </x-table.actions>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty :colspan="7" :message="__('No records found.')" />
                        @endforelse

                        @if($employees->hasPages())
                            <x-slot:pagination>
                                {{ $employees->links() }}
                            </x-slot:pagination>
                        @endif
                    </x-table>
                </main>
            </div>
        </div>

        {{-- Create Employee Modal --}}
        <x-crud-modal.form
            show="showCreateModal"
            :action-url="route('master-data.employees.store', request()->query())"
            method="POST"
            :title="__('New Employee')"
            :description="__('Staff registry, job positions, and human resource profiles')"
            icon-color="brand"
            max-width="2xl"
            enctype="multipart/form-data"
            :submit-text="__('Create')"
        >
            <div class="space-y-4">
                {{-- Photo preview & selection banner --}}
                <div class="flex items-center gap-4 p-3 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                    <div class="relative w-14 h-14 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-600 shrink-0 border-2 border-brand-500/30 flex items-center justify-center">
                        <template x-if="getEffectiveCreatePhoto()">
                            <img :src="getEffectiveCreatePhoto()" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!getEffectiveCreatePhoto()">
                            <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </template>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-semibold text-gray-900 dark:text-white">
                            {{ __('Employee Profile Photo') }}
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            {{ __('Processed via WebP compression and CAS deduplication.') }}
                        </p>
                        <input
                            type="file"
                            name="photo"
                            x-ref="createFileInput"
                            @change="handleCreatePhotoChange"
                            accept="image/png,image/jpeg,image/jpg,image/webp"
                            class="mt-1.5 block w-full text-xs text-gray-500 dark:text-gray-400 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-brand-600/10 file:text-brand-700 dark:file:bg-brand-600/20 dark:file:text-brand-300 hover:file:bg-brand-600/20 cursor-pointer"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Full Name --}}
                    <div>
                        <x-input-label for="create_full_name" :value="__('Full Name')" />
                        <x-text-input id="create_full_name" name="full_name" type="text" class="mt-1 block w-full" :value="old('full_name')" required />
                        <x-input-error class="mt-1" :messages="$errors->get('full_name')" />
                    </div>

                    {{-- Registration Number --}}
                    <div>
                        <x-input-label for="create_registration_number" :value="__('Registration Number')" />
                        <x-text-input id="create_registration_number" name="registration_number" type="text" class="mt-1 block w-full font-mono" :value="old('registration_number')" placeholder="EMP-2026-001" required />
                        <x-input-error class="mt-1" :messages="$errors->get('registration_number')" />
                    </div>

                    {{-- Position --}}
                    <div>
                        <x-input-label for="create_position" :value="__('Position')" />
                        <select id="create_position" name="position" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-600 dark:focus:border-brand-500 focus:ring-brand-600 dark:focus:ring-brand-500 shadow-sm text-sm" required>
                            <optgroup label="{{ __('Management & Executive Leadership') }}">
                                @foreach(\App\Enums\EmployeePosition::cases() as $pos)
                                    @if($pos->isManagement())
                                        <option value="{{ $pos->value }}" @selected(old('position') === $pos->value)>{{ $pos->label() }}</option>
                                    @endif
                                @endforeach
                            </optgroup>
                            <optgroup label="{{ __('Engineering & Specialist Roles') }}">
                                @foreach(\App\Enums\EmployeePosition::cases() as $pos)
                                    @if($pos->isEngineer())
                                        <option value="{{ $pos->value }}" @selected(old('position') === $pos->value)>{{ $pos->label() }}</option>
                                    @endif
                                @endforeach
                            </optgroup>
                            <optgroup label="{{ __('Field Operations & Technicians') }}">
                                @foreach(\App\Enums\EmployeePosition::cases() as $pos)
                                    @if($pos->isTechnician())
                                        <option value="{{ $pos->value }}" @selected(old('position') === $pos->value)>{{ $pos->label() }}</option>
                                    @endif
                                @endforeach
                            </optgroup>
                        </select>
                        <x-input-error class="mt-1" :messages="$errors->get('position')" />
                    </div>

                    {{-- Status --}}
                    <div>
                        <x-input-label for="create_status" :value="__('Status')" />
                        <select id="create_status" name="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-600 dark:focus:border-brand-500 focus:ring-brand-600 dark:focus:ring-brand-500 shadow-sm text-sm">
                            @foreach(\App\Enums\EmployeeStatus::cases() as $st)
                                <option value="{{ $st->value }}" @selected(old('status', 'active') === $st->value)>{{ $st->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-1" :messages="$errors->get('status')" />
                    </div>

                    {{-- Join Date --}}
                    <div>
                        <x-input-label for="create_join_date" :value="__('Join Date')" />
                        <x-text-input id="create_join_date" name="join_date" type="date" class="mt-1 block w-full" :value="old('join_date', date('Y-m-d'))" required />
                        <x-input-error class="mt-1" :messages="$errors->get('join_date')" />
                    </div>

                    {{-- Linked User (Optional) --}}
                    <div>
                        <x-input-label for="create_user_id" :value="__('Linked System User (Optional)')" />
                        <select id="create_user_id" name="user_id" x-model="createUserId" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-600 dark:focus:border-brand-500 focus:ring-brand-600 dark:focus:ring-brand-500 shadow-sm text-sm">
                            <option value="">{{ __('None (Not linked to user account)') }}</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-1" :messages="$errors->get('user_id')" />
                    </div>
                </div>

                {{-- Address --}}
                <div>
                    <x-input-label for="create_address" :value="__('Address')" />
                    <x-text-input id="create_address" name="address" type="text" class="mt-1 block w-full" :value="old('address')" placeholder="{{ __('City, Region, or Residence Address') }}" />
                    <x-input-error class="mt-1" :messages="$errors->get('address')" />
                </div>

                {{-- Financial Quarantine Section --}}
                @can('view employee compensation')
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="text-xs font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ __('Financial Compensation (Confidential)') }}</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="create_salary" :value="__('Base Salary (DZD)')" />
                                <x-text-input id="create_salary" name="salary" type="number" step="0.01" min="0" class="mt-1 block w-full font-mono" :value="old('salary', '0.00')" />
                                <x-input-error class="mt-1" :messages="$errors->get('salary')" />
                            </div>
                            <div>
                                <x-input-label for="create_daily_rate" :value="__('Mission Daily Rate (DZD)')" />
                                <x-text-input id="create_daily_rate" name="daily_rate" type="number" step="0.01" min="0" class="mt-1 block w-full font-mono" :value="old('daily_rate', '0.00')" />
                                <x-input-error class="mt-1" :messages="$errors->get('daily_rate')" />
                            </div>
                        </div>
                    </div>
                @endcan
            </div>
        </x-crud-modal.form>

        {{-- Edit Employee Modal --}}
        <x-crud-modal.form
            show="showEditModal"
            alpine-action="editEmployeeActionUrl"
            method="PUT"
            :title="__('Edit Employee')"
            :description="__('Staff registry, job positions, and human resource profiles')"
            icon-color="amber"
            max-width="2xl"
            enctype="multipart/form-data"
            :submit-text="__('Save Changes')"
        >
            <x-slot:hidden>
                <input type="hidden" name="edit_employee_id" :value="editEmployeeId">
            </x-slot:hidden>

            <div class="space-y-4">
                {{-- Photo preview & modification banner --}}
                <div class="flex items-center gap-4 p-3 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                    <div class="relative w-14 h-14 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-600 shrink-0 border-2 border-amber-500/30 flex items-center justify-center">
                        <template x-if="getEffectiveEditPhoto()">
                            <img :src="getEffectiveEditPhoto()" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!getEffectiveEditPhoto()">
                            <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </template>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-900 dark:text-white">
                                {{ __('Employee Profile Photo') }}
                            </span>
                            <template x-if="getEffectiveEditPhoto() && !editEmployeeRemovePhoto">
                                <button type="button" @click="editEmployeeRemovePhoto = true; editEmployeePhotoPreview = null" class="text-[11px] text-rose-600 dark:text-rose-400 hover:underline">
                                    {{ __('Remove Photo') }}
                                </button>
                            </template>
                            <template x-if="editEmployeeRemovePhoto">
                                <span class="text-[11px] text-rose-500 font-semibold">
                                    {{ __('Photo will be removed') }}
                                </span>
                            </template>
                        </div>
                        <input
                            type="file"
                            name="photo"
                            x-ref="editFileInput"
                            @change="handleEditPhotoChange"
                            accept="image/png,image/jpeg,image/jpg,image/webp"
                            class="mt-1.5 block w-full text-xs text-gray-500 dark:text-gray-400 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-amber-500/10 file:text-amber-700 dark:file:bg-amber-500/20 dark:file:text-amber-300 hover:file:bg-amber-500/20 cursor-pointer"
                        />
                        <input type="hidden" name="remove_photo" :value="editEmployeeRemovePhoto ? '1' : '0'">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Full Name --}}
                    <div>
                        <x-input-label for="edit_full_name" :value="__('Full Name')" />
                        <x-text-input id="edit_full_name" name="full_name" type="text" class="mt-1 block w-full" x-model="editEmployeeFullName" required />
                        <x-input-error class="mt-1" :messages="$errors->get('full_name')" />
                    </div>

                    {{-- Registration Number --}}
                    <div>
                        <x-input-label for="edit_registration_number" :value="__('Registration Number')" />
                        <x-text-input id="edit_registration_number" name="registration_number" type="text" class="mt-1 block w-full font-mono" x-model="editEmployeeRegistrationNumber" required />
                        <x-input-error class="mt-1" :messages="$errors->get('registration_number')" />
                    </div>

                    {{-- Position --}}
                    <div>
                        <x-input-label for="edit_position" :value="__('Position')" />
                        <select id="edit_position" name="position" x-model="editEmployeePosition" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-600 dark:focus:border-brand-500 focus:ring-brand-600 dark:focus:ring-brand-500 shadow-sm text-sm" required>
                            <optgroup label="{{ __('Management & Executive Leadership') }}">
                                @foreach(\App\Enums\EmployeePosition::cases() as $pos)
                                    @if($pos->isManagement())
                                        <option value="{{ $pos->value }}">{{ $pos->label() }}</option>
                                    @endif
                                @endforeach
                            </optgroup>
                            <optgroup label="{{ __('Engineering & Specialist Roles') }}">
                                @foreach(\App\Enums\EmployeePosition::cases() as $pos)
                                    @if($pos->isEngineer())
                                        <option value="{{ $pos->value }}">{{ $pos->label() }}</option>
                                    @endif
                                @endforeach
                            </optgroup>
                            <optgroup label="{{ __('Field Operations & Technicians') }}">
                                @foreach(\App\Enums\EmployeePosition::cases() as $pos)
                                    @if($pos->isTechnician())
                                        <option value="{{ $pos->value }}">{{ $pos->label() }}</option>
                                    @endif
                                @endforeach
                            </optgroup>
                        </select>
                        <x-input-error class="mt-1" :messages="$errors->get('position')" />
                    </div>

                    {{-- Status --}}
                    <div>
                        <x-input-label for="edit_status" :value="__('Status')" />
                        <select id="edit_status" name="status" x-model="editEmployeeStatus" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-amber-500 dark:focus:border-amber-600 focus:ring-amber-500 dark:focus:ring-amber-600 shadow-sm text-sm">
                            @foreach(\App\Enums\EmployeeStatus::cases() as $st)
                                <option value="{{ $st->value }}">{{ $st->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-1" :messages="$errors->get('status')" />
                    </div>

                    {{-- Join Date --}}
                    <div>
                        <x-input-label for="edit_join_date" :value="__('Join Date')" />
                        <x-text-input id="edit_join_date" name="join_date" type="date" class="mt-1 block w-full" x-model="editEmployeeJoinDate" required />
                        <x-input-error class="mt-1" :messages="$errors->get('join_date')" />
                    </div>

                    {{-- Linked User (Optional) --}}
                    <div>
                        <x-input-label for="edit_user_id" :value="__('Linked System User (Optional)')" />
                        <select id="edit_user_id" name="user_id" x-model="editEmployeeUserId" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-amber-500 dark:focus:border-amber-600 focus:ring-amber-500 dark:focus:ring-amber-600 shadow-sm text-sm">
                            <option value="">{{ __('None (Not linked to user account)') }}</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-1" :messages="$errors->get('user_id')" />
                    </div>
                </div>

                {{-- Address --}}
                <div>
                    <x-input-label for="edit_address" :value="__('Address')" />
                    <x-text-input id="edit_address" name="address" type="text" class="mt-1 block w-full" x-model="editEmployeeAddress" placeholder="{{ __('City, Region, or Residence Address') }}" />
                    <x-input-error class="mt-1" :messages="$errors->get('address')" />
                </div>

                {{-- Financial Quarantine Section --}}
                @can('view employee compensation')
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="text-xs font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ __('Financial Compensation (Confidential)') }}</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="edit_salary" :value="__('Base Salary (DZD)')" />
                                <x-text-input id="edit_salary" name="salary" type="number" step="0.01" min="0" class="mt-1 block w-full font-mono" x-model="editEmployeeSalary" />
                                <x-input-error class="mt-1" :messages="$errors->get('salary')" />
                            </div>
                            <div>
                                <x-input-label for="edit_daily_rate" :value="__('Mission Daily Rate (DZD)')" />
                                <x-text-input id="edit_daily_rate" name="daily_rate" type="number" step="0.01" min="0" class="mt-1 block w-full font-mono" x-model="editEmployeeDailyRate" />
                                <x-input-error class="mt-1" :messages="$errors->get('daily_rate')" />
                            </div>
                        </div>
                    </div>
                @endcan
            </div>
        </x-crud-modal.form>

        {{-- Delete Employee Confirmation Modal --}}
        <x-crud-modal.delete
            show="showDeleteModal"
            action-url="deleteEmployeeActionUrl"
            item-name="deleteEmployeeName"
            :title="__('Delete Employee')"
        />
    </div>
</x-app-layout>
