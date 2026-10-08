<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                    {{ __('Roles & Permissions') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('Inspect access control roles, assigned permission sets, and system boundaries') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="info" size="md">
                    {{ $roles->count() }} {{ __('Roles') }}
                </x-badge>
                <x-badge variant="success" size="md">
                    {{ $permissions->total() }} {{ __('Permissions') }}
                </x-badge>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        showRoleModal:            {{ ($errors->any() && old('form_type') === 'role') ? 'true' : 'false' }},
        showPermissionModal:      {{ ($errors->any() && old('form_type') === 'permission') ? 'true' : 'false' }},
        showEditRoleModal:        {{ ($errors->any() && old('form_type') === 'edit_role') ? 'true' : 'false' }},
        showEditPermissionModal:  {{ ($errors->any() && old('form_type') === 'edit_permission') ? 'true' : 'false' }},
        showDeleteRoleModal:       false,
        showDeletePermissionModal: false,
        showPermissionsCatalog:   {{ ($errors->any() && in_array(old('form_type'), ['permission', 'edit_permission'])) ? 'true' : 'false' }},

        createRolePermissions:    {{ (old('form_type') === 'role' && is_array(old('permissions'))) ? json_encode(old('permissions')) : '[]' }},

        editRoleId:               {{ old('form_type') === 'edit_role' ? (int) old('edit_role_id', 0) : 'null' }},
        editRoleName:             '{{ old('form_type') === 'edit_role' ? addslashes((string) old('name', '')) : '' }}',
        editRolePermissions:      {{ old('form_type') === 'edit_role' && is_array(old('permissions')) ? json_encode(old('permissions')) : '[]' }},
        editRoleActionUrl:        '{{ old('form_type') === 'edit_role' && old('edit_role_id') ? route('system-tables.roles.update', (int) old('edit_role_id')) : '' }}',

        editPermissionId:         {{ old('form_type') === 'edit_permission' ? (int) old('edit_permission_id', 0) : 'null' }},
        editPermissionName:       '{{ old('form_type') === 'edit_permission' ? addslashes((string) old('name', '')) : '' }}',
        editPermissionActionUrl:  '{{ old('form_type') === 'edit_permission' && old('edit_permission_id') ? route('system-tables.permissions.update', (int) old('edit_permission_id')) : '' }}',

        deleteRoleName:             '',
        deleteRoleActionUrl:        '',
        deletePermissionName:       '',
        deletePermissionActionUrl:  '',

        allPermissionNames:       {{ json_encode($permissionMatrix['all_names']) }},

        openEditRoleModal(id, name, permissions, actionUrl) {
            this.editRoleId = id;
            this.editRoleName = name;
            this.editRolePermissions = Array.isArray(permissions) ? [...permissions] : [];
            this.editRoleActionUrl = actionUrl;
            this.showEditRoleModal = true;
        },

        openEditPermissionModal(id, name, actionUrl) {
            this.editPermissionId = id;
            this.editPermissionName = name;
            this.editPermissionActionUrl = actionUrl;
            this.showEditPermissionModal = true;
        },

        openDeleteRoleModal(name, actionUrl) {
            this.deleteRoleName = name;
            this.deleteRoleActionUrl = actionUrl;
            this.showDeleteRoleModal = true;
        },

        openDeletePermissionModal(name, actionUrl) {
            this.deletePermissionName = name;
            this.deletePermissionActionUrl = actionUrl;
            this.showDeletePermissionModal = true;
        },

        toggleCreateEntityAll(actions) {
            const allSelected = actions.every(p => this.createRolePermissions.includes(p));
            if (allSelected) {
                this.createRolePermissions = this.createRolePermissions.filter(p => !actions.includes(p));
            } else {
                actions.forEach(p => {
                    if (!this.createRolePermissions.includes(p)) {
                        this.createRolePermissions.push(p);
                    }
                });
            }
        },

        toggleEditEntityAll(actions) {
            const allSelected = actions.every(p => this.editRolePermissions.includes(p));
            if (allSelected) {
                this.editRolePermissions = this.editRolePermissions.filter(p => !actions.includes(p));
            } else {
                actions.forEach(p => {
                    if (!this.editRolePermissions.includes(p)) {
                        this.editRolePermissions.push(p);
                    }
                });
            }
        },

        selectAllCreate() {
            this.createRolePermissions = [...this.allPermissionNames];
        },

        deselectAllCreate() {
            this.createRolePermissions = [];
        },

        selectAllEdit() {
            this.editRolePermissions = [...this.allPermissionNames];
        },

        deselectAllEdit() {
            this.editRolePermissions = [];
        }
    }">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-system-tabs active="roles" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-8">

                    @if (session('status'))
                        <x-alert variant="success">
                            {{ session('status') }}
                        </x-alert>
                    @endif

                    <!-- ============================================================ -->
                    <!-- Permissions Catalog Section (Collapsible & On-Demand)        -->
                    <!-- ============================================================ -->
                    <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 p-5 shadow-sm space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 flex items-center justify-center shrink-0 me-3.5">
                                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2.5 flex-wrap">
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                             {{ __('Permissions Catalog') }}
                                        </h3>
                                        <x-badge variant="success" class="ms-1.5">
                                             {{ __('Static Registry') }}
                                        </x-badge>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ __('Managed automatically by the System Permissions Registry. Hidden by default for clean administration.') }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 shrink-0">
                                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                    {{ $permissions->total() }} {{ __('Total permissions') }}
                                </span>
                                <x-secondary-button
                                    type="button"
                                    @click="showPermissionsCatalog = !showPermissionsCatalog"
                                    class="py-1.5 px-3 text-xs rounded-lg flex items-center gap-1.5"
                                >
                                    <svg class="w-4 h-4 transition-transform duration-200 me-1" :class="{ 'rotate-180': showPermissionsCatalog }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                    <span x-text="showPermissionsCatalog ? '{{ __('Hide Permissions Catalog') }}' : '{{ __('Manage Permissions Catalog') }}'"></span>
                                </x-secondary-button>
                                <div x-show="showPermissionsCatalog" x-cloak>
                                    <x-primary-button type="button" @click="showPermissionModal = true" class="py-1.5 px-3 text-xs rounded-lg flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        <span>{{ __('New Permission') }}</span>
                                    </x-primary-button>
                                </div>
                            </div>
                        </div>

                        <!-- Collapsible Table Content -->
                        <div x-show="showPermissionsCatalog" x-cloak class="pt-3 border-t border-gray-100 dark:border-gray-700/60">
                            <x-table>
                                <x-slot:header>
                                    <x-table.th>ID</x-table.th>
                                    <x-table.th>{{ __('Name') }}</x-table.th>
                                    <x-table.th>{{ __('Guard') }}</x-table.th>
                                    <x-table.th>{{ __('Roles using it') }}</x-table.th>
                                    <x-table.th>{{ __('Date') }}</x-table.th>
                                    <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                                </x-slot:header>

                                @forelse($permissions as $permission)
                                    <x-table.tr>
                                        <x-table.td class="font-mono font-bold text-gray-900 dark:text-white">#{{ $permission->id }}</x-table.td>
                                        <x-table.td class="font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                            {{ $permission->name }}
                                        </x-table.td>
                                        <x-table.td>
                                            <span class="px-2 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                                {{ $permission->guard_name }}
                                            </span>
                                        </x-table.td>
                                        <x-table.td class="font-bold text-gray-800 dark:text-gray-200">
                                            {{ $permission->roles_count }}
                                        </x-table.td>
                                        <x-table.td class="whitespace-nowrap">
                                            <x-date :value="$permission->created_at" format="datetime" />
                                        </x-table.td>
                                        <x-table.td class="whitespace-nowrap text-end">
                                            <x-table.actions class="justify-end">
                                                <x-table.action-edit
                                                    :title="__('Edit Permission')"
                                                    @click="openEditPermissionModal({{ $permission->id }}, '{{ addslashes($permission->name) }}', '{{ route('system-tables.permissions.update', $permission) }}')"
                                                />
                                                <x-table.action-delete
                                                    :title="__('Delete Permission')"
                                                    @click="openDeletePermissionModal('{{ addslashes($permission->name) }}', '{{ route('system-tables.permissions.destroy', $permission) }}')"
                                                />
                                            </x-table.actions>
                                        </x-table.td>
                                    </x-table.tr>
                                @empty
                                    <x-table.empty colspan="6" />
                                @endforelse

                                @if($permissions->hasPages())
                                    <x-slot:pagination>
                                        {{ $permissions->links() }}
                                    </x-slot:pagination>
                                @endif
                            </x-table>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- Configured Roles Section — Unified Table Component          -->
                    <!-- ============================================================ -->
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-500 shrink-0 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                    <span>{{ __('Configured Roles') }}</span>
                                    <x-badge variant="success" class="ms-1.5">
                                        {{ __('Static Registry') }}
                                    </x-badge>
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ __('Role definitions, functional scopes, and granted privileges') }}
                                    <span class="text-gray-400 dark:text-gray-500">&bull;</span>
                                    <span class="font-mono text-[11px] text-gray-400">({{ __('Static Registry') }}: <code>config/permissions.php</code>)</span>
                                </p>
                            </div>
                            <x-primary-button type="button" @click="showRoleModal = true" class="py-1.5 px-3 text-xs rounded-lg flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>{{ __('New Role') }}</span>
                            </x-primary-button>
                        </div>

                        <x-table>
                            <x-slot:header>
                                <x-table.th class="w-16">ID</x-table.th>
                                <x-table.th>{{ __('Role & Function') }}</x-table.th>
                                <x-table.th>{{ __('Functional Scope') }}</x-table.th>
                                <x-table.th class="text-center">{{ __('Assigned Users') }}</x-table.th>
                                <x-table.th>{{ __('Privileges Scope') }}</x-table.th>
                                <x-table.th>{{ __('Role Status') }}</x-table.th>
                                <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                            </x-slot:header>

                            @forelse($roles as $role)
                                @php
                                    $isSuper = in_array($role->name, $superRoles ?? ['Super-Admin'], true);
                                    $isDefault = $role->name === ($defaultRole ?? 'User');
                                @endphp
                                <x-table.tr>
                                    {{-- ID --}}
                                    <x-table.td class="font-mono font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                        #{{ $role->id }}
                                    </x-table.td>

                                    {{-- Role & Function --}}
                                    <x-table.td>
                                        <div class="flex flex-col gap-1">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white">
                                                {{ $discoveryService->getRoleFunctionalTitle($role->name) }}
                                            </span>
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-mono text-xs text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700/60 px-1.5 py-0.5 rounded">
                                                    {{ $role->name }}
                                                </span>
                                                <span class="text-[11px] text-gray-400 font-mono">
                                                    ({{ $role->guard_name }})
                                                </span>
                                            </div>
                                        </div>
                                    </x-table.td>

                                    {{-- Functional Scope --}}
                                    <x-table.td class="max-w-xs">
                                        <p class="text-xs text-gray-600 dark:text-gray-300" title="{{ $discoveryService->getRoleFunctionalScope($role->name) }}">
                                            {{ $discoveryService->getRoleFunctionalScope($role->name) }}
                                        </p>
                                    </x-table.td>

                                    {{-- Assigned Users --}}
                                    <x-table.td class="text-center whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 font-bold text-gray-800 dark:text-gray-200">
                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            <span>{{ $role->users_count }}</span>
                                        </span>
                                    </x-table.td>

                                    {{-- Privileges Scope --}}
                                    <x-table.td>
                                        @if($isSuper)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                {{ __('All System Privileges') }} ({{ $role->permissions->count() }})
                                            </span>
                                        @else
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border border-indigo-500/20">
                                                    {{ $role->permissions->count() }} {{ __('Permissions') }}
                                                </span>
                                                @foreach($role->permissions->take(3) as $perm)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-mono bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300">
                                                        {{ $perm->name }}
                                                    </span>
                                                @endforeach
                                                @if($role->permissions->count() > 3)
                                                    <span class="text-[11px] text-gray-400 font-medium">+{{ $role->permissions->count() - 3 }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </x-table.td>

                                    {{-- Role Status --}}
                                    <x-table.td class="whitespace-nowrap">
                                        @if($isSuper)
                                            <x-badge variant="warning">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                                {{ __('Protected System Role') }}
                                            </x-badge>
                                        @elseif($isDefault)
                                            <x-badge variant="info">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                {{ __('Default Role') }}
                                            </x-badge>
                                        @else
                                            <x-badge variant="neutral">
                                                {{ __('Custom Role') }}
                                            </x-badge>
                                        @endif
                                    </x-table.td>

                                    {{-- Actions --}}
                                    <x-table.td class="whitespace-nowrap text-end">
                                        <x-table.actions class="justify-end">
                                            @if($isSuper)
                                                <span class="text-xs text-gray-400 italic">
                                                    {{ __('Immutable') }}
                                                </span>
                                            @elseif($isDefault)
                                                <x-table.action-edit
                                                    :title="__('Edit Role')"
                                                    @click="openEditRoleModal({{ $role->id }}, '{{ addslashes($role->name) }}', {{ json_encode($role->permissions->pluck('name')->values()->all()) }}, '{{ route('system-tables.roles.update', $role) }}')"
                                                />
                                            @else
                                                <x-table.action-edit
                                                    :title="__('Edit Role')"
                                                    @click="openEditRoleModal({{ $role->id }}, '{{ addslashes($role->name) }}', {{ json_encode($role->permissions->pluck('name')->values()->all()) }}, '{{ route('system-tables.roles.update', $role) }}')"
                                                />
                                                <x-table.action-delete
                                                    :title="__('Delete Role')"
                                                    @click="openDeleteRoleModal('{{ addslashes($role->name) }}', '{{ route('system-tables.roles.destroy', $role) }}')"
                                                />
                                            @endif
                                        </x-table.actions>
                                    </x-table.td>
                                </x-table.tr>
                            @empty
                                <x-table.empty colspan="7" />
                            @endforelse
                        </x-table>
                    </div>

                    <!-- ============================================================ -->
                    <!-- Create Role Modal — x-crud-modal.form                        -->
                    <!-- ============================================================ -->
                    <x-crud-modal.form
                        show="showRoleModal"
                        :action-url="route('system-tables.roles.store')"
                        method="POST"
                        :title="__('Create New System Role')"
                        :description="__('Define a new access role and configure its granted permissions.')"
                        icon-color="indigo"
                        :submit-text="__('Create Role')"
                        max-width="3xl"
                    >
                        <x-slot:hidden>
                            <input type="hidden" name="form_type" value="role">
                        </x-slot:hidden>

                        <!-- Role Name -->
                        <div>
                            <x-input-label for="new_role_name" :value="__('Role Name')" />
                            <x-text-input
                                id="new_role_name"
                                name="name"
                                type="text"
                                class="mt-1 block w-full text-sm"
                                :value="old('form_type') === 'role' ? old('name') : ''"
                                required
                                autofocus
                                placeholder="{{ __('e.g. Editor, Supervisor') }}"
                            />
                            <x-input-error :messages="old('form_type') === 'role' ? $errors->get('name') : []" class="mt-1 text-xs" />
                        </div>

                        <!-- Dynamic Permission Matrix by Entity -->
                        <div class="space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div>
                                    <x-input-label :value="__('Permission Matrix by Entity')" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('Assign fine-grained CRUD privileges grouped by system module.') }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <button type="button"
                                            @click="selectAllCreate()"
                                            class="font-semibold text-brand-700 hover:text-brand-800 dark:text-brand-400 hover:underline">
                                        {{ __('Select All') }}
                                    </button>
                                    <span class="text-gray-300 dark:text-gray-600">&bull;</span>
                                    <button type="button"
                                            @click="deselectAllCreate()"
                                            class="font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 hover:underline">
                                        {{ __('Deselect All') }}
                                    </button>
                                </div>
                            </div>

                            <!-- Matrix Table Container -->
                            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm max-h-80 overflow-y-auto">
                                <table class="w-full text-xs text-start border-collapse">
                                    <thead class="sticky top-0 bg-gray-50 dark:bg-gray-900/90 backdrop-blur-sm border-b border-gray-200 dark:border-gray-700 z-10">
                                        <tr>
                                            <th class="py-2.5 px-3 font-semibold text-gray-700 dark:text-gray-200 text-start">{{ __('Module / Entity') }}</th>
                                            <th class="py-2.5 px-2 font-semibold text-center text-emerald-600 dark:text-emerald-400 w-16">{{ __('View') }}</th>
                                            <th class="py-2.5 px-2 font-semibold text-center text-brand-700 dark:text-brand-400 w-16">{{ __('Create') }}</th>
                                            <th class="py-2.5 px-2 font-semibold text-center text-amber-600 dark:text-amber-400 w-16">{{ __('Edit') }}</th>
                                            <th class="py-2.5 px-2 font-semibold text-center text-rose-600 dark:text-rose-400 w-16">{{ __('Delete') }}</th>
                                            <th class="py-2.5 px-3 font-semibold text-end text-gray-500 w-28">{{ __('Toggle Row') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                        @forelse($permissionMatrix['modules'] as $moduleKey => $module)
                                            <!-- Category Group Header -->
                                            <tr class="bg-gray-100/95 dark:bg-gray-800/95 border-y border-gray-200 dark:border-gray-700 sticky top-9 z-[5] backdrop-blur-sm">
                                                <td colspan="5" class="py-2 px-3">
                                                    <div class="flex items-center gap-2">
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider
                                                            @if($module['color'] === 'rose') bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/25
                                                            @elseif($module['color'] === 'indigo') bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 border border-indigo-500/25
                                                            @elseif($module['color'] === 'amber') bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/25
                                                            @elseif($module['color'] === 'blue') bg-blue-500/15 text-blue-700 dark:text-blue-300 border border-blue-500/25
                                                            @elseif($module['color'] === 'emerald') bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/25
                                                            @elseif($module['color'] === 'teal') bg-teal-500/15 text-teal-700 dark:text-teal-300 border border-teal-500/25
                                                            @else bg-gray-500/15 text-gray-700 dark:text-gray-300 border border-gray-500/25
                                                            @endif">
                                                            @if($module['color'] === 'rose')
                                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                            @elseif($module['color'] === 'indigo')
                                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                                            @elseif($module['color'] === 'amber')
                                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                            @elseif($module['color'] === 'blue')
                                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                                            @elseif($module['color'] === 'emerald')
                                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                                                            @elseif($module['color'] === 'teal')
                                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                            @endif
                                                            <span>{{ __($module['name']) }}</span>
                                                        </span>
                                                        <span class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">
                                                            ({{ count($module['entities']) }} {{ __('entities') }})
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="py-2 px-3 text-end">
                                                    <button type="button"
                                                            @click="toggleCreateEntityAll({{ json_encode($module['all_permissions']) }})"
                                                            class="text-[11px] font-semibold text-brand-700 hover:text-brand-800 dark:text-brand-400 hover:underline whitespace-nowrap">
                                                        {{ __('Toggle Category') }}
                                                    </button>
                                                </td>
                                            </tr>
                                            {{-- Module-level access permission row (e.g. "view metrology") --}}
                                            @if(!empty($module['view_permission']))
                                                <tr class="bg-gray-50/60 dark:bg-gray-700/20">
                                                    <td class="py-2 px-3 ps-5">
                                                        <label class="flex items-center gap-2 cursor-pointer group">
                                                            <input type="checkbox"
                                                                   name="permissions[]"
                                                                   value="{{ $module['view_permission'] }}"
                                                                   x-model="createRolePermissions"
                                                                   class="rounded border-gray-300 dark:border-gray-600 text-brand-600 focus:ring-brand-600 cursor-pointer w-4 h-4"
                                                                   title="{{ $module['view_permission'] }}">
                                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold
                                                                @if($module['color'] === 'rose') text-rose-700 dark:text-rose-300
                                                                @elseif($module['color'] === 'indigo') text-indigo-700 dark:text-indigo-300
                                                                @elseif($module['color'] === 'amber') text-amber-700 dark:text-amber-300
                                                                @elseif($module['color'] === 'blue') text-blue-700 dark:text-blue-300
                                                                @elseif($module['color'] === 'emerald') text-emerald-700 dark:text-emerald-300
                                                                @elseif($module['color'] === 'teal') text-teal-700 dark:text-teal-300
                                                                @else text-gray-600 dark:text-gray-400
                                                                @endif">
                                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                                                {{ __($module['view_permission']) }}
                                                            </span>
                                                        </label>
                                                    </td>
                                                    <td colspan="4" class="py-2 px-3">
                                                        <span class="text-[10px] text-gray-400 dark:text-gray-500 italic">{{ __('Module access permission') }}</span>
                                                    </td>
                                                </tr>
                                            @endif
                                            @foreach($module['entities'] as $entity => $entityData)
                                                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors">
                                                    <td class="py-2.5 px-3 ps-6 font-medium text-gray-900 dark:text-white">
                                                        <div class="flex items-center gap-2">
                                                            <span class="w-1.5 h-1.5 rounded-full shrink-0
                                                                @if($module['color'] === 'rose') bg-rose-500
                                                                @elseif($module['color'] === 'indigo') bg-indigo-500
                                                                @elseif($module['color'] === 'amber') bg-amber-500
                                                                @elseif($module['color'] === 'blue') bg-blue-500
                                                                @elseif($module['color'] === 'emerald') bg-emerald-500
                                                                @elseif($module['color'] === 'teal') bg-teal-500
                                                                @else bg-brand-600
                                                                @endif"></span>
                                                            <span class="font-mono capitalize font-bold text-xs">{{ __($entityData['display_name']) }}</span>
                                                        </div>
                                                    </td>
                                                    @foreach(['view', 'create', 'edit', 'delete'] as $act)
                                                        <td class="py-2.5 px-2 text-center align-middle">
                                                            @if(isset($entityData['actions'][$act]))
                                                                <input type="checkbox"
                                                                       name="permissions[]"
                                                                       value="{{ $entityData['actions'][$act] }}"
                                                                       x-model="createRolePermissions"
                                                                       class="rounded border-gray-300 dark:border-gray-600 text-brand-600 focus:ring-brand-600 cursor-pointer w-4 h-4"
                                                                       title="{{ $entityData['actions'][$act] }}">
                                                            @else
                                                                <span class="text-gray-300 dark:text-gray-600 text-xs">&mdash;</span>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                    <td class="py-2.5 px-3 text-end align-middle">
                                                        <button type="button"
                                                                @click="toggleCreateEntityAll({{ json_encode(array_values($entityData['actions'])) }})"
                                                                class="text-[11px] font-medium text-brand-700 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 px-2.5 py-1 rounded border border-indigo-200/60 dark:border-indigo-800/60 hover:bg-indigo-500/10 dark:hover:bg-indigo-500/20 transition-colors whitespace-nowrap">
                                                            {{ __('Toggle Row') }}
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @empty
                                            <tr>
                                                <td colspan="6" class="py-4 text-center text-gray-400 italic">
                                                    {{ __('No standard entities found.') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if(!empty($permissionMatrix['custom']))
                                <!-- Additional System Permissions -->
                                <div class="pt-2">
                                    <x-input-label :value="__('Additional System Permissions')" />
                                    <div class="mt-1.5 flex flex-wrap gap-2">
                                        @foreach($permissionMatrix['custom'] as $customPerm)
                                            <label class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 text-xs cursor-pointer hover:bg-white dark:hover:bg-gray-800 transition-colors">
                                                <input type="checkbox"
                                                       name="permissions[]"
                                                       value="{{ $customPerm }}"
                                                       x-model="createRolePermissions"
                                                       class="rounded border-gray-300 dark:border-gray-600 text-brand-600 focus:ring-brand-600 w-4 h-4">
                                                <span class="font-mono text-gray-700 dark:text-gray-300">{{ $customPerm }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <x-input-error :messages="old('form_type') === 'role' ? $errors->get('permissions') : []" class="mt-1 text-xs" />
                        </div>
                    </x-crud-modal.form>

                    <!-- ============================================================ -->
                    <!-- Create Permission Modal — x-crud-modal.form                  -->
                    <!-- ============================================================ -->
                    <x-crud-modal.form
                        show="showPermissionModal"
                        :action-url="route('system-tables.permissions.store')"
                        method="POST"
                        :title="__('Create New Permission')"
                        :description="__('Register a new permission into the system access catalog.')"
                        icon-color="emerald"
                        :submit-text="__('Create Permission')"
                    >
                        <x-slot:hidden>
                            <input type="hidden" name="form_type" value="permission">
                        </x-slot:hidden>

                        <!-- Permission Name -->
                        <div>
                            <x-input-label for="new_permission_name" :value="__('Permission Name')" />
                            <x-text-input
                                id="new_permission_name"
                                name="name"
                                type="text"
                                class="mt-1 block w-full text-sm"
                                :value="old('form_type') === 'permission' ? old('name') : ''"
                                required
                                autofocus
                                placeholder="{{ __('e.g. view-reports, manage-billing') }}"
                            />
                            <x-input-error :messages="old('form_type') === 'permission' ? $errors->get('name') : []" class="mt-1 text-xs" />
                        </div>
                    </x-crud-modal.form>

                    <!-- ============================================================ -->
                    <!-- Edit Role Modal — x-crud-modal.form                          -->
                    <!-- ============================================================ -->
                    <x-crud-modal.form
                        show="showEditRoleModal"
                        alpine-action="editRoleActionUrl"
                        method="PUT"
                        :title="__('Edit System Role')"
                        :description="__('Update role name and reconfigure its granted permissions.')"
                        icon-color="amber"
                        :submit-text="__('Save Changes')"
                        max-width="3xl"
                    >
                        <x-slot:hidden>
                            <input type="hidden" name="form_type" value="edit_role">
                            <input type="hidden" name="edit_role_id" :value="editRoleId">
                        </x-slot:hidden>

                        <!-- Role Name -->
                        <div>
                            <x-input-label for="edit_role_name" :value="__('Role Name')" />
                            <x-text-input
                                id="edit_role_name"
                                name="name"
                                type="text"
                                class="mt-1 block w-full text-sm"
                                x-model="editRoleName"
                                x-bind:readonly="editRoleName === '{{ $defaultRole ?? 'User' }}'"
                                x-bind:class="editRoleName === '{{ $defaultRole ?? 'User' }}' ? 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed opacity-80' : ''"
                                required
                                autofocus
                            />
                            <p x-show="editRoleName === '{{ $defaultRole ?? 'User' }}'" class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                {{ __('The default role name is protected and cannot be changed.') }}
                            </p>
                            <x-input-error :messages="old('form_type') === 'edit_role' ? $errors->get('name') : []" class="mt-1 text-xs" />
                        </div>

                        <!-- Dynamic Permission Matrix by Entity -->
                        <div class="space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div>
                                    <x-input-label :value="__('Permission Matrix by Entity')" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('Assign fine-grained CRUD privileges grouped by system module.') }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <button type="button"
                                            @click="selectAllEdit()"
                                            class="font-semibold text-brand-700 hover:text-brand-800 dark:text-brand-400 hover:underline">
                                        {{ __('Select All') }}
                                    </button>
                                    <span class="text-gray-300 dark:text-gray-600">&bull;</span>
                                    <button type="button"
                                            @click="deselectAllEdit()"
                                            class="font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 hover:underline">
                                        {{ __('Deselect All') }}
                                    </button>
                                </div>
                            </div>

                            <!-- Matrix Table Container -->
                            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm max-h-80 overflow-y-auto">
                                <table class="w-full text-xs text-start border-collapse">
                                    <thead class="sticky top-0 bg-gray-50 dark:bg-gray-900/90 backdrop-blur-sm border-b border-gray-200 dark:border-gray-700 z-10">
                                        <tr>
                                            <th class="py-2.5 px-3 font-semibold text-gray-700 dark:text-gray-200 text-start">{{ __('Module / Entity') }}</th>
                                            <th class="py-2.5 px-2 font-semibold text-center text-emerald-600 dark:text-emerald-400 w-16">{{ __('View') }}</th>
                                            <th class="py-2.5 px-2 font-semibold text-center text-brand-700 dark:text-brand-400 w-16">{{ __('Create') }}</th>
                                            <th class="py-2.5 px-2 font-semibold text-center text-amber-600 dark:text-amber-400 w-16">{{ __('Edit') }}</th>
                                            <th class="py-2.5 px-2 font-semibold text-center text-rose-600 dark:text-rose-400 w-16">{{ __('Delete') }}</th>
                                            <th class="py-2.5 px-3 font-semibold text-end text-gray-500 w-28">{{ __('Toggle Row') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                        @forelse($permissionMatrix['modules'] as $moduleKey => $module)
                                            <!-- Category Group Header -->
                                            <tr class="bg-gray-100/95 dark:bg-gray-800/95 border-y border-gray-200 dark:border-gray-700 sticky top-9 z-[5] backdrop-blur-sm">
                                                <td colspan="5" class="py-2 px-3">
                                                    <div class="flex items-center gap-2">
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider
                                                            @if($module['color'] === 'rose') bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/25
                                                            @elseif($module['color'] === 'indigo') bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 border border-indigo-500/25
                                                            @elseif($module['color'] === 'amber') bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/25
                                                            @elseif($module['color'] === 'blue') bg-blue-500/15 text-blue-700 dark:text-blue-300 border border-blue-500/25
                                                            @elseif($module['color'] === 'emerald') bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/25
                                                            @elseif($module['color'] === 'teal') bg-teal-500/15 text-teal-700 dark:text-teal-300 border border-teal-500/25
                                                            @else bg-gray-500/15 text-gray-700 dark:text-gray-300 border border-gray-500/25
                                                            @endif">
                                                            @if($module['color'] === 'rose')
                                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                            @elseif($module['color'] === 'indigo')
                                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                                            @elseif($module['color'] === 'amber')
                                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                            @elseif($module['color'] === 'blue')
                                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                                            @elseif($module['color'] === 'emerald')
                                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                                                            @elseif($module['color'] === 'teal')
                                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                            @endif
                                                            <span>{{ __($module['name']) }}</span>
                                                        </span>
                                                        <span class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">
                                                            ({{ count($module['entities']) }} {{ __('entities') }})
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="py-2 px-3 text-end">
                                                    <button type="button"
                                                            @click="toggleEditEntityAll({{ json_encode($module['all_permissions']) }})"
                                                            class="text-[11px] font-semibold text-brand-700 hover:text-brand-800 dark:text-brand-400 hover:underline whitespace-nowrap">
                                                        {{ __('Toggle Category') }}
                                                    </button>
                                                </td>
                                            </tr>
                                            {{-- Module-level access permission row (e.g. "view metrology") --}}
                                            @if(!empty($module['view_permission']))
                                                <tr class="bg-gray-50/60 dark:bg-gray-700/20">
                                                    <td class="py-2 px-3 ps-5">
                                                        <label class="flex items-center gap-2 cursor-pointer group">
                                                            <input type="checkbox"
                                                                   name="permissions[]"
                                                                   value="{{ $module['view_permission'] }}"
                                                                   x-model="editRolePermissions"
                                                                   class="rounded border-gray-300 dark:border-gray-600 text-brand-600 focus:ring-brand-600 cursor-pointer w-4 h-4"
                                                                   title="{{ $module['view_permission'] }}">
                                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold
                                                                @if($module['color'] === 'rose') text-rose-700 dark:text-rose-300
                                                                @elseif($module['color'] === 'indigo') text-indigo-700 dark:text-indigo-300
                                                                @elseif($module['color'] === 'amber') text-amber-700 dark:text-amber-300
                                                                @elseif($module['color'] === 'blue') text-blue-700 dark:text-blue-300
                                                                @elseif($module['color'] === 'emerald') text-emerald-700 dark:text-emerald-300
                                                                @elseif($module['color'] === 'teal') text-teal-700 dark:text-teal-300
                                                                @else text-gray-600 dark:text-gray-400
                                                                @endif">
                                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                                                {{ __($module['view_permission']) }}
                                                            </span>
                                                        </label>
                                                    </td>
                                                    <td colspan="4" class="py-2 px-3">
                                                        <span class="text-[10px] text-gray-400 dark:text-gray-500 italic">{{ __('Module access permission') }}</span>
                                                    </td>
                                                </tr>
                                            @endif
                                            @foreach($module['entities'] as $entity => $entityData)
                                                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors">
                                                    <td class="py-2.5 px-3 ps-6 font-medium text-gray-900 dark:text-white">
                                                        <div class="flex items-center gap-2">
                                                            <span class="w-1.5 h-1.5 rounded-full shrink-0
                                                                @if($module['color'] === 'rose') bg-rose-500
                                                                @elseif($module['color'] === 'indigo') bg-indigo-500
                                                                @elseif($module['color'] === 'amber') bg-amber-500
                                                                @elseif($module['color'] === 'blue') bg-blue-500
                                                                @elseif($module['color'] === 'emerald') bg-emerald-500
                                                                @elseif($module['color'] === 'teal') bg-teal-500
                                                                @else bg-brand-600
                                                                @endif"></span>
                                                            <span class="font-mono capitalize font-bold text-xs">{{ __($entityData['display_name']) }}</span>
                                                        </div>
                                                    </td>
                                                    @foreach(['view', 'create', 'edit', 'delete'] as $act)
                                                        <td class="py-2.5 px-2 text-center align-middle">
                                                            @if(isset($entityData['actions'][$act]))
                                                                <input type="checkbox"
                                                                       name="permissions[]"
                                                                       value="{{ $entityData['actions'][$act] }}"
                                                                       x-model="editRolePermissions"
                                                                       class="rounded border-gray-300 dark:border-gray-600 text-brand-600 focus:ring-brand-600 cursor-pointer w-4 h-4"
                                                                       title="{{ $entityData['actions'][$act] }}">
                                                            @else
                                                                <span class="text-gray-300 dark:text-gray-600 text-xs">&mdash;</span>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                    <td class="py-2.5 px-3 text-end align-middle">
                                                        <button type="button"
                                                                @click="toggleEditEntityAll({{ json_encode(array_values($entityData['actions'])) }})"
                                                                class="text-[11px] font-medium text-brand-700 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 px-2.5 py-1 rounded border border-indigo-200/60 dark:border-indigo-800/60 hover:bg-indigo-500/10 dark:hover:bg-indigo-500/20 transition-colors whitespace-nowrap">
                                                            {{ __('Toggle Row') }}
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @empty
                                            <tr>
                                                <td colspan="6" class="py-4 text-center text-gray-400 italic">
                                                    {{ __('No standard entities found.') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if(!empty($permissionMatrix['custom']))
                                <!-- Additional System Permissions -->
                                <div class="pt-2">
                                    <x-input-label :value="__('Additional System Permissions')" />
                                    <div class="mt-1.5 flex flex-wrap gap-2">
                                        @foreach($permissionMatrix['custom'] as $customPerm)
                                            <label class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 text-xs cursor-pointer hover:bg-white dark:hover:bg-gray-800 transition-colors">
                                                <input type="checkbox"
                                                       name="permissions[]"
                                                       value="{{ $customPerm }}"
                                                       x-model="editRolePermissions"
                                                       class="rounded border-gray-300 dark:border-gray-600 text-brand-600 focus:ring-brand-600 w-4 h-4">
                                                <span class="font-mono text-gray-700 dark:text-gray-300">{{ $customPerm }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <x-input-error :messages="old('form_type') === 'edit_role' ? $errors->get('permissions') : []" class="mt-1 text-xs" />
                        </div>
                    </x-crud-modal.form>

                    <!-- ============================================================ -->
                    <!-- Edit Permission Modal — x-crud-modal.form                    -->
                    <!-- ============================================================ -->
                    <x-crud-modal.form
                        show="showEditPermissionModal"
                        alpine-action="editPermissionActionUrl"
                        method="PUT"
                        :title="__('Edit System Permission')"
                        :description="__('Update permission name in the system access catalog.')"
                        icon-color="amber"
                        :submit-text="__('Save Changes')"
                    >
                        <x-slot:hidden>
                            <input type="hidden" name="form_type" value="edit_permission">
                            <input type="hidden" name="edit_permission_id" :value="editPermissionId">
                        </x-slot:hidden>

                        <!-- Permission Name -->
                        <div>
                            <x-input-label for="edit_perm_name" :value="__('Permission Name')" />
                            <x-text-input id="edit_perm_name" name="name" type="text" class="mt-1 block w-full text-sm" x-model="editPermissionName" required autofocus />
                            <x-input-error :messages="old('form_type') === 'edit_permission' ? $errors->get('name') : []" class="mt-1 text-xs" />
                        </div>
                    </x-crud-modal.form>

                    <!-- ============================================================ -->
                    <!-- Delete Role Modal — x-crud-modal.delete                      -->
                    <!-- ============================================================ -->
                    <x-crud-modal.delete
                        show="showDeleteRoleModal"
                        action-url="deleteRoleActionUrl"
                        item-name="deleteRoleName"
                        :title="__('Delete Role')"
                    />

                    <!-- ============================================================ -->
                    <!-- Delete Permission Modal — x-crud-modal.delete                -->
                    <!-- ============================================================ -->
                    <x-crud-modal.delete
                        show="showDeletePermissionModal"
                        action-url="deletePermissionActionUrl"
                        item-name="deletePermissionName"
                        :title="__('Delete Permission')"
                    />

                </main>
            </div>
        </div>
    </div>
</x-app-layout>
