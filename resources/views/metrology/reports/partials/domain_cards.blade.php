{{-- resources/views/metrology/reports/partials/domain_cards.blade.php --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- 1. Measuring Instruments Reports Domain Card -->
    @include('metrology.reports.report-instruments.partials.domain_card')

    <!-- 2. Gas Chromatograph Reports Domain Card -->
    @include('metrology.reports.report-chromatograph.partials.domain_card')

    <!-- 3. Standard Provers & Test Measures Domain Card -->
    @include('metrology.reports.report-prover.partials.domain_card')
</div>
