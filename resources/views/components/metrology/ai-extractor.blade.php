@php
    // تجميع الإعدادات والترجمات لتمريرها بشكل آمن إلى JavaScript
    $aiConfig = [
        'urls' => [
            'upload' => route('metrology.extractions.upload'),
            'unapplied' => route('metrology.extractions.unapplied'),
            'base' => url('/metrology/extractions'),
        ],
        'csrfToken' => csrf_token(),
        'translations' => [
            'invalidPdf' => __('Please upload a valid PDF document.'),
            'sizeLimit' => __('File size exceeds 20MB limit.'),
            'uploading' => __('Uploading PDF document...'),
            'uploadFailed' => __('Upload failed.'),
            'extractionReady' => __('Extraction Ready!'),
            'optimizing' => __('Optimizing & CAS Deduplication...'),
            'extractionError' => __('Extraction upload error.'),
            'multimodalAnalysis' => __('AI Multimodal Analysis...'),
            'aiKey' => __('AI Key'),
            'processFailed' => __('Extraction process failed.'),
            'timeout' => __('Extraction timed out. Please try again.'),
            'previewFailed' => __('Failed to load extraction preview.'),
            'months' => __('months'),
            'points' => __('points'),
            'specifications' => __('specifications'),
            'active' => __('Active'),
            'idle' => __('Idle'),
        ]
    ];
@endphp

<div class="mb-6 space-y-4" x-data="aiExtractor({{ Js::from($aiConfig) }})">
    <!-- AI Dropzone Banner -->
    <div 
        @dragover.prevent="dragOver = true"
        @dragleave.prevent="dragOver = false"
        @drop.prevent="handleDrop($event)"
        :class="dragOver ? 'border-brand-500 bg-emerald-50 dark:bg-emerald-950/30 ring-2 ring-brand-500/20' : 'border-gray-300 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50'"
        class="relative rounded-2xl border-2 border-dashed p-5 transition-all duration-200 shadow-sm"
    >
        <input 
            type="file" 
            x-ref="aiFileInput" 
            @change="handleFileSelect($event)" 
            accept="application/pdf" 
            class="hidden"
        >

        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4 text-center sm:text-start">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-brand-500 to-brand-600 text-white flex items-center justify-center shrink-0 shadow-md shadow-brand-500/20">
                    <i class="fas fa-wand-magic-sparkles text-xl"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 justify-center sm:justify-start">
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ __('AI Certificate Auto-Fill') }}
                        </h4>
                        <x-badge variant="primary">
                            {{ __('ISO 17025 Engine') }}
                        </x-badge>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Drag and drop calibration certificate PDF or click to browse for automatic data extraction') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <x-secondary-button type="button" @click="openHistory()">
                    <i class="fas fa-history text-xs me-1.5"></i>
                    {{ __('Recent Extractions') }}
                </x-secondary-button>

                <x-primary-button type="button" @click="$refs.aiFileInput.click()" x-bind:disabled="aiState.loading">
                    <!-- أيقونة الرفع العادية تظهر فقط عندما لا يكون هناك تحميل -->
                    <i x-show="!aiState.loading" class="fas fa-cloud-arrow-up text-xs me-1.5"></i>
                    
                    <!-- أيقونة الدوران تظهر أثناء التحميل -->
                    <i x-show="aiState.loading" x-cloak class="fas fa-circle-notch fa-spin text-xs me-1.5"></i>
                    
                    {{ __('Extract PDF with AI') }}
                </x-primary-button>
            </div>
        </div>

        <!-- Extraction Progress Indicator -->
        <div x-show="aiState.loading" x-cloak class="mt-4 pt-4 border-t border-brand-100 dark:border-gray-700/60">
            <div class="flex items-center justify-between text-xs mb-2">
                <span class="font-medium text-brand-700 dark:text-brand-300 flex items-center gap-2">
                    <i class="fas fa-circle-notch fa-spin text-brand-600"></i>
                    <span x-text="aiState.stepMessage"></span>
                </span>

                <!-- مؤشر حالة المفتاح (AI Key State Indicator) -->
                <div x-show="aiState.currentAiKey" 
                     x-transition 
                     class="hidden sm:flex items-center gap-1.5 px-2 py-0.5 rounded-md border text-[10px] font-mono transition-colors"
                     :class="aiState.keyStatus === 'active' ? 'bg-emerald-50/50 border-emerald-200 dark:bg-emerald-500/10 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-gray-50 border-gray-200 dark:bg-gray-800 dark:border-gray-700 text-gray-500'">
                    
                    <!-- النقطة النابضة (Ping Dot) -->
                    <span class="relative flex h-1.5 w-1.5">
                        <span x-show="aiState.keyStatus === 'active'" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-1.5 w-1.5" :class="aiState.keyStatus === 'active' ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                    </span>
                    
                    <span>
                        Key #<span x-text="aiState.currentAiKey"></span> 
                        <span x-text="aiState.keyStatus === 'active' ? '(' + config.translations.active + ')' : '(' + config.translations.idle + ')'"></span>
                    </span>
                </div>

                <span class="font-mono text-gray-500 dark:text-gray-400" x-text="aiState.progressPercent + '%'"></span>
            </div>
            
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                <div class="bg-gradient-to-r from-brand-500 to-brand-600 h-2 rounded-full transition-all duration-300"
                     :style="'width: ' + aiState.progressPercent + '%'">
                </div>
            </div>
            <p class="text-[11px] text-gray-400 mt-1" x-text="aiState.fileName"></p>
        </div>

        <!-- Extraction Error Banner -->
        <div x-show="aiState.errorMessage" x-cloak class="mt-4 pt-3 border-t border-rose-200 dark:border-rose-500/30">
            <div class="flex items-center justify-between text-xs text-rose-600 dark:text-rose-400">
                <div class="flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span x-text="aiState.errorMessage"></span>
                </div>
                <button type="button" @click="aiState.errorMessage = null" class="text-rose-400 hover:text-rose-600 dark:hover:text-rose-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Success Toast -->
        <div x-show="aiState.appliedSuccess" x-cloak class="mt-4 pt-3 border-t border-emerald-200 dark:border-emerald-500/30">
            <div class="flex items-center gap-2 text-xs text-emerald-600 dark:text-emerald-400">
                <i class="fas fa-check-circle"></i>
                <span>{{ __('Data applied to certificate form successfully.') }}</span>
            </div>
        </div>
    </div>

    <!-- Review & Apply Modal -->
    <div 
        x-show="aiState.previewModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog" 
        aria-modal="true"
    >
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="aiState.previewModalOpen = false"></div>

        <div class="flex min-h-screen items-center justify-center p-4">
            <div 
                class="relative w-full max-w-5xl rounded-2xl bg-white dark:bg-gray-800 shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden transform transition-all"
                @click.stop
            >
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/80">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-brand-500/10 dark:bg-brand-500/20 border border-brand-500/20 dark:border-brand-500/30 text-brand-600 dark:text-brand-300 flex items-center justify-center">
                            <i class="fas fa-file-invoice text-base"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ __('Review Extracted Certificate Data') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Review and confirm the data extracted by Gemini AI before applying it to the form.') }}
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="aiState.previewModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 max-h-[72vh] overflow-y-auto space-y-6">
                    <!-- Equipment Compatibility Audit Card -->
                    <template x-if="aiState.extractedData">
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800 shadow-sm">
                            <div class="px-4 py-3 bg-gray-50/80 dark:bg-gray-700/40 border-b border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/70 flex items-center justify-center text-xs">
                                        <i class="fas fa-shield-halved"></i>
                                    </div>
                                    <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                                        {{ __('Equipment Compatibility Audit') }}
                                    </h4>
                                </div>
                                <div>
                                    <template x-if="compatibilityAudit?.badgeVariant === 'success'">
                                        <x-badge variant="success"><span x-text="compatibilityAudit.badgeLabel"></span></x-badge>
                                    </template>
                                    <template x-if="compatibilityAudit?.badgeVariant === 'warning'">
                                        <x-badge variant="warning"><span x-text="compatibilityAudit.badgeLabel"></span></x-badge>
                                    </template>
                                    <template x-if="compatibilityAudit?.badgeVariant === 'danger'">
                                        <x-badge variant="danger"><span x-text="compatibilityAudit.badgeLabel"></span></x-badge>
                                    </template>
                                    <template x-if="compatibilityAudit?.badgeVariant === 'neutral'">
                                        <x-badge variant="neutral"><span x-text="compatibilityAudit.badgeLabel"></span></x-badge>
                                    </template>
                                </div>
                            </div>

                            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                                <!-- Certificate Device (from AI) -->
                                <div class="p-3.5 rounded-lg border border-gray-100 dark:border-gray-700/60 bg-gray-50/50 dark:bg-gray-900/30 space-y-2">
                                    <div class="flex items-center gap-2 text-brand-600 dark:text-brand-400 font-semibold border-b border-gray-200/60 dark:border-gray-700/60 pb-1.5">
                                        <i class="fas fa-file-contract"></i>
                                        <span>{{ __('Certificate Device') }}</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-[11px]">
                                        <div>
                                            <span class="text-gray-400 block">{{ __('Model / Type') }}</span>
                                            <span class="font-semibold text-gray-900 dark:text-white" x-text="aiState.extractedData.device?.model || '—'"></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 block">{{ __('Serial Number') }}</span>
                                            <span class="font-mono font-semibold text-gray-900 dark:text-white" x-text="aiState.extractedData.device?.serial_number || '—'"></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 block">{{ __('Manufacturer / Brand') }}</span>
                                            <span class="text-gray-700 dark:text-gray-300" x-text="aiState.extractedData.device?.manufacturer || '—'"></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 block">{{ __('Designation') }}</span>
                                            <span class="text-gray-700 dark:text-gray-300 truncate block" x-text="aiState.extractedData.device?.designation || '—'"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Selected Equipment (in Form) -->
                                <div class="p-3.5 rounded-lg border border-gray-100 dark:border-gray-700/60 bg-gray-50/50 dark:bg-gray-900/30 space-y-2">
                                    <div class="flex items-center justify-between border-b border-gray-200/60 dark:border-gray-700/60 pb-1.5">
                                        <div class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400 font-semibold">
                                            <i class="fas fa-microchip"></i>
                                            <span>{{ __('Selected Equipment') }}</span>
                                        </div>
                                        <span class="text-[10px] text-gray-400" x-text="(currentSpecs?.length || 0) + ' ' + config.translations.specifications"></span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-[11px]">
                                        <div class="col-span-2 sm:col-span-1">
                                            <span class="text-gray-400 block">{{ __('Equipment') }}</span>
                                            <span class="font-semibold text-gray-900 dark:text-white" x-text="currentEquipment ? (currentEquipment.name + ' (' + currentEquipment.code + ')') : '—'"></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 block">{{ __('Serial Number') }}</span>
                                            <span class="font-mono font-semibold text-gray-900 dark:text-white" x-text="currentEquipment?.serial_number || '—'"></span>
                                        </div>
                                        <div class="col-span-2">
                                            <span class="text-gray-400 block">{{ __('Designation') }}</span>
                                            <span class="text-gray-700 dark:text-gray-300 truncate block" x-text="currentEquipment?.designation || '—'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Audit Verdict Message & Missing Standards Warning -->
                            <div class="px-4 py-2.5 bg-gray-50/50 dark:bg-gray-700/20 border-t border-gray-100 dark:border-gray-700 text-xs">
                                <template x-if="compatibilityAudit">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-300">
                                            <i class="fas fa-circle-info text-xs" :class="{
                                                'text-emerald-500': compatibilityAudit.badgeVariant === 'success',
                                                'text-amber-500': compatibilityAudit.badgeVariant === 'warning',
                                                'text-rose-500': compatibilityAudit.badgeVariant === 'danger'
                                            }"></i>
                                            <span x-text="compatibilityAudit.message"></span>
                                        </div>
                                        <template x-if="compatibilityAudit.missingStandards?.length > 0">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[11px] text-gray-400">{{ __('Missing') }}:</span>
                                                <template x-for="sym in compatibilityAudit.missingStandards" :key="sym">
                                                    <x-badge variant="danger"><span x-text="sym"></span></x-badge>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <!-- Suggested Equipment Match Bar with One-Click Switch -->
                                <template x-if="suggestedEquipment">
                                    <div class="mt-2.5 p-3 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                        <div class="flex items-center gap-2.5 text-xs text-indigo-900 dark:text-indigo-200">
                                            <div class="w-6 h-6 rounded-full bg-indigo-200 dark:bg-indigo-500/30 text-indigo-700 dark:text-indigo-300 flex items-center justify-center shrink-0 text-xs">
                                                <i class="fas fa-lightbulb"></i>
                                            </div>
                                            <div>
                                                <span class="font-semibold">{{ __('Suggested Equipment Match') }}:</span>
                                                <span class="ms-1 font-bold" x-text="suggestedEquipment.name + ' (' + suggestedEquipment.code + ')'"></span>
                                                <span class="text-indigo-600 dark:text-indigo-400 ms-1 font-mono text-[11px]" x-text="'[S/N: ' + (suggestedEquipment.serial_number || '—') + ']'"></span>
                                            </div>
                                        </div>
                                        <x-primary-button type="button" @click="switchEquipment(suggestedEquipment.id)" class="py-1 px-3 text-xs shrink-0">
                                            <i class="fas fa-arrow-right-arrow-left me-1.5 text-xs"></i>
                                            {{ __('Switch to this Equipment') }}
                                        </x-primary-button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Extracted Header Metadata -->
                    <template x-if="aiState.extractedData?.header">
                        <div>
                            <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                <i class="fas fa-tag text-brand-600 text-xs"></i>
                                {{ __('Certificate Header Information') }}
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700/60 text-xs">
                                <div>
                                    <span class="text-gray-400 block text-[11px]">{{ __('Certificate Reference / Number') }}</span>
                                    <span class="font-semibold text-gray-900 dark:text-white" x-text="aiState.extractedData.header.reference || '—'"></span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[11px]">{{ __('Accredited Laboratory') }}</span>
                                    <span class="font-semibold text-gray-900 dark:text-white" x-text="aiState.extractedData.header.laboratory_name || '—'"></span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[11px]">{{ __('Calibration Date') }}</span>
                                    <span class="font-semibold text-gray-900 dark:text-white" x-text="aiState.extractedData.header.calibration_date || '—'"></span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[11px]">{{ __('Validity Period (Months)') }}</span>
                                    <span class="font-semibold text-gray-900 dark:text-white" x-text="aiState.extractedData.header.validity_period_months ? (aiState.extractedData.header.validity_period_months + ' ' + config.translations.months) : '—'"></span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[11px]">{{ __('Calculated Expiry Date') }}</span>
                                    <span class="font-semibold text-gray-900 dark:text-white" x-text="aiState.extractedData.header.expiry_date || '—'"></span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[11px]">{{ __('Environmental Conditions') }}</span>
                                    <span class="font-semibold text-gray-900 dark:text-white" x-text="aiState.extractedData.header.environmental_conditions || '—'"></span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Table Routing & Specification Mapping Plan -->
                    <template x-if="aiState.extractedData?.standards?.length > 0">
                        <div class="space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                                        <i class="fas fa-network-wired text-brand-600 text-xs"></i>
                                        {{ __('Table Routing & Specification Mapping Plan') }}
                                    </h4>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ __('Verify that each calibration table in the PDF is correctly assigned to its corresponding equipment specification before storing.') }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <x-badge variant="primary">
                                        <span x-text="mappedTablesCount + ' / ' + aiState.extractedData.standards.length + ' {{ __('Tables Mapped') }}'"></span>
                                    </x-badge>
                                </div>
                            </div>

                            <!-- Routing Matrix Table -->
                            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                                <table class="w-full text-xs text-start">
                                    <thead class="bg-gray-50/80 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 text-[11px] uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                                        <tr>
                                            <th class="px-4 py-2.5 text-start w-12">#</th>
                                            <th class="px-4 py-2.5 text-start">{{ __('PDF Calibration Table') }}</th>
                                            <th class="px-2 py-2.5 text-center w-10">{{ __('Direction') }}</th>
                                            <th class="px-4 py-2.5 text-start min-w-[260px]">{{ __('Target Equipment Specification') }}</th>
                                            <th class="px-4 py-2.5 text-end w-36">{{ __('Mapping Status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                        <template x-for="(std, sIdx) in aiState.extractedData.standards" :key="sIdx">
                                            <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/20 transition-colors">
                                                <td class="px-4 py-3 text-gray-400 font-mono text-[11px]" x-text="sIdx + 1"></td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-2">
                                                        <x-badge variant="info">
                                                            <span x-text="std.grandeur_symbol"></span>
                                                        </x-badge>
                                                        <div>
                                                            <span class="font-semibold text-gray-900 dark:text-white capitalize text-xs block" x-text="std.mode === 'source' ? 'Source / Génération' : 'Mesure / Input'"></span>
                                                            <span class="text-[11px] text-gray-400 font-mono" x-text="(std.points?.length || 0) + ' ' + config.translations.points"></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-2 py-3 text-center text-gray-400 dark:text-gray-500">
                                                    <i class="fas fa-arrow-right rtl:rotate-180 text-xs"></i>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <select 
                                                        x-model="tableRouting[sIdx]" 
                                                        class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500 shadow-sm"
                                                    >
                                                        <option value="">-- {{ __('Skip this Table') }} ({{ __('Unassigned') }}) --</option>
                                                        <template x-for="spec in currentSpecs" :key="spec.id">
                                                            <option :value="String(spec.id)" x-text="spec.name + ' [' + spec.symbol + ' - ' + (spec.type === 'source' ? 'Source' : 'Mesure') + ']'"></option>
                                                        </template>
                                                    </select>
                                                </td>
                                                <td class="px-4 py-3 text-end whitespace-nowrap">
                                                    <template x-if="tableRouting[sIdx] && isAutoMatched(sIdx, tableRouting[sIdx])">
                                                        <x-badge variant="success">
                                                            <i class="fas fa-check me-1 text-[10px]"></i>
                                                            {{ __('Matched') }} ({{ __('Auto') }})
                                                        </x-badge>
                                                    </template>
                                                    <template x-if="tableRouting[sIdx] && !isAutoMatched(sIdx, tableRouting[sIdx])">
                                                        <x-badge variant="warning">
                                                            <i class="fas fa-hand me-1 text-[10px]"></i>
                                                            {{ __('Manual') }}
                                                        </x-badge>
                                                    </template>
                                                    <template x-if="!tableRouting[sIdx]">
                                                        <x-badge variant="neutral">
                                                            <i class="fas fa-ban me-1 text-[10px]"></i>
                                                            {{ __('Skip this Table') }}
                                                        </x-badge>
                                                    </template>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <!-- Collapsible Points Data Inspector -->
                    <template x-if="aiState.extractedData?.standards?.length > 0">
                        <details class="group border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden bg-white dark:bg-gray-800 shadow-sm">
                            <summary class="cursor-pointer bg-gray-50/80 dark:bg-gray-700/40 px-4 py-3 flex items-center justify-between text-xs font-bold text-gray-700 dark:text-gray-300 select-none">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-table-list text-brand-600"></i>
                                    {{ __('Points Data Inspector') }}
                                </span>
                                <span class="text-gray-400 group-open:rotate-180 transition-transform duration-200">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </span>
                            </summary>

                            <div class="p-4 space-y-4 max-h-72 overflow-y-auto">
                                <template x-for="(std, sIdx) in aiState.extractedData.standards" :key="sIdx">
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                        <div class="bg-gray-100/70 dark:bg-gray-700/50 px-3 py-1.5 flex items-center justify-between text-xs">
                                            <div class="flex items-center gap-2">
                                                <x-badge variant="info">
                                                    <span x-text="std.grandeur_symbol"></span>
                                                </x-badge>
                                                <span class="text-gray-600 dark:text-gray-300 capitalize text-[11px]" x-text="std.mode"></span>
                                            </div>
                                            <span class="text-gray-400 font-mono text-[11px]" x-text="(std.points?.length || 0) + ' ' + config.translations.points"></span>
                                        </div>

                                        <template x-if="std.points?.length > 0">
                                            <div class="overflow-x-auto max-h-40 overflow-y-auto">
                                                <table class="w-full text-xs text-start">
                                                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 text-[11px] border-b border-gray-100 dark:border-gray-700">
                                                        <tr>
                                                            <th class="px-3 py-1.5 text-start">#</th>
                                                            <th class="px-3 py-1.5 text-start">{{ __('Nominal Value') }}</th>
                                                            <th class="px-3 py-1.5 text-start">{{ __('Reading / Displayed') }}</th>
                                                            <th class="px-3 py-1.5 text-start">{{ __('Correction') }}</th>
                                                            <th class="px-3 py-1.5 text-start">{{ __('Uncertainty') }} (U)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 font-mono text-[11px]">
                                                        <template x-for="(pt, pIdx) in std.points" :key="pIdx">
                                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20">
                                                                <td class="px-3 py-1 text-gray-400" x-text="pIdx + 1"></td>
                                                                <td class="px-3 py-1 font-semibold text-gray-900 dark:text-white" x-text="pt.nominal_value"></td>
                                                                <td class="px-3 py-1 text-gray-700 dark:text-gray-300" x-text="pt.reading_value"></td>
                                                                <td class="px-3 py-1 text-gray-700 dark:text-gray-300" x-text="pt.correction"></td>
                                                                <td class="px-3 py-1 text-gray-700 dark:text-gray-300" x-text="'±' + pt.uncertainty"></td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </details>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/80">
                    <x-secondary-button type="button" @click="aiState.previewModalOpen = false">
                        {{ __('Dismiss') }}
                    </x-secondary-button>

                    <x-primary-button type="button" @click="applyPlanToForm()">
                        <i class="fas fa-check-double text-xs me-1.5"></i>
                        {{ __('Confirm Plan & Store Tables') }}
                    </x-primary-button>
                </div>
            </div>
        </div>
    </div>

    <!-- History / Unapplied Extractions Modal -->
    <div 
        x-show="aiState.historyModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog" 
        aria-modal="true"
    >
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="aiState.historyModalOpen = false"></div>

        <div class="flex min-h-screen items-center justify-center p-4">
            <div 
                class="relative w-full max-w-xl rounded-2xl bg-white dark:bg-gray-800 shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden transform transition-all"
                @click.stop
            >
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/80">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <i class="fas fa-history text-base"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ __('Recent Certificate Extractions') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Select a recently processed certificate to review and apply to this form.') }}
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="aiState.historyModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <div class="p-6 max-h-[60vh] overflow-y-auto">
                    <div x-show="aiState.historyLoading" class="py-8 text-center text-gray-500 text-xs">
                        <i class="fas fa-circle-notch fa-spin text-lg text-brand-600 mb-2"></i>
                        <p>{{ __('Loading recent extractions...') }}</p>
                    </div>

                    <div x-show="!aiState.historyLoading && aiState.historyList.length === 0" class="py-8 text-center text-gray-400 text-xs">
                        <i class="fas fa-folder-open text-2xl mb-2"></i>
                        <p>{{ __('No recent unapplied extractions found.') }}</p>
                    </div>

                    <div x-show="!aiState.historyLoading && aiState.historyList.length > 0" class="space-y-2">
                        <template x-for="item in aiState.historyList" :key="item.id">
                            <div 
                                @click="selectFromHistory(item)"
                                class="flex items-center justify-between p-3 rounded-xl border border-gray-100 dark:border-gray-700 hover:border-brand-500 hover:bg-brand-50/20 dark:hover:bg-brand-950/10 cursor-pointer transition"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-600 flex items-center justify-center shrink-0">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-gray-900 dark:text-white" x-text="item.file_name"></p>
                                        <p class="text-[11px] text-gray-400" x-text="new Date(item.created_at).toLocaleString('en-GB')"></p>
                                    </div>
                                </div>
                                <span class="text-brand-600 dark:text-brand-400 text-xs font-semibold flex items-center gap-1">
                                    {{ __('Select') }}
                                    <i class="fas fa-chevron-right text-[10px]"></i>
                                </span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/80 text-end">
                    <x-secondary-button type="button" @click="aiState.historyModalOpen = false">
                        {{ __('Close') }}
                    </x-secondary-button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('aiExtractor', (config) => ({
            config: config,
            MAX_FILE_SIZE: 20 * 1024 * 1024,
            dragOver: false,
            tableRouting: {},
            
            // تهيئة صريحة وواضحة لكافة خصائص الحالة (Explicit State)
            aiState: {
                loading: false,
                step: 0,
                stepMessage: '',
                progressPercent: 0,
                errorMessage: null,
                fileName: '',
                extractionId: null,
                extractedData: null,
                previewModalOpen: false,
                historyModalOpen: false,
                historyLoading: false,
                historyList: [],
                appliedSuccess: false,
                pollTimer: null,
                currentAiKey: null,
                keyStatus: 'idle', // 'idle' or 'active'
            },

            init() {
                // مراقبة المتغير الأب بأمان إذا كان موجوداً ضمن الـ Scope
                this.$watch('selectedEquipmentId', () => {
                    if (this.aiState.previewModalOpen) {
                        this.buildRoutingPlan();
                    }
                });
            },

            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) this.processUpload(file);
            },

            handleDrop(event) {
                this.dragOver = false;
                const file = event.dataTransfer.files[0];
                if (file && file.type === 'application/pdf') {
                    this.processUpload(file);
                } else {
                    this.showError(this.config.translations.invalidPdf);
                }
            },

            processUpload(file) {
                if (!file || file.type !== 'application/pdf') {
                    this.showError(this.config.translations.invalidPdf);
                    return;
                }

                if (file.size > this.MAX_FILE_SIZE) {
                    this.showError(this.config.translations.sizeLimit);
                    return;
                }

                this.resetStateForUpload(file.name);
                const formData = new FormData();
                formData.append('certificate_file', file);
                
                if (this.selectedEquipmentId) {
                    formData.append('equipment_id', this.selectedEquipmentId);
                }

                fetch(this.config.urls.upload, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.config.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { 
                            throw new Error(err.message || this.config.translations.uploadFailed); 
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    this.aiState.extractionId = data.id;

                    if (data.status === 'completed' && data.extracted_data) {
                        this.completeExtraction(data.extracted_data);
                        return;
                    }

                    this.aiState.step = 2;
                    this.aiState.stepMessage = this.config.translations.optimizing;
                    this.aiState.progressPercent = 50;
                    this.startPolling(data.id);
                })
                .catch(err => this.showError(err.message || this.config.translations.extractionError));
            },

            startPolling(extractionId) {
                this.clearPolling();
                let pollCount = 0;
                
                this.aiState.pollTimer = setInterval(() => {
                    pollCount++;
                    if (pollCount === 2) {
                        this.aiState.step = 3;
                        this.aiState.stepMessage = this.config.translations.multimodalAnalysis;
                        this.aiState.progressPercent = 75;
                    }

                    fetch(`${this.config.urls.base}/${extractionId}/status`, {
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Network error');
                        return res.json();
                    })
                    .then(statusData => {
                        // التقاط رقم المفتاح وتحديث حالته للنشط
                        if (statusData.ai_key_index) {
                            this.aiState.currentAiKey = statusData.ai_key_index;
                            this.aiState.keyStatus = 'active'; // المفتاح الآن في وضع المعالجة
                            this.aiState.stepMessage = `${this.config.translations.multimodalAnalysis} — ${statusData.ai_model || ''}`;
                        }

                        if (statusData.status === 'completed') {
                            this.clearPolling();
                            this.aiState.keyStatus = 'idle'; // بمجرد الانتهاء يعود المفتاح لحالة الخمول
                            this.aiState.step = 4;
                            this.aiState.stepMessage = this.config.translations.extractionReady;
                            this.aiState.progressPercent = 100;
                            this.fetchPreview(extractionId);
                        } else if (statusData.status === 'failed') {
                            this.clearPolling();
                            this.aiState.keyStatus = 'idle'; // إيقاف المفتاح في حال الفشل
                            this.showError(statusData.error_message || this.config.translations.processFailed);
                        }
                    })
                    .catch(() => {
                        // تفادي التسرب في حالة فشل الخادم كلياً وتجاوز المحاولات
                        if (pollCount > 30) {
                            this.clearPolling();
                            this.aiState.keyStatus = 'idle'; // إعادة المفتاح لحالة الخمول عند انتهاء المهلة
                            this.showError(this.config.translations.timeout);
                        }
                    });
                }, 2000);
            },

            fetchPreview(extractionId) {
                fetch(`${this.config.urls.base}/${extractionId}/preview`, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(previewData => {
                    this.completeExtraction(previewData.extracted_data);
                })
                .catch(() => this.showError(this.config.translations.previewFailed));
            },

            openHistory() {
                this.aiState.historyLoading = true;
                this.aiState.historyModalOpen = true;

                let url = this.config.urls.unapplied;
                if (this.selectedEquipmentId) {
                    url += '?equipment_id=' + this.selectedEquipmentId;
                }

                fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(data => {
                    this.aiState.historyList = data.extractions || [];
                    this.aiState.historyLoading = false;
                })
                .catch(() => {
                    this.aiState.historyLoading = false;
                });
            },

            selectFromHistory(item) {
                this.aiState.extractionId = item.id;
                this.aiState.fileName = item.file_name;
                this.completeExtraction(item.extracted_data);
                this.aiState.historyModalOpen = false;
            },

            buildRoutingPlan() {
                if (!this.aiState.extractedData?.standards) {
                    this.tableRouting = {};
                    return;
                }

                const specs = this.currentSpecs || [];
                const routing = {};

                this.aiState.extractedData.standards.forEach((std, idx) => {
                    const stdSymbol = (std.grandeur_symbol || '').trim().toLowerCase();
                    const stdMode = std.mode || 'measurement';

                    let targetSpec = specs.find(s => (s.symbol || '').trim().toLowerCase() === stdSymbol && s.type === stdMode);
                    if (!targetSpec) targetSpec = specs.find(s => (s.symbol || '').trim().toLowerCase() === stdSymbol);
                    if (!targetSpec && specs.length === 1) targetSpec = specs[0];

                    routing[idx] = targetSpec ? String(targetSpec.id) : '';
                });

                this.tableRouting = routing;
            },

            applyPlanToForm() {
                if (!this.aiState.extractedData) return;

                const header = this.aiState.extractedData.header || {};
                const standards = this.aiState.extractedData.standards || [];

                // تحديث الـ DOM الخاص بالحقول المباشرة بأمان وبدون خرق لـ State Machine
                this.safelyUpdateInput('reference', header.reference);
                this.safelyUpdateInput('laboratory_name', header.laboratory_name);
                
                if (header.remarks) {
                    const remInput = document.querySelector('textarea[name="remarks"]');
                    if (remInput) {
                        remInput.value = (remInput.value ? remInput.value + '\n' : '') + header.remarks;
                        remInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }

                // تحديث حالة الأب إذا كانت متوفرة
                if (header.calibration_date && this.calDate !== undefined) this.calDate = header.calibration_date;
                if (header.validity_period_months && this.validityMonths !== undefined) this.validityMonths = header.validity_period_months;
                
                if (header.expiry_date && this.expiryDate !== undefined) {
                    this.expiryDate = header.expiry_date;
                } else if (typeof this.updateExpiry === 'function') {
                    this.updateExpiry();
                }

                // تطبيق النقاط والمعايرة
                const newPoints = [];
                standards.forEach((std, sIdx) => {
                    const targetSpecId = this.tableRouting[sIdx];
                    if (!targetSpecId || String(targetSpecId) === '') return;

                    (std.points || []).forEach(pt => {
                        newPoints.push({
                            nominal_value: pt.nominal_value ?? '',
                            correction: pt.correction ?? '',
                            uncertainty: pt.uncertainty ?? '',
                            equipment_specification_id: String(targetSpecId)
                        });
                    });
                });

                if (newPoints.length > 0 && this.points !== undefined) {
                    const hasExistingData = this.points.some(p => p.nominal_value !== '' || p.correction !== '');
                    this.points = hasExistingData ? this.points.concat(newPoints) : newPoints;

                    const firstPtSpec = this.points.find(p => p.equipment_specification_id)?.equipment_specification_id;
                    if (firstPtSpec && this.activeSpecId !== undefined) {
                        this.activeSpecId = firstPtSpec;
                    }
                }

                // إرسال حدث نظيف لتحديث أي مكونات أخرى مهتمة (Event-Driven Communication)
                this.$dispatch('extraction-applied', { header, points: newPoints });

                this.markAsAppliedInBackend();
                this.closeAndShowSuccess();
            },

            switchEquipment(targetId) {
                if (!targetId) return;
                this.selectedEquipmentId = String(targetId);
                const sel = document.querySelector('select[name="equipment_id"]');
                if (sel) {
                    sel.value = String(targetId);
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                }
                this.buildRoutingPlan();
            },

            // --- الدوال المساعدة للتنظيم والموثوقية (Helper Methods) ---

            resetStateForUpload(fileName) {
                this.aiState.loading = true;
                this.aiState.step = 1;
                this.aiState.stepMessage = this.config.translations.uploading;
                this.aiState.progressPercent = 25;
                this.aiState.errorMessage = null;
                this.aiState.fileName = fileName;
                this.aiState.currentAiKey = null;
                this.aiState.keyStatus = 'idle';
            },

            completeExtraction(extractedData) {
                this.aiState.loading = false;
                this.aiState.extractedData = extractedData;
                this.buildRoutingPlan();
                this.aiState.previewModalOpen = true;
            },

            showError(msg) {
                this.aiState.loading = false;
                this.aiState.errorMessage = msg;
            },

            clearPolling() {
                if (this.aiState.pollTimer) {
                    clearInterval(this.aiState.pollTimer);
                    this.aiState.pollTimer = null;
                }
            },

            safelyUpdateInput(name, value) {
                if (!value) return;
                const input = document.querySelector(`input[name="${name}"]`);
                if (input) {
                    input.value = value;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            },

            markAsAppliedInBackend() {
                if (this.aiState.extractionId) {
                    fetch(`${this.config.urls.base}/${this.aiState.extractionId}/mark-applied`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.config.csrfToken,
                            'Accept': 'application/json'
                        }
                    }).catch(() => {}); // الخطأ هنا لا يهم تجربة المستخدم
                }
            },

            closeAndShowSuccess() {
                this.aiState.previewModalOpen = false;
                this.aiState.appliedSuccess = true;
                setTimeout(() => { this.aiState.appliedSuccess = false; }, 6000);
            },

            // --- Computed Properties / Getters ---

            get autoMatchedSpecs() {
                if (!this.aiState.extractedData?.standards) return {};
                const specs = this.currentSpecs || [];
                const auto = {};
                this.aiState.extractedData.standards.forEach((std, idx) => {
                    const stdSymbol = (std.grandeur_symbol || '').trim().toLowerCase();
                    const stdMode = std.mode || 'measurement';
                    let target = specs.find(s => (s.symbol || '').trim().toLowerCase() === stdSymbol && s.type === stdMode);
                    if (!target) target = specs.find(s => (s.symbol || '').trim().toLowerCase() === stdSymbol);
                    if (!target && specs.length === 1) target = specs[0];
                    auto[idx] = target ? String(target.id) : '';
                });
                return auto;
            },

            isAutoMatched(idx, specId) {
                return String(specId) !== '' && String(specId) === String(this.autoMatchedSpecs[idx]);
            },

            get mappedTablesCount() {
                if (!this.tableRouting) return 0;
                return Object.values(this.tableRouting).filter(id => id && String(id) !== '').length;
            },

            get currentEquipment() {
                if (!this.selectedEquipmentId || !this.equipmentsData) return null;
                return this.equipmentsData[this.selectedEquipmentId] || null;
            },

            get suggestedEquipment() {
                if (!this.aiState.extractedData || !this.equipmentsData) return null;
                const dev = this.aiState.extractedData.device;
                if (!dev) return null;

                const devSerial = (dev.serial_number || '').trim().toLowerCase();
                const devModel = (dev.model || '').trim().toLowerCase();
                const devCode = (dev.identification_code || '').trim().toLowerCase();

                if (!devSerial && !devModel && !devCode) return null;

                for (const [id, eq] of Object.entries(this.equipmentsData)) {
                    if (String(id) === String(this.selectedEquipmentId)) continue;
                    const eqSerial = (eq.serial_number || '').trim().toLowerCase();
                    const eqShort = (eq.short_name || '').trim().toLowerCase();
                    const eqFull = (eq.full_name || '').trim().toLowerCase();
                    const eqCode = (eq.code || '').trim().toLowerCase();

                    if (devSerial && eqSerial && devSerial === eqSerial) return eq;
                    if (devCode && eqCode && (devCode === eqCode || devCode.includes(eqCode))) return eq;
                    if (devModel && devModel.length > 2 && (eqShort.includes(devModel) || eqFull.includes(devModel))) return eq;
                }
                return null;
            },

            get compatibilityAudit() {
                if (!this.aiState.extractedData) return null;
                const dev = this.aiState.extractedData.device || {};
                const eq = this.currentEquipment;
                const standards = this.aiState.extractedData.standards || [];

                if (!eq) {
                    return {
                        status: 'no_equipment',
                        badgeVariant: 'warning',
                        badgeLabel: '{{ __('No Equipment Selected') }}',
                        message: '{{ __('Please select target equipment in the form to run compatibility audit and map tables.') }}',
                        mismatch: false,
                        missingStandards: []
                    };
                }

                const devSerial = (dev.serial_number || '').trim().toLowerCase();
                const eqSerial = (eq.serial_number || '').trim().toLowerCase();
                const devModel = (dev.model || '').trim().toLowerCase();
                const eqName = (eq.name || eq.short_name || eq.full_name || '').trim().toLowerCase();

                let hasSerialMismatch = (devSerial && eqSerial && devSerial !== eqSerial);
                let hasModelMismatch = (devModel && devModel.length > 2 && !eqName.includes(devModel));

                const missingStandards = [];
                standards.forEach(std => {
                    const sym = (std.grandeur_symbol || '').trim().toLowerCase();
                    const exists = (eq.specifications || []).some(s => (s.symbol || '').trim().toLowerCase() === sym);
                    if (!exists && !missingStandards.includes(std.grandeur_symbol)) {
                        missingStandards.push(std.grandeur_symbol);
                    }
                });

                if (hasSerialMismatch) {
                    return {
                        status: 'mismatch',
                        badgeVariant: 'danger',
                        badgeLabel: '{{ __('Device Mismatch Warning') }}',
                        message: '{{ __('Serial number in certificate does not match selected equipment.') }}',
                        mismatch: true,
                        missingStandards: missingStandards
                    };
                }

                if (hasModelMismatch) {
                    return {
                        status: 'model_warning',
                        badgeVariant: 'warning',
                        badgeLabel: '{{ __('Device Mismatch Warning') }}',
                        message: '{{ __('Model in certificate differs from selected equipment.') }}',
                        mismatch: true,
                        missingStandards: missingStandards
                    };
                }

                if (missingStandards.length > 0) {
                    return {
                        status: 'partial_specs',
                        badgeVariant: 'warning',
                        badgeLabel: '{{ __('Target Equipment Specification') }}',
                        message: '{{ __('Some certificate standards have no matching specifications on this equipment.') }}',
                        mismatch: false,
                        missingStandards: missingStandards
                    };
                }

                return {
                    status: 'compatible',
                    badgeVariant: 'success',
                    badgeLabel: '{{ __('Verified Compatible') }}',
                    message: '{{ __('All tables accurately mapped to their corresponding specifications.') }}',
                    mismatch: false,
                    missingStandards: []
                };
            }
        }));
    });
</script>
@endpush