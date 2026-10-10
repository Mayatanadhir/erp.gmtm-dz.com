{{-- resources/views/metrology/reports/report-chromatograph/partials/domain_card.blade.php --}}
<!-- 2. Gas Chromatograph Reports (report-chromatograph) -->
<div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60 flex flex-col justify-between">
    <div>
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <x-tool-icon name="chromatograph" class="w-10 h-10 shrink-0" />
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Gas Chromatograph (GC) Reports') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Natural Gas Quality & Energy Characterization') }}</p>
                </div>
            </div>
            <x-badge variant="info" size="sm">{{ __('Chromatographs') }}</x-badge>
        </div>
        <div class="flex flex-wrap gap-1.5 mb-3">
            <x-badge variant="neutral" size="sm">OIML R 140 / ISO 6974</x-badge>
            <x-badge variant="neutral" size="sm">Molar % (C1-C6+)</x-badge>
            <x-badge variant="neutral" size="sm">Gross Heating Value (PCS)</x-badge>
            <x-badge variant="neutral" size="sm">Wobbe Index</x-badge>
        </div>
        <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
            {{ __('Detailed analytical verification reports for on-line process gas chromatographs (CPG). Validates response factors using certified reference gas bottles, verifies component repeatability, and computes physical gas properties.') }}
        </p>
    </div>
    <div class="flex flex-wrap gap-2 pt-3 border-t border-gray-100 dark:border-gray-700/60">
        <a href="{{ route('metrology.reports.report-chromatograph.index') }}">
            <x-primary-button type="button" class="gap-1.5 text-xs py-2 px-3">
                <span>{{ __('Chromatographs Table') }}</span>
                <span class="rtl:rotate-180">&rarr;</span>
            </x-primary-button>
        </a>
    </div>
</div>
