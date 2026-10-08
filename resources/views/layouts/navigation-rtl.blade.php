<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 transition-colors duration-200">
    <!-- Primary Navigation Menu -->
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center py-1 transition-transform duration-150 hover:scale-105">
                        <x-application-logo class="h-12 w-auto sm:h-14" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 space-x-reverse sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Workspace') }}
                    </x-nav-link>

                    {{-- 🔬 Metrology & Equipments --}}
                    @can('view metrology')
                    <x-nav-link :href="route('dashboard_metrology')" :active="request()->routeIs('dashboard_metrology') || request()->routeIs('metrology.*')">
                        {{ __('Metrology') }}
                    </x-nav-link>
                    @endcan

                    {{-- 💼 Operations & Projects --}}
                    @can('view operations')
                    <x-nav-link :href="route('dashboard_operations')" :active="request()->routeIs('dashboard_operations') || request()->routeIs('operations.*')">
                        {{ __('Operations') }}
                    </x-nav-link>
                    @endcan

                    {{-- 📈 Internal and Analytical Management --}}
                    @can('view analytics')
                    <x-nav-link :href="route('dashboard_analytics')" :active="request()->routeIs('dashboard_analytics') || request()->routeIs('analytics.*')">
                        {{ __('Analytics') }}
                    </x-nav-link>
                    @endcan

                    {{-- 💰 Financial Management --}}
                    @can('view financial')
                    <x-nav-link :href="route('dashboard_financial')" :active="request()->routeIs('dashboard_financial') || request()->routeIs('financial.*')">
                        {{ __('Financial') }}
                    </x-nav-link>
                    @endcan

                    {{-- 🗂️ Master Data --}}
                    @can('view master data')
                    <x-nav-link :href="route('dashboard_master_data')" :active="request()->routeIs('dashboard_master_data') || request()->routeIs('master-data.*')">
                        {{ __('Master Data') }}
                    </x-nav-link>
                    @endcan

                    @if(Auth::user()?->isSuperAdmin() || Auth::user()?->hasRole('Super-Admin'))
                        <x-nav-link :href="route('system-tables.index')" :active="request()->routeIs('system-tables.*')">
                            {{ __('System Control') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- Theme Switcher -->
                <div class="hidden sm:flex sm:items-center">
                    <x-theme-switcher />
                </div>

                <!-- Language Switcher -->
                <div class="hidden sm:flex sm:items-center">
                    <x-language-switcher />
                </div>

                <!-- Settings Dropdown (Google Account Popover) -->
                <div class="hidden sm:flex sm:items-center sm:ms-2">
                    <x-dropdown align="right" width="80" contentClasses="p-0 bg-white dark:bg-gray-800 overflow-hidden">
                        <x-slot name="trigger">
                            <button type="button" class="relative flex items-center justify-center p-0.5 rounded-full ring-2 ring-gray-200 dark:ring-gray-700 hover:ring-brand-500 dark:hover:ring-brand-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-600 dark:focus:ring-offset-gray-800 transition-all duration-200" title="{{ Auth::user()->name }}">
                                @if(Auth::user()->profile_photo_url)
                                    <img class="h-9 w-9 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}">
                                @else
                                    <div class="h-9 w-9 rounded-full bg-gradient-to-tr from-brand-600 to-brand-800 text-white font-bold text-sm flex items-center justify-center shadow-inner">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </div>
                                @endif
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="p-5 text-center">
                                <!-- Email Header -->
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 truncate max-w-[260px] mx-auto mb-3">
                                    {{ Auth::user()->email }}
                                </div>

                                <!-- Large Centered Avatar with Camera Badge -->
                                <div class="relative inline-block mx-auto mb-3">
                                    @if(Auth::user()->profile_photo_url)
                                        <img class="w-20 h-20 rounded-full object-cover ring-4 ring-brand-100 dark:ring-brand-500/30 shadow-md mx-auto" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}">
                                    @else
                                        <div class="w-20 h-20 rounded-full bg-gradient-to-tr from-brand-600 to-brand-800 text-white font-bold text-3xl flex items-center justify-center ring-4 ring-brand-100 dark:ring-brand-500/30 shadow-md mx-auto">
                                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <a href="{{ route('profile.edit') }}" title="{{ __('Change Photo') }}" class="absolute bottom-0 end-0 p-1.5 bg-white dark:bg-gray-700 rounded-full shadow-md border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition text-gray-600 dark:text-gray-200">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                                        </svg>
                                    </a>
                                </div>

                                <!-- Greeting / User Name -->
                                <div class="text-base font-bold text-gray-900 dark:text-white leading-tight">
                                    {{ __('Hi, :name!', ['name' => Auth::user()->name]) }}
                                </div>

                                <!-- Role & Status Badges -->
                                <div class="mt-2 flex items-center justify-center gap-2 flex-wrap">
                                    @if(Auth::user()->isSuperAdmin() || Auth::user()->hasRole('Super-Admin'))
                                        <x-badge variant="warning">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 0 1 2.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0 1 10 1.944ZM11 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm0-7a1 1 0 1 0-2 0v3a1 1 0 1 0 2 0V7Z" clip-rule="evenodd" />
                                            </svg>
                                            {{ __('Super-Admin') }}
                                        </x-badge>
                                    @elseif(Auth::user()->roles->isNotEmpty())
                                        @foreach(Auth::user()->roles as $role)
                                            <x-badge variant="info">
                                                {{ $role->name }}
                                            </x-badge>
                                        @endforeach
                                    @endif

                                   
                                </div>

                                <!-- Iconic Google Pill Button: "Manage your Account" -->
                                <div class="mt-4">
                                    <a href="{{ route('profile.edit') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2 border border-gray-300 dark:border-gray-600 rounded-full text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition shadow-sm">
                                        <svg class="w-3.5 h-3.5 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                        </svg>
                                        <span>{{ __('Manage your Account') }}</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Footer: Log Out (Google Style) -->
                            <div class="border-t border-gray-100 dark:border-gray-700/80 p-2 bg-gray-50/50 dark:bg-gray-800/50">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/20 hover:text-rose-700 dark:hover:text-rose-300 transition">
                                        <svg class="w-4 h-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                        </svg>
                                        <span>{{ __('Log Out') }}</span>
                                    </button>
                                </form>
                            </div>
                        </x-slot>
                    </x-dropdown>
                </div>

                <!-- Hamburger -->
                <div class="-me-2 flex items-center sm:hidden">
                    <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Workspace') }}
            </x-responsive-nav-link>

            @can('view metrology')
            <x-responsive-nav-link :href="route('dashboard_metrology')" :active="request()->routeIs('dashboard_metrology') || request()->routeIs('metrology.*')">
                {{ __('Metrology') }}
            </x-responsive-nav-link>
            @endcan

            @can('view operations')
            <x-responsive-nav-link :href="route('dashboard_operations')" :active="request()->routeIs('dashboard_operations') || request()->routeIs('operations.*')">
                {{ __('Operations') }}
            </x-responsive-nav-link>
            @endcan

            @can('view analytics')
            <x-responsive-nav-link :href="route('dashboard_analytics')" :active="request()->routeIs('dashboard_analytics') || request()->routeIs('analytics.*')">
                {{ __('Analytics') }}
            </x-responsive-nav-link>
            @endcan

            @can('view financial')
            <x-responsive-nav-link :href="route('dashboard_financial')" :active="request()->routeIs('dashboard_financial') || request()->routeIs('financial.*')">
                {{ __('Financial') }}
            </x-responsive-nav-link>
            @endcan

            @can('view master data')
            <x-responsive-nav-link :href="route('dashboard_master_data')" :active="request()->routeIs('dashboard_master_data') || request()->routeIs('master-data.*')">
                {{ __('Master Data') }}
            </x-responsive-nav-link>
            @endcan

            @if(Auth::user()?->isSuperAdmin() || Auth::user()?->hasRole('Super-Admin'))
                <x-responsive-nav-link :href="route('system-tables.index')" :active="request()->routeIs('system-tables.*')">
                    {{ __('System Control') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="py-3 px-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2">
            <x-theme-switcher />
            <x-language-switcher />
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-700">
            <div class="px-4 flex items-center gap-3">
                @if(Auth::user()->profile_photo_url)
                    <img class="h-10 w-10 rounded-full object-cover ring-2 ring-gray-200 dark:ring-gray-700" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}">
                @else
                    <div class="h-10 w-10 rounded-full bg-gradient-to-tr from-brand-600 to-brand-800 text-white font-bold text-sm flex items-center justify-center ring-2 ring-gray-200 dark:ring-gray-700">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500 dark:text-gray-400">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
