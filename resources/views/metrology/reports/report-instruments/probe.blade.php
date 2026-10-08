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
                        <input type="hidden" id="emt_limit" value="{{ $specifications->accuracy_value }}">

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
                                            <th class="px-4 py-3 text-center">{{ __('Reference Temp T_ref (°C)') }}</th>
                                            <th class="px-4 py-3 text-center">{{ __('Measured Resistance (Ω)') }}</th>
                                            <th class="px-4 py-3 text-center">{{ __('Indicated Temp T_sonde (°C)') }}</th>
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
                                                $appliedTemp = (float)($specifications->range_min + ($percentage / 100) * ($specifications->range_max - $specifications->range_min));
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
                                                <td class="px-4 py-2.5 text-center">
                                                    <input type="number" step="0.001" name="points[{{ $index }}][reference_temperature]"
                                                           value="{{ $refTemp }}" required
                                                           class="input-temp-etalon w-32 text-center text-xs font-mono font-bold rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white py-1.5 px-2 focus:ring-brand-500 focus:border-brand-500" />
                                                </td>
                                                <td class="px-4 py-2.5 text-center">
                                                    <input type="number" step="0.001" name="points[{{ $index }}][measured_resistance]"
                                                           value="{{ $measuredR ?? '' }}" required
                                                           placeholder="100.000"
                                                           class="input-resistance w-32 text-center text-xs font-mono font-bold rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white py-1.5 px-2 focus:ring-brand-500 focus:border-brand-500" />
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

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
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
            const inputResistance = row.querySelector('.input-resistance');
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

                const emtPoint = !isNaN(tEssai) ? (0.150 + 0.002 * Math.abs(tEssai)) : 0.150;
                if (cellEmtPoint) {
                    cellEmtPoint.textContent = '±' + emtPoint.toFixed(3);
                }

                if (rawResistance !== '' && !isNaN(rSonde) && rSonde > 0) {
                    const tSonde = resistanceToTemp(rSonde);
                    if (!isNaN(tSonde)) {
                        cellTempSonde.textContent = tSonde.toFixed(3);
                        if (inputIndicatedTemp) inputIndicatedTemp.value = tSonde.toFixed(4);

                        if (rawEtalon !== '' && !isNaN(tEtalon)) {
                            const erreur = tSonde - tEtalon;
                            cellErreur.textContent = (erreur >= 0 ? '+' : '') + erreur.toFixed(3);
                            cellErreur.className = 'px-4 py-2.5 text-center font-mono font-bold cell-erreur ' + (Math.abs(erreur) <= emtPoint + 0.000001 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400');

                            if (Math.abs(erreur) <= emtPoint + 0.000001) {
                                cellConformite.innerHTML = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300/60"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> OK</span>';
                                if (inputConformite) inputConformite.value = 1;
                            } else {
                                cellConformite.innerHTML = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-300/60"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg> NOK</span>';
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

            inputEtalon?.addEventListener('input', calculate);
            inputResistance?.addEventListener('input', calculate);
            calculate();
        });
    });
    </script>
    @endpush
</x-app-layout>