@php
    $user = auth()->user()->loadMissing('roles');

    $isSuperAdmin = $user->isSuperAdmin() || $user->hasRole('Super-Admin');

    // الوحدات التي يملك المستخدم صلاحية رؤيتها فقط
    $modules = collect(config('workspace.modules'))
        ->filter(fn ($module) => $user->can($module['permission']));
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-tool-icon name="workspace" class="w-14 h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Workspace') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('General information and quick access to your modules') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if($isSuperAdmin)
                    <x-badge variant="primary" size="md">
                        {{ __('Super-Admin') }}
                    </x-badge>
                @elseif($user->roles->isNotEmpty())
                    <x-badge variant="info" size="md">
                        {{ $user->roles->first()->name }}
                    </x-badge>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Executive Welcome Banner -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700/60 p-6 transition-all duration-200">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                    <div class="flex items-center gap-4 sm:gap-5 text-center sm:text-start">
                        <x-application-logo class="h-14 w-auto shrink-0 max-w-[160px]" />
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ __('Welcome to') }} GMTM
                            </h3>
                            <p class="text-sm font-medium text-brand-700 dark:text-brand-400">
                                {{ __('Generale Maintenance & Travaux Montage') }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Signed in as :name (:email)', ['name' => $user->name, 'email' => $user->email]) }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        {{-- رابط مباشر بدل وضع <button> داخل <a> (HTML غير صالح) --}}
                        <a href="{{ route('profile.edit') }}"
                           class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md text-xs font-semibold text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800">
                            {{ __('Manage your Account') }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Business Modules Grid -->
            <section aria-labelledby="modules-heading">
                <h3 id="modules-heading" class="text-base font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand-600" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span>{{ __('Business Management Portals') }}</span>
                </h3>

                @if($modules->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                        @foreach($modules as $module)
                            <x-module-card :module="$module" />
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-gray-300 dark:border-gray-600 p-8 text-center">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ __('No modules available for your account') }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Ask an administrator to grant you access to the modules you need.') }}
                        </p>
                    </div>
                @endif
            </section>

        </div>
    </div>
</x-app-layout>