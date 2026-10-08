@props(['active' => 'index'])

@php
$tabs = [
    'index' => [
        'name' => __('Overview'),
        'route' => route('dashboard_analytics'),
        'permission' => 'view analytics',
        'tool' => 'overview',
    ],
    'forecasts' => [
        'name' => __('Annual Forecasts'),
        'route' => route('analytics.forecasts'),
        'permission' => 'view annual forecasts',
        'tool' => 'forecasts',
    ],
    'statistics' => [
        'name' => __('Company Statistics'),
        'route' => route('analytics.statistics'),
        'permission' => 'view company statistics',
        'tool' => 'statistics',
    ],
];
@endphp

<div class="rounded-2xl bg-white dark:bg-gray-800 p-3.5 shadow-sm border border-gray-100 dark:border-gray-700/60 sticky top-24">
    <nav class="space-y-1.5" aria-label="{{ __('Internal and Analytical Management') }}">
        @foreach($tabs as $key => $tab)
            @can($tab['permission'])
                @php
                    $isActive = ($active === $key);
                @endphp
                <a href="{{ $tab['route'] }}"
                   class="group flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-medium transition-all duration-200 {{ $isActive ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/20 dark:bg-brand-700 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700/50' }}">
                    <x-tool-icon :name="$tab['tool']" class="w-6 h-6 shrink-0 transition-transform duration-200 group-hover:scale-110" />
                    <span class="truncate">{{ $tab['name'] }}</span>
                </a>
            @endcan
        @endforeach
    </nav>
</div>
