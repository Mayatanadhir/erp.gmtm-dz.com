<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('metrology.reports.show', $report->id) }}"
                   class="p-2.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition shadow-2xs">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <x-tool-icon name="instruments" class="w-12 h-12 sm:w-14 sm:h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ __('Flow Computer Verification Saisie') }} &bull; <span dir="ltr" class="font-mono text-brand-600 dark:text-brand-400">{{ $instrument->tag_number }}</span>
                        </h2>
                        <x-badge variant="neutral" size="sm">{{ __('Flow Computer / ADC') }}</x-badge>
                        @if($report->status === 'completed')
                            <x-badge variant="success" size="sm" :dot="true">{{ __('Completed') }}</x-badge>
                        @else
                            <x-badge variant="warning" size="sm" :dot="true">{{ __('In Progress') }}</x-badge>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex flex-wrap items-center gap-2">
                        <span>{{ __('Report:') }} <strong class="font-mono text-gray-700 dark:text-gray-300">{{ $report->report_number }}</strong></span>
                        <span>&bull;</span>
                        <span>{{ $report->mission?->site?->full_name ?? $report->mission?->site?->short_name ?? __('Site Unassigned') }}</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('metrology.reports.show', $report->id) }}">
                    <x-secondary-button type="button" class="gap-1.5 text-xs">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>{{ __('Back to Report') }}</span>
                    </x-secondary-button>
                </a>
                @if($activeTransmitter)
                    <x-primary-button type="submit" form="calibration-form" class="gap-1.5 text-xs shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        <span>{{ __('Save Channel Data') }}</span>
                    </x-primary-button>
                @endif
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

                    @if(session('success'))
                        <x-alert variant="success" :title="session('success')" :dismissible="true" />
                    @endif

                    @if(isset($errors) && $errors->any())
                        <x-alert variant="danger" :title="__('Please correct the following errors:')" :dismissible="true">
                            <ul class="list-disc list-inside space-y-0.5 mt-2 text-xs">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    @endif

                    <!-- Multi-Channel Selector Tabs -->
                    <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-2">
                            <svg class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <span>{{ __('Associated Measurement Channels & Transmitters') }} ({{ $linkedTransmitters->count() }})</span>
                        </h4>

                        @if($linkedTransmitters->isEmpty())
                            <div class="rounded-lg bg-amber-50 dark:bg-amber-950/40 p-4 border border-amber-200 dark:border-amber-800/60 text-xs text-amber-800 dark:text-amber-300">
                                {{ __('No linked transmitters configured for this flow computer yet.') }}
                            </div>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($linkedTransmitters as $trans)
                                    @php
                                        $isActive = ($activeTransmitter && $activeTransmitter->id == $trans->id);
                                        $chanVerif = $allVerifications->get($trans->id);
                                        $chanPoints = $chanVerif?->points ?? collect();
                                        $hasEmpty = $chanPoints->contains(function ($p) {
                                            return is_null($p->indicated_value) || trim((string)$p->indicated_value) === '';
                                        });
                                        $isChanComplete = ($chanPoints->count() >= 10 && !$hasEmpty);
                                        $isChanConforme = $isChanComplete && $chanPoints->every(fn($p) => (int)$p->is_conforme === 1);
                                        $transSpec = $trans->specifications->first();
                                    @endphp
                                    <a href="{{ route('metrology.reports.saisie', ['report' => $report->id, 'instrument' => $instrument->id, 'transmitter_id' => $trans->id]) }}"
                                       class="p-3.5 rounded-xl border transition flex items-center justify-between gap-3 {{ $isActive ? 'bg-brand-50/70 dark:bg-brand-950/40 border-brand-500 ring-2 ring-brand-500/20 shadow-xs' : 'bg-gray-50/50 dark:bg-gray-900/40 border-gray-200 dark:border-gray-700 hover:border-brand-300' }}">
                                        <div>
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold font-mono {{ $isActive ? 'bg-brand-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                                                    {{ $trans->pivot->channel_number ?? 'Ch' }}
                                                </span>
                                                <span class="font-bold text-xs font-mono text-gray-900 dark:text-white" dir="ltr">
                                                    {{ $trans->tag_number }}
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                                {{ $transSpec?->grandeur?->name ?? 'Mesure' }}
                                                ({{ floatval($transSpec?->range_min ?? 0) }} &rarr; {{ floatval($transSpec?->range_max ?? 0) }} {{ $transSpec?->grandeur?->symbol ?? 'bar' }})
                                            </p>
                                        </div>
                                        <div>
                                            @if($isChanComplete)
                                                @if($isChanConforme)
                                                    <x-badge variant="success" size="sm" :dot="true">{{ __('OK') }}</x-badge>
                                                @else
                                                    <x-badge variant="danger" size="sm" :dot="true">{{ __('NOK') }}</x-badge>
                                                @endif
                                            @elseif($chanPoints->isNotEmpty())
                                                <x-badge variant="warning" size="sm">{{ __('In Progress') }}</x-badge>
                                            @else
                                                <x-badge variant="neutral" size="sm">{{ __('To Do') }}</x-badge>
                                            @endif
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if($activeTransmitter && $channelData)
                        <form id="calibration-form" action="{{ route('metrology.reports.saisie.flow_computer', ['report' => $report->id, 'instrument' => $instrument->id]) }}" method="POST" class="space-y-6">
                            @csrf
                            <input type="hidden" name="transmitter_id" value="{{ $activeTransmitter->id }}">
                            <input type="hidden" id="channel_min" value="{{ $channelData['min'] }}">
                            <input type="hidden" id="channel_max" value="{{ $channelData['max'] }}">
                            <input type="hidden" id="emt_limit" value="{{ $channelData['emt_approved'] }}">
                            <input type="hidden" id="is_relative_emt" value="{{ $channelData['is_relative_emt'] ? '1' : '0' }}">
                            <input type="hidden" id="is_temp" value="{{ $channelData['is_temp'] ? '1' : '0' }}">

                            <!-- Card 1: Channel Info & Standards -->
                            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 mb-5 border-b border-gray-100 dark:border-gray-700/60">
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>{{ __('Channel Parameters & Master Standards') }}</span>
                                    </h3>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-badge variant="info" size="sm">
                                            {{ $activeTransmitter->channel_number ?? 'Ch1' }}: {{ $activeTransmitter->tag_number }} ({{ $channelData['grandeur'] }})
                                        </x-badge>
                                        <x-badge variant="success" size="sm" :dot="true">
                                            {{ __('Approved EMT:') }} ±{{ $channelData['emt_approved'] }} {{ $channelData['emt_display'] }}
                                        </x-badge>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs mb-4">
                                    <!-- Flow Computer Tag -->
                                    <div>
                                        <x-input-label class="text-xs mb-1">{{ __('Flow Computer Tag') }}</x-input-label>
                                        <input type="text" value="{{ $instrument->tag_number }}" disabled
                                               class="w-full text-xs rounded-md border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60 text-gray-900 dark:text-white font-mono font-bold py-2 px-3 cursor-not-allowed" />
                                    </div>

                                    <!-- Serial Number -->
                                    <div>
                                        <x-input-label class="text-xs mb-1">{{ __('Serial Number') }}</x-input-label>
                                        <input type="text" value="{{ $instrument->serial_number }}" disabled
                                               class="w-full text-xs rounded-md border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60 text-gray-600 dark:text-gray-300 font-mono py-2 px-3 cursor-not-allowed" />
                                    </div>

                                    <!-- Verification Date -->
                                    <div>
                                        <x-input-label for="verification_date" class="text-xs mb-1">
                                            {{ __('Verification Date') }} <span class="text-rose-500">*</span>
                                        </x-input-label>
                                        <input type="date" name="verification_date" value="{{ $channelData['verification_date'] }}" required
                                               class="input-base w-full text-xs py-2 px-3 focus:border-brand-600 focus:ring-brand-600" />
                                    </div>

                                    <!-- Shunt Resistance -->
                                    <div>
                                        <x-input-label for="shunt_resistance" class="text-xs mb-1">
                                            {{ __('Shunt Resistance (Ω)') }} <span class="text-rose-500">*</span>
                                        </x-input-label>
                                        <input type="number" step="0.01" name="shunt_resistance" value="{{ $channelData['shunt_resistance'] }}" required
                                               class="input-base w-full text-xs font-mono font-bold py-2 px-3 focus:border-brand-600 focus:ring-brand-600" placeholder="250.00" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs pt-3 border-t border-gray-100 dark:border-gray-700/60">
                                    <!-- Calibrator 1: Current Generator / Multimeter -->
                                    <div>
                                        <x-input-label for="calibrator_1" class="text-xs mb-1">
                                            {{ __('Calibrator 1') }} ({{ __('Current Generator / mA') }})
                                        </x-input-label>
                                        <select name="calibrator_1" id="calibrator_1" class="input-base w-full text-xs py-2 px-3">
                                            <option value="">{{ __('Select standard generator...') }}</option>
                                            @foreach($options1 as $cal)
                                                <option value="{{ $cal->id }}" {{ (string)$selectedCal1 === (string)$cal->id ? 'selected' : '' }}>
                                                    {{ $cal->full_name ?? ($cal->designation ?? ($cal->name ?? $cal->internal_code)) }} ({{ $cal->serial_number ?? $cal->internal_code }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div id="cal1-status-info" class="mt-1 text-[11px] min-h-[16px]"></div>
                                    </div>

                                    <!-- Calibrator 2: Reference Standard -->
                                    <div>
                                        <x-input-label for="calibrator_2" class="text-xs mb-1">
                                            {{ __('Calibrator 2') }} ({{ $channelData['grandeur'] }})
                                        </x-input-label>
                                        <select name="calibrator_2" id="calibrator_2" class="input-base w-full text-xs py-2 px-3">
                                            <option value="">{{ __('Select reference standard...') }}</option>
                                            @foreach($options2 as $cal)
                                                <option value="{{ $cal->id }}" {{ (string)$selectedCal2 === (string)$cal->id ? 'selected' : '' }}>
                                                    {{ $cal->full_name ?? ($cal->designation ?? ($cal->name ?? $cal->internal_code)) }} ({{ $cal->serial_number ?? $cal->internal_code }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div id="cal2-status-info" class="mt-1 text-[11px] min-h-[16px]"></div>
                                    </div>

                                    <!-- Channel Scale -->
                                    <div>
                                        <x-input-label class="text-xs mb-1">{{ __('Channel Scale') }} ({{ $activeTransmitter->tag_number }})</x-input-label>
                                        <input type="text" value="{{ floatval($channelData['min']) }} &rarr; {{ floatval($channelData['max']) }} {{ $channelData['symbol'] }} (Span: {{ $channelData['span'] }} {{ $channelData['symbol'] }})" disabled
                                               class="w-full text-xs rounded-md border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60 text-gray-700 dark:text-gray-300 font-mono font-bold py-2 px-3 cursor-not-allowed" />
                                    </div>
                                </div>
                            </div>

                            <!-- Card 2: Measurement Points Table -->
                            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60 space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-700/60">
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>{{ __('Measurement Points') }} – {{ $activeTransmitter->channel_number ?? 'Canal' }} ({{ $channelData['grandeur'] }})</span>
                                    </h3>
                                    <div class="flex items-center gap-2">
                                        <x-badge variant="neutral" size="sm">{{ __('Calibrator 1 → 4-20 mA Generator') }}</x-badge>
                                        <x-badge variant="info" size="sm">{{ __('Calibrator 2 → ') }} {{ $channelData['grandeur'] }}</x-badge>
                                    </div>
                                </div>

                                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-2xs">
                                    <table class="equipment-table min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                                        <thead class="bg-gray-50/90 dark:bg-gray-900/60 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            <tr>
                                                <th class="px-3 py-3 text-center w-12">#</th>
                                                <th class="px-4 py-3 text-center">{{ __('Applied (%)') }}</th>
                                                <th class="px-4 py-3 text-center">{{ __('Current (mA)') }}</th>
                                                <th class="px-4 py-3 text-center text-brand-700 dark:text-brand-400 bg-brand-50/60 dark:bg-brand-950/50 border-x border-brand-200/40 dark:border-brand-900/50">
                                                    {{ __('correction (Interpolated)') }} (mA)
                                                </th>
                                                <th class="px-4 py-3 text-center">{{ __('Calculated Expected') }} ({{ $channelData['symbol'] }})</th>
                                                <th class="px-4 py-3 text-center text-emerald-700 dark:text-emerald-400 bg-emerald-50/60 dark:bg-emerald-950/50 border-x border-emerald-200/40 dark:border-emerald-900/50">
                                                    {{ __('correction (Interpolated)') }} ({{ $channelData['symbol'] }})
                                                </th>
                                                <th class="px-4 py-3 text-center">{{ __('Indicated Read Value') }} ({{ $channelData['symbol'] }}) *</th>
                                                <th class="px-4 py-3 text-center">{{ __('Error') }} ({{ $channelData['emt_display'] }})</th>
                                                <th class="px-4 py-3 text-center">{{ __('EMT (±)') }}</th>
                                                <th class="px-4 py-3 text-center">{{ __('Verdict') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="points-table-body" class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
                                            @php
                                                $defaultPercentages = $defaultPercentages ?? [0.0, 25.0, 50.0, 75.0, 100.0, 100.0, 75.0, 50.0, 25.0, 0.0];
                                                $existingPoints = isset($verification) && isset($verification->points) ? $verification->points : collect();
                                            @endphp
                                            @foreach($defaultPercentages as $index => $percentage)
                                                @php
                                                    $defaultmA = 4.0 + ($percentage / 100.0) * 16.0;
                                                    $existingPoint = $existingPoints->firstWhere('step_order', $index + 1);
                                                    $measuredSignal = $existingPoint && !is_null($existingPoint->measured_signal) ? $existingPoint->measured_signal : number_format($defaultmA, 4, '.', '');
                                                    $indicatedValue = $existingPoint?->indicated_value ?? '';
                                                @endphp
                                                <tr class="point-row hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition-colors">
                                                    <td class="px-3 py-2.5 text-center font-mono font-bold text-gray-500 dark:text-gray-400">
                                                        {{ $index + 1 }}
                                                    </td>
                                                    <td class="px-4 py-2.5 text-center font-mono font-bold text-brand-600 dark:text-brand-400">
                                                        <input type="hidden" name="points[{{ $index }}][applied_percentage]" value="{{ $percentage }}">
                                                        <span>{{ number_format($percentage, 1) }}%</span>
                                                    </td>
                                                    <!-- Current (mA) Input -->
                                                    <td class="px-4 py-2.5 text-center">
                                                        <input type="number" step="0.0001" name="points[{{ $index }}][measured_signal]"
                                                               value="{{ $measuredSignal }}" required
                                                               class="input-signal w-32 text-center text-xs font-mono font-bold rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white py-1.5 px-2 focus:ring-brand-500 focus:border-brand-500" />
                                                    </td>
                                                    <!-- Calibrator 1 Corrected Signal (Read-only + Interpolated) -->
                                                    <td class="px-4 py-2.5 text-center font-mono font-bold cell-signal-corrigee text-brand-700 dark:text-brand-400 bg-brand-50/30 dark:bg-brand-950/30 border-x border-brand-100/50 dark:border-brand-900/30">
                                                        <span class="signal-corrigee-val">{{ isset($existingPoint->corrected_signal) ? number_format($existingPoint->corrected_signal, 4, '.', '') : (isset($existingPoint->measured_signal) ? number_format($existingPoint->measured_signal, 4, '.', '') : '-') }}</span>
                                                        <input type="hidden" name="points[{{ $index }}][calibrator_1_correction]" class="input-cal1-correction" value="{{ $existingPoint->calibrator_1_correction ?? '' }}">
                                                        <input type="hidden" name="points[{{ $index }}][corrected_signal]" class="input-corrected-signal" value="{{ $existingPoint->corrected_signal ?? '' }}">
                                                    </td>
                                                    <!-- Calculated Expected Value -->
                                                    <td class="px-4 py-2.5 text-center font-mono font-bold cell-calculated">
                                                        <span class="val-calculee-span">{{ isset($existingPoint->expected_value) ? number_format($existingPoint->expected_value, 4, '.', '') : '-' }}</span>
                                                        <input type="hidden" name="points[{{ $index }}][expected_signal]" value="{{ number_format($defaultmA, 4, '.', '') }}">
                                                        <input type="hidden" name="points[{{ $index }}][expected_value]" class="input-expected-val" value="{{ $existingPoint->expected_value ?? '' }}">
                                                    </td>
                                                    <!-- Calibrator 2 Corrected Expected Value (Read-only + Interpolated) -->
                                                    <td class="px-4 py-2.5 text-center font-mono font-bold cell-val-corrigee text-emerald-700 dark:text-emerald-400 bg-emerald-50/30 dark:bg-emerald-950/30 border-x border-emerald-100/50 dark:border-emerald-900/30">
                                                        <span class="val-corrigee-span">{{ isset($existingPoint->corrected_expected_value) ? number_format($existingPoint->corrected_expected_value, 4, '.', '') : (isset($existingPoint->expected_value) ? number_format($existingPoint->expected_value, 4, '.', '') : '-') }}</span>
                                                        <input type="hidden" name="points[{{ $index }}][calibrator_2_correction]" class="input-cal2-correction" value="{{ $existingPoint->calibrator_2_correction ?? '' }}">
                                                        <input type="hidden" name="points[{{ $index }}][corrected_expected_value]" class="input-corrected-expected" value="{{ $existingPoint->corrected_expected_value ?? '' }}">
                                                    </td>
                                                    <!-- Indicated Read Value Input -->
                                                    <td class="px-4 py-2.5 text-center">
                                                        <input type="number" step="0.0001" name="points[{{ $index }}][indicated_value]"
                                                               value="{{ $indicatedValue }}" required
                                                               placeholder="0.0000"
                                                               class="input-indicated w-32 text-center text-xs font-mono font-bold rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white py-1.5 px-2 focus:ring-brand-500 focus:border-brand-500" />
                                                    </td>
                                                    <td class="px-4 py-2.5 text-center font-mono font-bold cell-erreur">
                                                        -
                                                    </td>
                                                    <td class="px-4 py-2.5 text-center font-mono font-semibold text-gray-500 dark:text-gray-400">
                                                        ±{{ is_numeric($channelData['emt_approved']) ? number_format((float)$channelData['emt_approved'], 4, '.', '') : $channelData['emt_approved'] }}
                                                    </td>
                                                    <td class="px-4 py-2.5 text-center cell-conformite">
                                                        -
                                                    </td>
                                                    <input type="hidden" name="points[{{ $index }}][is_conforme]" class="input-conformite" value="">
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Bottom Action Bar -->
                            <div class="flex items-center justify-between rounded-xl bg-white dark:bg-gray-800 p-4 border border-gray-100 dark:border-gray-700/60 shadow-xs">
                                <a href="{{ route('metrology.reports.show', $report->id) }}">
                                    <x-secondary-button type="button" class="gap-1.5 text-xs">
                                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                        <span>{{ __('Back to Report') }}</span>
                                    </x-secondary-button>
                                </a>
                                <x-primary-button type="submit" class="gap-2 text-xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>{{ __('Save Channel Data') }}</span>
                                </x-primary-button>
                            </div>
                        </form>
                    @endif

                </main>
            </div>
        </div>
    </div>

    <!-- Active Calibrators Points Data -->
    <script id="calibrators-points-data" type="application/json">@json($calibratorsPointsMap ?? [])</script>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const rawMin = document.getElementById('channel_min')?.value;
        const rawMax = document.getElementById('channel_max')?.value;
        const rangeMin = !isNaN(parseFloat(rawMin)) ? parseFloat(rawMin) : 0;
        const rangeMax = !isNaN(parseFloat(rawMax)) ? parseFloat(rawMax) : 100;
        const emtLimit = !isNaN(parseFloat(document.getElementById('emt_limit')?.value)) ? parseFloat(document.getElementById('emt_limit').value) : 0.1;
        const isRelativeEmt = document.getElementById('is_relative_emt')?.value === '1';
        const isTemp = document.getElementById('is_temp')?.value === '1';
        const span = (rangeMax - rangeMin) !== 0 ? (rangeMax - rangeMin) : 100;

        const calibratorsDataEl = document.getElementById('calibrators-points-data');
        let calibratorsPointsMap = {};
        if (calibratorsDataEl) {
            try {
                calibratorsPointsMap = JSON.parse(calibratorsDataEl.textContent || '{}');
            } catch (e) {
                console.warn('Failed to parse calibrators points map', e);
            }
        }

        /**
         * خوارزمية الاستيفاء الخطي المترولوجي لنقاط شهادة المعايرة
         */
        function interpolatePoints(points, targetX) {
            if (!points || !Array.isArray(points) || points.length === 0 || isNaN(targetX)) {
                return { correction: 0, hasData: false };
            }

            for (let i = 0; i < points.length; i++) {
                if (Math.abs(points[i].nominal - targetX) < 1e-6) {
                    return { correction: Number(points[i].correction), hasData: true, exact: true };
                }
            }

            const sorted = [...points].sort((a, b) => a.nominal - b.nominal);
            const minNom = sorted[0].nominal;
            const maxNom = sorted[sorted.length - 1].nominal;

            if (targetX <= minNom) {
                return { correction: Number(sorted[0].correction), hasData: true, clamped: true };
            }
            if (targetX >= maxNom) {
                return { correction: Number(sorted[sorted.length - 1].correction), hasData: true, clamped: true };
            }

            for (let i = 0; i < sorted.length - 1; i++) {
                const p1 = sorted[i];
                const p2 = sorted[i + 1];
                if (targetX >= p1.nominal && targetX <= p2.nominal) {
                    if (p2.nominal === p1.nominal) {
                        return { correction: Number(p1.correction), hasData: true };
                    }
                    const ratio = (targetX - p1.nominal) / (p2.nominal - p1.nominal);
                    const interpCorr = Number(p1.correction) + ratio * (Number(p2.correction) - Number(p1.correction));
                    return { correction: interpCorr, hasData: true };
                }
            }

            return { correction: 0, hasData: false };
        }

        /**
         * تحديث شارات حالة نقاط المعايرة تحت قوائم اختيار الأجهزة
         */
        function updateCalibratorStatus() {
            const cal1Select = document.getElementById('calibrator_1');
            const cal2Select = document.getElementById('calibrator_2');
            const cal1Info = document.getElementById('cal1-status-info');
            const cal2Info = document.getElementById('cal2-status-info');

            if (cal1Select && cal1Info) {
                const val1 = cal1Select.value;
                const pts1 = val1 && calibratorsPointsMap[val1] ? calibratorsPointsMap[val1] : [];
                if (val1 && pts1.length > 0) {
                    cal1Info.innerHTML = `<span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-semibold"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> ${pts1.length} {{ __('points') }} ({{ __('Interpolation Active') }})</span>`;
                } else if (val1) {
                    cal1Info.innerHTML = `<span class="text-gray-400 dark:text-gray-500">{{ __('No certificate points (Correction = 0)') }}</span>`;
                } else {
                    cal1Info.innerHTML = '';
                }
            }

            if (cal2Select && cal2Info) {
                const val2 = cal2Select.value;
                const pts2 = val2 && calibratorsPointsMap[val2] ? calibratorsPointsMap[val2] : [];
                if (val2 && pts2.length > 0) {
                    cal2Info.innerHTML = `<span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-semibold"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> ${pts2.length} {{ __('points') }} ({{ __('Interpolation Active') }})</span>`;
                } else if (val2) {
                    cal2Info.innerHTML = `<span class="text-gray-400 dark:text-gray-500">{{ __('No certificate points (Correction = 0)') }}</span>`;
                } else {
                    cal2Info.innerHTML = '';
                }
            }
        }

        function calculateRow(row) {
            const inputSignal = row.querySelector('.input-signal');
            const cellSignalCorrigee = row.querySelector('.cell-signal-corrigee');
            const inputCal1Corr = row.querySelector('.input-cal1-correction');
            const inputCorrSignal = row.querySelector('.input-corrected-signal');

            const cellCalculated = row.querySelector('.cell-calculated');
            const spanCalculated = cellCalculated?.querySelector('.val-calculee-span');
            const inputExpectedVal = row.querySelector('.input-expected-val');

            const cellValCorrigee = row.querySelector('.cell-val-corrigee');
            const spanValCorrigee = cellValCorrigee?.querySelector('.val-corrigee-span');
            const inputCal2Corr = row.querySelector('.input-cal2-correction');
            const inputCorrExpected = row.querySelector('.input-corrected-expected');

            const inputIndicated = row.querySelector('.input-indicated');
            const cellErreur = row.querySelector('.cell-erreur');
            const cellConformite = row.querySelector('.cell-conformite');
            const inputConformite = row.querySelector('.input-conformite');

            const rawSignal = inputSignal ? inputSignal.value.trim() : '';
            const rawIndicated = inputIndicated ? inputIndicated.value.trim() : '';

            const hasSignal = rawSignal !== '';
            const hasIndicated = rawIndicated !== '';

            const signal_mA = hasSignal ? parseFloat(rawSignal) : NaN;
            const indicated = hasIndicated ? parseFloat(rawIndicated) : NaN;

            const cal1Id = document.getElementById('calibrator_1')?.value;
            const cal2Id = document.getElementById('calibrator_2')?.value;
            const cal1Points = (cal1Id && calibratorsPointsMap[cal1Id]) ? calibratorsPointsMap[cal1Id] : [];
            const cal2Points = (cal2Id && calibratorsPointsMap[cal2Id]) ? calibratorsPointsMap[cal2Id] : [];

            // 1. استيفاء وتصحيح إشارة التيار المحقونة (Calibrator 1)
            let signalCorrigee = signal_mA;
            let cal1Correction = 0;
            if (!isNaN(signal_mA)) {
                const res1 = interpolatePoints(cal1Points, signal_mA);
                cal1Correction = res1.correction;
                signalCorrigee = signal_mA + cal1Correction;

                if (cellSignalCorrigee) {
                    const spanSig = cellSignalCorrigee.querySelector('.signal-corrigee-val');
                    if (spanSig) spanSig.textContent = signalCorrigee.toFixed(4);
                    if (res1.hasData && Math.abs(cal1Correction) > 1e-6) {
                        cellSignalCorrigee.title = `Correction Calibrator 1: ${(cal1Correction >= 0 ? '+' : '')}${cal1Correction.toFixed(5)} mA`;
                    } else {
                        cellSignalCorrigee.title = '';
                    }
                }
                if (inputCal1Corr) inputCal1Corr.value = cal1Correction.toFixed(6);
                if (inputCorrSignal) inputCorrSignal.value = signalCorrigee.toFixed(6);
            } else {
                if (cellSignalCorrigee) {
                    const spanSig = cellSignalCorrigee.querySelector('.signal-corrigee-val');
                    if (spanSig) spanSig.textContent = '-';
                    cellSignalCorrigee.title = '';
                }
                if (inputCal1Corr) inputCal1Corr.value = '';
                if (inputCorrSignal) inputCorrSignal.value = '';
            }

            // 2. حساب القيمة الاسمية المتوقعة من الإشارة المصححة
            if (hasSignal && !isNaN(signalCorrigee) && span !== 0) {
                const val_calculee = rangeMin + ((signalCorrigee - 4.0) / 16.0) * span;
                if (spanCalculated) spanCalculated.textContent = val_calculee.toFixed(4);
                if (inputExpectedVal) inputExpectedVal.value = val_calculee.toFixed(4);

                // 3. استيفاء وتصحيح القيمة المتوقعة (Calibrator 2)
                let valCorrigee = val_calculee;
                let cal2Correction = 0;
                if (!isNaN(val_calculee)) {
                    const res2 = interpolatePoints(cal2Points, val_calculee);
                    cal2Correction = res2.correction;
                    valCorrigee = val_calculee + cal2Correction;

                    if (cellValCorrigee) {
                        if (spanValCorrigee) spanValCorrigee.textContent = valCorrigee.toFixed(4);
                        if (res2.hasData && Math.abs(cal2Correction) > 1e-6) {
                            cellValCorrigee.title = `Correction Calibrator 2: ${(cal2Correction >= 0 ? '+' : '')}${cal2Correction.toFixed(4)}`;
                        } else {
                            cellValCorrigee.title = '';
                        }
                    }
                    if (inputCal2Corr) inputCal2Corr.value = cal2Correction.toFixed(6);
                    if (inputCorrExpected) inputCorrExpected.value = valCorrigee.toFixed(6);
                } else {
                    if (cellValCorrigee) {
                        if (spanValCorrigee) spanValCorrigee.textContent = '-';
                        cellValCorrigee.title = '';
                    }
                    if (inputCal2Corr) inputCal2Corr.value = '';
                    if (inputCorrExpected) inputCorrExpected.value = '';
                }

                // 4. تقييم الخطأ والمطابقة بالمقارنة مع القيمة المصححة (valCorrigee)
                if (hasIndicated && !isNaN(indicated)) {
                    const erreurAbs = indicated - valCorrigee;

                    if (isTemp) {
                        cellErreur.textContent = (erreurAbs >= 0 ? '+' : '') + erreurAbs.toFixed(4);
                        const isOk = Math.abs(erreurAbs) <= emtLimit + 0.000001;
                        cellErreur.className = 'px-4 py-2.5 text-center font-mono font-bold cell-erreur ' + (isOk ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400');
                        cellConformite.innerHTML = isOk 
                            ? '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300/60"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> OK</span>'
                            : '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-300/60"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg> NOK</span>';
                        inputConformite.value = isOk ? 1 : 0;
                    } else {
                        const erreurRel = (span !== 0) ? (Math.abs(erreurAbs) / span) * 100 : 0;
                        cellErreur.textContent = (erreurAbs >= 0 ? '+' : '') + (isRelativeEmt ? erreurRel.toFixed(4) + '%' : erreurAbs.toFixed(4));
                        const erreurEval = isRelativeEmt ? erreurRel : Math.abs(erreurAbs);
                        const isOk = (erreurEval <= emtLimit + 0.000001);
                        cellErreur.className = 'px-4 py-2.5 text-center font-mono font-bold cell-erreur ' + (isOk ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400');
                        cellConformite.innerHTML = isOk
                            ? '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300/60"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> OK</span>'
                            : '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-300/60"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg> NOK</span>';
                        inputConformite.value = isOk ? 1 : 0;
                    }
                } else {
                    cellErreur.textContent = '-';
                    cellConformite.innerHTML = '-';
                    inputConformite.value = '';
                }
            } else {
                if (spanCalculated) spanCalculated.textContent = '-';
                if (spanValCorrigee) spanValCorrigee.textContent = '-';
                cellErreur.textContent = '-';
                cellConformite.innerHTML = '-';
                inputConformite.value = '';
            }
        }

        const table = document.querySelector('.equipment-table');
        if (table) {
            table.addEventListener('input', function(e) {
                const target = e.target;
                if (target && (target.classList.contains('input-signal') || target.classList.contains('input-indicated'))) {
                    const row = target.closest('.point-row');
                    if (row) calculateRow(row);
                }
            });

            document.querySelectorAll('.point-row').forEach(row => calculateRow(row));
        }

        document.getElementById('calibrator_1')?.addEventListener('change', function() {
            updateCalibratorStatus();
            document.querySelectorAll('.point-row').forEach(row => calculateRow(row));
        });
        document.getElementById('calibrator_2')?.addEventListener('change', function() {
            updateCalibratorStatus();
            document.querySelectorAll('.point-row').forEach(row => calculateRow(row));
        });

        updateCalibratorStatus();
    });
    </script>
    @endpush
</x-app-layout>