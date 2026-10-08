<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                    {{ __('Users & Sessions') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('Inspect registered accounts, role assignments, and active user sessions') }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-badge variant="info" size="md">
                    {{ $users->total() }} {{ __('Total Users') }}
                </x-badge>

                @if($registrationOpen ?? is_registration_open())
                    <a href="{{ route('system-tables.settings') }}"
                       title="{{ __('Click to configure System Settings') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20 hover:bg-emerald-500/20 transition-colors">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>{{ __('Registration: Open') }}</span>
                    </a>
                @else
                    <a href="{{ route('system-tables.settings') }}"
                       title="{{ __('Click to configure System Settings') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-500/10 text-rose-700 dark:text-rose-300 border border-rose-500/20 hover:bg-rose-500/20 transition-colors">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                        <span>{{ __('Registration: Closed') }}</span>
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        showCreateModal: {{ ($errors->has('name') || $errors->has('email') || $errors->has('password') || $errors->has('role') || $errors->has('status') || $errors->has('profile_photo_path')) && !old('_method') ? 'true' : 'false' }},
        showEditModal: {{ ($errors->has('name') || $errors->has('email') || $errors->has('password') || $errors->has('role') || $errors->has('status') || $errors->has('profile_photo_path')) && old('_method') === 'PUT' ? 'true' : 'false' }},
        showDeleteModal: false,

        authUserId: {{ auth()->id() }},
        editUserId: {{ old('_method') === 'PUT' ? (int) old('edit_user_id', 0) : 'null' }},
        editUserIsSelf: {{ old('_method') === 'PUT' && (int) old('edit_user_id', 0) === auth()->id() ? 'true' : 'false' }},
        editUserName: '{{ old('_method') === 'PUT' ? addslashes((string) old('name', '')) : '' }}',
        editUserEmail: '{{ old('_method') === 'PUT' ? addslashes((string) old('email', '')) : '' }}',
        editUserRole: '{{ old('_method') === 'PUT' ? addslashes((string) old('role', '')) : '' }}',
        editUserStatus: '{{ old('_method') === 'PUT' ? addslashes((string) old('status', 'active')) : 'active' }}',
        editUserPhotoPath: '{{ old('_method') === 'PUT' ? addslashes((string) old('profile_photo_path', '')) : '' }}',
        editUserPhotoUrl: '',
        editUserPhotoPreview: null,
        editUserPhotoName: '',
        editUserRemovePhoto: false,
        editUserActionUrl: '{{ old('_method') === 'PUT' && old('edit_user_id') ? route('system-tables.users.update', (int) old('edit_user_id')) : '' }}',

        deleteUserName: '',
        deleteUserActionUrl: '',

        openEditModal(id, name, email, role, status, photoPath, photoUrl, actionUrl) {
            this.editUserId = id;
            this.editUserIsSelf = (id === this.authUserId);
            this.editUserName = name;
            this.editUserEmail = email;
            this.editUserRole = role;
            this.editUserStatus = status || 'active';
            this.editUserPhotoPath = photoPath || '';
            this.editUserPhotoUrl = photoUrl || '';
            this.editUserPhotoPreview = null;
            this.editUserPhotoName = '';
            this.editUserRemovePhoto = false;
            this.editUserActionUrl = actionUrl;
            if (this.$refs.editFileInput) {
                this.$refs.editFileInput.value = '';
            }
            this.showEditModal = true;
        },

        openDeleteModal(name, actionUrl) {
            this.deleteUserName = name;
            this.deleteUserActionUrl = actionUrl;
            this.showDeleteModal = true;
        }
    }">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-system-tabs active="users" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-8">

                    @if (session('status'))
                        <x-alert variant="success">
                            {{ session('status') }}
                        </x-alert>
                    @endif

                    <!-- Users Section -->
                    <x-table>
                        <x-slot:toolbar>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                <span>{{ __('Users Table') }} (<code>users</code>)</span>
                            </h3>

                            <div class="flex flex-wrap items-center gap-3">
                                <!-- Global Filter Form -->
                                <x-global-filter
                                    :action="route('system-tables.users')"
                                    :search="true"
                                    :search-placeholder="__('Search by name or email...')"
                                    :search-value="$search"
                                    :submit-text="__('Search')"
                                >
                                    <select name="status" onchange="this.form.submit()" class="py-1.5 ps-2.5 pe-8 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600 shadow-sm">
                                        <option value="">{{ __('All Statuses') }}</option>
                                        <option value="active" @selected($status === 'active')>{{ __('Active') }}</option>
                                        <option value="suspended" @selected($status === 'suspended')>{{ __('Suspended') }}</option>
                                    </select>
                                </x-global-filter>

                                <!-- Create User Button -->
                                <x-primary-button type="button" @click="showCreateModal = true" class="py-1.5 px-3 text-xs rounded-lg flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span>{{ __('New User') }}</span>
                                </x-primary-button>
                            </div>
                        </x-slot:toolbar>

                    <x-slot:header>
                        <x-table.th>ID</x-table.th>
                        <x-table.th>{{ __('User') }}</x-table.th>
                        <x-table.th>{{ __('Email') }}</x-table.th>
                        <x-table.th>{{ __('Roles') }}</x-table.th>
                        <x-table.th>{{ __('Account Status') }}</x-table.th>
                        <x-table.th>{{ __('Date') }}</x-table.th>
                        <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                    </x-slot:header>

                    @forelse($users as $user)
                        <x-table.tr>
                            <x-table.td class="font-mono font-bold text-gray-900 dark:text-white">#{{ $user->id }}</x-table.td>
                            <x-table.td class="font-semibold text-gray-900 dark:text-white">
                                <div class="flex items-center gap-3">
                                    @if($user->profile_photo_url)
                                        <img src="{{ $user->profile_photo_url }}"
                                             alt="{{ $user->name }}"
                                             class="w-8 h-8 rounded-full object-cover border border-gray-200 dark:border-gray-700 shadow-sm shrink-0">
                                    @else
                                        <div class="w-8 h-8 rounded-full bg-brand-600/10 dark:bg-brand-600/20 text-brand-700 dark:text-brand-400 flex items-center justify-center font-bold text-xs shrink-0">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <span class="font-semibold text-gray-900 dark:text-white block truncate">{{ $user->name }}</span>
                                        @if($user->photo_hash)
                                            <span class="font-mono text-[10px] text-gray-400 dark:text-gray-500 block truncate" title="Hash: {{ $user->photo_hash }}">
                                                #{{ substr($user->photo_hash, 0, 10) }}...
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </x-table.td>
                            <x-table.td class="font-mono">{{ $user->email }}</x-table.td>
                            <x-table.td>
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles as $role)
                                        <x-badge variant="info">
                                            {{ $role->name }}
                                        </x-badge>
                                    @empty
                                        <span class="text-gray-400 italic">{{ __('None') }}</span>
                                    @endforelse
                                </div>
                            </x-table.td>
                            <x-table.td>
                                <div class="space-y-1">
                                    @if($user->isActive())
                                        <x-badge variant="success" :dot="true">
                                            {{ __('Active') }}
                                        </x-badge>
                                    @else
                                        <x-badge variant="danger" :dot="true">
                                            {{ __('Suspended / Locked') }}
                                        </x-badge>
                                    @endif

                                    <div class="text-[11px]">
                                        @if($user->email_verified_at)
                                            <span class="text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                                <svg class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                {{ __('Verified') }}
                                            </span>
                                        @else
                                            <span class="text-amber-500 dark:text-amber-400 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                {{ __('Unverified') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </x-table.td>
                            <x-table.td class="whitespace-nowrap">
                                <x-date :value="$user->created_at" format="datetime" />
                            </x-table.td>
                            <x-table.td class="whitespace-nowrap text-end">
                                <x-table.actions class="justify-end">
                                    {{-- Quick Toggle Account Status (Anti-lockout: exclude authenticated user) --}}
                                    @if($user->id !== auth()->id())
                                        @if($user->isActive())
                                            <form method="POST" action="{{ route('system-tables.users.toggle-status', array_merge(['user' => $user->id], request()->query())) }}" class="inline">
                                                @csrf
                                                <x-table.action
                                                    type="delete"
                                                    button-type="submit"
                                                    :title="__('Lock Account')"
                                                    onclick="return confirm('{{ __('Are you sure you want to lock this user account?') }}')"
                                                >
                                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                    </svg>
                                                </x-table.action>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('system-tables.users.toggle-status', array_merge(['user' => $user->id], request()->query())) }}" class="inline">
                                                @csrf
                                                <x-table.action
                                                    type="success"
                                                    button-type="submit"
                                                    :title="__('Unlock Account')"
                                                    onclick="return confirm('{{ __('Are you sure you want to unlock this user account?') }}')"
                                                >
                                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                                    </svg>
                                                </x-table.action>
                                            </form>
                                        @endif
                                    @endif

                                    <x-table.action-edit
                                        type="button"
                                        :title="__('Edit User')"
                                        @click="openEditModal({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ addslashes($user->email) }}', '{{ $user->roles->first()?->name ?? 'User' }}', '{{ $user->status?->value ?? 'active' }}', '{{ addslashes((string) $user->profile_photo_path) }}', '{{ addslashes((string) ($user->profile_photo_url ?? '')) }}', '{{ route('system-tables.users.update', array_merge(['user' => $user->id], request()->query())) }}')"
                                    />
                                    @if($user->id !== auth()->id())
                                        <x-table.action-delete
                                            :title="__('Delete User')"
                                            type="button"
                                            @click="openDeleteModal('{{ addslashes($user->name) }}', '{{ route('system-tables.users.destroy', array_merge(['user' => $user->id], request()->query())) }}')"
                                        />
                                    @endif
                                </x-table.actions>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.empty colspan="7" />
                    @endforelse

                    @if($users->hasPages())
                        <x-slot:pagination>
                            {{ $users->links() }}
                        </x-slot:pagination>
                    @endif
                </x-table>

                <!-- Active Sessions Section -->
                <x-table>
                    <x-slot:toolbar>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span>{{ __('Active Sessions') }} (<code>sessions</code>)</span>
                        </h3>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                            {{ $sessions->total() }} {{ __('Sessions tracked') }}
                        </span>
                    </x-slot:toolbar>

                    <x-slot:header>
                        <x-table.th>{{ __('Session ID') }}</x-table.th>
                        <x-table.th>{{ __('User') }}</x-table.th>
                        <x-table.th>{{ __('IP Address') }}</x-table.th>
                        <x-table.th>{{ __('User Agent') }}</x-table.th>
                        <x-table.th>{{ __('Last Activity') }}</x-table.th>
                        <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                    </x-slot:header>

                    @forelse($sessions as $session)
                        <x-table.tr>
                            <x-table.td class="font-mono text-gray-400 dark:text-gray-500">
                                {{ substr($session->id, 0, 16) }}...
                            </x-table.td>
                            <x-table.td class="font-semibold text-gray-900 dark:text-white">
                                @if($session->user_name)
                                    <span>{{ $session->user_name }}</span>
                                    <span class="block text-xs font-mono font-normal text-gray-400">#{{ $session->user_id }}</span>
                                @else
                                    <span class="text-gray-400 italic">{{ __('Guest') }}</span>
                                @endif
                            </x-table.td>
                            <x-table.td class="font-mono text-gray-800 dark:text-gray-200">
                                {{ $session->ip_address ?? '127.0.0.1' }}
                            </x-table.td>
                            <x-table.td class="max-w-xs truncate text-gray-500 dark:text-gray-400" title="{{ $session->user_agent }}">
                                {{ $session->user_agent ?? 'N/A' }}
                            </x-table.td>
                            <x-table.td class="whitespace-nowrap">
                                {{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }}
                            </x-table.td>
                            <x-table.td class="whitespace-nowrap text-end">
                                <x-table.actions class="justify-end">
                                    <x-table.action-delete :title="__('Terminate Session')" />
                                </x-table.actions>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.empty colspan="6" />
                    @endforelse

                    @if($sessions->hasPages())
                        <x-slot:pagination>
                            {{ $sessions->links() }}
                        </x-slot:pagination>
                    @endif
                </x-table>

                <!-- ============================================================ -->
                <!-- Create User Modal — x-crud-modal.form                        -->
                <!-- ============================================================ -->
                <x-crud-modal.form
                    show="showCreateModal"
                    :action-url="route('system-tables.users.store', request()->query())"
                    method="POST"
                    enctype="multipart/form-data"
                    :title="__('Create New System User')"
                    :description="__('Add a new user account with credentials and role assignment.')"
                    icon-color="green"
                    :submit-text="__('Create User')"
                >
                    <!-- Name -->
                    <div>
                        <x-input-label for="new_user_name" :value="__('Full Name')" />
                        <x-text-input id="new_user_name" name="name" type="text" class="mt-1 block w-full text-sm" :value="old('name')" required autofocus placeholder="{{ __('e.g. John Doe') }}" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1 text-xs" />
                    </div>

                    <!-- Email -->
                    <div>
                        <x-input-label for="new_user_email" :value="__('Email Address')" />
                        <x-text-input id="new_user_email" name="email" type="email" class="mt-1 block w-full text-sm" :value="old('email')" required placeholder="{{ __('e.g. user@example.com') }}" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs" />
                    </div>

                    <!-- Password -->
                    <div>
                        <x-input-label for="new_user_password" :value="__('Password')" />
                        <x-text-input id="new_user_password" name="password" type="password" class="mt-1 block w-full text-sm" required placeholder="••••••••" />
                        <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs" />
                    </div>

                    <!-- Password Confirmation -->
                    <div>
                        <x-input-label for="new_user_password_confirmation" :value="__('Confirm Password')" />
                        <x-text-input id="new_user_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full text-sm" required placeholder="••••••••" />
                    </div>

                    <!-- Role & Status Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="new_user_role" :value="__('Role Assignment')" />
                            <select id="new_user_role" name="role" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-600 focus:ring-brand-600 text-sm">
                                <option value="" disabled>-- {{ __('Select Role') }} --</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" @selected(old('role', 'User') === $role->name)>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-1 text-xs" />
                        </div>

                        <div>
                            <x-input-label for="new_user_status" :value="__('Account Status')" />
                            <select id="new_user_status" name="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-600 focus:ring-brand-600 text-sm">
                                <option value="active" @selected(old('status', 'active') === 'active')>{{ __('Active') }}</option>
                                <option value="suspended" @selected(old('status') === 'suspended')>{{ __('Suspended') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('status')" class="mt-1 text-xs" />
                        </div>
                    </div>

                    <!-- Profile Photo Selection -->
                    <div x-data="{
                        createPhotoPreview: null,
                        createPhotoName: '',
                        handleFileChange(event) {
                            const file = event.target.files[0];
                            if (file) {
                                this.createPhotoName = file.name;
                                this.createPhotoPreview = URL.createObjectURL(file);
                            }
                        },
                        clearPhoto() {
                            this.createPhotoPreview = null;
                            this.createPhotoName = '';
                            $refs.createFileInput.value = '';
                        }
                    }">
                        <x-input-label for="new_user_photo_file" :value="__('Profile Photo (Optional)')" />
                        <div class="mt-2 flex items-center gap-4">
                            <!-- Avatar Preview -->
                            <div class="relative w-14 h-14 rounded-full overflow-hidden border-2 border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 shrink-0 flex items-center justify-center shadow-inner">
                                <template x-if="createPhotoPreview">
                                    <img :src="createPhotoPreview" alt="Preview" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!createPhotoPreview">
                                    <svg class="w-7 h-7 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </template>
                            </div>

                            <!-- Controls -->
                            <div class="flex-1 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <input
                                        type="file"
                                        id="new_user_photo_file"
                                        name="photo"
                                        accept="image/png,image/jpeg,image/webp,image/gif"
                                        class="hidden"
                                        x-ref="createFileInput"
                                        @change="handleFileChange($event)"
                                    >
                                    <x-secondary-button
                                        type="button"
                                        class="text-xs py-1.5 px-3"
                                        @click="$refs.createFileInput.click()"
                                    >
                                        <svg class="w-3.5 h-3.5 me-1.5 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ __('Choose Photo') }}
                                    </x-secondary-button>

                                    <button
                                        type="button"
                                        x-show="createPhotoPreview"
                                        x-cloak
                                        @click="clearPhoto()"
                                        class="text-xs text-rose-600 dark:text-rose-400 hover:underline font-medium"
                                    >
                                        {{ __('Remove') }}
                                    </button>
                                </div>

                                <div class="flex items-center gap-2">
                                    <x-text-input
                                        id="new_user_photo_path"
                                        name="profile_photo_path"
                                        type="text"
                                        class="block w-full text-xs"
                                        :value="old('profile_photo_path')"
                                        placeholder="{{ __('Or enter photo URL / path: e.g. photos/avatar.jpg') }}"
                                    />
                                </div>
                                <p class="text-xs text-gray-400 dark:text-gray-500" x-text="createPhotoName ? '{{ __('Selected:') }} ' + createPhotoName : '{{ __('PNG, JPG, WEBP up to 5MB') }}'"></p>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('photo')" class="mt-1 text-xs" />
                        <x-input-error :messages="$errors->get('profile_photo_path')" class="mt-1 text-xs" />
                    </div>
                </x-crud-modal.form>

                <!-- ============================================================ -->
                <!-- Edit User Modal — x-crud-modal.form                          -->
                <!-- ============================================================ -->
                <x-crud-modal.form
                    show="showEditModal"
                    alpine-action="editUserActionUrl"
                    method="PUT"
                    enctype="multipart/form-data"
                    :title="__('Edit System User')"
                    :description="__('Update user credentials, profile information, or assigned roles.')"
                    icon-color="amber"
                    :submit-text="__('Save Changes')"
                >
                    <x-slot:hidden>
                        <input type="hidden" name="edit_user_id" :value="editUserId">
                    </x-slot:hidden>

                    <!-- Name -->
                    <div>
                        <x-input-label for="edit_user_name_input" :value="__('Full Name')" />
                        <x-text-input id="edit_user_name_input" name="name" type="text" class="mt-1 block w-full text-sm" x-model="editUserName" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-1 text-xs" />
                    </div>

                    <!-- Email -->
                    <div>
                        <x-input-label for="edit_user_email_input" :value="__('Email Address')" />
                        <x-text-input id="edit_user_email_input" name="email" type="email" class="mt-1 block w-full text-sm" x-model="editUserEmail" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs" />
                    </div>

                    <!-- Password (Optional) -->
                    <div>
                        <x-input-label for="edit_user_password_input" :value="__('New Password (Optional)')" />
                        <x-text-input id="edit_user_password_input" name="password" type="password" class="mt-1 block w-full text-sm" placeholder="{{ __('Leave blank to keep current password') }}" />
                        <p class="mt-1 text-xs text-gray-400">{{ __('Leave empty if you do not wish to change the password.') }}</p>
                        <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs" />
                    </div>

                    <!-- Password Confirmation -->
                    <div>
                        <x-input-label for="edit_user_password_confirmation_input" :value="__('Confirm New Password')" />
                        <x-text-input id="edit_user_password_confirmation_input" name="password_confirmation" type="password" class="mt-1 block w-full text-sm" placeholder="{{ __('Confirm new password') }}" />
                    </div>

                    <!-- Role & Status Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="edit_user_role_select" :value="__('Role Assignment')" />

                            {{-- Read-only display when editing own account --}}
                            <template x-if="editUserIsSelf">
                                <div>
                                    <div class="mt-1 flex items-center gap-2 px-3 py-2 rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 text-sm text-gray-500 dark:text-gray-400 cursor-not-allowed select-none">
                                        <svg class="w-4 h-4 text-brand-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m2-6a3 3 0 100-6 3 3 0 000 6z" />
                                        </svg>
                                        <span x-text="editUserRole"></span>
                                    </div>
                                    <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ __('You cannot change your own role.') }}</p>
                                </div>
                            </template>

                            {{-- Normal editable select for other users --}}
                            <template x-if="!editUserIsSelf">
                                <select id="edit_user_role_select" name="role" x-model="editUserRole" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-600 focus:ring-brand-600 text-sm">
                                    <option value="" disabled>-- {{ __('Select Role') }} --</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </template>

                            <x-input-error :messages="$errors->get('role')" class="mt-1 text-xs" />
                        </div>

                        <div>
                            <x-input-label for="edit_user_status_select" :value="__('Account Status')" />

                            {{-- Read-only display when editing own account --}}
                            <template x-if="editUserIsSelf">
                                <div>
                                    <div class="mt-1 flex items-center gap-2 px-3 py-2 rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 text-sm text-gray-500 dark:text-gray-400 cursor-not-allowed select-none">
                                        <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span x-text="editUserStatus === 'active' ? '{{ __('Active') }}' : '{{ __('Suspended / Locked') }}'"></span>
                                    </div>
                                    <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ __('You cannot suspend or lock your own account.') }}</p>
                                </div>
                            </template>

                            {{-- Normal editable select for other users --}}
                            <template x-if="!editUserIsSelf">
                                <select id="edit_user_status_select" name="status" x-model="editUserStatus" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-600 focus:ring-brand-600 text-sm">
                                    <option value="active">{{ __('Active') }}</option>
                                    <option value="suspended">{{ __('Suspended / Locked') }}</option>
                                </select>
                            </template>

                            <x-input-error :messages="$errors->get('status')" class="mt-1 text-xs" />
                        </div>
                    </div>

                    <!-- Profile Photo Selection -->
                    <div>
                        <x-input-label for="edit_user_photo_path" :value="__('Profile Photo (Optional)')" />
                        <input type="hidden" name="remove_photo" :value="editUserRemovePhoto ? '1' : '0'">

                        <div class="mt-2 flex items-center gap-4">
                            <!-- Avatar Preview -->
                            <div class="relative w-14 h-14 rounded-full overflow-hidden border-2 border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 shrink-0 flex items-center justify-center shadow-inner">
                                <template x-if="editUserPhotoPreview">
                                    <img :src="editUserPhotoPreview" alt="Preview" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!editUserPhotoPreview && editUserPhotoUrl && !editUserRemovePhoto">
                                    <img :src="editUserPhotoUrl" alt="Current Photo" class="w-full h-full object-cover">
                                </template>
                                <template x-if="(!editUserPhotoPreview && !editUserPhotoUrl) || (!editUserPhotoPreview && editUserRemovePhoto)">
                                    <svg class="w-7 h-7 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </template>
                            </div>

                            <!-- Controls -->
                            <div class="flex-1 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <input
                                        type="file"
                                        id="edit_user_photo_file"
                                        name="photo"
                                        accept="image/png,image/jpeg,image/webp,image/gif"
                                        class="hidden"
                                        x-ref="editFileInput"
                                        @change="
                                            const file = $event.target.files[0];
                                            if (file) {
                                                editUserPhotoName = file.name;
                                                editUserPhotoPreview = URL.createObjectURL(file);
                                                editUserRemovePhoto = false;
                                            }
                                        "
                                    >
                                    <x-secondary-button
                                        type="button"
                                        class="text-xs py-1.5 px-3"
                                        @click="$refs.editFileInput.click()"
                                    >
                                        <svg class="w-3.5 h-3.5 me-1.5 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ __('Choose Photo') }}
                                    </x-secondary-button>

                                    <button
                                        type="button"
                                        x-show="(editUserPhotoPreview || editUserPhotoUrl) && !editUserRemovePhoto"
                                        x-cloak
                                        @click="
                                            editUserPhotoPreview = null;
                                            editUserPhotoName = '';
                                            editUserPhotoPath = '';
                                            editUserRemovePhoto = true;
                                            if ($refs.editFileInput) { $refs.editFileInput.value = ''; }
                                        "
                                        class="text-xs text-rose-600 dark:text-rose-400 hover:underline font-medium"
                                    >
                                        {{ __('Remove Photo') }}
                                    </button>
                                </div>

                                <div class="flex items-center gap-2">
                                    <x-text-input
                                        id="edit_user_photo_path"
                                        name="profile_photo_path"
                                        type="text"
                                        class="block w-full text-xs"
                                        x-model="editUserPhotoPath"
                                        placeholder="{{ __('Or enter photo URL / path: e.g. photos/avatar.jpg') }}"
                                    />
                                </div>
                                <p class="text-xs text-gray-400 dark:text-gray-500" x-text="editUserPhotoName ? '{{ __('Selected:') }} ' + editUserPhotoName : '{{ __('PNG, JPG, WEBP up to 5MB') }}'"></p>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('photo')" class="mt-1 text-xs" />
                        <x-input-error :messages="$errors->get('profile_photo_path')" class="mt-1 text-xs" />
                    </div>
                </x-crud-modal.form>

                <!-- ============================================================ -->
                <!-- Delete User Modal — x-crud-modal.delete                      -->
                <!-- ============================================================ -->
                <x-crud-modal.delete
                    show="showDeleteModal"
                    action-url="deleteUserActionUrl"
                    item-name="deleteUserName"
                    :title="__('Delete User')"
                />

                </main>
            </div>
        </div>
    </div>
</x-app-layout>
