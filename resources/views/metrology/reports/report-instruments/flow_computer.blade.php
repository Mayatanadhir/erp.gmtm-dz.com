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
                                        <x-badge variant="neutral" size="sm">{{ __('Cycle 4-20 mA') }}</x-badge>
                                        <x-badge variant="info" size="sm">{{ $channelData['symbol'] }}</x-badge>
                                    </div>
                                </div>

                                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-2xs">
                                    <table class="equipment-table min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                                        <thead class="bg-gray-50/90 dark:bg-gray-900/60 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            <tr>
                                                <th class="px-3 py-3 text-center w-12">#</th>
                                                <th class="px-4 py-3 text-center">{{ __('Applied (%)') }}</th>
                                                <th class="px-4 py-3 text-center">{{ __('Current (mA)') }}</th>
                                                <th class="px-4 py-3 text-center">{{ __('Calculated Expected') }} ({{ $channelData['symbol'] }})</th>
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
                                                    <td class="px-4 py-2.5 text-center">
                                                        <input type="number" step="0.0001" name="points[{{ $index }}][measured_signal]"
                                                               value="{{ $measuredSignal }}" required
                                                               class="input-signal w-32 text-center text-xs font-mono font-bold rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white py-1.5 px-2 focus:ring-brand-500 focus:border-brand-500" />
                                                    </td>
                                                    <td class="px-4 py-2.5 text-center font-mono font-bold text-indigo-600 dark:text-indigo-400 cell-calculated">
                                                        -
                                                    </td>
                                                    <input type="hidden" name="points[{{ $index }}][calculated_value]" class="input-expected-val" value="">
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

        function calculateRow(row) {
            const inputSignal = row.querySelector('.input-signal');
            const cellCalculated = row.querySelector('.cell-calculated');
            const inputExpectedVal = row.querySelector('.input-expected-val');
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

            if (hasSignal && !isNaN(signal_mA) && span !== 0) {
                const val_calculee = rangeMin + ((signal_mA - 4.0) / 16.0) * span;
                cellCalculated.textContent = val_calculee.toFixed(4);
                if (inputExpectedVal) inputExpectedVal.value = val_calculee.toFixed(4);

                if (hasIndicated && !isNaN(indicated)) {
                    const erreurAbs = indicated - val_calculee;

                    if (isTemp) {
                        cellErreur.textContent = (erreurAbs >= 0 ? '+' : '') + erreurAbs.toFixed(4);
                        const isOk = Math.abs(erreurAbs) <= emtLimit + 0.000001;
                        cellErreur.className = 'px-4 py-2.5 text-center font-mono font-bold cell-erreur ' + (isOk ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400');
                        cellConformite.innerHTML = isOk 
                            ? '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300/60"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> OK</span>'
                            : '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-300/60"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg> NOK</span>';
                        inputConformite.value = isOk ? 1 : 0;
                    } else {
                        const erreurRel = (span !== 0) ? (Math.abs(erreurAbs) / span) * 100 : 0;
                        cellErreur.textContent = (erreurAbs >= 0 ? '+' : '') + (isRelativeEmt ? erreurRel.toFixed(4) + '%' : erreurAbs.toFixed(4));
                        const erreurEval = isRelativeEmt ? erreurRel : Math.abs(erreurAbs);
                        const isOk = (erreurEval <= emtLimit + 0.000001);
                        cellErreur.className = 'px-4 py-2.5 text-center font-mono font-bold cell-erreur ' + (isOk ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400');
                        cellConformite.innerHTML = isOk
                            ? '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300/60"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> OK</span>'
                            : '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-300/60"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg> NOK</span>';
                        inputConformite.value = isOk ? 1 : 0;
                    }
                } else {
                    cellErreur.textContent = '-';
                    cellConformite.innerHTML = '-';
                    inputConformite.value = '';
                }
            } else {
                cellCalculated.textContent = '-';
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
    });
    </script>
    @endpush
</x-app-layout>