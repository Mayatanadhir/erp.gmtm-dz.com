<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-tool-icon name="prover" class="w-14 h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ __('Nouveau Rapport de Calibration Prover & Jauges') }}
                        </h2>
                        <x-badge variant="neutral" size="sm">report-Prover</x-badge>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Initialisation d\'une session de calibration pour tubes étalons (Compact Provers / Pipe Provers) et jauges volumétriques étalons') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('metrology.reports.report-prover.index') }}">
                    <x-secondary-button type="button" class="gap-2 text-xs">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>{{ __('Tableau Provers') }}</span>
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
                                <x-tool-icon name="prover" class="w-5 h-5" />
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-brand-900 dark:text-brand-200">
                                    {{ __('Module de Métrologie Volumétrique (Provers & Jauges)') }}
                                </h4>
                                <p class="text-[11px] text-brand-700/80 dark:text-brand-300/80 mt-0.5">
                                    {{ __('Configuration de la session de calibration par eau dégazée / hydrocarbures, vérification du volume de base V0 et répétabilité.') }}
                                </p>
                            </div>
                        </div>
                        <x-badge variant="info" size="sm" class="hidden sm:inline-flex shrink-0">
                            {{ __('API MPMS Ch. 4 / ISO 7278') }}
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
                        <input type="hidden" name="category" value="prover">
                        <input type="hidden" name="status" value="progress">

                        <!-- Section: General Report Info -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-100 dark:border-gray-700/60 flex items-center gap-2">
                                <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>{{ __('Informations Générales du Rapport Prover') }}</span>
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Report Number -->
                                <div>
                                    <x-input-label for="report_number" class="text-xs mb-1">
                                        {{ __('Numéro de Rapport') }} <span class="text-rose-500">*</span>
                                    </x-input-label>
                                    <input type="text"
                                           id="report_number"
                                           name="report_number"
                                           value="{{ old('report_number', $suggestedNumber) }}"
                                           required
                                           dir="ltr"
                                           placeholder="RPT-PRV-YYYY-NNN"
                                           class="input-base w-full text-xs font-mono font-bold tracking-wider py-2 px-3" />
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">{{ __('Numéro séquentiel officiel pour le rapport prover.') }}</p>
                                </div>

                                <!-- Mission -->
                                <div>
                                    <x-input-label for="mission_id" class="text-xs mb-1">
                                        {{ __('Mission / Site Associé') }}
                                    </x-input-label>
                                    <select id="mission_id"
                                            name="mission_id"
                                            class="input-base w-full text-xs py-2 px-3">
                                        <option value="">{{ __('--- Sélectionner une Mission ---') }}</option>
                                        @foreach($missions as $m)
                                            <option value="{{ $m->id }}" @selected(old('mission_id') == $m->id)>
                                                {{ $m->code ?? $m->reference ?? 'MS-'.$m->id }} &bull; {{ $m->site?->short_name ?? $m->site?->name ?? __('Sans Site') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">{{ __('Le choix de la mission charge les tubes étalons et jauges présents sur le site.') }}</p>
                                </div>

                                <!-- Site Name (Readonly) -->
                                <div>
                                    <x-input-label for="site_name_display" class="text-xs mb-1">
                                        {{ __('Site d\'Intervention') }}
                                    </x-input-label>
                                    <input type="text"
                                           id="site_name_display"
                                           readonly
                                           placeholder="{{ __('Sélectionné automatiquement depuis la mission...') }}"
                                           class="w-full text-xs rounded-md border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60 text-gray-600 dark:text-gray-300 font-semibold py-2 px-3 cursor-not-allowed focus:outline-none" />
                                </div>

                                <!-- Status (Locked to Progress on Creation) -->
                                <div>
                                    <x-input-label class="text-xs mb-1">
                                        {{ __('Statut Initial') }}
                                    </x-input-label>
                                    <div class="flex items-center h-[38px] px-3 rounded-md border border-amber-200 dark:border-amber-800/40 bg-amber-50/50 dark:bg-amber-950/20 text-xs font-semibold">
                                        <x-badge variant="warning" :dot="true" :dotPing="true" size="sm">
                                            {{ __('En cours (Progress)') }}
                                        </x-badge>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Reference Calibrators (Standard Measures) -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-100 dark:border-gray-700/60 flex items-center gap-2">
                                <x-tool-icon name="prover" class="w-5 h-5 shrink-0" />
                                <span>{{ __('Équipements et Étalons de Référence Volumétrique') }}</span>
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label class="text-xs mb-1">
                                        {{ __('Jauge Volumétrique Étalon (Master Gauge)') }}
                                    </x-input-label>
                                    <select name="default_calibrators[flow_computer_calibrator_1]" class="input-base w-full text-xs py-2 px-3">
                                        <option value="">{{ __('--- Sélectionner la Jauge de Référence ---') }}</option>
                                        @foreach($calibratorsData['flow_computer_calibrator_1'] ?? [] as $eq)
                                            <option value="{{ $eq->id }}">{{ $eq->tag_number ?? $eq->designation }} &bull; {{ $eq->serial_number }}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">{{ __('Jauge étalon certifiée par certificat d\'étalonnage valide.') }}</p>
                                </div>

                                <div>
                                    <x-input-label class="text-xs mb-1">
                                        {{ __('Thermomètre Numérique Étalon') }}
                                    </x-input-label>
                                    <select name="default_calibrators[temperature_calibrator_1]" class="input-base w-full text-xs py-2 px-3">
                                        <option value="">{{ __('--- Sélectionner le Thermomètre Étalon ---') }}</option>
                                        @foreach($calibratorsData['temperature_calibrator_1'] ?? [] as $eq)
                                            <option value="{{ $eq->id }}">{{ $eq->tag_number ?? $eq->designation }} &bull; {{ $eq->serial_number }}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">{{ __('Pour la mesure de température fluide Prover et Jauge (API Ch. 12).') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Eligible Provers & Gauges Checklist -->
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-2 border-b border-gray-100 dark:border-gray-700/60">
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <x-tool-icon name="prover" class="w-5 h-5 shrink-0" />
                                        <span>{{ __('Tubes Étalons et Jauges Disponibles sur le Site') }}</span>
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ __('Cochez les tubes étalons (Provers) à inclure dans ce rapport de calibration.') }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-secondary-button type="button" id="btn_select_all" class="!px-2.5 !py-1 !text-xs font-semibold">
                                        {{ __('Tout Cocher') }}
                                    </x-secondary-button>
                                    <x-secondary-button type="button" id="btn_deselect_all" class="!px-2.5 !py-1 !text-xs font-semibold">
                                        {{ __('Tout Décocher') }}
                                    </x-secondary-button>
                                </div>
                            </div>

                            <div id="instruments_container" class="space-y-2">
                                <div id="instruments_empty_notice" class="py-8 text-center text-xs text-gray-500 dark:text-gray-400">
                                    <svg class="w-8 h-8 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    <span>{{ __('Veuillez sélectionner une mission pour charger les tubes étalons et jauges.') }}</span>
                                </div>
                                <div id="instruments_list" class="grid grid-cols-1 md:grid-cols-2 gap-3 hidden">
                                    <!-- Populated via AJAX when mission is selected -->
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700/60">
                            <a href="{{ route('metrology.reports.report-prover.index') }}">
                                <x-secondary-button type="button" class="text-xs">
                                    {{ __('Annuler') }}
                                </x-secondary-button>
                            </a>

                            <x-primary-button type="submit" class="text-xs gap-2">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ __('Créer le Rapport Prover & Démarrer la Calibration') }}</span>
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
            const category = 'prover';

            function loadMissionInstruments(missionId) {
                if (!missionId) {
                    siteDisplay.value = '';
                    emptyNotice.classList.remove('hidden');
                    instrumentsList.classList.add('hidden');
                    instrumentsList.innerHTML = '';
                    return;
                }

                emptyNotice.textContent = "{{ __('Chargement des équipements Prover...') }}";
                emptyNotice.classList.remove('hidden');
                instrumentsList.classList.add('hidden');

                const url = `{{ url('metrology/reports/mission-details') }}/${missionId}?category=${category}`;

                fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(response => response.json())
                    .then(data => {
                        siteDisplay.value = data.site_name || '-';

                        if (!data.instruments || data.instruments.length === 0) {
                            emptyNotice.textContent = "{{ __('Aucun Prover ou Jauge étalon répertorié sur le site de cette mission.') }}";
                            emptyNotice.classList.remove('hidden');
                            instrumentsList.classList.add('hidden');
                            instrumentsList.innerHTML = '';
                            return;
                        }

                        emptyNotice.classList.add('hidden');
                        instrumentsList.classList.remove('hidden');
                        instrumentsList.innerHTML = '';

                        data.instruments.forEach(inst => {
                            const imageHtml = inst.image_url
                                ? `<img src="${inst.image_url}" alt="${inst.tag_number || ''}" class="w-11 h-11 object-contain rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60 p-0.5 shrink-0 shadow-xs" />`
                                : `<div class="w-11 h-11 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-100/70 dark:bg-gray-700/50 flex items-center justify-center text-gray-400 dark:text-gray-500 shrink-0">
                                     <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                   </div>`;

                            const card = document.createElement('label');
                            card.className = 'group flex items-center gap-3 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-brand-500 dark:hover:border-brand-500 bg-white dark:bg-gray-800 cursor-pointer transition-all shadow-sm';
                            card.innerHTML = `
                                <input type="checkbox" name="instrument_ids[]" value="${inst.id}" checked
                                       class="instrument-check w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-brand-600 focus:ring-brand-500 dark:bg-gray-700 dark:focus:ring-offset-gray-800 shrink-0" />
                                ${imageHtml}
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <span dir="ltr" class="font-bold text-xs text-gray-900 dark:text-white font-mono">${inst.tag_number || '---'}</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-brand-500/10 text-brand-800 dark:text-brand-300 border border-brand-500/20 capitalize">${inst.instrument_type}</span>
                                    </div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                        SN: <strong class="font-mono text-gray-700 dark:text-gray-300">${inst.serial_number || '---'}</strong> &bull; Base Volume: <strong class="font-mono text-gray-700 dark:text-gray-300">${inst.spec?.nominal_base_volume ? inst.spec.nominal_base_volume + ' L' : '---'}</strong>
                                    </div>
                                </div>
                            `;
                            instrumentsList.appendChild(card);
                        });
                    })
                    .catch(err => {
                        console.error('Error loading mission details:', err);
                        emptyNotice.textContent = "{{ __('Erreur lors du chargement des équipements.') }}";
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

