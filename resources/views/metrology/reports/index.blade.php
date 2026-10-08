<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-tool-icon name="reports" class="w-14 h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Metrology Calibration Reports') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Generate, inspect, and certify calibration reports across field instruments, chromatographs, and standard provers') }}
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-metrology-tabs active="reports" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-6">
                    @canany(['view reports', 'view measuring instruments', 'view equipment', 'view calibration certificates'])

                        <!-- Specialized Dashboards Switcher (Unified 4-Tab Suite) -->
                        @include('metrology.reports.partials.switcher', ['active' => 'hub'])

                        <!-- 1. Metrics & Statistics Cards -->
                        @include('metrology.reports.partials.stats_cards')

                        <!-- 2. Specialized Metrology Domain Explorer Cards -->
                        @include('metrology.reports.partials.domain_cards')

                        <!-- 3. Classified Reports Table with Category Tabs -->
                        @include('metrology.reports.partials.reports_table')

                    @else
                        <!-- Fallback if no metrology permissions granted -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-8 text-center border border-gray-100 dark:border-gray-700/60 shadow-sm">
                            <div class="w-12 h-12 mx-auto rounded-full bg-brand-500/10 dark:bg-brand-500/20 text-brand-700 dark:text-brand-400 flex items-center justify-center mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <h4 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Access Restricted') }}</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-md mx-auto">
                                {{ __('You do not currently have permissions to view metrological reports or instruments. Contact your system administrator to request access.') }}
                            </p>
                        </div>
                    @endcanany
                </main>
            </div>
        </div>
    </div>
</x-app-layout>
