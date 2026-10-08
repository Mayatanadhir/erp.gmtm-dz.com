<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-tool-icon name="module-analytics" class="w-14 h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Dashboard Analytics') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Annual forecasts, performance statistics, and executive reports') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="info" size="md" :dot="true">
                    {{ __('Internal and Analytical Management') }}
                </x-badge>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0" aria-label="{{ __('Analytics navigation') }}">
                    <x-analytics-tabs active="index" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-6">
                    @canany(['view annual forecasts', 'view company statistics'])
                    <!-- Metrics Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Annual Forecasts Card -->
                        @can('view annual forecasts')
                        <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md flex flex-col justify-between">
                            <div>
                                <div class="flex items-start justify-between gap-4 mb-4">
                                    <div class="flex items-center gap-3">
                                        <x-tool-icon name="forecasts" class="w-12 h-12 shrink-0 transition-transform duration-200 group-hover:scale-105" />
                                        <div>
                                            <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Annual Forecasts') }}</h3>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Projections & Planning') }}</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-medium text-brand-700 dark:text-brand-400 uppercase tracking-wider">{{ __('Forecasts') }}</span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                                    {{ __('Monitor annual budgetary forecasts and financial projections for strategic planning.') }}
                                </p>
                            </div>
                            <div class="pt-4 border-t border-gray-100 dark:border-gray-700/60">
                                <a href="{{ route('analytics.forecasts') }}"
                                   aria-label="{{ __('Open :name', ['name' => __('Annual Forecasts')]) }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-brand-600 hover:bg-brand-700 dark:bg-brand-700 dark:hover:bg-brand-600 text-white shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800">
                                    <span>{{ __('Annual Forecasts') }}</span>
                                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                </a>
                            </div>
                        </div>
                        @endcan

                        <!-- Company Statistics Card -->
                        @can('view company statistics')
                        <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md flex flex-col justify-between">
                            <div>
                                <div class="flex items-start justify-between gap-4 mb-4">
                                    <div class="flex items-center gap-3">
                                        <x-tool-icon name="statistics" class="w-12 h-12 shrink-0 transition-transform duration-200 group-hover:scale-105" />
                                        <div>
                                            <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Company Statistics') }}</h3>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Executive Statistics & KPI') }}</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-medium text-brand-700 dark:text-brand-400 uppercase tracking-wider">{{ __('Key Metrics') }}</span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                                    {{ __('Inspect global enterprise performance indicators and operational metrics for executive management.') }}
                                </p>
                            </div>
                            <div class="pt-4 border-t border-gray-100 dark:border-gray-700/60">
                                <a href="{{ route('analytics.statistics') }}"
                                   aria-label="{{ __('Open :name', ['name' => __('Company Statistics')]) }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-brand-600 hover:bg-brand-700 dark:bg-brand-700 dark:hover:bg-brand-600 text-white shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800">
                                    <span>{{ __('Company Statistics') }}</span>
                                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                </a>
                            </div>
                        </div>
                        @endcan
                    </div>
                    @else
                    <!-- Fallback if no analytical entity permissions granted -->
                    <div class="rounded-xl bg-white dark:bg-gray-800 p-8 text-center border border-gray-100 dark:border-gray-700/60 shadow-sm" role="status">
                        <div class="w-12 h-12 mx-auto rounded-full bg-brand-600/10 dark:bg-brand-600/20 text-brand-700 dark:text-brand-400 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <h4 class="text-base font-bold text-gray-900 dark:text-white">{{ __('No Accessible Explorers') }}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-md mx-auto">
                            {{ __('You do not currently have permissions to view any analytical entities in this module. Contact your system administrator to grant the required permissions.') }}
                        </p>
                    </div>
                    @endcanany
                </main>
            </div>
        </div>
    </div>
</x-app-layout>
