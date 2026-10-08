<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('metrology.reports.report-instruments.index') }}"
                   class="p-2.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition shadow-2xs">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <x-tool-icon name="instruments" class="w-12 h-12 sm:w-14 sm:h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ __('Measuring Instruments Report') }} &bull; <span dir="ltr" class="font-mono text-brand-600 dark:text-brand-400">{{ $report->report_number }}</span>
                        </h2>
                        <x-badge variant="neutral" size="sm">{{ __('Measuring Instruments') }}</x-badge>
                        @if($report->status === 'completed')
                            <x-badge variant="success" size="sm" :dot="true">{{ __('Completed') }}</x-badge>
                        @else
                            <x-badge variant="warning" size="sm" :dot="true">{{ __('In Progress') }}</x-badge>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex flex-wrap items-center gap-2">
                        <span>{{ $report->mission?->site?->full_name ?? $report->mission?->site?->short_name ?? __('Site Unassigned') }}</span>
                        @if($report->mission)
                            <span>&bull;</span>
                            <span>{{ __('Mission:') }} <strong class="font-mono text-gray-700 dark:text-gray-300">{{ $report->mission->reference ?? $report->mission->code }}</strong></span>
                        @endif
                        @if($report->mission?->client)
                            <span>&bull;</span>
                            <span>{{ $report->mission->client->name }}</span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Header Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                @can('edit reports')
                    <a href="{{ route('metrology.reports.edit', $report->id) }}">
                        <x-warning-button type="button" class="gap-1.5 text-xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>{{ __('Edit Report') }}</span>
                        </x-warning-button>
                    </a>
                @endcan

                <a href="{{ route('metrology.reports.pdf', $report->id) }}" target="_blank">
                    <x-danger-button type="button" class="gap-1.5 text-xs">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
                        <span>{{ __('PDF (EMT)') }}</span>
                    </x-danger-button>
                </a>

                <a href="{{ route('metrology.reports.pdf.summary', $report->id) }}" target="_blank">
                    <x-info-button type="button" class="gap-1.5 text-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>{{ __('Summary PDF') }}</span>
                    </x-info-button>
                </a>

                <a href="{{ route('metrology.reports.report-instruments.index') }}">
                    <x-secondary-button type="button" class="gap-1.5 text-xs">
                        <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>{{ __('Dashboard') }}</span>
                    </x-secondary-button>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-metrology-tabs active="reports" />
                </aside>

                <!-- Main Content Area -->
                <main class="flex-1 w-full min-w-0 space-y-6">

                    <!-- KPI / Statistics 5-Card Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
                        <!-- Total Instruments -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-4 border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Total Instruments') }}</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total'] ?? 0 }}</p>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-500/20 text-brand-600 dark:text-brand-400 flex items-center justify-center shrink-0 border border-brand-500/20">
                                <x-tool-icon name="instruments" class="w-5 h-5 shrink-0" />
                            </div>
                        </div>

                        <!-- Conforme -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-4 border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">{{ __('Compliant') }}</p>
                                <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-300 mt-1">
                                    {{ max(0, ($stats['termines'] ?? 0) - ($stats['non_conformes'] ?? 0)) }}
                                </p>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-100 dark:border-emerald-800/40">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        </div>

                        <!-- Non Conforme -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-4 border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-rose-600 dark:text-rose-400">{{ __('Non-Compliant') }}</p>
                                <p class="text-2xl font-bold text-rose-700 dark:text-rose-300 mt-1">{{ $stats['non_conformes'] ?? 0 }}</p>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 border border-rose-100 dark:border-rose-800/40">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>
                        </div>

                        <!-- En Cours / À Faire -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-4 border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-amber-600 dark:text-amber-400">{{ __('In Progress') }}</p>
                                <p class="text-2xl font-bold text-amber-700 dark:text-amber-300 mt-1">{{ $stats['en_cours'] ?? 0 }}</p>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 border border-amber-100 dark:border-amber-800/40">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>

                        <!-- Progression Rate -->
                        <div class="col-span-2 sm:col-span-1 rounded-xl bg-white dark:bg-gray-800 p-4 border border-gray-100 dark:border-gray-700/60 shadow-xs flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Progress') }}</p>
                                <span class="text-xs font-bold font-mono text-brand-600 dark:text-brand-400">{{ $progressPercentage }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2.5 mt-2 overflow-hidden">
                                <div class="bg-brand-600 dark:bg-brand-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $progressPercentage }}%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- General Report & Session Parameters Card -->
                    <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100 dark:border-gray-700/60">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>{{ __('Report Information & Parameters') }}</span>
                            </h3>
                            <x-badge variant="info" size="sm">{{ __('Legal Metrology OAM / CEI 60751') }}</x-badge>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                            <!-- Report Number -->
                            <div class="p-3 rounded-lg bg-gray-50/70 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                                <span class="text-gray-500 dark:text-gray-400 font-medium block">{{ __('Report Number') }}</span>
                                <span class="text-sm font-bold font-mono text-gray-900 dark:text-white mt-1 block" dir="ltr">{{ $report->report_number }}</span>
                            </div>

                            <!-- Mission Reference -->
                            <div class="p-3 rounded-lg bg-gray-50/70 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                                <span class="text-gray-500 dark:text-gray-400 font-medium block">{{ __('Mission Reference') }}</span>
                                <span class="text-sm font-bold font-mono text-gray-900 dark:text-white mt-1 block" dir="ltr">
                                    {{ $report->mission?->reference ?? $report->mission?->code ?? '---' }}
                                </span>
                            </div>

                            <!-- Site -->
                            <div class="p-3 rounded-lg bg-gray-50/70 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                                <span class="text-gray-500 dark:text-gray-400 font-medium block">{{ __('Site / Installation') }}</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white mt-1 block truncate">
                                    {{ $report->mission?->site?->full_name ?? $report->mission?->site?->short_name ?? '---' }}
                                </span>
                                @if($siteCode = $report->mission?->site?->site_code)
                                    <span class="text-[11px] text-gray-400 font-mono">({{ $siteCode }})</span>
                                @endif
                            </div>

                            <!-- Date Created -->
                            <div class="p-3 rounded-lg bg-gray-50/70 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                                <span class="text-gray-500 dark:text-gray-400 font-medium block">{{ __('Creation Date') }}</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white mt-1 block" dir="ltr">
                                    {{ $report->created_at?->format('d/m/Y') ?? '---' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Verified Instruments Table -->
                    <x-table>
                        <x-slot:toolbar>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 border border-blue-100 dark:border-blue-800/40">
                                        <x-tool-icon name="instruments" class="w-4 h-4 shrink-0" />
                                    </div>
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                        {{ __('Verified Instruments in this Report') }}
                                    </h3>
                                    <x-badge variant="neutral" size="sm">{{ count($appareilsData) }} {{ __('instruments') }}</x-badge>
                                </div>
                            </div>
                        </x-slot:toolbar>

                        <x-slot:header>
                            <x-table.th class="w-12 text-center">#</x-table.th>
                            <x-table.th>{{ __('Tag & Designation') }}</x-table.th>
                            <x-table.th>{{ __('Type') }}</x-table.th>
                            <x-table.th>{{ __('Measuring Range') }}</x-table.th>
                            <x-table.th class="text-center">{{ __('Status') }}</x-table.th>
                            <x-table.th class="text-center">{{ __('Actions') }}</x-table.th>
                        </x-slot:header>

                        @forelse($appareilsData as $app)
                            <x-table.tr>
                                <!-- Sequence Number -->
                                <x-table.td class="text-center font-mono font-semibold text-gray-500">
                                    {{ $app['sequence'] }}
                                </x-table.td>

                                <!-- Tag, Serial & Image -->
                                <x-table.td>
                                    <div class="flex items-center gap-3">
                                        @if(!empty($app['image_url']))
                                            <img src="{{ $app['image_url'] }}"
                                                 alt="{{ $app['tag'] }}"
                                                 class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-gray-700 shrink-0 shadow-2xs" />
                                        @else
                                            <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 flex items-center justify-center shrink-0 text-gray-400">
                                                <x-tool-icon name="instruments" class="w-5 h-5 opacity-60" />
                                            </div>
                                        @endif
                                        <div>
                                            <span class="font-bold text-sm text-gray-900 dark:text-white font-mono block" dir="ltr">
                                                {{ $app['tag'] }}
                                            </span>
                                            <span class="text-[11px] text-gray-500 dark:text-gray-400 font-mono block" dir="ltr">
                                                S/N: {{ $app['serial'] ?? '---' }}
                                            </span>
                                        </div>
                                    </div>
                                </x-table.td>

                                <!-- Type -->
                                <x-table.td>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 dark:bg-gray-700/60 text-gray-700 dark:text-gray-300">
                                        {{ $app['type'] }}
                                    </span>
                                </x-table.td>

                                <!-- Plage de mesure -->
                                <x-table.td class="font-mono text-xs" dir="ltr">
                                    {{ $app['plage'] }}
                                </x-table.td>

                                <!-- Metrological Status Badge -->
                                <x-table.td class="text-center">
                                    @if($app['status_label'] === 'Conforme')
                                        <x-badge variant="success" size="sm" :dot="true">{{ __('Compliant') }}</x-badge>
                                    @elseif($app['status_label'] === 'Non conforme')
                                        <x-badge variant="danger" size="sm" :dot="true">{{ __('Non-Compliant') }}</x-badge>
                                    @elseif($app['status_label'] === 'En cours')
                                        <x-badge variant="warning" size="sm" :dot="true">{{ __('In Progress') }}</x-badge>
                                    @else
                                        <x-badge variant="neutral" size="sm" :dot="true">{{ __('To Do') }}</x-badge>
                                    @endif
                                </x-table.td>

                                <!-- Actions -->
                                <x-table.td class="text-center">
                                    <x-table.actions class="justify-center">
                                        <!-- Calibration Saisie Entry -->
                                        <x-table.action-edit
                                            href="{{ route('metrology.reports.saisie', ['report' => $report->id, 'instrument' => $app['id']]) }}"
                                            title="{{ __('Enter or Edit Verification Data') }}" />

                                        <!-- Error Curve -->
                                        <x-table.action
                                            type="view"
                                            href="{{ route('metrology.reports.curve', ['report' => $report->id, 'instrument' => $app['id']]) }}"
                                            title="{{ __('View Error Curve') }}"
                                            class="!bg-brand-500/10 !text-brand-700 !border-brand-500/20 hover:!bg-brand-600 hover:!text-white dark:!bg-brand-500/20 dark:!text-brand-300 dark:hover:!bg-brand-600 dark:hover:!text-white">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                                        </x-table.action>
                                    </x-table.actions>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty :colspan="6" :message="__('No instruments assigned to this report yet.')" />
                        @endforelse
                    </x-table>

                </main>
            </div>
        </div>
    </div>
</x-app-layout>
