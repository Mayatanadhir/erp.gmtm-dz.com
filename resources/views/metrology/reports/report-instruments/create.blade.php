<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-tool-icon name="instruments" class="w-14 h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ __('New Measuring Instruments Report') }}
                        </h2>
                        <x-badge variant="neutral" size="sm">{{ __('Measuring Instruments') }}</x-badge>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Initialize a metrological verification session for transmitters (PT/TT/PDT), Pt100 RTD probes, and flow computers.') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('metrology.reports.report-instruments.index') }}">
                    <x-secondary-button type="button" class="gap-2 text-xs">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>{{ __('Instruments Dashboard') }}</span>
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

                <!-- Main Form Area -->
                <main class="flex-1 w-full min-w-0 space-y-6">

                    <!-- Info Banner -->
                    <div class="rounded-xl bg-brand-50/70 dark:bg-brand-950/30 p-4 border border-brand-200/70 dark:border-brand-800/50 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-brand-600 dark:bg-brand-500 text-white shadow-sm shrink-0">
                                <x-tool-icon name="instruments" class="w-5 h-5" />
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-brand-900 dark:text-brand-200">
                                    {{ __('Industrial Instruments Metrology Module') }}
                                </h4>
                                <p class="text-[11px] text-brand-700/80 dark:text-brand-300/80 mt-0.5">
                                    {{ __('This form configures a certified calibration session for 4-20mA / HART measurement loops, RTD probes, and acquisition chains.') }}
                                </p>
                            </div>
                        </div>
                        <x-badge variant="info" size="sm" class="hidden sm:inline-flex shrink-0">
                            {{ __('OIML / CEI 60751') }}
                        </x-badge>
                    </div>

                    @if(isset($errors) && $errors->any())
                        <x-alert variant="danger" :title="__('Please correct the following errors:')" :dismissible="true">
                            <ul class="list-disc list-inside space-y-0.5 mt-2 text-xs">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    @endif

                    <form action="{{ route('metrology.reports.store') }}" method="POST" id="report_form" class="space-y-6">
                        @csrf
                        <input type="hidden" name="category" value="instruments">
                        <input type="hidden" name="status" value="progress">

                        <!-- Section: General Report Info -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-100 dark:border-gray-700/60 flex items-center gap-2">
                                <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>{{ __('General Report Information') }}</span>
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Report Number -->
                                <div>
                                    <x-input-label for="report_number" class="text-xs mb-1">
                                        {{ __('Report Number') }} <span class="text-rose-500">*</span>
                                    </x-input-label>
                                    <input type="text"
                                           id="report_number"
                                           name="report_number"
                                           value="{{ old('report_number', $suggestedNumber) }}"
                                           required
                                           dir="ltr"
                                           placeholder="RPT-YYYY-NNN"
                                           class="input-base w-full text-xs font-mono font-bold tracking-wider py-2 px-3" />
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">{{ __('Official sequential number generated automatically.') }}</p>
                                </div>

                                <!-- Mission -->
                                <div>
                                    <x-input-label for="mission_id" class="text-xs mb-1">
                                        {{ __('Associated Mission / Site') }}
                                    </x-input-label>
                                    <select id="mission_id"
                                            name="mission_id"
                                            class="input-base w-full text-xs py-2 px-3">
                                        <option value="">{{ __('--- Select a Mission ---') }}</option>
                                        @foreach($missions as $m)
                                            <option value="{{ $m->id }}" @selected(old('mission_id') == $m->id)>
                                                {{ $m->code ?? $m->reference ?? 'MS-'.$m->id }} &bull; {{ $m->site?->short_name ?? $m->site?->name ?? __('No Site') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">{{ __('Selecting a mission loads its site, eligible instruments, and the calibrators deployed on it.') }}</p>
                                </div>

                                <!-- Site Name (Readonly) -->
                                <div>
                                    <x-input-label for="site_name_display" class="text-xs mb-1">
                                        {{ __('Intervention Site') }}
                                    </x-input-label>
                                    <input type="text"
                                           id="site_name_display"
                                           readonly
                                           placeholder="{{ __('Automatically selected from mission...') }}"
                                           class="w-full text-xs rounded-md border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60 text-gray-600 dark:text-gray-300 font-semibold py-2 px-3 cursor-not-allowed focus:outline-none" />
                                </div>

                                <!-- Initial Status -->
                                <div>
                                    <x-input-label class="text-xs mb-1">
                                        {{ __('Initial Status') }}
                                    </x-input-label>
                                    <div class="flex items-center h-[38px] px-3 rounded-md border border-amber-200 dark:border-amber-800/40 bg-amber-50/50 dark:bg-amber-950/20 text-xs font-semibold">
                                        <x-badge variant="warning" :dot="true" :dotPing="true" size="sm">
                                            {{ __('In Progress') }}
                                        </x-badge>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Reference Calibrators (Standard Instruments) -->
                        @include('metrology.reports.report-instruments.partials.calibrators_card')

                        <!-- Section: Eligible Instruments Checklist -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-2 border-b border-gray-100 dark:border-gray-700/60">
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <x-tool-icon name="instruments" class="w-5 h-5 shrink-0" />
                                        <span>{{ __('Measuring Instruments Available on Site') }}</span>
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ __('Check the transmitters, Pt100 RTD probes, and flow computers to include in this report.') }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-secondary-button type="button" id="btn_select_all" class="!px-2.5 !py-1 !text-xs font-semibold">
                                        {{ __('Select All') }}
                                    </x-secondary-button>
                                    <x-secondary-button type="button" id="btn_deselect_all" class="!px-2.5 !py-1 !text-xs font-semibold">
                                        {{ __('Deselect All') }}
                                    </x-secondary-button>
                                </div>
                            </div>

                            <div id="instruments_container" class="space-y-2">
                                <div id="instruments_empty_notice" class="py-8 text-center text-xs text-gray-500 dark:text-gray-400">
                                    <svg class="w-8 h-8 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    <span>{{ __('Please select a mission to load corresponding instruments.') }}</span>
                                </div>
                                <div id="instruments_list" class="grid grid-cols-1 md:grid-cols-2 gap-3 hidden">
                                    <!-- Populated via AJAX when mission is selected -->
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700/60">
                            <a href="{{ route('metrology.reports.report-instruments.index') }}">
                                <x-secondary-button type="button" class="text-xs">
                                    {{ __('Cancel') }}
                                </x-secondary-button>
                            </a>

                            <x-primary-button type="submit" class="text-xs gap-2">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ __('Create Report & Start Data Entry') }}</span>
                            </x-primary-button>
                        </div>
                    </form>

                </main>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const missionSelect = document.getElementById('mission_id');
            const siteDisplay = document.getElementById('site_name_display');
            const emptyNotice = document.getElementById('instruments_empty_notice');
            const instrumentsList = document.getElementById('instruments_list');
            const btnSelectAll = document.getElementById('btn_select_all');
            const btnDeselectAll = document.getElementById('btn_deselect_all');
            const calibratorsCard = document.getElementById('calibrators_card');
            const calibratorSelects = document.querySelectorAll('[data-calibrator-family]');
            const category = 'instruments';
            const noMissionNotice = @json(__('Please select a mission to load corresponding instruments.'));

            /**
             * Rebuild every calibrator dropdown with the equipment deployed on the selected mission.
             * A previous choice is kept only if that calibrator is still available.
             */
            function populateCalibrators(calibratorsByFamily, placeholderKey) {
                calibratorSelects.forEach(select => {
                    const options = (calibratorsByFamily && calibratorsByFamily[select.dataset.calibratorFamily]) || [];
                    const previousValue = select.value;
                    const placeholder = options.length > 0
                        ? calibratorsCard.dataset.placeholderSelect
                        : calibratorsCard.dataset[placeholderKey];

                    select.replaceChildren(new Option(placeholder, ''));
                    options.forEach(option => {
                        select.add(new Option(option.label, option.id, false, String(option.id) === previousValue));
                    });
                });
            }

            function loadMissionInstruments(missionId) {
                if (!missionId) {
                    siteDisplay.value = '';
                    emptyNotice.textContent = noMissionNotice;
                    emptyNotice.classList.remove('hidden');
                    instrumentsList.classList.add('hidden');
                    instrumentsList.innerHTML = '';
                    populateCalibrators({}, 'placeholderNoMission');
                    return;
                }

                emptyNotice.textContent = "{{ __('Loading instruments in progress...') }}";
                emptyNotice.classList.remove('hidden');
                instrumentsList.classList.add('hidden');

                const url = `{{ url('metrology/reports/mission-details') }}/${missionId}?category=${category}`;

                fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(response => response.json())
                    .then(data => {
                        siteDisplay.value = data.site_name || '-';
                        populateCalibrators(data.calibrators, 'placeholderNone');

                        if (!data.instruments || data.instruments.length === 0) {
                            emptyNotice.textContent = "{{ __('No measuring instruments (transmitters, probes, flow computers) found on this mission site.') }}";
                            emptyNotice.classList.remove('hidden');
                            instrumentsList.classList.add('hidden');
                            instrumentsList.innerHTML = '';
                            return;
                        }

                        emptyNotice.classList.add('hidden');
                        instrumentsList.classList.remove('hidden');
                        instrumentsList.innerHTML = '';

                        function escapeHtml(str) {
                            if (str === null || str === undefined) return '';
                            return String(str)
                                .replace(/&/g, '&amp;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;')
                                .replace(/"/g, '&quot;')
                                .replace(/'/g, '&#039;');
                        }

                        data.instruments.forEach(inst => {
                            const rangeStr = (inst.range_min !== null && inst.range_max !== null)
                                ? `${inst.range_min} - ${inst.range_max} ${inst.unit || ''}`
                                : '---';

                            const safeId = parseInt(inst.id, 10);
                            const safeTag = escapeHtml(inst.tag_number || '---');
                            const safeSerial = escapeHtml(inst.serial_number || '---');
                            const safeType = escapeHtml(inst.instrument_type || '');
                            const safeRange = escapeHtml(rangeStr);
                            const safeImageUrl = (inst.image_url && (inst.image_url.startsWith('/') || inst.image_url.startsWith('http://') || inst.image_url.startsWith('https://')))
                                ? encodeURI(inst.image_url)
                                : null;

                            const imageHtml = safeImageUrl
                                ? `<img src="${safeImageUrl}" alt="${safeTag}" class="w-11 h-11 object-contain rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60 p-0.5 shrink-0 shadow-xs" />`
                                : `<div class="w-11 h-11 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-100/70 dark:bg-gray-700/50 flex items-center justify-center text-gray-400 dark:text-gray-500 shrink-0">
                                     <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                   </div>`;

                            const card = document.createElement('label');
                            card.className = 'group flex items-center gap-3 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-brand-500 dark:hover:border-brand-500 bg-white dark:bg-gray-800 cursor-pointer transition-all shadow-sm';
                            card.innerHTML = `
                                <input type="checkbox" name="instrument_ids[]" value="${safeId}" checked
                                       class="instrument-check w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-brand-600 focus:ring-brand-500 dark:bg-gray-700 dark:focus:ring-offset-gray-800 shrink-0" />
                                ${imageHtml}
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <span dir="ltr" class="font-bold text-xs text-gray-900 dark:text-white font-mono">${safeTag}</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-brand-500/10 text-brand-800 dark:text-brand-300 border border-brand-500/20 capitalize">${safeType}</span>
                                    </div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1.5 flex-wrap">
                                        <span>SN: <strong class="font-mono text-gray-700 dark:text-gray-300">${safeSerial}</strong></span>
                                        <span>&bull;</span>
                                        <span>${"{{ __('Range') }}"}: <span class="font-mono text-gray-700 dark:text-gray-300">${safeRange}</span></span>
                                    </div>
                                </div>
                            `;
                            instrumentsList.appendChild(card);
                        });
                    })
                    .catch(err => {
                        console.error('Error loading mission details:', err);
                        emptyNotice.textContent = "{{ __('Error loading mission instruments.') }}";
                        emptyNotice.classList.remove('hidden');
                    });
            }

            missionSelect.addEventListener('change', function () {
                loadMissionInstruments(this.value);
            });

            btnSelectAll.addEventListener('click', function () {
                document.querySelectorAll('.instrument-check').forEach(cb => cb.checked = true);
            });

            btnDeselectAll.addEventListener('click', function () {
                document.querySelectorAll('.instrument-check').forEach(cb => cb.checked = false);
            });

            if (missionSelect.value) {
                loadMissionInstruments(missionSelect.value);
            }
        });
    </script>
    @endpush
</x-app-layout>
