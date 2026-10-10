{{-- resources/views/metrology/reports/report-instruments/partials/domain_card.blade.php --}}
<!-- 1. Measuring Instruments Reports (report-instruments) -->
<div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60 flex flex-col justify-between">
    <div>
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <x-tool-icon name="instruments" class="w-10 h-10 shrink-0" />
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Measuring Instruments Reports') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Transmitters, Pt100 RTD Probes & Flow Computers') }}</p>
                </div>
            </div>
            <x-badge variant="info" size="sm">{{ __('Instruments') }}</x-badge>
        </div>
        <div class="flex flex-wrap gap-1.5 mb-3">
            <x-badge variant="neutral" size="sm">PT / TT / PDT</x-badge>
            <x-badge variant="neutral" size="sm">Pt100 (IEC 60751)</x-badge>
            <x-badge variant="neutral" size="sm">ADC 4-20mA / 1-5V</x-badge>
            <x-badge variant="success" size="sm" :dot="true">{{ __('EMT Verdict') }}</x-badge>
        </div>
        <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
            {{ __('Generate official calibration reports for field industrial loops. Automatically computes errors across 5 reference setpoints, checks maximum permissible error (EMT) conformance, and renders SVG calibration curves.') }}
        </p>
    </div>
    <div class="flex flex-wrap gap-2 pt-3 border-t border-gray-100 dark:border-gray-700/60">
        <a href="{{ route('metrology.reports.report-instruments.index') }}">
            <x-primary-button type="button" class="gap-1.5 text-xs py-2 px-3">
                <span>{{ __('Instruments Table') }}</span>
                <span class="rtl:rotate-180">&rarr;</span>
            </x-primary-button>
        </a>
    </div>
</div>
