<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                    {{ __('System Settings') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('Manage dynamic application parameters, access gates, and infrastructure switches') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/70">
                    <span class="w-2 h-2 rounded-full bg-brand-600 animate-pulse"></span>
                    {{ __('Dynamic Engine Active') }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        registrationOpen: {{ $registrationOpen ? 'true' : 'false' }},
        isRTL: {{ app()->getLocale() === 'ar' ? 'true' : 'false' }},
        isTogglingRegistration: false,
        toast: {
            show: false,
            message: '',
            type: 'success'
        },
        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => {
                this.toast.show = false;
            }, 4000);
        },
        async toggleRegistration() {
            if (this.isTogglingRegistration) return;
            this.isTogglingRegistration = true;

            try {
                const response = await fetch(@js(route('system-tables.settings.toggle-registration')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @js(csrf_token()),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        enabled: !this.registrationOpen
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.registrationOpen = data.enabled;
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.message || @js(__('Failed to update registration status.')), 'error');
                }
            } catch (error) {
                this.showToast(@js(__('A network error occurred while updating settings.')), 'error');
            } finally {
                this.isTogglingRegistration = false;
            }
        }
    }">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-system-tabs active="settings" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-8">
                    {{-- Standard Session Feedback via Unified Alert Component --}}
                    @if (session('status'))
                        <x-alert variant="success">
                            {{ session('status') }}
                        </x-alert>
                    @endif

                    @if (session('error'))
                        <x-alert variant="danger">
                            {{ session('error') }}
                        </x-alert>
                    @endif

                    @if ($errors->any())
                        <x-alert variant="danger">
                            <ul class="list-disc list-inside text-xs space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    @endif

                    {{-- Reactive Real-Time AJAX Feedback via Unified Alert Component --}}
                    <div x-show="toast.show" x-cloak x-transition class="space-y-4">
                        <div x-show="toast.type === 'success'">
                            <x-alert variant="success" :dismissible="true">
                                <span x-text="toast.message"></span>
                            </x-alert>
                        </div>
                        <div x-show="toast.type === 'error' || toast.type === 'danger'">
                            <x-alert variant="danger" :dismissible="true">
                                <span x-text="toast.message"></span>
                            </x-alert>
                        </div>
                    </div>

                    <!-- Section 1: Authentication & Access Gates -->
                    <div class="rounded-2xl border border-gray-100 dark:border-gray-700/60 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/70 flex items-center justify-center font-bold">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                        {{ __('Registration & User Onboarding') }}
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('Control public account creation, visitor accessibility, and security perimeter') }}
                                    </p>
                                </div>
                            </div>
                            <div>
                                <span x-show="registrationOpen"
                                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    {{ __('Registrations Open') }}
                                </span>
                                <span x-show="!registrationOpen"
                                      x-cloak
                                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-700 dark:text-rose-300 border border-rose-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    {{ __('Registrations Closed') }}
                                </span>
                            </div>
                        </div>

                        <div class="p-6 divide-y divide-gray-100 dark:divide-gray-700/60">
                            <!-- Toggle Item: Allow New User Registrations -->
                            <div class="py-4 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                            {{ __('Allow new user registrations') }}
                                        </h4>
                                        <span class="font-mono text-[10px] text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700/50 px-2 py-0.5 rounded">
                                            allow_registration
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-gray-400 dark:text-gray-500 flex items-center gap-1.5 pt-0.5">
                                     <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                      
                                       <p class="text-xs text-gray-500 dark:text-gray-400 max-w-2xl leading-relaxed"> {{ __('When disabled, visitors cannot access the registration page or submit new registrations, and registration links are automatically hidden.') }}</p> 
                                    
                                    
                                          </div>
                                </div>

                                <div class="flex items-center gap-4 shrink-0">
                                    <button type="button"
                                            @click="toggleRegistration()"
                                            :disabled="isTogglingRegistration"
                                            class="relative inline-flex h-7 w-14 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                                            :class="registrationOpen ? 'bg-emerald-500' : 'bg-rose-500'"
                                            role="switch"
                                            :aria-checked="registrationOpen">
                                        <span class="sr-only">{{ __('Allow new user registrations') }}</span>
                                        <span aria-hidden="true"
                                              class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out flex items-center justify-center"
                                              :class="isRTL ? (registrationOpen ? 'switch-knob-right' : 'switch-knob-left') : (registrationOpen ? 'switch-knob-left' : 'switch-knob-right')">
                                            <svg x-show="isTogglingRegistration" class="animate-spin h-3.5 w-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <template x-if="!isTogglingRegistration">
                                                <span class="w-2 h-2 rounded-full" :class="registrationOpen ? 'bg-emerald-400' : 'bg-rose-400'"></span>
                                            </template>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Dynamic System Configuration Registry -->
                    <x-table>
                        <x-slot:toolbar>
                            <div class="flex items-center justify-between w-full">
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span>{{ __('Active Configuration Registry') }} (<code>system_settings</code>)</span>
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ __('Stored parameters currently managed by the Dynamic System Settings Engine') }}
                                    </p>
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                    {{ $settings->count() }} {{ __('Total Keys') }}
                                </span>
                            </div>
                        </x-slot:toolbar>

                        <x-slot:header>
                            <x-table.th>{{ __('Setting Key') }}</x-table.th>
                            <x-table.th>{{ __('Group') }}</x-table.th>
                            <x-table.th>{{ __('Stored Value') }}</x-table.th>
                            <x-table.th>{{ __('Description') }}</x-table.th>
                            <x-table.th>{{ __('Last Updated') }}</x-table.th>
                        </x-slot:header>

                        @forelse($settings as $setting)
                            <x-table.tr>
                                <x-table.td>
                                    <div class="flex flex-col gap-0.5">
                                        <span class="font-semibold text-gray-900 dark:text-white">
                                            {{ __($setting->key) }}
                                        </span>
                                        <span class="font-mono text-[10px] text-gray-400 dark:text-gray-500">
                                            {{ $setting->key }}
                                        </span>
                                    </div>
                                </x-table.td>
                                <x-table.td>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        {{ __($setting->group) }}
                                    </span>
                                </x-table.td>
                                <x-table.td class="font-mono text-xs">
                                    @if(is_bool($setting->value))
                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold {{ $setting->value ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-700 dark:text-rose-300 border border-rose-500/20' }}">
                                            {{ $setting->value ? 'true' : 'false' }}
                                        </span>
                                    @elseif(is_array($setting->value))
                                        <span class="text-gray-600 dark:text-gray-400" title="{{ json_encode($setting->value) }}">
                                            {{ \Illuminate\Support\Str::limit(json_encode($setting->value), 40) }}
                                        </span>
                                    @else
                                        <span class="text-gray-900 dark:text-gray-200">
                                            {{ (string) $setting->value }}
                                        </span>
                                    @endif
                                </x-table.td>
                                <x-table.td class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $setting->description ? __($setting->description) : '—' }}
                                </x-table.td>
                                <x-table.td class="whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                    <x-date :value="$setting->updated_at" format="datetime" />
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty colspan="5" :message="__('No custom system settings found in storage.')" />
                        @endforelse
                    </x-table>

                    <!-- Section 3: Architecture & Engineering Specifications -->
                    
                </main>
            </div>
        </div>
    </div>
</x-app-layout>
