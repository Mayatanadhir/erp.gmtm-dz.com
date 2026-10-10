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
                            {{ __('Temperature Probe Verification Saisie') }} &bull; <span dir="ltr" class="font-mono text-brand-600 dark:text-brand-400">{{ $instrument->tag_number }}</span>
                        </h2>
                        <x-badge variant="info" size="sm">{{ __('Probe Pt100 (IEC 60751)') }}</x-badge>
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
                <x-primary-button type="submit" form="calibration-form" class="gap-1.5 text-xs shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    <span>{{ __('Save Calibration Data') }}</span>
                </x-primary-button>
            </div>
        </div>
    </x-slot>

    @php
        $options1 = $options1 ?? collect();
        $options2 = $options2 ?? collect();
        $selectedCal1 = old('calibrator_1', $selectedCal1 ?? null);
        $selectedCal2 = old('calibrator_2', $selectedCal2 ?? null);
    @endphp

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

                    <form id="calibration-form" action="{{ route('metrology.reports.saisie.probe', ['report' => $report->id, 'instrument' => $instrument->id]) }}" method="POST" class="space-y-6">
                        @csrf
                        <input type="hidden" id="emt_limit" value="{{ $specifications?->accuracy_value ?? '0.15' }}">

                        <!-- Card 1: General Info & Reference Standards -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 mb-5 border-b border-gray-100 dark:border-gray-700/60">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>{{ __('General Information & Reference Calibrators') }}</span>
                                </h3>
                                <div class="flex items-center gap-2">
                                    <x-badge variant="info" size="sm">
                                        {{ __('EMT Class A:') }} ±(0.15 + 0.002 × |t|) °C
                                    </x-badge>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs mb-4">
                                <!-- Tag -->
                                <div>
                                    <x-input-label class="text-xs mb-1">{{ __('Tag Number') }}</x-input-label>
                                    <input type="text" value="{{ $instrument->tag_number }}" disabled
                                           class="w-full text-xs rounded-md border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60 text-gray-900 dark:text-white font-mono font-bold py-2 px-3 cursor-not-allowed" />
                                </div>

                                <!-- Serial Number -->
                                <div>
                                    <x-input-label class="text-xs mb-1">{{ __('Serial Number (S/N)') }}</x-input-label>
                                    <input type="text" value="{{ $instrument->serial_number }}" disabled
                                           class="w-full text-xs rounded-md border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60 text-gray-600 dark:text-gray-300 font-mono py-2 px-3 cursor-not-allowed" />
                                </div>

                                <!-- Verification Date -->
                                <div>
                                    <x-input-label for="verification_date" class="text-xs mb-1">
                                        {{ __('Verification Date') }} <span class="text-rose-500">*</span>
                                    </x-input-label>
                                    @php
                                        $formattedDate = old('verification_date', isset($verification->verification_date) ? (is_string($verification->verification_date) ? \Carbon\Carbon::parse($verification->verification_date)->format('Y-m-d') : $verification->verification_date->format('Y-m-d')) : date('Y-m-d'));
                                    @endphp
                                    <input type="date" id="verification_date" name="verification_date" value="{{ $formattedDate }}" required
                                           class="input-base w-full text-xs py-2 px-3 focus:border-brand-600 focus:ring-brand-600" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs pt-3 border-t border-gray-100 dark:border-gray-700/60">
                                <!-- Calibrator 1: Thermal Block / Bath -->
                                <div>
                                    <x-input-label for="calibrator_1" class="text-xs mb-1">
                                        {{ __('Calibrator 1') }} ({{ __('Dry-block / Thermostatic Bath') }})
                                    </x-input-label>
                                    <select name="calibrator_1" id="calibrator_1" class="input-base w-full text-xs py-2 px-3">
                                        <option value="">{{ __('Select thermal standard...') }}</option>
                                        @foreach($options1 as $cal)
                                            <option value="{{ $cal->id }}" {{ (string)$selectedCal1 === (string)$cal->id ? 'selected' : '' }}>
                                                {{ $cal->full_name ?? ($cal->designation ?? ($cal->name ?? $cal->internal_code)) }} ({{ $cal->serial_number ?? $cal->internal_code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div id="cal1-status-info" class="mt-1 text-[11px] min-h-[16px]"></div>
                                </div>

                                <!-- Calibrator 2: Reference Thermometer / Multimeter -->
                                <div>
                                    <x-input-label for="calibrator_2" class="text-xs mb-1">
                                        {{ __('Calibrator 2') }} ({{ __('Reference Thermometer / Multimeter') }})
                                    </x-input-label>
                                    <select name="calibrator_2" id="calibrator_2" class="input-base w-full text-xs py-2 px-3">
                                        <option value="">{{ __('Select reference thermometer / multimeter...') }}</option>
                                        @foreach($options2 as $cal)
                                            <option value="{{ $cal->id }}" {{ (string)$selectedCal2 === (string)$cal->id ? 'selected' : '' }}>
                                                {{ $cal->full_name ?? ($cal->designation ?? ($cal->name ?? $cal->internal_code)) }} ({{ $cal->serial_number ?? $cal->internal_code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div id="cal2-status-info" class="mt-1 text-[11px] min-h-[16px]"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Measurement Points Table -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60 space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-700/60">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span>{{ __('Measurement Points (IEC 60751 Callendar-Van Dusen)') }}</span>
                                </h3>
                                <div class="flex items-center gap-2">
                                    <x-badge variant="neutral" size="sm">
                                        {{ __('Calibrator 1 → T(°C)') }}
                                    </x-badge>
                                    <x-badge variant="info" size="sm">
                                        {{ __('Calibrator 2 → R(Ω)') }}
                                    </x-badge>
                                </div>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-2xs">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                                    <thead class="bg-gray-50/90 dark:bg-gray-900/60 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        <tr>
                                            <th class="px-3 py-3 text-center w-12">#</th>
                                            <th class="px-4 py-3 text-center">{{ __('Test Temp (°C)') }}</th>
                                            <th class="px-4 py-3 text-center">{{ __('Applied Temp (°C)') }}</th>
                                            <th class="px-4 py-3 text-center text-brand-700 dark:text-brand-400 bg-brand-50/60 dark:bg-brand-950/50 border-x border-brand-200/40 dark:border-brand-900/50">
                                                {{ __('correction (Interpolated)') }} (°C)
                                            </th>
                                            <th class="px-4 py-3 text-center">{{ __('Measured Resistance (Ω)') }}</th>
                                            <th class="px-4 py-3 text-center text-emerald-700 dark:text-emerald-400 bg-emerald-50/60 dark:bg-emerald-950/50 border-x border-emerald-200/40 dark:border-emerald-900/50">
                                                {{ __('correction (Interpolated)') }} (Ω)
                                            </th>
                                            <th class="px-4 py-3 text-center">{{ __('Indicated Temp T_probe (°C)') }}</th>
                                            <th class="px-4 py-3 text-center">{{ __('Error (°C)') }}</th>
                                            <th class="px-4 py-3 text-center">{{ __('EMT (± °C)') }}</th>
                                            <th class="px-4 py-3 text-center">{{ __('Verdict') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="points-table-body" class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
                                        @php
                                            $oam = app(\App\Services\OamMetrologyService::class);
                                        @endphp
                                        @foreach($defaultPercentages as $index => $percentage)
                                            @php
                                                $pMin = (float)($specifications?->range_min ?? -50.0);
                                                $pMax = (float)($specifications?->range_max ?? 200.0);
                                                $appliedTemp = (float)($pMin + ($percentage / 100) * ($pMax - $pMin));
                                                $existingPoint = isset($verification) && isset($verification->points) ? $verification->points->where('step_order', $index + 1)->first() : null;
                                                $refTemp = $existingPoint?->reference_temperature ?? number_format($appliedTemp, 2, '.', '');
                                                $measuredR = $existingPoint?->measured_resistance;
                                                $indicatedT = $existingPoint?->indicated_temperature;
                                                if ($measuredR !== null && $indicatedT === null) {
                                                    $indicatedT = $oam->resistanceToTemperature((float)$measuredR);
                                                }
                                                $expectedEmt = 0.150 + (0.002 * abs($appliedTemp));
                                            @endphp
                                            <tr class="point-row hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition-colors">
                                                <td class="px-3 py-2.5 text-center font-mono font-bold text-gray-500 dark:text-gray-400">
                                                    {{ $index + 1 }}
                                                </td>
                                                <td class="px-4 py-2.5 text-center font-mono font-bold text-brand-600 dark:text-brand-400">
                                                    <input type="hidden" class="input-temp-essai" value="{{ number_format($appliedTemp, 2, '.', '') }}">
                                                    <span>{{ number_format($appliedTemp, 2) }}</span>
                                                </td>
                                                <!-- Normal Applied Temperature (Input) -->
                                                <td class="px-4 py-2.5 text-center cell-temp-etalon">
                                                    <input type="number" step="0.001" name="points[{{ $index }}][reference_temperature]"
                                                           value="{{ $refTemp }}" required
                                                           class="input-temp-etalon w-28 text-center text-xs font-mono font-bold rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white py-1.5 px-2 focus:ring-brand-500 focus:border-brand-500" />
                                                </td>
                                                <!-- Corrected Applied Temperature (Read-only + Interpolated) -->
                                                <td class="px-4 py-2.5 text-center font-mono font-bold cell-temp-corrigee text-brand-700 dark:text-brand-400 bg-brand-50/30 dark:bg-brand-950/30 border-x border-brand-100/50 dark:border-brand-900/30">
                                                    <span class="ref-corrigee-val">{{ isset($existingPoint->corrected_reference_temperature) ? number_format($existingPoint->corrected_reference_temperature, 3, '.', '') : (isset($existingPoint->reference_temperature) ? number_format($existingPoint->reference_temperature, 3, '.', '') : '-') }}</span>
                                                    <input type="hidden" name="points[{{ $index }}][calibrator_1_correction]" class="input-cal1-correction" value="{{ $existingPoint->calibrator_1_correction ?? '' }}">
                                                    <input type="hidden" name="points[{{ $index }}][corrected_reference_temperature]" class="input-corrected-reference" value="{{ $existingPoint->corrected_reference_temperature ?? '' }}">
                                                </td>
                                                <!-- Measured Resistance Input -->
                                                <td class="px-4 py-2.5 text-center cell-resistance">
                                                    <input type="number" step="0.001" name="points[{{ $index }}][measured_resistance]"
                                                           value="{{ $measuredR ?? '' }}" required
                                                           placeholder="100.000"
                                                           class="input-resistance w-28 text-center text-xs font-mono font-bold rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white py-1.5 px-2 focus:ring-brand-500 focus:border-brand-500" />
                                                </td>
                                                <!-- Corrected Measured Resistance (Read-only + Interpolated) -->
                                                <td class="px-4 py-2.5 text-center font-mono font-bold cell-res-corrigee text-emerald-700 dark:text-emerald-400 bg-emerald-50/30 dark:bg-emerald-950/30 border-x border-emerald-100/50 dark:border-emerald-900/30">
                                                    <span class="res-corrigee-val">{{ isset($existingPoint->corrected_measured_resistance) ? number_format($existingPoint->corrected_measured_resistance, 4, '.', '') : (isset($existingPoint->measured_resistance) ? number_format($existingPoint->measured_resistance, 4, '.', '') : '-') }}</span>
                                                    <input type="hidden" name="points[{{ $index }}][calibrator_2_correction]" class="input-cal2-correction" value="{{ $existingPoint->calibrator_2_correction ?? '' }}">
                                                    <input type="hidden" name="points[{{ $index }}][corrected_measured_resistance]" class="input-corrected-resistance" value="{{ $existingPoint->corrected_measured_resistance ?? '' }}">
                                                </td>
                                                <td class="px-4 py-2.5 text-center font-mono font-bold text-indigo-600 dark:text-indigo-400 cell-temp-sonde">
                                                    {{ $indicatedT !== null ? number_format($indicatedT, 3, '.', '') : '-' }}
                                                </td>
                                                <input type="hidden" name="points[{{ $index }}][indicated_temperature]" class="input-temp-ind" value="{{ $indicatedT !== null ? number_format((float)$indicatedT, 4, '.', '') : '' }}">
                                                <td class="px-4 py-2.5 text-center font-mono font-bold cell-erreur">
                                                    -
                                                </td>
                                                <td class="px-4 py-2.5 text-center font-mono font-semibold cell-emt-point text-gray-500 dark:text-gray-400">
                                                    ±{{ number_format($expectedEmt, 3) }}
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
                                <span>{{ __('Save Calibration Data') }}</span>
                            </x-primary-button>
                        </div>
                    </form>

                </main>
            </div>
        </div>
    </div>

    <!-- Active Calibrators Points Data -->
    <script id="calibrators-points-data" type="application/json">@json($calibratorsPointsMap ?? [])</script>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
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

        function resistanceToTemp(r) {
            const r0 = 100.0;
            const a = 3.9083e-3;
            const b = -5.775e-7;
            const c = -4.183e-12;

            if (r >= r0) {
                const discriminant = Math.pow(r0 * a, 2) - 4 * (r0 * b) * (r0 - r);
                if (discriminant < 0) return NaN;
                return (-r0 * a + Math.sqrt(discriminant)) / (2 * r0 * b);
            }

            let t = -100.0;
            for (let i = 0; i < 20; i++) {
                const f = r0 * (1 + a * t + b * t * t + c * (t - 100) * Math.pow(t, 3)) - r;
                const fPrime = r0 * (a + 2 * b * t - 300 * c * t * t + 4 * c * Math.pow(t, 3));
                if (fPrime === 0) break;
                const tNext = t - f / fPrime;
                if (Math.abs(tNext - t) < 1e-5) return tNext;
                t = tNext;
            }
            return t;
        }

        const rows = document.querySelectorAll('.point-row');

        rows.forEach(row => {
            const inputEssai = row.querySelector('.input-temp-essai');
            const inputEtalon = row.querySelector('.input-temp-etalon');
            const cellTempCorrigee = row.querySelector('.cell-temp-corrigee');
            const inputCal1Corr = row.querySelector('.input-cal1-correction');
            const inputCorrRef = row.querySelector('.input-corrected-reference');
            const inputResistance = row.querySelector('.input-resistance');
            const cellResCorrigee = row.querySelector('.cell-res-corrigee');
            const inputCal2Corr = row.querySelector('.input-cal2-correction');
            const inputCorrRes = row.querySelector('.input-corrected-resistance');
            const cellTempSonde = row.querySelector('.cell-temp-sonde');
            const inputIndicatedTemp = row.querySelector('.input-temp-ind');
            const cellErreur = row.querySelector('.cell-erreur');
            const cellEmtPoint = row.querySelector('.cell-emt-point');
            const cellConformite = row.querySelector('.cell-conformite');
            const inputConformite = row.querySelector('.input-conformite');

            function calculate() {
                const rawEssai = inputEssai ? inputEssai.value.trim() : '';
                const rawEtalon = inputEtalon ? inputEtalon.value.trim() : '';
                const rawResistance = inputResistance ? inputResistance.value.trim() : '';

                const tEssai = rawEssai !== '' ? parseFloat(rawEssai) : NaN;
                const tEtalon = rawEtalon !== '' ? parseFloat(rawEtalon) : NaN;
                const rSonde = rawResistance !== '' ? parseFloat(rawResistance) : NaN;

                const cal1Id = document.getElementById('calibrator_1')?.value;
                const cal2Id = document.getElementById('calibrator_2')?.value;
                const cal1Points = (cal1Id && calibratorsPointsMap[cal1Id]) ? calibratorsPointsMap[cal1Id] : [];
                const cal2Points = (cal2Id && calibratorsPointsMap[cal2Id]) ? calibratorsPointsMap[cal2Id] : [];

                // 1. استيفاء وتصحيح القيمة المرجعية المطبقة (Calibrator 1)
                let tEtalonCorrige = tEtalon;
                let cal1Correction = 0;
                if (!isNaN(tEtalon)) {
                    const res1 = interpolatePoints(cal1Points, tEtalon);
                    cal1Correction = res1.correction;
                    tEtalonCorrige = tEtalon + cal1Correction;

                    if (cellTempCorrigee) {
                        const spanVal = cellTempCorrigee.querySelector('.ref-corrigee-val');
                        if (spanVal) spanVal.textContent = tEtalonCorrige.toFixed(3);
                        if (res1.hasData && Math.abs(cal1Correction) > 1e-6) {
                            cellTempCorrigee.title = `Correction Calibrator 1: ${(cal1Correction >= 0 ? '+' : '')}${cal1Correction.toFixed(4)} °C`;
                        } else {
                            cellTempCorrigee.title = '';
                        }
                    }
                    if (inputCal1Corr) inputCal1Corr.value = cal1Correction.toFixed(6);
                    if (inputCorrRef) inputCorrRef.value = tEtalonCorrige.toFixed(6);
                } else {
                    if (cellTempCorrigee) {
                        const spanVal = cellTempCorrigee.querySelector('.ref-corrigee-val');
                        if (spanVal) spanVal.textContent = '-';
                        cellTempCorrigee.title = '';
                    }
                    if (inputCal1Corr) inputCal1Corr.value = '';
                    if (inputCorrRef) inputCorrRef.value = '';
                }

                // 2. استيفاء وتصحيح المقاومة المقاسة (Calibrator 2)
                let rSondeCorrigee = rSonde;
                let cal2Correction = 0;
                if (!isNaN(rSonde)) {
                    const res2 = interpolatePoints(cal2Points, rSonde);
                    cal2Correction = res2.correction;
                    rSondeCorrigee = rSonde + cal2Correction;

                    if (cellResCorrigee) {
                        const spanRes = cellResCorrigee.querySelector('.res-corrigee-val');
                        if (spanRes) spanRes.textContent = rSondeCorrigee.toFixed(4);
                        if (res2.hasData && Math.abs(cal2Correction) > 1e-6) {
                            cellResCorrigee.title = `Correction Calibrator 2: ${(cal2Correction >= 0 ? '+' : '')}${cal2Correction.toFixed(4)} Ω`;
                        } else {
                            cellResCorrigee.title = '';
                        }
                    }
                    if (inputCal2Corr) inputCal2Corr.value = cal2Correction.toFixed(6);
                    if (inputCorrRes) inputCorrRes.value = rSondeCorrigee.toFixed(6);
                } else {
                    if (cellResCorrigee) {
                        const spanRes = cellResCorrigee.querySelector('.res-corrigee-val');
                        if (spanRes) spanRes.textContent = '-';
                        cellResCorrigee.title = '';
                    }
                    if (inputCal2Corr) inputCal2Corr.value = '';
                    if (inputCorrRes) inputCorrRes.value = '';
                }

                const emtPoint = !isNaN(tEssai) ? (0.150 + 0.002 * Math.abs(tEssai)) : 0.150;
                if (cellEmtPoint) {
                    cellEmtPoint.textContent = '±' + emtPoint.toFixed(3);
                }

                // 3. حساب درجة حرارة المسبار من المقاومة المصححة (Callendar-Van Dusen)
                if (rawResistance !== '' && !isNaN(rSondeCorrigee) && rSondeCorrigee > 0) {
                    const tSonde = resistanceToTemp(rSondeCorrigee);
                    if (!isNaN(tSonde)) {
                        cellTempSonde.textContent = tSonde.toFixed(3);
                        if (inputIndicatedTemp) inputIndicatedTemp.value = tSonde.toFixed(4);

                        if (!isNaN(tEtalonCorrige)) {
                            const erreur = tSonde - tEtalonCorrige;
                            cellErreur.textContent = (erreur >= 0 ? '+' : '') + erreur.toFixed(3);
                            cellErreur.className = 'px-4 py-2.5 text-center font-mono font-bold cell-erreur ' + (Math.abs(erreur) <= emtPoint + 0.000001 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400');

                            if (Math.abs(erreur) <= emtPoint + 0.000001) {
                                cellConformite.innerHTML = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300/60"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> OK</span>';
                                if (inputConformite) inputConformite.value = 1;
                            } else {
                                cellConformite.innerHTML = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-300/60"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg> NOK</span>';
                                if (inputConformite) inputConformite.value = 0;
                            }
                        } else {
                            cellErreur.textContent = '-';
                            cellConformite.innerHTML = '-';
                            if (inputConformite) inputConformite.value = '';
                        }
                    } else {
                        cellTempSonde.textContent = '-';
                        if (inputIndicatedTemp) inputIndicatedTemp.value = '';
                        cellErreur.textContent = '-';
                        cellConformite.innerHTML = '-';
                        if (inputConformite) inputConformite.value = '';
                    }
                } else {
                    cellTempSonde.textContent = '-';
                    if (inputIndicatedTemp) inputIndicatedTemp.value = '';
                    cellErreur.textContent = '-';
                    cellConformite.innerHTML = '-';
                    if (inputConformite) inputConformite.value = '';
                }
            }

            row.calculate = calculate;
            inputEtalon?.addEventListener('input', calculate);
            inputResistance?.addEventListener('input', calculate);
            calculate();
        });

        document.getElementById('calibrator_1')?.addEventListener('change', function() {
            updateCalibratorStatus();
            rows.forEach(r => r.calculate?.());
        });
        document.getElementById('calibrator_2')?.addEventListener('change', function() {
            updateCalibratorStatus();
            rows.forEach(r => r.calculate?.());
        });

        updateCalibratorStatus();
    });
    </script>
    @endpush
</x-app-layout>