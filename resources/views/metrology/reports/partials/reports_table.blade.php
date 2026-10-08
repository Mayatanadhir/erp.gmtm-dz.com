{{-- resources/views/metrology/reports/partials/reports_table.blade.php --}}
@php
    $currentCategory = $selectedCategory ?? request('category', 'all');
    if (empty($currentCategory)) {
        $currentCategory = 'all';
    }
@endphp

<div class="space-y-4">
    <!-- Category Separation & Filtering Tabs -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white dark:bg-gray-800 p-2.5 rounded-xl border border-gray-100 dark:border-gray-700/60 shadow-sm">
        <div class="flex flex-wrap items-center gap-1.5">
            <!-- All Reports Tab -->
            <a href="{{ route('metrology.reports.index', array_filter(array_merge(request()->query(), ['category' => null, 'page' => null]))) }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold transition-colors duration-150 {{ $currentCategory === 'all' ? 'bg-brand-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white' }}">
                <span>{{ __('Tous les Rapports') }}</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $currentCategory === 'all' ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                    {{ $totalCount ?? 0 }}
                </span>
            </a>

            <!-- Measuring Instruments Tab -->
            <a href="{{ route('metrology.reports.index', array_filter(array_merge(request()->query(), ['category' => 'instruments', 'page' => null]))) }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold transition-colors duration-150 {{ $currentCategory === 'instruments' ? 'bg-brand-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white' }}">
                <x-tool-icon name="instruments" class="w-3.5 h-3.5 shrink-0" />
                <span>{{ __('Instruments de Mesure') }}</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $currentCategory === 'instruments' ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                    {{ $instrumentsReportsCount ?? 0 }}
                </span>
            </a>

            <!-- Standard Provers Tab -->
            <a href="{{ route('metrology.reports.index', array_filter(array_merge(request()->query(), ['category' => 'prover', 'page' => null]))) }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold transition-colors duration-150 {{ $currentCategory === 'prover' ? 'bg-brand-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white' }}">
                <x-tool-icon name="prover" class="w-3.5 h-3.5 shrink-0" />
                <span>{{ __('Provers & Tubes') }}</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $currentCategory === 'prover' ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                    {{ $proversReportsCount ?? 0 }}
                </span>
            </a>

            <!-- Gas Chromatographs Tab -->
            <a href="{{ route('metrology.reports.index', array_filter(array_merge(request()->query(), ['category' => 'chromatograph', 'page' => null]))) }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold transition-colors duration-150 {{ $currentCategory === 'chromatograph' ? 'bg-brand-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white' }}">
                <x-tool-icon name="chromatograph" class="w-3.5 h-3.5 shrink-0" />
                <span>{{ __('Chromatographes CPG') }}</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $currentCategory === 'chromatograph' ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                    {{ $chromatographsReportsCount ?? 0 }}
                </span>
            </a>
        </div>

        <!-- Action / Create Shortcut -->
        @can('create reports')
        <div class="flex items-center gap-2">
            <a href="{{ route('metrology.reports.create', ['category' => $currentCategory !== 'all' ? $currentCategory : 'instruments']) }}">
                <x-primary-button type="button" class="gap-1.5 text-xs py-2 px-3 shadow-sm">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>
                        @if($currentCategory === 'prover')
                            {{ __('Nouveau Rapport Prover') }}
                        @elseif($currentCategory === 'chromatograph')
                            {{ __('Nouveau Rapport CPG') }}
                        @else
                            {{ __('Nouveau Rapport') }}
                        @endif
                    </span>
                </x-primary-button>
            </a>
        </div>
        @endcan
    </div>

    <!-- Categorized Table Rendering: Delegated Exclusively to Dedicated Category Partials -->
    @if($currentCategory === 'instruments')
        @include('metrology.reports.report-instruments.partials.reports_table')
    @elseif($currentCategory === 'chromatograph')
        @include('metrology.reports.report-chromatograph.partials.reports_table')
    @elseif($currentCategory === 'prover')
        @include('metrology.reports.report-Prover.partials.reports_table')
    @else
        @include('metrology.reports.partials.all_reports_table')
    @endif
</div>
