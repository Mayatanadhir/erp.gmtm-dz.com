{{-- resources/views/metrology/reports/partials/stats_cards.blade.php --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
    <!-- Measuring Instruments Card -->
    <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Measuring Instruments') }}</p>
                <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $instrumentsCount ?? 0 }}</h3>
                <p class="mt-1 text-xs text-brand-700 dark:text-brand-400 font-medium">{{ __('Rapport Transmitters, Probes & ADC') }}</p>
            </div>
            <x-tool-icon name="instruments" class="w-12 h-12 shrink-0 transition-transform duration-200 group-hover:scale-105" />
        </div>
    </div>

    <!-- Gas Chromatographs Card -->
    <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Gas Chromatographs') }}</p>
                <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $chromatographsCount ?? 0 }}</h3>
                <p class="mt-1 text-xs text-brand-700 dark:text-brand-400 font-medium">{{ __('rapport CPG & Energy Analysis') }}</p>
            </div>
            <x-tool-icon name="chromatograph" class="w-12 h-12 shrink-0 transition-transform duration-200 group-hover:scale-105" />
        </div>
    </div>

    <!-- Standard Provers Card -->
    <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Standard Provers') }}</p>
                <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $proversCount ?? 0 }}</h3>
                <p class="mt-1 text-xs text-brand-700 dark:text-brand-400 font-medium">{{ __('rapport Mastering Gas & Provers') }}</p>
            </div>
            <x-tool-icon name="prover" class="w-12 h-12 shrink-0 transition-transform duration-200 group-hover:scale-105" />
        </div>
    </div>

    <!-- Calibration Reports Card -->
    <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Calibration Reports') }}</p>
                <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $totalCount ?? ($reports->total() ?? count($reports)) }}</h3>
                <p class="mt-1 text-xs text-brand-700 dark:text-brand-400 font-medium">{{ __('Documents Ready') }}</p>
            </div>
            <x-tool-icon name="reports" class="w-12 h-12 shrink-0 transition-transform duration-200 group-hover:scale-105" />
        </div>
        <div class="mt-4 border-t border-gray-100 dark:border-gray-700/60 pt-3">
            <a href="{{ route('metrology.reports.report-instruments.index') }}" class="inline-flex items-center text-xs font-semibold text-brand-700 dark:text-brand-400 hover:underline">
                <span>{{ __('View Explorer') }}</span> &rarr;
            </a>
        </div>
    </div>
</div>
