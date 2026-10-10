{{-- resources/views/metrology/reports/report-Prover/partials/domain_card.blade.php --}}
<!-- 3. Standard Provers & Test Measures Reports (report-Prover) -->
<div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60 flex flex-col justify-between">
    <div>
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <x-tool-icon name="prover" class="w-10 h-10 shrink-0" />
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Standard Prover & Volume Reports') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Volumetric & Gravimetric Prover Standards') }}</p>
                </div>
            </div>
            <x-badge variant="info" size="sm">{{ __('Provers') }}</x-badge>
        </div>
        <div class="flex flex-wrap gap-1.5 mb-3">
            <x-badge variant="neutral" size="sm">Mastering Gas MG-1000</x-badge>
            <x-badge variant="neutral" size="sm">Base Volume (V0)</x-badge>
            <x-badge variant="neutral" size="sm">Repeatability ≤ 0.05%</x-badge>
            <x-badge variant="neutral" size="sm">Meter Factor (MF)</x-badge>
        </div>
        <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
            {{ __('Field calibration and recalibration certificates for high-pressure prover vessels and standard test measures. Performs thermal expansion, pressure elasticity corrections, and detector switch repeatability validation.') }}
        </p>
    </div>
    <div class="flex flex-wrap gap-2 pt-3 border-t border-gray-100 dark:border-gray-700/60">
        <a href="{{ route('metrology.reports.report-prover.index') }}">
            <x-primary-button type="button" class="gap-1.5 text-xs py-2 px-3">
                <span>{{ __('Provers Table') }}</span>
                <span class="rtl:rotate-180">&rarr;</span>
            </x-primary-button>
        </a>
    </div>
</div>
