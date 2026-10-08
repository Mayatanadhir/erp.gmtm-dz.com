<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-tool-icon name="module-metrology" class="w-14 h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Dashboard Metrology') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Comprehensive overview of measuring instruments, equipment, calibrator movements, and calibration certificates') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="info" size="md" :dot="true"> 
                    {{ __('Metrology & Equipments') }}
                </x-badge>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-metrology-tabs active="index" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-6">
                    @canany(['view measuring instruments', 'view equipment', 'view calibrator movements', 'view calibration certificates', 'view quantities units', 'view reports'])
                    <!-- Metrics Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                        <!-- Measuring Instruments Card -->
                        @can('view measuring instruments')
                        <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Measuring Instruments') }}</p>
                                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">0</h3>
                                    <p class="mt-1 text-xs text-brand-700 dark:text-brand-400 font-medium">{{ __('Catalog Active') }}</p>
                                </div>
                                <x-tool-icon name="instruments" class="w-12 h-12 shrink-0 transition-transform duration-200 group-hover:scale-105" />
                            </div>
                            <div class="mt-4 border-t border-gray-100 dark:border-gray-700/60 pt-3">
                                <a href="{{ route('metrology.instruments') }}" class="inline-flex items-center text-xs font-semibold text-brand-700 dark:text-brand-400 hover:underline">
                                    <span>{{ __('View Explorer') }}</span> &rarr;
                                </a>
                            </div>
                        </div>
                        @endcan

                        <!-- Equipment Card -->
                        @can('view equipment')
                        <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Equipment') }}</p>
                                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">0</h3>
                                    <p class="mt-1 text-xs text-brand-700 dark:text-brand-400 font-medium">{{ __('Operational') }}</p>
                                </div>
                                <x-tool-icon name="equipment" class="w-12 h-12 shrink-0 transition-transform duration-200 group-hover:scale-105" />
                            </div>
                            <div class="mt-4 border-t border-gray-100 dark:border-gray-700/60 pt-3">
                                <a href="{{ route('metrology.equipment') }}" class="inline-flex items-center text-xs font-semibold text-brand-700 dark:text-brand-400 hover:underline">
                                    <span>{{ __('View Explorer') }}</span> &rarr;
                                </a>
                            </div>
                        </div>
                        @endcan

                        <!-- Calibrator Movements Card -->
                        @can('view calibrator movements')
                        <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Calibrator Movements') }}</p>
                                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">0</h3>
                                    <p class="mt-1 text-xs text-brand-700 dark:text-brand-400 font-medium">{{ __('Tracking') }}</p>
                                </div>
                                <x-tool-icon name="calibrator-movements" class="w-12 h-12 shrink-0 transition-transform duration-200 group-hover:scale-105" />
                            </div>
                            <div class="mt-4 border-t border-gray-100 dark:border-gray-700/60 pt-3">
                                <a href="{{ route('metrology.calibrator-movements') }}" class="inline-flex items-center text-xs font-semibold text-brand-700 dark:text-brand-400 hover:underline">
                                    <span>{{ __('View Explorer') }}</span> &rarr;
                                </a>
                            </div>
                        </div>
                        @endcan

                        <!-- Calibration Certificates Card -->
                        @can('view calibration certificates')
                        <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Calibration Certificates') }}</p>
                                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">0</h3>
                                    <p class="mt-1 text-xs text-brand-700 dark:text-brand-400 font-medium">{{ __('Certifications') }}</p>
                                </div>
                                <x-tool-icon name="calibration-certificates" class="w-12 h-12 shrink-0 transition-transform duration-200 group-hover:scale-105" />
                            </div>
                            <div class="mt-4 border-t border-gray-100 dark:border-gray-700/60 pt-3">
                                <a href="{{ route('metrology.calibration-certificates') }}" class="inline-flex items-center text-xs font-semibold text-brand-700 dark:text-brand-400 hover:underline">
                                    <span>{{ __('View Explorer') }}</span> &rarr;
                                </a>
                            </div>
                        </div>
                        @endcan

                        <!-- Quantities & Units Card -->
                        @can('view quantities units')
                        <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Quantities & Units') }}</p>
                                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">0</h3>
                                    <p class="mt-1 text-xs text-brand-700 dark:text-brand-400 font-medium">{{ __('Standards') }}</p>
                                </div>
                                <x-tool-icon name="units" class="w-12 h-12 shrink-0 transition-transform duration-200 group-hover:scale-105" />
                            </div>
                            <div class="mt-4 border-t border-gray-100 dark:border-gray-700/60 pt-3">
                                <a href="{{ route('metrology.units') }}" class="inline-flex items-center text-xs font-semibold text-brand-700 dark:text-brand-400 hover:underline">
                                    <span>{{ __('View Explorer') }}</span> &rarr;
                                </a>
                            </div>
                        </div>
                        @endcan

                        <!-- Reports Management Card -->
                        @can('view reports')
                        <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Reports Management') }}</p>
                                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">0</h3>
                                    <p class="mt-1 text-xs text-brand-700 dark:text-brand-400 font-medium">{{ __('Documents Ready') }}</p>
                                </div>
                                <x-tool-icon name="reports" class="w-12 h-12 shrink-0 transition-transform duration-200 group-hover:scale-105" />
                            </div>
                            <div class="mt-4 border-t border-gray-100 dark:border-gray-700/60 pt-3">
                                <a href="{{ route('metrology.reports') }}" class="inline-flex items-center text-xs font-semibold text-brand-700 dark:text-brand-400 hover:underline">
                                    <span>{{ __('View Explorer') }}</span> &rarr;
                                </a>
                            </div>
                        </div>
                        @endcan
                    </div>

                    <!-- Module Overview & Quick Access -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Instruments & Equipment Explorer Card -->
                        @canany(['view measuring instruments', 'view equipment'])
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                <x-tool-icon name="instruments" class="w-12 h-12 shrink-0" />
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Instruments & Devices') }}</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Inventory and operational tracking') }}</p>
                                </div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                                {{ __('Inspect measurement tools, technical specifications, and device lifecycle states across all project sites.') }}
                            </p>
                            <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100 dark:border-gray-700/60">
                                @can('view measuring instruments')
                                <a href="{{ route('metrology.instruments') }}">
                                    <x-primary-button type="button" class="text-xs">
                                        {{ __('Measuring Instruments') }}
                                    </x-primary-button>
                                </a>
                                @endcan
                                @can('view equipment')
                                <a href="{{ route('metrology.equipment') }}">
                                    <x-primary-button type="button" class="text-xs">
                                        {{ __('Equipment') }}
                                    </x-primary-button>
                                </a>
                                @endcan
                            </div>
                        </div>
                        @endcanany

                        <!-- Calibration & Quality Assurance Card -->
                        @canany(['view calibrator movements', 'view calibration certificates', 'view reports'])
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                <x-tool-icon name="calibration-certificates" class="w-12 h-12 shrink-0" />
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Quality & Verification') }}</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Audit compliance and calibration logs') }}</p>
                                </div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                                {{ __('Track calibrator transfers between operational sites and verify official calibration certificates for regulatory compliance.') }}
                            </p>
                            <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100 dark:border-gray-700/60">
                                @can('view calibrator movements')
                                <a href="{{ route('metrology.calibrator-movements') }}">
                                    <x-primary-button type="button" class="text-xs">
                                        {{ __('Calibrator Movements') }}
                                    </x-primary-button>
                                </a>
                                @endcan
                                @can('view calibration certificates')
                                <a href="{{ route('metrology.calibration-certificates') }}">
                                    <x-primary-button type="button" class="text-xs">
                                        {{ __('Calibration Certificates') }}
                                    </x-primary-button>
                                </a>
                                @endcan
                                @can('view reports')
                                <a href="{{ route('metrology.reports') }}">
                                    <x-primary-button type="button" class="text-xs">
                                        {{ __('Reports Management') }}
                                    </x-primary-button>
                                </a>
                                @endcan
                            </div>
                        </div>
                        @endcanany
                    </div>
                    @else
                    <!-- Fallback if no metrological entity permissions granted -->
                    <div class="rounded-xl bg-white dark:bg-gray-800 p-8 text-center border border-gray-100 dark:border-gray-700/60 shadow-sm">
                        <div class="w-12 h-12 mx-auto rounded-full bg-brand-500/10 dark:bg-brand-500/20 text-brand-700 dark:text-brand-400 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <h4 class="text-base font-bold text-gray-900 dark:text-white">{{ __('No Accessible Explorers') }}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-md mx-auto">
                            {{ __('You do not currently have permissions to view any metrological entities in this module. Contact your system administrator to grant the required permissions.') }}
                        </p>
                    </div>
                    @endcanany
                </main>
            </div>
        </div>
    </div>
</x-app-layout>
