{{-- resources/views/metrology/reports/partials/all_reports_table.blade.php --}}
<x-table>
    <x-slot:toolbar>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 w-full">
            <div class="flex items-center gap-2.5">
                <x-tool-icon name="reports" class="w-6 h-6 shrink-0" />
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('All Verification & Calibration Reports') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Consolidated global overview of all metrological domains') }}</p>
                </div>
            </div>

            <!-- Search Form -->
            <form method="GET" action="{{ route('metrology.reports.index') }}" class="flex items-center gap-2">
                <div class="relative">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="{{ __('Search reference...') }}"
                           class="text-xs rounded-lg border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white pl-8 pr-3 py-1.5 focus:border-brand-500 focus:ring-brand-500">
                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                @if(request('search') || request('status') || request('mission_id'))
                    <a href="{{ route('metrology.reports.index') }}"
                       class="text-xs text-rose-600 hover:underline">
                        {{ __('Clear') }}
                    </a>
                @endif
            </form>
        </div>
    </x-slot:toolbar>

    <x-slot:header>
        <x-table.th>{{ __('Reference') }}</x-table.th>
        <x-table.th>{{ __('Classification') }}</x-table.th>
        <x-table.th>{{ __('Status') }}</x-table.th>
        <x-table.th>{{ __('Instrument / Site') }}</x-table.th>
        <x-table.th>{{ __('Calibrators') }}</x-table.th>
        <x-table.th>{{ __('EMT Verdict') }}</x-table.th>
        <x-table.th>{{ __('Execution Date') }}</x-table.th>
        <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
    </x-slot:header>

    @forelse($reports as $report)
        <tr>
            <x-table.td class="font-semibold text-gray-900 dark:text-white">
                <a href="{{ route('metrology.reports.show', $report->id) }}" class="text-brand-700 dark:text-brand-400 hover:underline font-mono">
                    {{ $report->report_number }}
                </a>
            </x-table.td>
            <x-table.td>
                <x-badge :variant="$report->category_badge_variant" size="sm">
                    <x-tool-icon :name="$report->category" class="w-3.5 h-3.5 inline mr-1" />
                    {{ $report->category_label }}
                </x-badge>
            </x-table.td>
            <x-table.td>
                <x-badge :variant="$report->status === 'completed' ? 'success' : 'warning'" size="sm" :dot="true">
                    {{ $report->status_label }}
                </x-badge>
            </x-table.td>
            <x-table.td>
                <div class="text-xs text-gray-900 dark:text-white font-medium">
                    {{ $report->mission?->site?->name ?? __('Unassigned Site') }}
                </div>
                @if($report->mission)
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $report->mission->reference }}
                    </div>
                @endif
            </x-table.td>
            <x-table.td>
                <span class="text-xs text-gray-600 dark:text-gray-300">
                    {{ count($report->default_calibrators ?? []) }} {{ __('Configured') }}
                </span>
            </x-table.td>
            <x-table.td>
                <x-badge :variant="$report->compliance_verdict['variant']" size="sm" :dot="true">
                    {{ $report->compliance_verdict['label'] }}
                </x-badge>
            </x-table.td>
            <x-table.td class="text-xs text-gray-500 dark:text-gray-400">
                {{ $report->created_at?->format('Y-m-d') ?? '-' }}
            </x-table.td>
            <x-table.td class="text-end">
                <x-table.actions>
                    <x-table.action-view href="{{ route('metrology.reports.show', $report->id) }}" />
                    <x-table.action-pdf href="{{ route('metrology.reports.pdf', $report->id) }}" target="_blank" />
                    @can('edit reports')
                        <x-table.action-edit href="{{ route('metrology.reports.edit', $report->id) }}" />
                    @endcan
                </x-table.actions>
            </x-table.td>
        </tr>
    @empty
        <x-table.empty :colspan="8" :message="__('No reports found in this category. Click above to initialize a new report.')" />
    @endforelse
</x-table>

@if($reports->hasPages())
    <div class="mt-4">
        {{ $reports->links() }}
    </div>
@endif
