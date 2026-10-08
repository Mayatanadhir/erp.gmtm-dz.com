<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- ─── User Identity Hero Card ─── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700/60 shadow-sm overflow-hidden">
                {{-- Gradient Banner --}}
                <div class="h-24 bg-gradient-to-r from-brand-600 via-brand-500 to-brand-700"></div>

                {{-- Avatar + Details --}}
                <div class="px-6 pb-6">
                    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 -mt-12">
                        {{-- Avatar --}}
                        <div class="relative shrink-0">
                            @if($user->profile_photo_url)
                                <img class="w-24 h-24 rounded-full object-cover ring-4 ring-white dark:ring-gray-800 shadow-lg"
                                     src="{{ $user->profile_photo_url }}"
                                     alt="{{ $user->name }}">
                            @else
                                <div class="w-24 h-24 rounded-full bg-gradient-to-tr from-brand-600 to-brand-800 text-white font-bold text-3xl flex items-center justify-center ring-4 ring-white dark:ring-gray-800 shadow-lg">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>

                        {{-- Badges Row --}}
                        <div class="flex flex-wrap items-center gap-2 pb-1">
                            @if($user->isSuperAdmin() || $user->hasRole('Super-Admin'))
                                <x-badge variant="warning">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 0 1 2.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0 1 10 1.944ZM11 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm0-7a1 1 0 1 0-2 0v3a1 1 0 1 0 2 0V7Z" clip-rule="evenodd" />
                                    </svg>
                                    {{ __('Super-Admin') }}
                                </x-badge>
                            @elseif($user->roles->isNotEmpty())
                                @foreach($user->roles as $role)
                                    <x-badge variant="info">{{ $role->name }}</x-badge>
                                @endforeach
                            @endif

                            @if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && $user->hasVerifiedEmail())
                                <x-badge variant="success" :dot="true">{{ __('Verified') }}</x-badge>
                            @elseif($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                                <x-badge variant="danger">{{ __('Unverified') }}</x-badge>
                            @endif

                            @if($user->status)
                                <x-badge :variant="$user->isActive() ? 'success' : 'danger'" :dot="true">
                                    {{ $user->status->label() }}
                                </x-badge>
                            @endif

                            <x-badge variant="neutral">
                                <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                                {{ __('Member since :date', ['date' => $user->created_at->format('d/m/Y')]) }}
                            </x-badge>
                        </div>
                    </div>

                    {{-- Name & Email --}}
                    <div class="mt-3">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1.5 mt-0.5">
                            <svg class="w-3.5 h-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                            {{ $user->email }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- ─── 2-Column Grid: Left = Profile Info, Right = Password + Danger ─── --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                {{-- Column 1: Profile Information & Photo --}}
                <div class="p-6 sm:p-8 bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700/60 transition-colors duration-200">
                    @include('profile.partials.update-profile-information-form')
                </div>

                {{-- Column 2: Password + Account Deletion --}}
                <div class="space-y-6">
                    <div class="p-6 sm:p-8 bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700/60 transition-colors duration-200">
                        @include('profile.partials.update-password-form')
                    </div>

                    <div class="p-6 sm:p-8 bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700/60 transition-colors duration-200">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
