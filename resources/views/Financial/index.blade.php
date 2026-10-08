<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-tool-icon name="expenses" class="w-14 h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Financial Management') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Bank guarantees, expenses, and financial charges tracking') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="success" size="md" :dot="true">
                    {{ __('Financial Management') }}
                </x-badge>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-financial-tabs active="index" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-6">
                    @canany(['view warranties', 'view expenses'])
                    <!-- Metrics Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                        <!-- Bank Guarantees Card -->
                        @can('view warranties')
                        <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Bank Guarantees') }}</p>
                                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">0</h3>
                                    <p class="mt-1 text-xs text-brand-700 dark:text-brand-400 font-medium">{{ __('Guarantee Tracking') }}</p>
                                </div>
                                <x-tool-icon name="warranties" class="w-12 h-12 shrink-0 transition-transform duration-200 group-hover:scale-105" />
                            </div>
                            <div class="mt-4 border-t border-gray-100 dark:border-gray-700/60 pt-3">
                                <a href="{{ route('financial.warranties') }}" class="inline-flex items-center text-xs font-semibold text-brand-700 dark:text-brand-400 hover:underline">
                                    <span>{{ __('View Explorer') }}</span> &rarr;
                                </a>
                            </div>
                        </div>
                        @endcan

                        <!-- Expenses & Charges Card -->
                        @can('view expenses')
                        <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Expenses & Charges') }}</p>
                                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">0</h3>
                                    <p class="mt-1 text-xs text-brand-700 dark:text-brand-400 font-medium">{{ __('Cost Tracking') }}</p>
                                </div>
                                <x-tool-icon name="expenses" class="w-12 h-12 shrink-0 transition-transform duration-200 group-hover:scale-105" />
                            </div>
                            <div class="mt-4 border-t border-gray-100 dark:border-gray-700/60 pt-3">
                                <a href="{{ route('financial.expenses') }}" class="inline-flex items-center text-xs font-semibold text-brand-700 dark:text-brand-400 hover:underline">
                                    <span>{{ __('View Explorer') }}</span> &rarr;
                                </a>
                            </div>
                        </div>
                        @endcan
                    </div>

                    <!-- Module Overview & Quick Access -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Bank Guarantees Card -->
                        @can('view warranties')
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <x-tool-icon name="warranties" class="w-12 h-12 shrink-0" />
                                    <div>
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Bank Guarantees') }}</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Guarantee tracking and management') }}</p>
                                    </div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                                {{ __('Track bank guarantee obligations, coverage periods, and financial commitments') }}
                            </p>
                            <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100 dark:border-gray-700/60">
                                <a href="{{ route('financial.warranties') }}">
                                    <x-primary-button type="button" class="text-xs">
                                        {{ __('Bank Guarantees') }}
                                    </x-primary-button>
                                </a>
                            </div>
                        </div>
                        @endcan

                        <!-- Expenses & Charges Card -->
                        @can('view expenses')
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <x-tool-icon name="expenses" class="w-12 h-12 shrink-0" />
                                    <div>
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Expenses & Charges') }}</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Cost control and financial tracking') }}</p>
                                    </div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                                {{ __('Analyze operational expenses, track financial charges across sites, and monitor budgets') }}
                            </p>
                            <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100 dark:border-gray-700/60">
                                <a href="{{ route('financial.expenses') }}">
                                    <x-primary-button type="button" class="text-xs">
                                        {{ __('Expenses & Charges') }}
                                    </x-primary-button>
                                </a>
                            </div>
                        </div>
                        @endcan
                    </div>
                    @else
                    <!-- Fallback if no financial permissions granted -->
                    <div class="rounded-xl bg-white dark:bg-gray-800 p-8 text-center border border-gray-100 dark:border-gray-700/60 shadow-sm">
                        <div class="w-12 h-12 mx-auto rounded-full bg-brand-600/10 dark:bg-brand-600/20 text-brand-700 dark:text-brand-400 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <h4 class="text-base font-bold text-gray-900 dark:text-white">{{ __('No Accessible Explorers') }}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-md mx-auto">
                            {{ __('You do not currently have permissions to view any financial entities in this module. Contact your system administrator to grant the required permissions') }}
                        </p>
                    </div>
                    @endcanany
                </main>
            </div>
        </div>
    </div>
</x-app-layout>
