{{-- resources/views/metrology/reports/partials/switcher.blade.php --}}
@props(['active' => 'hub'])

@php
    $activeTab = $active ?? 'hub';
    $tabs = [
        'hub' => [
            'name' => __('Reports Hub'),
            'route' => route('metrology.reports.index'),
            'tool' => 'reports',
        ],
        'instruments' => [
            'name' => __('Instruments Reports Table'),
            'route' => route('metrology.reports.report-instruments.index'),
            'tool' => 'instruments',
        ],
        'prover' => [
            'name' => __('Provers Reports Table'),
            'route' => route('metrology.reports.report-prover.index'),
            'tool' => 'prover',
        ],
        'chromatograph' => [
            'name' => __('Chromatographs Reports Table'),
            'route' => route('metrology.reports.report-chromatograph.index'),
            'tool' => 'chromatograph',
        ],
    ];
@endphp

<!-- Specialized Dashboards Switcher (Unified 4-Tab Suite) -->
<div class="rounded-xl bg-white dark:bg-gray-800 p-2 shadow-sm border border-gray-100 dark:border-gray-700/60 flex flex-wrap gap-2">
    @foreach($tabs as $key => $tab)
        @php
            $isActive = ($activeTab === $key);
        @endphp
        <a href="{{ $tab['route'] }}"
           class="flex-1 min-w-[170px] flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-xs transition duration-150 {{ $isActive ? 'font-bold bg-brand-600 text-white shadow-sm' : 'font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white' }}">
            <x-tool-icon :name="$tab['tool']" class="w-4 h-4 shrink-0" />
            <span>{{ $tab['name'] }}{{ $isActive ? ' (' . __('Active') . ')' : '' }}</span>
        </a>
    @endforeach
</div>
