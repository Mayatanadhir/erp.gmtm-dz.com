<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 flex items-center justify-center shrink-0 shadow-sm border border-emerald-200 dark:border-emerald-800/70">
                    <i class="fas fa-certificate text-xl"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ $certificate->reference ?: ('CERT-#' . $certificate->id) }}
                        </h2>
                        <x-badge :variant="$certificate->status?->badgeVariant() ?? 'neutral'">
                            {{ $certificate->status?->label() ?? ucfirst((string) $certificate->status) }}
                        </x-badge>
                        @if($certificate->is_locked)
                            <x-badge variant="neutral" :title="__('Locked against modification')">
                                <i class="fas fa-lock text-[11px] text-amber-500"></i>
                                <span>{{ __('Locked') }}</span>
                            </x-badge>
                        @else
                            <x-badge variant="success" :title="__('Editable Draft')">
                                <i class="fas fa-lock-open text-[11px]"></i>
                                <span>{{ __('Unlocked') }}</span>
                            </x-badge>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('Associated with') }}: 
                        <strong class="text-gray-700 dark:text-gray-200">{{ $certificate->equipment?->short_name ?: $certificate->equipment?->full_name }}</strong> 
                        ({{ $certificate->equipment?->internal_code }})
                    </p>
                </div>
            </div>

            <!-- Header Action Controls -->
            <div class="flex items-center flex-wrap gap-2">
                <x-secondary-button href="{{ route('metrology.calibration-certificates') }}" class="gap-1.5 text-xs">
                    <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>{{ __('Back to List') }}</span>
                </x-secondary-button>

                @if($certificate->certificate_path)
                    <x-success-button href="{{ route('metrology.calibration-certificates.download', $certificate) }}" class="gap-1.5 text-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>{{ __('Download PDF') }}</span>
                    </x-success-button>
                @endif

                @can('edit calibration certificates')
                    @if(! $certificate->is_locked)
                        <x-edit-button href="{{ route('metrology.calibration-certificates.edit', $certificate) }}" class="gap-1.5 text-xs">
                            {{ __('Edit Certificate') }}
                        </x-edit-button>

                        <form method="POST" action="{{ route('metrology.calibration-certificates.approve', $certificate) }}" class="inline" x-data>
                            @csrf
                            <x-primary-button type="button" @click="if (confirm({{ json_encode(__('Are you sure you want to approve and officially lock this certificate?')) }})) { $el.closest('form').submit(); }" class="gap-1.5 text-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ __('Approve & Lock') }}</span>
                            </x-primary-button>
                        </form>
                    @else
                        <!-- Unlock with mandatory reason button -->
                        <x-danger-button type="button" x-data="" @click="$dispatch('open-modal', 'unlock-certificate-modal')" class="gap-1.5 text-xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                            <span>{{ __('Unlock Certificate') }}</span>
                        </x-danger-button>
                    @endif
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ activeTab: @js(in_array(request('tab'), ['points', 'interpolation', 'document', 'environment'], true) ? request('tab') : 'points') }">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Flash Notifications -->
            @if(session('success'))
                <x-alert variant="success">{{ session('success') }}</x-alert>
            @endif
            @if(session('error'))
                <x-alert variant="danger">{{ session('error') }}</x-alert>
            @endif

            <!-- 3-Card Header Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Certificate Metrology Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm space-y-3">
                    <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2">
                        <i class="fas fa-microscope text-brand-600"></i>
                        <span>{{ __('Calibration Information') }}</span>
                    </h4>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/60">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Laboratory') }}:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $certificate->laboratory_name ?: '—' }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/60">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Calibration Date') }}:</span>
                            <span class="font-semibold text-gray-900 dark:text-white"><x-date :value="$certificate->calibration_date" /></span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/60">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Expiry Date') }}:</span>
                            <span class="font-semibold text-gray-900 dark:text-white"><x-date :value="$certificate->expiry_date" /></span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Validity Period') }}:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $certificate->validity_period_months }} {{ __('Months') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Equipment Context Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2">
                            <i class="fas fa-tools text-emerald-600"></i>
                            <span>{{ __('Equipment Details') }}</span>
                        </h4>
                        @if($certificate->equipment)
                            <a href="{{ route('metrology.equipment.show', $certificate->equipment) }}" class="text-[11px] text-brand-600 hover:text-brand-700 dark:text-brand-400 font-semibold hover:underline">
                                {{ __('View Profile') }} &rarr;
                            </a>
                        @endif
                    </div>

                    <div class="flex items-start gap-3.5">
                        <!-- Equipment Image Thumbnail -->
                        <div class="w-20 h-20 shrink-0 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-center shadow-inner">
                            @if($certificate->equipment?->image_url)
                                <img src="{{ $certificate->equipment->image_url }}" alt="{{ $certificate->equipment->full_name }}" class="w-full h-full object-contain p-1">
                            @else
                                <div class="text-2xl text-gray-400">
                                    <i class="fas {{ $certificate->equipment?->category?->icon() ?? 'fa-tools' }}"></i>
                                </div>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0 space-y-1.5 text-xs">
                            <div class="flex justify-between py-0.5 border-b border-gray-100 dark:border-gray-700/60">
                                <span class="text-gray-500 dark:text-gray-400">{{ __('Equipment') }}:</span>
                                <span class="font-bold text-gray-900 dark:text-white truncate ps-1.5">{{ $certificate->equipment?->short_name ?: $certificate->equipment?->full_name }}</span>
                            </div>
                            <div class="flex justify-between py-0.5 border-b border-gray-100 dark:border-gray-700/60">
                                <span class="text-gray-500 dark:text-gray-400">{{ __('Internal Code') }}:</span>
                                <span class="font-mono font-bold text-brand-600 dark:text-brand-400">{{ $certificate->equipment?->internal_code ?: '—' }}</span>
                            </div>
                            <div class="flex justify-between py-0.5 border-b border-gray-100 dark:border-gray-700/60">
                                <span class="text-gray-500 dark:text-gray-400">{{ __('Serial Number') }}:</span>
                                <span class="font-mono font-medium text-gray-900 dark:text-white">{{ $certificate->equipment?->serial_number ?: '—' }}</span>
                            </div>
                            <div class="flex justify-between py-0.5">
                                <span class="text-gray-500 dark:text-gray-400">{{ __('Category') }}:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $certificate->equipment?->category?->label() ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Compliance & Financial Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm space-y-3">
                    <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2">
                        <i class="fas fa-shield-alt text-brand-600"></i>
                        <span>{{ __('Audit & Financials') }}</span>
                    </h4>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/60">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Calibration Cost') }}:</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ number_format((float) ($certificate->price ?? 0), 2) }} {{ __('DZD') }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/60">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Created By') }}:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $certificate->creator?->name ?: __('System / Legacy') }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/60">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Approved By') }}:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $certificate->approver?->name ?: '—' }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Approved At') }}:</span>
                            <span class="font-medium text-gray-900 dark:text-white"><x-date :value="$certificate->approved_at" format="datetime" /></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation Bar -->
            <div class="border-b border-gray-200 dark:border-gray-700 flex items-center gap-4 text-sm font-medium">
                <button type="button" @click="activeTab = 'points'" :class="activeTab === 'points' ? 'border-brand-600 text-brand-600 dark:text-brand-400 font-bold border-b-2' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 border-b-2 border-transparent'" class="py-3 px-1 flex items-center gap-2 transition">
                    <i class="fas fa-list-ol"></i>
                    <span>{{ __('Calibration Points') }}</span>
                    <x-badge variant="neutral" size="sm">
                        {{ $certificate->calibrationPoints->count() }}
                    </x-badge>
                </button>

                <button type="button" @click="activeTab = 'interpolation'" :class="activeTab === 'interpolation' ? 'border-brand-600 text-brand-600 dark:text-brand-400 font-bold border-b-2' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 border-b-2 border-transparent'" class="py-3 px-1 flex items-center gap-2 transition">
                    <i class="fas fa-chart-line"></i>
                    <span>{{ __('Interpolation & 5-Point Curves') }}</span>
                    @if($certificate->calibrationInterpolations->isNotEmpty())
                        <x-badge variant="success" size="sm">
                            {{ $certificate->calibrationInterpolations->count() }}
                        </x-badge>
                    @endif
                </button>

                @if($certificate->certificate_path)
                    <button type="button" @click="activeTab = 'document'" :class="activeTab === 'document' ? 'border-brand-600 text-brand-600 dark:text-brand-400 font-bold border-b-2' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 border-b-2 border-transparent'" class="py-3 px-1 flex items-center gap-2 transition">
                        <i class="fas fa-file-pdf"></i>
                        <span>{{ __('Certificate Document') }}</span>
                    </button>
                @endif

                <button type="button" @click="activeTab = 'environment'" :class="activeTab === 'environment' ? 'border-brand-600 text-brand-600 dark:text-brand-400 font-bold border-b-2' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 border-b-2 border-transparent'" class="py-3 px-1 flex items-center gap-2 transition">
                    <i class="fas fa-cloud-sun"></i>
                    <span>{{ __('Ambient & Remarks') }}</span>
                </button>
            </div>

            <!-- Tab 2: Calibration Points Tables (Sourced from Equipment Specifications & Standards) -->
            <div x-show="activeTab === 'points'" x-data="{ pointsCategory: 'all' }" class="space-y-6">
                @php
                    $specifications = $specifications ?? collect();
                    $mesureSpecs = $mesureSpecs ?? collect();
                    $sourceSpecs = $sourceSpecs ?? collect();
                    $matchedPointIds = [];
                @endphp

                @if($certificate->calibrationPoints->isEmpty())
                    <div class="p-8 text-center rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        <i class="fas fa-ruler-combined text-gray-400 text-3xl mb-2"></i>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No calibration points recorded for this certificate.') }}</p>
                    </div>
                @else
                    @if($mesureSpecs->isNotEmpty() && $sourceSpecs->isNotEmpty())
                        <!-- Category Switcher / Filter Bar -->
                        <div class="flex flex-wrap items-center justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-200/80 dark:border-gray-700/80">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-filter me-1 text-gray-400"></i>
                                    {{ __('Filter by Category') }}:
                                </span>
                                <div class="inline-flex p-1 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 gap-1 text-xs">
                                    <button type="button" @click="pointsCategory = 'all'"
                                            :class="pointsCategory === 'all' ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 shadow-sm font-semibold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                                            class="px-3 py-1.5 rounded-md transition flex items-center gap-1.5">
                                        <span>{{ __('All Standards') }}</span>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full font-mono" :class="pointsCategory === 'all' ? 'bg-white/20 text-white dark:bg-gray-900/20 dark:text-gray-900' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'">
                                            {{ $certificate->calibrationPoints->count() }}
                                        </span>
                                    </button>

                                    <button type="button" @click="pointsCategory = 'measurement'"
                                            :class="pointsCategory === 'measurement' ? 'bg-emerald-600 text-white shadow-sm font-semibold' : 'text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-500/20'"
                                            class="px-3 py-1.5 rounded-md transition flex items-center gap-1.5">
                                        <i class="fas fa-sign-in-alt text-[10px]"></i>
                                        <span>{{ __('Measurement / In') }}</span>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full font-mono" :class="pointsCategory === 'measurement' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300'">
                                            {{ $mesureSpecs->count() }}
                                        </span>
                                    </button>

                                    <button type="button" @click="pointsCategory = 'source'"
                                            :class="pointsCategory === 'source' ? 'bg-amber-600 text-white shadow-sm font-semibold' : 'text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-500/20'"
                                            class="px-3 py-1.5 rounded-md transition flex items-center gap-1.5">
                                        <i class="fas fa-bolt text-[10px]"></i>
                                        <span>{{ __('Source / Out') }}</span>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full font-mono" :class="pointsCategory === 'source' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300'">
                                            {{ $sourceSpecs->count() }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($mesureSpecs->isNotEmpty())
                        <!-- Section: Measurement Standards from Equipment -->
                        <div x-show="pointsCategory === 'all' || pointsCategory === 'measurement'" class="space-y-5">
                            <div class="flex items-center justify-between pb-2 border-b border-emerald-200/60 dark:border-emerald-800/40">
                                <h3 class="text-sm font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2">
                                    <i class="fas fa-sign-in-alt text-emerald-600 dark:text-emerald-400"></i>
                                    <span>{{ __('Measurement Standards & Capabilities (Sensors / In)') }}</span>
                                    <x-badge variant="success" class="text-[10px]">{{ __('Measurement / In') }}</x-badge>
                                </h3>
                                <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                                    {{ $mesureSpecs->count() }} {{ __('Standards') }}
                                </span>
                            </div>

                            @foreach($mesureSpecs as $spec)
                                @php
                                    $unit = $spec->grandeur?->symbol ?: '';
                                    $specPoints = $certificate->calibrationPoints->filter(function ($pt) use ($spec, $specifications) {
                                        if ($pt->equipment_specification_id === $spec->id) {
                                            return true;
                                        }
                                        if ($pt->equipment_specification_id === null && $specifications->count() === 1) {
                                            return true;
                                        }
                                        return false;
                                    })->values();

                                    foreach ($specPoints as $sp) {
                                        $matchedPointIds[] = $sp->id;
                                    }
                                @endphp

                                <x-table class="border border-emerald-200/90 dark:border-emerald-800/70 shadow-sm ring-1 ring-emerald-500/10">
                                    <x-slot:toolbar>
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between w-full gap-3">
                                            <div class="flex items-center gap-3">
                                                <x-grandeur-icon :grandeur="$spec->grandeur" size="md" :withBackground="true" />
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                                            {{ $spec->grandeur?->name ?? __('Standard Parameter') }}
                                                        </h4>
                                                        @if($unit)
                                                            <span class="px-2 py-0.5 rounded-md font-mono text-xs font-bold border {{ $spec->discipline()->badgeClass() }}">
                                                                {{ $unit }}
                                                            </span>
                                                        @endif
                                                        <x-badge variant="success" class="text-[10px]">
                                                            {{ __('Measurement / In') }}
                                                        </x-badge>
                                                    </div>
                                                    <div class="flex flex-wrap items-center gap-3 text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                                        @if($spec->range_min !== null && $spec->range_max !== null)
                                                            <span class="inline-flex items-center gap-1 font-mono">
                                                                <i class="fas fa-arrows-alt-h text-gray-400"></i>
                                                                <span class="text-gray-400">{{ __('Measurement Range') }}:</span>
                                                                <strong class="text-gray-700 dark:text-gray-200">{{ $spec->range_min }} → {{ $spec->range_max }} {{ $unit }}</strong>
                                                            </span>
                                                        @endif
                                                        @if($spec->accuracy_value !== null)
                                                            <span class="inline-flex items-center gap-1 font-mono">
                                                                <i class="fas fa-bullseye text-gray-400"></i>
                                                                <span class="text-gray-400">{{ __('Accuracy') }}:</span>
                                                                <strong class="text-gray-700 dark:text-gray-200">±{{ $spec->accuracy_value }} {{ $spec->accuracy_type?->value ?? '%' }}</strong>
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('metrology.calibration-certificates.show', ['certificate' => $certificate, 'tab' => 'interpolation', 'spec_id' => $spec->id]) }}"
                                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 transition shadow-xs">
                                                    <i class="fas fa-chart-line text-emerald-600 dark:text-emerald-400"></i>
                                                    <span>{{ __('View Curve') }}</span>
                                                </a>
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    {{ $specPoints->count() }} {{ __('Points') }}
                                                </span>
                                            </div>
                                        </div>
                                    </x-slot:toolbar>

                                    <x-slot:header>
                                        <x-table.th class="w-12 text-emerald-900 dark:text-emerald-300">#</x-table.th>
                                        <x-table.th class="text-emerald-900 dark:text-emerald-300">{{ __('Nominal Value') }} @if($unit)<span class="text-xs font-normal text-emerald-700 dark:text-emerald-400">[{{ $unit }}]</span>@endif</x-table.th>
                                        <x-table.th class="text-emerald-900 dark:text-emerald-300">{{ __('Correction (C)') }} @if($unit)<span class="text-xs font-normal text-emerald-700 dark:text-emerald-400">[{{ $unit }}]</span>@endif</x-table.th>
                                        <x-table.th class="text-emerald-900 dark:text-emerald-300">{{ __('Uncertainty (U)') }} @if($unit)<span class="text-xs font-normal text-emerald-700 dark:text-emerald-400">[{{ $unit }}]</span>@endif</x-table.th>
                                        <x-table.th class="text-emerald-900 dark:text-emerald-300">{{ __('Lower Limit (C - U)') }} @if($unit)<span class="text-xs font-normal text-emerald-700 dark:text-emerald-400">[{{ $unit }}]</span>@endif</x-table.th>
                                        <x-table.th class="text-emerald-900 dark:text-emerald-300">{{ __('Upper Limit (C + U)') }} @if($unit)<span class="text-xs font-normal text-emerald-700 dark:text-emerald-400">[{{ $unit }}]</span>@endif</x-table.th>
                                        <x-table.th class="text-emerald-900 dark:text-emerald-300">{{ __('Tolerance Status') }}</x-table.th>
                                    </x-slot:header>

                                    @forelse($specPoints as $idx => $pt)
                                        <x-table.tr class="hover:bg-emerald-50/50 dark:hover:bg-emerald-500/10 transition-colors">
                                            <x-table.td class="align-middle">
                                                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded font-mono font-bold text-xs bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                                    {{ $idx + 1 }}
                                                </span>
                                            </x-table.td>
                                            <x-table.td class="font-bold text-gray-900 dark:text-white font-mono">
                                                {{ $pt->nominal_value }}
                                                @if($unit)
                                                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 ms-1">{{ $unit }}</span>
                                                @endif
                                            </x-table.td>
                                            <x-table.td class="font-mono">
                                                {{ ($pt->correction > 0 ? '+' : '') . $pt->correction }}
                                                @if($unit)
                                                    <span class="text-xs text-gray-400 ms-1">{{ $unit }}</span>
                                                @endif
                                            </x-table.td>
                                            <x-table.td class="font-mono">
                                                ±{{ $pt->uncertainty }}
                                                @if($unit)
                                                    <span class="text-xs text-gray-400 ms-1">{{ $unit }}</span>
                                                @endif
                                            </x-table.td>
                                            <x-table.td class="font-mono text-gray-500 dark:text-gray-400">
                                                {{ round($pt->lower_limit, 4) }}
                                                @if($unit)
                                                    <span class="text-xs text-gray-400 ms-1">{{ $unit }}</span>
                                                @endif
                                            </x-table.td>
                                            <x-table.td class="font-mono text-gray-500 dark:text-gray-400">
                                                {{ round($pt->upper_limit, 4) }}
                                                @if($unit)
                                                    <span class="text-xs text-gray-400 ms-1">{{ $unit }}</span>
                                                @endif
                                            </x-table.td>
                                            <x-table.td>
                                                <x-badge :variant="$pt->status?->badgeVariant() ?? 'neutral'">
                                                    {{ $pt->status?->label() ?? __('Undetermined') }}
                                                </x-badge>
                                            </x-table.td>
                                        </x-table.tr>
                                    @empty
                                        <x-table.empty :colspan="7" :message="__('No calibration points recorded for this standard.')" />
                                    @endforelse
                                </x-table>
                            @endforeach
                        </div>
                    @endif

                    @if($sourceSpecs->isNotEmpty())
                        <!-- Section: Source Standards from Equipment -->
                        <div x-show="pointsCategory === 'all' || pointsCategory === 'source'" class="space-y-5">
                            <div class="flex items-center justify-between pb-2 border-b border-amber-200/60 dark:border-amber-800/40">
                                <h3 class="text-sm font-bold text-amber-800 dark:text-amber-300 flex items-center gap-2">
                                    <i class="fas fa-bolt text-amber-600 dark:text-amber-400"></i>
                                    <span>{{ __('Source Standards & Capabilities (Generators / Out)') }}</span>
                                    <x-badge variant="warning" class="text-[10px]">{{ __('Source / Out') }}</x-badge>
                                </h3>
                                <span class="text-xs font-semibold text-amber-700 dark:text-amber-400">
                                    {{ $sourceSpecs->count() }} {{ __('Standards') }}
                                </span>
                            </div>

                            @foreach($sourceSpecs as $spec)
                                @php
                                    $unit = $spec->grandeur?->symbol ?: '';
                                    $specPoints = $certificate->calibrationPoints->filter(function ($pt) use ($spec, $specifications, $matchedPointIds) {
                                        if (in_array($pt->id, $matchedPointIds, true)) {
                                            return false;
                                        }
                                        if ($pt->equipment_specification_id === $spec->id) {
                                            return true;
                                        }
                                        if ($pt->equipment_specification_id === null && $specifications->count() === 1) {
                                            return true;
                                        }
                                        return false;
                                    })->values();

                                    foreach ($specPoints as $sp) {
                                        $matchedPointIds[] = $sp->id;
                                    }
                                @endphp

                                <x-table class="border border-amber-200/90 dark:border-amber-800/70 shadow-sm ring-1 ring-amber-500/10">
                                    <x-slot:toolbar>
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between w-full gap-3">
                                            <div class="flex items-center gap-3">
                                                <x-grandeur-icon :grandeur="$spec->grandeur" size="md" :withBackground="true" />
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                                            {{ $spec->grandeur?->name ?? __('Standard Parameter') }}
                                                        </h4>
                                                        @if($unit)
                                                            <span class="px-2 py-0.5 rounded-md font-mono text-xs font-bold border {{ $spec->discipline()->badgeClass() }}">
                                                                {{ $unit }}
                                                            </span>
                                                        @endif
                                                        <x-badge variant="warning" class="text-[10px]">
                                                            {{ __('Source / Out') }}
                                                        </x-badge>
                                                    </div>
                                                    <div class="flex flex-wrap items-center gap-3 text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                                        @if($spec->range_min !== null && $spec->range_max !== null)
                                                            <span class="inline-flex items-center gap-1 font-mono">
                                                                <i class="fas fa-arrows-alt-h text-gray-400"></i>
                                                                <span class="text-gray-400">{{ __('Generation Range') }}:</span>
                                                                <strong class="text-gray-700 dark:text-gray-200">{{ $spec->range_min }} → {{ $spec->range_max }} {{ $unit }}</strong>
                                                            </span>
                                                        @endif
                                                        @if($spec->accuracy_value !== null)
                                                            <span class="inline-flex items-center gap-1 font-mono">
                                                                <i class="fas fa-bullseye text-gray-400"></i>
                                                                <span class="text-gray-400">{{ __('Accuracy') }}:</span>
                                                                <strong class="text-gray-700 dark:text-gray-200">±{{ $spec->accuracy_value }} {{ $spec->accuracy_type?->value ?? '%' }}</strong>
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('metrology.calibration-certificates.show', ['certificate' => $certificate, 'tab' => 'interpolation', 'spec_id' => $spec->id]) }}"
                                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-amber-700 dark:text-amber-300 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/20 transition shadow-xs">
                                                    <i class="fas fa-chart-line text-amber-600 dark:text-amber-400"></i>
                                                    <span>{{ __('View Curve') }}</span>
                                                </a>
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                    {{ $specPoints->count() }} {{ __('Points') }}
                                                </span>
                                            </div>
                                        </div>
                                    </x-slot:toolbar>

                                    <x-slot:header>
                                        <x-table.th class="w-12 text-amber-900 dark:text-amber-300">#</x-table.th>
                                        <x-table.th class="text-amber-900 dark:text-amber-300">{{ __('Nominal Value') }} @if($unit)<span class="text-xs font-normal text-amber-700 dark:text-amber-400">[{{ $unit }}]</span>@endif</x-table.th>
                                        <x-table.th class="text-amber-900 dark:text-amber-300">{{ __('Correction (C)') }} @if($unit)<span class="text-xs font-normal text-amber-700 dark:text-amber-400">[{{ $unit }}]</span>@endif</x-table.th>
                                        <x-table.th class="text-amber-900 dark:text-amber-300">{{ __('Uncertainty (U)') }} @if($unit)<span class="text-xs font-normal text-amber-700 dark:text-amber-400">[{{ $unit }}]</span>@endif</x-table.th>
                                        <x-table.th class="text-amber-900 dark:text-amber-300">{{ __('Lower Limit (C - U)') }} @if($unit)<span class="text-xs font-normal text-amber-700 dark:text-amber-400">[{{ $unit }}]</span>@endif</x-table.th>
                                        <x-table.th class="text-amber-900 dark:text-amber-300">{{ __('Upper Limit (C + U)') }} @if($unit)<span class="text-xs font-normal text-amber-700 dark:text-amber-400">[{{ $unit }}]</span>@endif</x-table.th>
                                        <x-table.th class="text-amber-900 dark:text-amber-300">{{ __('Tolerance Status') }}</x-table.th>
                                    </x-slot:header>

                                    @forelse($specPoints as $idx => $pt)
                                        <x-table.tr class="hover:bg-amber-50/50 dark:hover:bg-amber-500/10 transition-colors">
                                            <x-table.td class="align-middle">
                                                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded font-mono font-bold text-xs bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20">
                                                    {{ $idx + 1 }}
                                                </span>
                                            </x-table.td>
                                            <x-table.td class="font-bold text-gray-900 dark:text-white font-mono">
                                                {{ $pt->nominal_value }}
                                                @if($unit)
                                                    <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 ms-1">{{ $unit }}</span>
                                                @endif
                                            </x-table.td>
                                            <x-table.td class="font-mono">
                                                {{ ($pt->correction > 0 ? '+' : '') . $pt->correction }}
                                                @if($unit)
                                                    <span class="text-xs text-gray-400 ms-1">{{ $unit }}</span>
                                                @endif
                                            </x-table.td>
                                            <x-table.td class="font-mono">
                                                ±{{ $pt->uncertainty }}
                                                @if($unit)
                                                    <span class="text-xs text-gray-400 ms-1">{{ $unit }}</span>
                                                @endif
                                            </x-table.td>
                                            <x-table.td class="font-mono text-gray-500 dark:text-gray-400">
                                                {{ round($pt->lower_limit, 4) }}
                                                @if($unit)
                                                    <span class="text-xs text-gray-400 ms-1">{{ $unit }}</span>
                                                @endif
                                            </x-table.td>
                                            <x-table.td class="font-mono text-gray-500 dark:text-gray-400">
                                                {{ round($pt->upper_limit, 4) }}
                                                @if($unit)
                                                    <span class="text-xs text-gray-400 ms-1">{{ $unit }}</span>
                                                @endif
                                            </x-table.td>
                                            <x-table.td>
                                                <x-badge :variant="$pt->status?->badgeVariant() ?? 'neutral'">
                                                    {{ $pt->status?->label() ?? __('Undetermined') }}
                                                </x-badge>
                                            </x-table.td>
                                        </x-table.tr>
                                    @empty
                                        <x-table.empty :colspan="7" :message="__('No calibration points recorded for this standard.')" />
                                    @endforelse
                                </x-table>
                            @endforeach
                        </div>
                    @endif

                    @php
                        $unlinkedPoints = $certificate->calibrationPoints->reject(fn($pt) => in_array($pt->id, $matchedPointIds, true))->values();
                    @endphp

                    @if($unlinkedPoints->isNotEmpty())
                        <!-- Section: Other / Unlinked Calibration Points -->
                        <div x-show="pointsCategory === 'all' || pointsCategory === 'other'" class="space-y-3">
                            <x-table class="border border-gray-200/90 dark:border-gray-700/70 shadow-sm">
                                <x-slot:toolbar>
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between w-full gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-gray-500/15 text-gray-600 dark:text-gray-400 flex items-center justify-center font-bold shadow-xs">
                                                <i class="fas fa-ruler text-sm"></i>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                                        {{ __('Other Calibration Points') }}
                                                    </h4>
                                                    <x-badge variant="neutral" class="text-[10px]">
                                                        {{ __('General') }}
                                                    </x-badge>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                {{ $unlinkedPoints->count() }} {{ __('Points') }}
                                            </span>
                                        </div>
                                    </div>
                                </x-slot:toolbar>

                                <x-slot:header>
                                    <x-table.th class="w-12">#</x-table.th>
                                    <x-table.th>{{ __('Physical Quantity / Parameter') }}</x-table.th>
                                    <x-table.th>{{ __('Nominal Value') }}</x-table.th>
                                    <x-table.th>{{ __('Correction (C)') }}</x-table.th>
                                    <x-table.th>{{ __('Uncertainty (U)') }}</x-table.th>
                                    <x-table.th>{{ __('Lower Limit (C - U)') }}</x-table.th>
                                    <x-table.th>{{ __('Upper Limit (C + U)') }}</x-table.th>
                                    <x-table.th>{{ __('Tolerance Status') }}</x-table.th>
                                </x-slot:header>

                                @foreach($unlinkedPoints as $idx => $pt)
                                    <x-table.tr>
                                        <x-table.td class="align-middle">
                                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded font-mono font-bold text-xs bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                {{ $idx + 1 }}
                                            </span>
                                        </x-table.td>
                                        <x-table.td class="font-semibold text-gray-900 dark:text-white">
                                            <div class="flex items-center gap-2">
                                                <x-grandeur-icon :grandeur="$pt->equipmentSpecification?->grandeur" size="xs" :withBackground="true" />
                                                <span>{{ $pt->equipmentSpecification?->grandeur?->name ?? __('Standard Parameter') }}</span>
                                                @if($pt->equipmentSpecification?->grandeur?->symbol)
                                                    <span class="inline-flex items-center font-mono text-[10px] font-bold px-1.5 py-0.5 rounded border {{ $pt->equipmentSpecification?->discipline()?->badgeClass() ?? 'border-gray-200 dark:border-gray-700' }}">
                                                        {{ $pt->equipmentSpecification->grandeur->symbol }}
                                                    </span>
                                                @endif
                                            </div>
                                        </x-table.td>
                                        <x-table.td class="font-bold text-gray-900 dark:text-white font-mono">{{ $pt->nominal_value }}</x-table.td>
                                        <x-table.td class="font-mono">{{ ($pt->correction > 0 ? '+' : '') . $pt->correction }}</x-table.td>
                                        <x-table.td class="font-mono">±{{ $pt->uncertainty }}</x-table.td>
                                        <x-table.td class="font-mono text-gray-500 dark:text-gray-400">{{ round($pt->lower_limit, 4) }}</x-table.td>
                                        <x-table.td class="font-mono text-gray-500 dark:text-gray-400">{{ round($pt->upper_limit, 4) }}</x-table.td>
                                        <x-table.td>
                                            <x-badge :variant="$pt->status?->badgeVariant() ?? 'neutral'">
                                                {{ $pt->status?->label() ?? __('Undetermined') }}
                                            </x-badge>
                                        </x-table.td>
                                    </x-table.tr>
                                @endforeach
                            </x-table>
                        </div>
                    @endif
                @endif
            </div>

            <!-- Tab: Interpolation & 5-Point Curves (Florian Platel Model) -->
            <x-curve
                :certificate="$certificate"
                :specifications="$specifications"
                :active-specification="$activeSpecification"
                :five-point-grid="$fivePointGrid"
                :comparison-data="$comparisonData"
                :tab-condition="'activeTab === \'interpolation\''"
            />

            <!-- Tab 3: Certificate Document Viewer -->
            @if($certificate->certificate_path)
                <div x-show="activeTab === 'document'" class="space-y-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200/80 dark:border-gray-700/80 p-4 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-file-pdf text-2xl text-rose-500"></i>
                                <div>
                                    <h4 class="font-bold text-sm text-gray-900 dark:text-white">
                                        {{ $certificate->file_name ?: basename($certificate->certificate_path) }}
                                    </h4>
                                    <p class="text-xs text-gray-500">
                                        @if($certificate->file_size)
                                            {{ number_format($certificate->file_size / 1024, 1) }} KB &bull;
                                        @endif
                                        @if($certificate->certificate_hash)
                                            <span class="inline-flex items-center gap-1">
                                                <span class="text-gray-500 dark:text-gray-400">{{ __('SHA-256') }}:</span>
                                                <span class="font-mono text-[10px]">{{ substr($certificate->certificate_hash, 0, 16) }}...</span>
                                                <x-badge variant="success" size="sm" class="ms-1">{{ __('CAS Verified') }}</x-badge>
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-xs">{{ __('Document Attached') }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <a href="{{ route('metrology.calibration-certificates.download', $certificate) }}" class="px-4 py-2 bg-gray-900 hover:bg-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                                <i class="fas fa-download mr-1"></i> {{ __('Download Document') }}
                            </a>
                        </div>

                        <!-- Embedded PDF Frame (Lazy Loaded on Tab Selection) -->
                        <div class="w-full h-[750px] rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900">
                            <template x-if="activeTab === 'document'">
                                <iframe src="{{ $certificate->certificate_url }}#toolbar=1" title="{{ __('Calibration Certificate Document') }}" class="w-full h-full" frameborder="0"></iframe>
                            </template>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Tab 4: Environmental Conditions & Technical Remarks -->
            <div x-show="activeTab === 'environment'" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm text-center">
                        <div class="w-10 h-10 mx-auto rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-600 flex items-center justify-center mb-2">
                            <i class="fas fa-temperature-high text-lg"></i>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase">{{ __('Ambient Temperature') }}</p>
                        <p class="text-xl font-black text-gray-900 dark:text-white mt-1">
                            {{ $certificate->environmental_conditions['temperature_celsius'] ?? '—' }} °C
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm text-center">
                        <div class="w-10 h-10 mx-auto rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center mb-2">
                            <i class="fas fa-tint text-lg"></i>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase">{{ __('Relative Humidity') }}</p>
                        <p class="text-xl font-black text-gray-900 dark:text-white mt-1">
                            {{ $certificate->environmental_conditions['humidity_percent'] ?? '—' }} %
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm text-center">
                        <div class="w-10 h-10 mx-auto rounded-full bg-purple-50 dark:bg-purple-900/30 text-purple-600 flex items-center justify-center mb-2">
                            <i class="fas fa-compress-arrows-alt text-lg"></i>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase">{{ __('Atmospheric Pressure') }}</p>
                        <p class="text-xl font-black text-gray-900 dark:text-white mt-1">
                            {{ $certificate->environmental_conditions['atmospheric_pressure_hpa'] ?? '—' }} hPa
                        </p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm space-y-2">
                    <h4 class="font-bold text-xs text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        {{ __('Metrologist Technical Remarks') }}
                    </h4>
                    <p class="text-sm text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-line">
                        {{ $certificate->remarks ?: __('No special remarks recorded for this certificate.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Modal: Unlock Certificate with Reason -->
        <x-modal name="unlock-certificate-modal" focusable>
            <form method="POST" action="{{ route('metrology.calibration-certificates.unlock', $certificate) }}" class="p-6 space-y-4">
                @csrf
                <div class="flex items-center gap-3 text-rose-600">
                    <div class="w-10 h-10 rounded-full bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center shrink-0">
                        <i class="fas fa-exclamation-triangle text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-gray-900 dark:text-white">{{ __('Authorize Certificate Unlock') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('This action will be recorded in the immutable ISO audit trail.') }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Mandatory Justification / Reason') }} <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="reason" required rows="3" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-rose-500 focus:border-rose-500" placeholder="{{ __('Specify the reason for unlocking this certified record...') }}"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" @click="$dispatch('close-modal', 'unlock-certificate-modal')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-lg transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                        {{ __('Confirm Unlock') }}
                    </button>
                </div>
            </form>
        </x-modal>
    </div>
</x-app-layout>
