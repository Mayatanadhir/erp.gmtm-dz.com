@props([
    'certificate',
    'specifications' => null,
    'activeSpecification' => null,
    'fivePointGrid' => [],
    'comparisonData' => [],
    'tabCondition' => null,
])

@php
    $specifications = $specifications ?? ($certificate->equipment?->specifications()->with('grandeur')->get() ?? collect());
    $activeSpecification = $activeSpecification ?? $specifications->first();
@endphp

<div @if($tabCondition) x-show="{{ $tabCondition }}" @endif
     x-data="interpolationViewer({{ Js::from($fivePointGrid['chart_data'] ?? []) }}, {{ Js::from($comparisonData ?? []) }})"
     x-init="init()"
     @if($tabCondition) x-effect="if ({{ $tabCondition }}) { $nextTick(() => { handleTabSwitch(); }); }" @endif
     {{ $attributes->merge(['class' => 'space-y-6']) }}>

    <!-- Multi-Parameter Specification Selection (If Multiple Standards Exist) -->
    @if($specifications->count() > 1)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200/80 dark:border-gray-700/80 p-4 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h4 class="font-bold text-xs text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <i class="fas fa-layer-group text-brand-600"></i>
                        <span>{{ __('Select Standard / Parameter') }}</span>
                    </h4>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('This certificate calibrates multiple standards. Select a parameter to isolate its 5-point grid and interpolation curve.') }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach($specifications as $spec)
                        @php
                            $isActive = ($activeSpecification?->id === $spec->id);
                            $specName = $spec->grandeur?->name ?? __('Standard Parameter');
                            $specSymbol = $spec->grandeur?->symbol ? '(' . $spec->grandeur->symbol . ')' : '';
                            $isSource = $spec->grandeur?->type === \App\Enums\GrandeurType::Source;
                            $typeLabel = $isSource ? __('Source / Out') : __('Measurement / In');
                            $typeIcon = $isSource ? 'fa-bolt' : 'fa-sign-in-alt';
                        @endphp
                        <a href="{{ route('metrology.calibration-certificates.show', ['certificate' => $certificate, 'tab' => 'interpolation', 'spec_id' => $spec->id]) }}"
                           class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition border {{ $isActive
                               ? ($isSource ? 'bg-amber-600 text-white border-amber-600 shadow-sm ring-2 ring-amber-500/20' : 'bg-emerald-600 text-white border-emerald-600 shadow-sm ring-2 ring-emerald-500/20')
                               : ($isSource ? 'bg-amber-500/10 text-amber-800 dark:text-amber-300 border-amber-500/20 hover:bg-amber-500/20' : 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border-emerald-500/20 hover:bg-emerald-500/20') }}">
                            <x-grandeur-icon :grandeur="$spec->grandeur" size="xs" :colored="!$isActive" />
                            <span>{{ $specName }} {{ $specSymbol }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded font-bold uppercase tracking-wider {{ $isActive ? 'bg-white/20 text-white' : ($isSource ? 'bg-amber-500/20 text-amber-900 dark:text-amber-200' : 'bg-emerald-500/20 text-emerald-900 dark:text-emerald-200') }}">
                                <i class="fas {{ $typeIcon }} text-[9px] me-0.5"></i>
                                {{ $typeLabel }}
                            </span>
                            @if($spec->range_min !== null && $spec->range_max !== null)
                                <span class="text-[10px] opacity-80 font-mono">[{{ $spec->range_min }} ~ {{ $spec->range_max }}]</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Configuration & Setup Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200/80 dark:border-gray-700/80 p-5 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-700/60">
            <div>
                <h3 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-calculator text-brand-600"></i>
                    <span>{{ __('Standard 5-Point Interpolated Grid Configuration') }}</span>
                    @if(!empty($fivePointGrid['parameter_name']))
                        <span class="text-xs font-normal text-gray-500 dark:text-gray-400">
                            &bull; {{ $fivePointGrid['parameter_name'] }}
                            @if(!empty($fivePointGrid['unit_symbol']))
                                <span class="font-mono font-semibold text-brand-600 dark:text-brand-400">({{ $fivePointGrid['unit_symbol'] }})</span>
                            @endif
                        </span>
                    @endif
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Calculated using Florian Platel Metrological Model (ISO 17025 compliant with modeling and experimental uncertainty decomposition).') }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if(!empty($fivePointGrid['discipline_badge']))
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold border {{ $fivePointGrid['discipline_badge'] }}">
                        <x-grandeur-icon :discipline="$fivePointGrid['discipline'] ?? 'generic'" size="xs" :colored="false" />
                        <span>{{ $fivePointGrid['discipline_label'] ?? '' }}</span>
                    </span>
                @endif
                @if(!empty($fivePointGrid['grandeur_type_badge']))
                    <x-badge :variant="$fivePointGrid['grandeur_type_badge']" :dot="true">
                        {{ $fivePointGrid['grandeur_type_label'] }}
                    </x-badge>
                @endif
                @if(!empty($fivePointGrid['unit_symbol']))
                    <x-badge variant="neutral">
                        {{ __('Unit') }}: {{ $fivePointGrid['unit_symbol'] }}
                    </x-badge>
                @endif
                <x-badge variant="primary" :dot="true">
                    {{ __('ISO 17025 Metrology') }}
                </x-badge>
            </div>
        </div>

        <!-- Directive Alert -->
        <div class="rounded-lg bg-brand-500/10 border border-brand-500/20 p-3 flex items-start gap-2.5 text-xs text-brand-800 dark:text-brand-300">
            <i class="fas fa-filter mt-0.5 text-brand-600 shrink-0"></i>
            <span>{{ __('Directive Note: Any calibration points outside the [min, max] range are strictly ignored.') }}</span>
        </div>

        <form method="POST" action="{{ route('metrology.calibration-certificates.interpolation-grid', $certificate) }}" class="space-y-4">
            @csrf
            <input type="hidden" name="equipment_specification_id" value="{{ $activeSpecification?->id }}">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Min Range Bound') }}@if(!empty($fivePointGrid['unit_symbol'])) ({{ $fivePointGrid['unit_symbol'] }})@endif
                    </label>
                    <input type="number" step="any" name="min_bound" x-model="minBound" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-600 focus:border-brand-600 font-mono" placeholder="{{ $fivePointGrid['span_min'] ?? '' }}">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Max Range Bound') }}@if(!empty($fivePointGrid['unit_symbol'])) ({{ $fivePointGrid['unit_symbol'] }})@endif
                    </label>
                    <input type="number" step="any" name="max_bound" x-model="maxBound" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-600 focus:border-brand-600 font-mono" placeholder="{{ $fivePointGrid['span_max'] ?? '' }}">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('5 Reference Setpoints (Ascending)') }}@if(!empty($fivePointGrid['unit_symbol'])) ({{ $fivePointGrid['unit_symbol'] }})@endif
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <div>
                        <span class="block text-[11px] text-gray-500 mb-1 font-mono font-medium">{{ __('Point 1 (0%)') }}</span>
                        <input type="number" step="any" name="points[]" x-model.number="p1" required class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-600 focus:border-brand-600 font-mono font-bold">
                    </div>
                    <div>
                        <span class="block text-[11px] text-gray-500 mb-1 font-mono font-medium">{{ __('Point 2 (25%)') }}</span>
                        <input type="number" step="any" name="points[]" x-model.number="p2" required class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-600 focus:border-brand-600 font-mono font-bold">
                    </div>
                    <div>
                        <span class="block text-[11px] text-gray-500 mb-1 font-mono font-medium">{{ __('Point 3 (50%)') }}</span>
                        <input type="number" step="any" name="points[]" x-model.number="p3" required class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-600 focus:border-brand-600 font-mono font-bold">
                    </div>
                    <div>
                        <span class="block text-[11px] text-gray-500 mb-1 font-mono font-medium">{{ __('Point 4 (75%)') }}</span>
                        <input type="number" step="any" name="points[]" x-model.number="p4" required class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-600 focus:border-brand-600 font-mono font-bold">
                    </div>
                    <div>
                        <span class="block text-[11px] text-gray-500 mb-1 font-mono font-medium">{{ __('Point 5 (100%)') }}</span>
                        <input type="number" step="any" name="points[]" x-model.number="p5" required class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-600 focus:border-brand-600 font-mono font-bold">
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-end items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-700/60">
                <x-secondary-button type="button" @click="resetUniform()">
                    <i class="fas fa-magic me-1.5"></i>
                    {{ __('Reset to Uniform (0% - 100%)') }}
                </x-secondary-button>
                <x-primary-button type="submit">
                    <i class="fas fa-save me-1.5"></i>
                    {{ __('Calculate & Save 5-Point Grid') }}
                </x-primary-button>
            </div>
        </form>
    </div>

    <!-- Platel Metrology Decomposition Table -->
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <h4 class="font-bold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center gap-2">
                <i class="fas fa-table text-indigo-600"></i>
                <span>{{ __('5-Point Interpolation & Uncertainty Decomposition') }}</span>
                @if(!empty($fivePointGrid['parameter_name']))
                    <span class="text-[11px] normal-case font-normal text-gray-500">
                        ({{ $fivePointGrid['parameter_name'] }}{{ !empty($fivePointGrid['unit_symbol']) ? ' - ' . $fivePointGrid['unit_symbol'] : '' }})
                    </span>
                @endif
                @if(!empty($fivePointGrid['grandeur_type_badge']))
                    <x-badge :variant="$fivePointGrid['grandeur_type_badge']">
                        {{ $fivePointGrid['grandeur_type_label'] }}
                    </x-badge>
                @endif
            </h4>
            @if($certificate->calibrationInterpolations->where('equipment_specification_id', $activeSpecification?->id)->count() === 5)
                <x-badge variant="success" :dot="true">
                    {{ __('Persisted in Database') }}
                </x-badge>
            @endif
        </div>

        <x-table>
            <x-slot:header>
                <x-table.th>#</x-table.th>
                <x-table.th>{{ __('Target Setpoint (X)') }}{{ !empty($fivePointGrid['unit_symbol']) ? ' [' . $fivePointGrid['unit_symbol'] . ']' : '' }}</x-table.th>
                <x-table.th>{{ __('Interpolated Value (Y)') }}{{ !empty($fivePointGrid['unit_symbol']) ? ' [' . $fivePointGrid['unit_symbol'] . ']' : '' }}</x-table.th>
                <x-table.th>{{ __('Experimental Unc. (u_exp)') }}{{ !empty($fivePointGrid['unit_symbol']) ? ' [' . $fivePointGrid['unit_symbol'] . ']' : '' }}</x-table.th>
                <x-table.th>{{ __('Curvature (a2)') }}</x-table.th>
                <x-table.th>{{ __('Modeling Unc. (u_mod)') }}{{ !empty($fivePointGrid['unit_symbol']) ? ' [' . $fivePointGrid['unit_symbol'] . ']' : '' }}</x-table.th>
                <x-table.th>{{ __('Combined Unc. (uc)') }}{{ !empty($fivePointGrid['unit_symbol']) ? ' [' . $fivePointGrid['unit_symbol'] . ']' : '' }}</x-table.th>
                <x-table.th>{{ __('Expanded Unc. (U, k=2)') }}{{ !empty($fivePointGrid['unit_symbol']) ? ' [' . $fivePointGrid['unit_symbol'] . ']' : '' }}</x-table.th>
                <x-table.th>{{ __('Confidence Interval [Y - U, Y + U]') }}{{ !empty($fivePointGrid['unit_symbol']) ? ' [' . $fivePointGrid['unit_symbol'] . ']' : '' }}</x-table.th>
                <x-table.th>{{ __('Methodology') }}</x-table.th>
            </x-slot:header>

            @forelse($fivePointGrid['points'] ?? [] as $pt)
                <x-table.tr>
                    <x-table.td class="font-mono text-gray-400 font-medium">{{ $pt['point_index'] }}</x-table.td>
                    <x-table.td class="font-mono font-bold text-gray-900 dark:text-white">{{ $pt['target_x'] }}</x-table.td>
                    <x-table.td class="font-mono font-bold text-brand-600 dark:text-brand-400">{{ $pt['interpolated_value'] }}</x-table.td>
                    <x-table.td class="font-mono text-gray-600 dark:text-gray-300">{{ $pt['u_exp'] }}</x-table.td>
                    <x-table.td class="font-mono text-gray-400">{{ $pt['curvature_a2'] }}</x-table.td>
                    <x-table.td class="font-mono text-gray-600 dark:text-gray-300">{{ $pt['u_mod'] }}</x-table.td>
                    <x-table.td class="font-mono text-gray-700 dark:text-gray-200 font-semibold">{{ $pt['u_combined'] }}</x-table.td>
                    <x-table.td class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $pt['expanded_uncertainty'] }}</x-table.td>
                    <x-table.td class="font-mono text-xs text-gray-500 dark:text-gray-400">
                        [{{ $pt['confidence_interval']['lower'] }}, {{ $pt['confidence_interval']['upper'] }}]
                    </x-table.td>
                    <x-table.td>
                        @if($pt['is_exact_point'])
                            <x-badge variant="success" :dot="true">
                                {{ __('Exact Certificate Point') }}
                            </x-badge>
                        @else
                            <x-badge variant="info" :dot="true">
                                {{ __('Platel Interpolated') }}
                            </x-badge>
                        @endif
                    </x-table.td>
                </x-table.tr>
            @empty
                <x-table.empty :colspan="10" :message="__('At least 2 calibration points are required to compute interpolation curves.')" />
            @endforelse
        </x-table>
    </div>

    <!-- Curves Visualizations (Single Certificate + Equipment Historical Comparison) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Single Certificate Calibration Curve -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200/80 dark:border-gray-700/80 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700/60">
                <div>
                    <h4 class="font-bold text-xs text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <i class="fas fa-wave-square {{ ($fivePointGrid['grandeur_type'] ?? '') === 'source' ? 'text-amber-600' : 'text-emerald-600' }}"></i>
                        <span>{{ __('Single Certificate Calibration Curve & Uncertainty Envelope') }}</span>
                        @if(!empty($fivePointGrid['parameter_name']))
                            <span class="text-[11px] normal-case font-normal text-gray-500">
                                ({{ $fivePointGrid['parameter_name'] }}{{ !empty($fivePointGrid['unit_symbol']) ? ' - ' . $fivePointGrid['unit_symbol'] : '' }})
                            </span>
                        @endif
                        @if(!empty($fivePointGrid['grandeur_type_badge']))
                            <x-badge :variant="$fivePointGrid['grandeur_type_badge']">
                                {{ $fivePointGrid['grandeur_type_label'] }}
                            </x-badge>
                        @endif
                    </h4>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Correction curve with shaded Florian Platel expanded uncertainty envelope (±U, k=2).') }}
                    </p>
                </div>
            </div>

            @if(!empty($fivePointGrid['has_data']))
                <div class="relative w-full h-80 sm:h-96">
                    <canvas id="singleCurveChart" class="w-full h-full"></canvas>
                </div>
            @else
                <div class="flex flex-col items-center justify-center p-12 text-center text-xs text-gray-500 dark:text-gray-400 space-y-3 bg-gray-50 dark:bg-gray-700/20 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                    <div class="w-12 h-12 rounded-full bg-brand-500/10 text-brand-600 flex items-center justify-center text-xl">
                        <i class="fas fa-wave-square"></i>
                    </div>
                    <div class="max-w-md space-y-1">
                        <p class="font-semibold text-gray-700 dark:text-gray-300">
                            {{ __('No Calibration Points on Certificate for this Standard') }}
                        </p>
                        <p class="text-[11px]">
                            {{ __('This certificate does not contain enough calibration points (minimum 2) for this equipment standard to construct the curve.') }}
                        </p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Multi-Certificate Historical Comparison Curve -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200/80 dark:border-gray-700/80 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700/60">
                <div>
                    <h4 class="font-bold text-xs text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <i class="fas fa-history text-indigo-600"></i>
                        <span>{{ __('Historical Multi-Certificate Evolution Curve (Equipment Comparison)') }}</span>
                        @if(!empty($fivePointGrid['parameter_name']))
                            <span class="text-[11px] normal-case font-normal text-gray-500">
                                ({{ $fivePointGrid['parameter_name'] }}{{ !empty($fivePointGrid['unit_symbol']) ? ' - ' . $fivePointGrid['unit_symbol'] : '' }})
                            </span>
                        @endif
                        @if(!empty($fivePointGrid['grandeur_type_badge']))
                            <x-badge :variant="$fivePointGrid['grandeur_type_badge']">
                                {{ $fivePointGrid['grandeur_type_label'] }}
                            </x-badge>
                        @endif
                    </h4>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Evolution of the 5 Reference Points across all calibration certificates of this equipment over time.') }}
                    </p>
                </div>
            </div>

            @if(!empty($comparisonData['has_data']) && count($comparisonData['labels'] ?? []) > 1)
                <div class="relative w-full h-80 sm:h-96">
                    <canvas id="comparisonCurveChart" class="w-full h-full"></canvas>
                </div>
            @else
                <div class="flex flex-col items-center justify-center p-12 text-center text-xs text-gray-500 dark:text-gray-400 space-y-3 bg-gray-50 dark:bg-gray-700/20 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                    <div class="w-12 h-12 rounded-full bg-brand-500/10 text-brand-600 flex items-center justify-center text-xl">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="max-w-md space-y-1">
                        <p class="font-semibold text-gray-700 dark:text-gray-300">
                            {{ __('Single Certificate on Record') }}
                        </p>
                        <p class="text-[11px]">
                            {{ __('Only 1 historical certificate is on record for this equipment. Future calibration certificates will automatically construct the multi-certificate drift trend line.') }}
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script src="{{ asset('js/chart.umd.min.js') }}"></script>
        <script>
            function interpolationViewer(singleChartData, comparisonChartData) {
                return {
                    singleChart: null,
                    comparisonChart: null,
                    minBound: {{ json_encode($fivePointGrid['span_min'] ?? 0) }},
                    maxBound: {{ json_encode($fivePointGrid['span_max'] ?? 0) }},
                    p1: {{ json_encode($fivePointGrid['points'][0]['target_x'] ?? 0) }},
                    p2: {{ json_encode($fivePointGrid['points'][1]['target_x'] ?? 0) }},
                    p3: {{ json_encode($fivePointGrid['points'][2]['target_x'] ?? 0) }},
                    p4: {{ json_encode($fivePointGrid['points'][3]['target_x'] ?? 0) }},
                    p5: {{ json_encode($fivePointGrid['points'][4]['target_x'] ?? 0) }},
                    init() {
                        this.$nextTick(() => {
                            if (this.isTabVisible()) {
                                this.renderCharts();
                            }
                        });
                    },
                    isTabVisible() {
                        const canvas = document.getElementById('singleCurveChart');
                        return Boolean(canvas && canvas.offsetParent !== null);
                    },
                    handleTabSwitch() {
                        this.$nextTick(() => {
                            if (!this.singleChart) {
                                this.renderCharts();
                            } else {
                                try {
                                    this.singleChart.resize();
                                } catch (e) {
                                    this.renderSingleChart();
                                }
                                if (this.comparisonChart) {
                                    try {
                                        this.comparisonChart.resize();
                                    } catch (e) {
                                        this.renderComparisonChart();
                                    }
                                }
                            }
                        });
                    },
                    resetUniform() {
                        const min = parseFloat(this.minBound);
                        const max = parseFloat(this.maxBound);
                        if (isNaN(min) || isNaN(max) || min >= max) {
                            return;
                        }
                        const span = max - min;
                        this.p1 = Math.round(min * 10000) / 10000;
                        this.p2 = Math.round((min + 0.25 * span) * 10000) / 10000;
                        this.p3 = Math.round((min + 0.50 * span) * 10000) / 10000;
                        this.p4 = Math.round((min + 0.75 * span) * 10000) / 10000;
                        this.p5 = Math.round(max * 10000) / 10000;
                    },
                    renderCharts() {
                        if (typeof Chart === 'undefined') {
                            return;
                        }
                        this.renderSingleChart();
                        this.renderComparisonChart();
                    },
                    renderSingleChart() {
                        const canvas = document.getElementById('singleCurveChart');
                        if (!canvas || !singleChartData || !singleChartData.labels || singleChartData.labels.length === 0) {
                            return;
                        }
                        if (canvas.offsetParent === null) {
                            return;
                        }
                        if (this.singleChart) {
                            try {
                                this.singleChart.stop();
                                this.singleChart.destroy();
                            } catch (e) {}
                            this.singleChart = null;
                        }

                        const isDark = document.documentElement.classList.contains('dark');
                        const textColor = isDark ? '#9ca3af' : '#4b5563';
                        const gridColor = isDark ? 'rgba(156, 163, 175, 0.15)' : 'rgba(107, 114, 128, 0.1)';
                        const isSource = singleChartData.grandeur_type === 'source';
                        const mainColor = singleChartData.discipline_color || (isSource ? '#d97706' : '#059669');
                        const hexToRgba = (hex, alpha) => {
                            let c = hex.replace('#', '');
                            if (c.length === 3) c = c.split('').map(x => x + x).join('');
                            const num = parseInt(c, 16);
                            return `rgba(${(num >> 16) & 255}, ${(num >> 8) & 255}, ${num & 255}, ${alpha})`;
                        };
                        const envelopeBorder = hexToRgba(mainColor, 0.45);
                        const envelopeBg = hexToRgba(mainColor, 0.12);

                        this.singleChart = new Chart(canvas, {
                            type: 'line',
                            data: {
                                labels: singleChartData.labels,
                                datasets: [
                                    {
                                        label: @js(__('Upper Limit (Y + U)')),
                                        data: singleChartData.u_upper,
                                        borderColor: envelopeBorder,
                                        borderDash: [5, 5],
                                        borderWidth: 1.5,
                                        pointRadius: 0,
                                        fill: false,
                                        tension: 0.1
                                    },
                                    {
                                        label: @js(__('Lower Limit (Y - U)')),
                                        data: singleChartData.u_lower,
                                        borderColor: envelopeBorder,
                                        borderDash: [5, 5],
                                        borderWidth: 1.5,
                                        pointRadius: 0,
                                        fill: 0,
                                        backgroundColor: envelopeBg,
                                        tension: 0.1
                                    },
                                    {
                                        label: @js(__('Interpolated Curve (Y)')),
                                        data: singleChartData.values,
                                        borderColor: mainColor,
                                        backgroundColor: mainColor,
                                        borderWidth: 2.5,
                                        pointRadius: 5,
                                        pointHoverRadius: 7,
                                        pointBackgroundColor: '#ffffff',
                                        pointBorderColor: mainColor,
                                        pointBorderWidth: 2,
                                        fill: false,
                                        tension: 0.1
                                    }
                                ]
                            },
                            options: {
                                animation: false,
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    intersect: false,
                                    mode: 'index'
                                },
                                scales: {
                                    x: {
                                        title: {
                                            display: true,
                                            text: @js(__('Nominal Setpoint (X)')) + (singleChartData.unit ? ' [' + singleChartData.unit + ']' : ''),
                                            color: textColor,
                                            font: { size: 11, weight: '600' }
                                        },
                                        ticks: { color: textColor, font: { size: 10 } },
                                        grid: { color: gridColor }
                                    },
                                    y: {
                                        title: {
                                            display: true,
                                            text: @js(__('Correction / Measured Value (Y)')) + (singleChartData.unit ? ' [' + singleChartData.unit + ']' : ''),
                                            color: textColor,
                                            font: { size: 11, weight: '600' }
                                        },
                                        ticks: { color: textColor, font: { size: 10 } },
                                        grid: { color: gridColor }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        position: 'top',
                                        labels: { color: textColor, font: { size: 11 } }
                                    },
                                    tooltip: {
                                        padding: 10,
                                        cornerRadius: 8
                                    }
                                }
                            }
                        });
                    },
                    renderComparisonChart() {
                        const canvas = document.getElementById('comparisonCurveChart');
                        if (!canvas || !comparisonChartData || !comparisonChartData.labels || comparisonChartData.labels.length === 0) {
                            return;
                        }
                        if (canvas.offsetParent === null) {
                            return;
                        }
                        if (this.comparisonChart) {
                            try {
                                this.comparisonChart.stop();
                                this.comparisonChart.destroy();
                            } catch (e) {}
                            this.comparisonChart = null;
                        }

                        const isDark = document.documentElement.classList.contains('dark');
                        const textColor = isDark ? '#9ca3af' : '#4b5563';
                        const gridColor = isDark ? 'rgba(156, 163, 175, 0.15)' : 'rgba(107, 114, 128, 0.1)';

                        this.comparisonChart = new Chart(canvas, {
                            type: 'line',
                            data: {
                                labels: comparisonChartData.labels,
                                datasets: comparisonChartData.datasets || []
                            },
                            options: {
                                animation: false,
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    intersect: false,
                                    mode: 'index'
                                },
                                scales: {
                                    x: {
                                        title: {
                                            display: true,
                                            text: @js(__('Calibration Date')),
                                            color: textColor,
                                            font: { size: 11, weight: '600' }
                                        },
                                        ticks: { color: textColor, font: { size: 10 } },
                                        grid: { color: gridColor }
                                    },
                                    y: {
                                        title: {
                                            display: true,
                                            text: @js(__('Interpolated Correction (Y)')) + (comparisonChartData.unit ? ' [' + comparisonChartData.unit + ']' : ''),
                                            color: textColor,
                                            font: { size: 11, weight: '600' }
                                        },
                                        ticks: { color: textColor, font: { size: 10 } },
                                        grid: { color: gridColor }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        position: 'top',
                                        labels: { color: textColor, font: { size: 11 } }
                                    },
                                    tooltip: {
                                        padding: 10,
                                        cornerRadius: 8
                                    }
                                }
                            }
                        });
                    }
                };
            }
        </script>
    @endpush
@endonce
