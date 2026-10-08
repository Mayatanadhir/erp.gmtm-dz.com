<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-tool-icon name="module-operations" class="w-14 h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Dashboard Operations') }}
                    </h2> 
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('Monitor and manage missions, contracts, attachments, and article types') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="warning" size="md" :dot="true">
                    {{ __('Operations & Projects') }}
                </x-badge>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-operations-tabs active="index" /> 
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-6">
                    @canany(['view missions', 'view contracts', 'view attachments', 'view article types'])
                    <!-- Metrics Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                        <!-- Mission Management Card -->
                        @can('view missions')
                        <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Mission Management') }}</p>
                                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">0</h3>
                                    <p class="mt-1 text-xs text-brand-700 dark:text-brand-400 font-medium">{{ __('Field Operations') }}</p>
                                </div>
                                <x-tool-icon name="missions" class="w-12 h-12 shrink-0 transition-transform duration-200 group-hover:scale-105" />
                            </div>
                            <div class="mt-4 border-t border-gray-100 dark:border-gray-700/60 pt-3">
                                <a href="{{ route('operations.missions') }}" class="inline-flex items-center text-xs font-semibold text-brand-700 dark:text-brand-400 hover:underline">
                                    <span>{{ __('View Explorer') }}</span> &rarr;
                                </a>
                            </div>
                        </div>
                        @endcan

                        <!-- Contracts Card -->
                        @can('view contracts')
                        <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Contracts Management') }}</p>
                                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">0</h3>
                                    <p class="mt-1 text-xs text-brand-700 dark:text-brand-400 font-medium">{{ __('Active Agreements') }}</p>
                                </div>
                                <x-tool-icon name="contracts" class="w-12 h-12 shrink-0 transition-transform duration-200 group-hover:scale-105" />
                            </div>
                            <div class="mt-4 border-t border-gray-100 dark:border-gray-700/60 pt-3">
                                <a href="{{ route('operations.contracts') }}" class="inline-flex items-center text-xs font-semibold text-brand-700 dark:text-brand-400 hover:underline">
                                    <span>{{ __('View Explorer') }}</span> &rarr;
                                </a>
                            </div>
                        </div>
                        @endcan

                        <!-- Attachments List Card -->
                        @can('view attachments')
                        <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Attachments List') }}</p>
                                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">0</h3>
                                    <p class="mt-1 text-xs text-brand-700 dark:text-brand-400 font-medium">{{ __('Project Files') }}</p>
                                </div>
                                <x-tool-icon name="attachments" class="w-12 h-12 shrink-0 transition-transform duration-200 group-hover:scale-105" />
                            </div>
                            <div class="mt-4 border-t border-gray-100 dark:border-gray-700/60 pt-3">
                                <a href="{{ route('operations.attachments') }}" class="inline-flex items-center text-xs font-semibold text-brand-700 dark:text-brand-400 hover:underline">
                                    <span>{{ __('View Explorer') }}</span> &rarr;
                                </a>
                            </div>
                        </div>
                        @endcan

                        <!-- Article Types Card -->
                        @can('view article types')
                        <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Classification of Articles') }}</p>
                                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">0</h3>
                                    <p class="mt-1 text-xs text-brand-700 dark:text-brand-400 font-medium">{{ __('Classifications') }}</p>
                                </div>
                                <x-tool-icon name="article-types" class="w-12 h-12 shrink-0 transition-transform duration-200 group-hover:scale-105" />
                            </div>
                            <div class="mt-4 border-t border-gray-100 dark:border-gray-700/60 pt-3">
                                <a href="{{ route('operations.article-types') }}" class="inline-flex items-center text-xs font-semibold text-brand-700 dark:text-brand-400 hover:underline">
                                    <span>{{ __('View Explorer') }}</span> &rarr;
                                </a>
                            </div>
                        </div>
                        @endcan
                    </div>

                    <!-- Module Overview & Quick Access -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Missions & Field Activities Card -->
                        @canany(['view missions', 'view attachments'])
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                <x-tool-icon name="missions" class="w-12 h-12 shrink-0" />
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Missions & Interventions') }}</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Operational field mission workflows') }}</p>
                                </div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                                {{ __('Plan, dispatch, and track engineering field missions, team assignments, and operational execution records.') }}
                            </p>
                            <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100 dark:border-gray-700/60">
                                @can('view missions')
                                <a href="{{ route('operations.missions') }}">
                                    <x-primary-button type="button" class="text-xs">
                                        {{ __('Mission Management') }}
                                    </x-primary-button>
                                </a>
                                @endcan
                                @can('view attachments')
                                <a href="{{ route('operations.attachments') }}">
                                    <x-primary-button type="button" class="text-xs">
                                        {{ __('Attachments List') }}
                                    </x-primary-button>
                                </a>
                                @endcan
                            </div>
                        </div>
                        @endcanany

                        <!-- Legal & Classifications Card -->
                        @canany(['view contracts', 'view article types'])
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                <x-tool-icon name="contracts" class="w-12 h-12 shrink-0" />
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Agreements & Classifications') }}</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Contractual obligations and item types') }}</p>
                                </div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                                {{ __('Manage client and partner contracts and maintain operational article type classifications.') }}
                            </p>
                            <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100 dark:border-gray-700/60">
                                @can('view contracts')
                                <a href="{{ route('operations.contracts') }}">
                                    <x-primary-button type="button" class="text-xs">
                                        {{ __('Contracts') }}
                                    </x-primary-button>
                                </a>
                                @endcan
                                @can('view article types')
                                <a href="{{ route('operations.article-types') }}">
                                    <x-primary-button type="button" class="text-xs">
                                        {{ __('Classification of Articles') }}
                                    </x-primary-button>
                                </a>
                                @endcan
                            </div>
                        </div>
                        @endcanany
                    </div>
                    @else
                    <!-- Fallback if no operational entity permissions granted -->
                    <div class="rounded-xl bg-white dark:bg-gray-800 p-8 text-center border border-gray-100 dark:border-gray-700/60 shadow-sm">
                        <div class="w-12 h-12 mx-auto rounded-full bg-amber-500/10 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <h4 class="text-base font-bold text-gray-900 dark:text-white">{{ __('No Accessible Explorers') }}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-md mx-auto">
                            {{ __('You do not currently have permissions to view any operational entities in this module. Contact your system administrator to grant the required permissions.') }}
                        </p>
                    </div>
                    @endcanany
                </main>
            </div>
        </div>
    </div>
</x-app-layout>
