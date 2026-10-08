@php
    /* ────────────────────────────────────────────────────────────
       Helpers
    ──────────────────────────────────────────────────────────── */
    $num     = fn ($v) => ($v === null || $v === '') ? null : (float) $v;
    $dateStr = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('Y-m-d') : null;
    $val     = fn ($v) => $v instanceof \BackedEnum ? $v->value : $v;

    /* ────────────────────────────────────────────────────────────
       KPI cards
    ──────────────────────────────────────────────────────────── */
    $kpiUrl = fn (string $type) => route(
        'metrology.instruments',
        request('instrument_type') === $type
            ? request()->except(['instrument_type', 'page'])
            : array_merge(request()->except('page'), ['instrument_type' => $type])
    );

    $kpis = [
        [
            'label'  => __('Total Instruments'),
            'value'  => $stats['total'] ?? 0,
            'url'    => route('metrology.instruments', request()->except(['instrument_type', 'status', 'page'])),
            'icon'   => 'fa-boxes',
            'active' => false,
            'box'    => 'bg-brand-50 dark:bg-brand-500/20 text-brand-600 dark:text-brand-400',
            'ring'   => '',
        ],
        [
            'label'  => __('Transmitters'),
            'value'  => $stats['transmitters'] ?? 0,
            'url'    => $kpiUrl('transmitter'),
            'icon'   => 'fa-satellite-dish',
            'active' => request('instrument_type') === 'transmitter',
            'box'    => 'bg-blue-50 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400',
            'ring'   => 'ring-2 ring-blue-500 border-blue-500 bg-blue-50/20',
        ],
        [
            'label'  => __('Flow Computers'),
            'value'  => $stats['flow_computers'] ?? 0,
            'url'    => $kpiUrl('flow_computer'),
            'icon'   => 'fa-server',
            'active' => request('instrument_type') === 'flow_computer',
            'box'    => 'bg-amber-50 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400',
            'ring'   => 'ring-2 ring-amber-500 border-amber-500 bg-amber-50/20',
        ],
        [
            'label'  => __('Standard Gauges'),
            'value'  => $stats['standard_gauges'] ?? 0,
            'url'    => $kpiUrl('standard_gauge'),
            'icon'   => 'fa-flask',
            'active' => request('instrument_type') === 'standard_gauge',
            'box'    => 'bg-emerald-50 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400',
            'ring'   => 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/20',
        ],
        [
            'label'  => __('Provers'),
            'value'  => $stats['provers'] ?? 0,
            'url'    => $kpiUrl('prover'),
            'icon'   => 'fa-tachometer-alt',
            'active' => request('instrument_type') === 'prover',
            'box'    => 'bg-violet-50 dark:bg-violet-500/20 text-violet-600 dark:text-violet-400',
            'ring'   => 'ring-2 ring-violet-500 border-violet-500 bg-violet-50/20',
        ],
    ];

    /* ────────────────────────────────────────────────────────────
       Row payloads for the edit / delete modals (built once)
    ──────────────────────────────────────────────────────────── */
    $rows = $instruments->getCollection()->map(function ($i) use ($num, $dateStr, $val) {
        $g = $i->standardGaugeSpecification;
        $p = $i->proverSpecification;

        return [
            'id'               => $i->id,
            'tag_number'       => $i->tag_number,
            'serial_number'    => $i->serial_number,
            'site_id'          => $i->site_id ? (string) $i->site_id : '',
            'instrument_type'  => $val($i->instrument_type),
            'status'           => $val($i->status),
            'process_variable' => $val($i->process_variable),
            'measurement_type' => $val($i->measurement_type),
            'fluid_type'       => $val($i->fluid_type),
            'technology'       => $i->technology,
            'image_url'        => $i->image_url,
            'update_url'       => route('metrology.instruments.update', array_merge(['instrument' => $i->id], request()->query())),
            'delete_url'       => route('metrology.instruments.destroy', array_merge(['instrument' => $i->id], request()->query())),
            'specifications'   => $i->specifications->map(fn ($s) => [
                'grandeur_id'    => $s->grandeur_id,
                'range_min'      => $num($s->range_min),
                'range_max'      => $num($s->range_max),
                'accuracy_value' => $num($s->accuracy_value),
                'accuracy_type'  => $val($s->accuracy_type) ?: '%',
            ])->values()->all(),
            'transmitters'     => $i->linkedTransmitters->map(fn ($t) => [
                'id'             => (string) $t->id,
                'transmitter_id' => (string) $t->id,
                'channel'        => (string) ($t->pivot->channel_number ?? 'Ch1'),
                'channel_number' => (string) ($t->pivot->channel_number ?? 'Ch1'),
            ])->values()->all(),
            'gauge'            => $g ? [
                'nominal_capacity_liters'        => $num($g->nominal_capacity_liters),
                'neck_scale_sensitivity'         => $num($g->neck_scale_sensitivity),
                'cubical_expansion_coef_gcm'     => $num($g->cubical_expansion_coef_gcm),
                'vessel_material'                => $g->vessel_material ?? 'Stainless Steel',
                'base_reference_temperature'     => $num($g->base_reference_temperature) ?? 20.00,
                'calibration_certificate_number' => $g->calibration_certificate_number,
                'calibration_date'               => $dateStr($g->calibration_date),
                'calibration_expiry_date'        => $dateStr($g->calibration_expiry_date),
            ] : null,
            'prover'           => $p ? [
                'type'                   => $val($p->type) ?? 'bidirectional_pipe',
                'inner_diameter'         => $num($p->inner_diameter),
                'wall_thickness'         => $num($p->wall_thickness),
                'nominal_base_volume'    => $num($p->nominal_base_volume),
                'cubical_expansion_coef' => $num($p->cubical_expansion_coef),
                'elasticity_modulus'     => $num($p->elasticity_modulus),
                'area_expansion_coef'    => $num($p->area_expansion_coef),
                'linear_expansion_coef'  => $num($p->linear_expansion_coef),
                'material'               => $p->material,
                'pulse_interpolation'    => (bool) ($p->pulse_interpolation ?? false),
            ] : null,
        ];
    })->values()->all();

    /* ────────────────────────────────────────────────────────────
       Form state (blank + restored from old() after a validation error)
    ──────────────────────────────────────────────────────────── */
    $allGrandeurs = $measurementGrandeurs->concat($sourceGrandeurs)->unique('id');
    $blankSpecs = $allGrandeurs->mapWithKeys(fn ($g) => [
        $g->id => ['selected' => false, 'min' => '', 'max' => '', 'acc' => '', 'acc_type' => '%'],
    ])->all();

    $transmittersData = $transmitters->map(fn($t) => [
        'id'            => (string) $t->id,
        'tag'           => $t->tag_number,
        'site_id'       => $t->site_id ? (string) $t->site_id : '',
        'serial_number' => $t->serial_number,
    ])->values()->all();

    $blankForm = [
        'tag_number'       => '',
        'serial_number'    => '',
        'site_id'          => '',
        'instrument_type'  => 'transmitter',
        'status'           => 'active',
        'process_variable' => 'pressure',
        'measurement_type' => 'Relative',
        'fluid_type'       => 'liquid',
        'technology'       => 'SMART',
        'specs'            => $blankSpecs,
        'transmitters'     => [],
        'gauge'            => [
            'nominal_capacity_liters'        => '',
            'neck_scale_sensitivity'         => '',
            'cubical_expansion_coef_gcm'     => '0.00005100',
            'vessel_material'                => 'Stainless Steel',
            'base_reference_temperature'     => '20.00',
            'calibration_certificate_number' => '',
            'calibration_date'               => '',
            'calibration_expiry_date'        => '',
        ],
        'prover'           => [
            'type'                   => \App\Enums\ProverType::cases()[0]->value ?? 'bidirectional_pipe',
            'inner_diameter'         => '',
            'wall_thickness'         => '',
            'nominal_base_volume'    => '',
            'cubical_expansion_coef' => '',
            'elasticity_modulus'     => '',
            'area_expansion_coef'    => '',
            'linear_expansion_coef'  => '',
            'material'               => 'Mild Steel',
            'pulse_interpolation'    => false,
        ],
    ];

    $oldMode     = old('_form');                       // 'create' | 'edit' | null
    $reopen      = $errors->any() && in_array($oldMode, ['create', 'edit'], true);
    $initialForm = $blankForm;

    if ($reopen) {
        foreach (['tag_number', 'serial_number', 'site_id', 'instrument_type', 'status', 'process_variable', 'measurement_type', 'fluid_type', 'technology'] as $k) {
            $initialForm[$k] = (string) old($k, $blankForm[$k] ?? '');
        }
        foreach ((array) old('params', []) as $gid => $p) {
            if (isset($initialForm['specs'][$gid])) {
                $initialForm['specs'][$gid] = [
                    'selected' => ! empty($p['selected']),
                    'min'      => $p['min'] ?? '',
                    'max'      => $p['max'] ?? '',
                    'acc'      => $p['acc'] ?? '',
                    'acc_type' => $p['acc_type'] ?? '%',
                ];
            }
        }
        if (is_array(old('transmitters'))) {
            $initialForm['transmitters'] = [];
            foreach (old('transmitters') as $row) {
                if (!empty($row['id']) || !empty($row['transmitter_id'])) {
                    $initialForm['transmitters'][] = [
                        'id' => (string) ($row['id'] ?? $row['transmitter_id']),
                        'channel' => (string) ($row['channel'] ?? $row['channel_number'] ?? 'Ch1'),
                    ];
                }
            }
        }
        $initialForm['gauge']  = array_merge($blankForm['gauge'],  array_map(fn ($v) => $v ?? '', (array) old('standard_gauge_spec', [])));
        $initialForm['prover'] = array_merge($blankForm['prover'], array_map(fn ($v) => $v ?? '', (array) old('prover_spec', [])));
        if (isset(old('prover_spec')['pulse_interpolation'])) {
            $initialForm['prover']['pulse_interpolation'] = (bool) old('prover_spec.pulse_interpolation');
        }
    }

    $storeUrl      = route('metrology.instruments.store', request()->query());
    $initialEditId = ($reopen && $oldMode === 'edit' && old('edit_id')) ? (int) old('edit_id') : null;
    $initialAction = $initialEditId
        ? route('metrology.instruments.update', array_merge(['instrument' => $initialEditId], request()->query()))
        : $storeUrl;

    $inputCls = 'mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600';
    $fileCls  = 'mt-1 block w-full text-xs text-gray-500 file:me-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-gray-700 dark:file:text-gray-300';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <x-tool-icon name="instruments" class="w-11 h-11 sm:w-12 sm:h-12 shrink-0" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Measuring Instruments') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Manage industrial measurement instruments, technical specifications, and tracking status') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="info" size="md">
                    {{ $instruments->total() }} {{ __('Records') }}
                </x-badge>
                @can('create measuring instruments')
                    {{-- Header sits outside the page's x-data, so we dispatch a window event --}}
                    <x-primary-button
                        type="button"
                        x-data
                        @click="$dispatch('open-create-instrument-modal'); window.dispatchEvent(new CustomEvent('open-create-instrument-modal'))"
                        class="flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>{{ __('New Instrument') }}</span>
                    </x-primary-button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8"
        @open-create-instrument-modal.window="openCreate()"
        @keydown.escape.window="showFormModal = false; showDeleteModal = false"
        x-effect="document.body.classList.toggle('overflow-hidden', showFormModal || showDeleteModal)"
        x-data="{
            rows: @js($rows),
            blank: @js($blankForm),
            storeUrl: @js($storeUrl),
            allTransmitters: (function() {
                try {
                    const b64 = '{{ base64_encode(json_encode($transmittersData, JSON_UNESCAPED_UNICODE)) }}';
                    if (!b64) return [];
                    const bin = atob(b64);
                    const bytes = Uint8Array.from(bin, c => c.charCodeAt(0));
                    return JSON.parse(new TextDecoder().decode(bytes));
                } catch(e) {
                    return [];
                }
            })(),

            showFormModal: @js($reopen),
            showErrors: @js($reopen),
            showDeleteModal: false,

            mode: @js($reopen ? $oldMode : 'create'),
            actionUrl: @js($initialAction),
            editId: @js($initialEditId),
            form: @js($initialForm),

            imageUrl: '',
            imagePreview: null,
            removeImage: false,

            deleteName: '',
            deleteActionUrl: '',

            measurementOptions: {
                pressure: [
                    { value: 'Relative', label: @js(__('Pression Relative / Gauge')) },
                    { value: 'Absolute', label: @js(__('Pression Absolue')) },
                    { value: 'Differential', label: @js(__('Pression Différentielle (ΔP)')) }
                ],
                flow: [
                    { value: 'Mass', label: @js(__('Mass Flow (Massique)')) },
                    { value: 'Volumetric', label: @js(__('Volumetric Flow (Volumique)')) }
                ],
                level: [
                    { value: 'Hydrostatic', label: @js(__('Hydrostatic Level (Hydrostatique)')) },
                    { value: 'Radar', label: @js(__('Radar Level')) },
                    { value: 'Ultrasonic', label: @js(__('Ultrasonic Level (Ultrasonique)')) }
                ],
                temperature: [
                    { value: 'RTD_PT100', label: @js(__('RTD / Pt100 (Résistance)')) },
                    { value: 'Thermocouple', label: @js(__('Thermocouple')) }
                ]
            },

            get currentMeasurementTypeList() {
                return this.measurementOptions[this.form.process_variable] || [];
            },

            get availableTransmittersForSite() {
                if (!this.form.site_id) return this.allTransmitters;
                return this.allTransmitters.filter(t => !t.site_id || String(t.site_id) === String(this.form.site_id));
            },

            addWiringRow() {
                const nextChannel = 'Ch' + (this.form.transmitters.length + 1);
                this.form.transmitters.push({
                    id: '',
                    channel: nextChannel
                });
            },

            removeWiringRow(idx) {
                this.form.transmitters.splice(idx, 1);
            },

            onTypeChange() {
                const type = this.form.instrument_type;
                if (type === 'chromatograph') {
                    this.form.process_variable = 'quality';
                    this.form.fluid_type = 'gas';
                    this.form.technology = this.form.technology && ['TCD', 'FID', 'TCD_FID'].includes(this.form.technology) ? this.form.technology : 'TCD';
                    this.form.measurement_type = 'Gas_Chromatography';
                } else if (type === 'standard_gauge') {
                    this.form.process_variable = 'volume';
                    this.form.fluid_type = 'liquid';
                    this.form.technology = 'Conventional';
                    this.form.measurement_type = 'Volumetric_Standard';
                } else if (type === 'prover') {
                    this.form.process_variable = 'volume';
                    this.form.fluid_type = 'liquid';
                    this.form.technology = 'Conventional';
                    this.form.measurement_type = 'Volumetric_Displacement';
                } else if (type === 'flow_computer') {
                    this.form.technology = 'SMART';
                    if (!this.form.transmitters || this.form.transmitters.length === 0) {
                        this.addWiringRow();
                    }
                } else if (type === 'probe') {
                    this.form.process_variable = 'temperature';
                    if (!this.form.technology || ['Conventional', 'SMART'].includes(this.form.technology)) {
                        this.form.technology = 'RTD_PT100_4W';
                    }
                    if (!this.form.measurement_type || ['Relative', 'Absolute', 'Differential'].includes(this.form.measurement_type)) {
                        this.form.measurement_type = 'Class_A';
                    }
                } else if (type === 'transmitter') {
                    if (!this.form.process_variable || ['quality', 'volume'].includes(this.form.process_variable)) {
                        this.form.process_variable = 'pressure';
                    }
                    if (!this.form.technology || !['Conventional', 'SMART'].includes(this.form.technology)) {
                        this.form.technology = 'SMART';
                    }
                }
            },

            clone(o) {
                return JSON.parse(JSON.stringify(o));
            },
            fill(base, src) {
                Object.entries(src || {}).forEach(([k, v]) => {
                    if (v !== null && v !== undefined) base[k] = v;
                });
                return base;
            },
            resetMedia(url = '') {
                if (this.imagePreview) URL.revokeObjectURL(this.imagePreview);
                this.imagePreview = null;
                this.removeImage = false;
                this.imageUrl = url;
            },
            previewImage(e) {
                if (this.imagePreview) URL.revokeObjectURL(this.imagePreview);
                const file = e.target.files[0];
                this.imagePreview = file ? URL.createObjectURL(file) : null;
            },

            openCreate() {
                this.mode = 'create';
                this.actionUrl = this.storeUrl;
                this.editId = null;
                this.form = this.clone(this.blank);
                this.resetMedia();
                this.showErrors = false;
                this.showFormModal = true;
            },
            openEdit(id) {
                const r = this.rows.find(x => x.id === id);
                if (!r) return;
                const f = this.clone(this.blank);
                ['tag_number', 'serial_number', 'site_id', 'instrument_type', 'status', 'process_variable', 'measurement_type', 'fluid_type', 'technology']
                    .forEach(k => { f[k] = r[k] ?? ''; });
                (r.specifications || []).forEach(s => {
                    if (f.specs[s.grandeur_id]) {
                        f.specs[s.grandeur_id] = {
                            selected: true,
                            min: s.range_min ?? '',
                            max: s.range_max ?? '',
                            acc: s.accuracy_value ?? '',
                            acc_type: s.accuracy_type || '%'
                        };
                    }
                });
                if (r.transmitters && r.transmitters.length) {
                    f.transmitters = this.clone(r.transmitters);
                }
                this.fill(f.gauge, r.gauge);
                this.fill(f.prover, r.prover);
                this.form = f;
                this.mode = 'edit';
                this.editId = r.id;
                this.actionUrl = r.update_url;
                this.resetMedia(r.image_url || '');
                this.showErrors = false;
                this.showFormModal = true;
            },
            openDelete(id) {
                const r = this.rows.find(x => x.id === id);
                if (!r) return;
                this.deleteName = r.tag_number;
                this.deleteActionUrl = r.delete_url;
                this.showDeleteModal = true;
            }
        }">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-metrology-tabs active="instruments" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-6">
                    @if (session('success'))
                        <x-alert variant="success">{{ session('success') }}</x-alert>
                    @endif

                    @if (session('error'))
                        <x-alert variant="danger">{{ session('error') }}</x-alert>
                    @endif

                    <!-- KPI Statistics Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 w-full">
                        @foreach($kpis as $kpi)
                            <a href="{{ $kpi['url'] }}"
                               class="p-3.5 sm:p-4 rounded-xl border bg-white dark:bg-gray-800 shadow-sm flex items-center gap-3 hover:shadow-md transition group {{ $kpi['active'] ? $kpi['ring'] : 'border-gray-200 dark:border-gray-700' }}">
                                <div class="p-2 sm:p-2.5 rounded-lg {{ $kpi['box'] }} shrink-0 group-hover:scale-105 transition">
                                    <i class="fas {{ $kpi['icon'] }} text-base sm:text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-medium truncate">{{ $kpi['label'] }}</div>
                                    <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-white mt-0.5">{{ $kpi['value'] }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <!-- Main Instruments Table -->
                    <x-table>
                        <x-slot:toolbar>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                                    <x-tool-icon name="instruments" class="w-6 h-6 shrink-0" />
                                    <span>{{ __('Measuring Instruments Registry') }}</span>
                                </h3>

                                <div class="flex flex-wrap items-center gap-3">
                                    <x-global-filter
                                        :action="route('metrology.instruments')"
                                        :search="true"
                                        :search-placeholder="__('Search by Tag, Serial number...')"
                                        :search-value="request('search')"
                                        :submit-text="__('Search')"
                                    >
                                        <select name="site_id" onchange="this.form.submit()" class="py-1.5 ps-2.5 pe-8 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600 shadow-sm">
                                            <option value="">{{ __('All Sites') }}</option>
                                            @foreach($sites as $site)
                                                <option value="{{ $site->id }}" @selected(request('site_id') == $site->id)>
                                                    {{ $site->short_name ?? $site->full_name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <select name="instrument_type" onchange="this.form.submit()" class="py-1.5 ps-2.5 pe-8 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600 shadow-sm">
                                            <option value="">{{ __('All Types') }}</option>
                                            @foreach(\App\Enums\InstrumentType::cases() as $type)
                                                <option value="{{ $type->value }}" @selected(request('instrument_type') === $type->value)>
                                                    {{ $type->label() }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <select name="status" onchange="this.form.submit()" class="py-1.5 ps-2.5 pe-8 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600 shadow-sm">
                                            <option value="">{{ __('All Statuses') }}</option>
                                            @foreach(\App\Enums\InstrumentStatus::cases() as $st)
                                                <option value="{{ $st->value }}" @selected(request('status') === $st->value)>
                                                    {{ $st->label() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </x-global-filter>
                                </div>
                            </div>
                        </x-slot:toolbar>

                        <x-slot:header>
                            <x-table.th class="w-16">{{ __('Photo') }}</x-table.th>
                            <x-table.th>{{ __('Tag & Serial') }}</x-table.th>
                            <x-table.th>{{ __('Instrument Type') }}</x-table.th>
                            <x-table.th>{{ __('Specifications / Ranges') }}</x-table.th>
                            <x-table.th>{{ __('Site') }}</x-table.th>
                            <x-table.th>{{ __('Status') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                        </x-slot:header>

                        @forelse($instruments as $instrument)
                            @php
                                $iType   = $instrument->instrument_type;
                                $iStatus = $instrument->status;
                                $iIcon   = $iType?->icon() ?? 'fa-microchip';
                                $gauge   = $instrument->standardGaugeSpecification;
                                $prover  = $instrument->proverSpecification;
                            @endphp
                            <x-table.tr>
                                <x-table.td>
                                    @if($instrument->image_url)
                                        <img src="{{ $instrument->image_url }}" alt="{{ $instrument->tag_number }}" class="w-11 h-11 object-contain rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm bg-white dark:bg-gray-800 p-0.5">
                                    @else
                                        <div class="w-11 h-11 rounded-lg bg-gray-100 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400">
                                            <i class="fas {{ $iIcon }}"></i>
                                        </div>
                                    @endif
                                </x-table.td>

                                <x-table.td>
                                    <div class="font-bold text-gray-900 dark:text-white font-mono text-sm">{{ $instrument->tag_number }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-mono mt-0.5">{{ __('S/N') }}: {{ $instrument->serial_number }}</div>
                                </x-table.td>

                                <x-table.td>
                                    @if($iType)
                                        <x-badge :variant="$iType->badgeVariant()" size="sm">
                                            <i class="fas {{ $iIcon }} me-1"></i>
                                            {{ $iType->label() }}
                                        </x-badge>
                                    @else
                                        <span class="text-gray-400 text-xs">---</span>
                                    @endif
                                    @if($instrument->technology)
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 font-medium">{{ $instrument->technology }}</div>
                                    @endif
                                </x-table.td>

                                <x-table.td>
                                    @if($instrument->specifications->isNotEmpty())
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($instrument->specifications as $spec)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-mono bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                                    {{ $spec->grandeur?->name }}: {{ $spec->range_min ?? '---' }} → {{ $spec->range_max ?? '---' }} {{ $spec->grandeur?->symbol }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @elseif($gauge)
                                        <span class="text-xs font-mono text-gray-700 dark:text-gray-300">
                                            {{ $gauge->nominal_capacity_liters !== null ? number_format((float) $gauge->nominal_capacity_liters, 3) . ' ' . __('L') : '---' }}
                                        </span>
                                    @elseif($prover)
                                        <span class="text-xs font-mono text-gray-700 dark:text-gray-300">
                                            {{ $prover->type instanceof \App\Enums\ProverType ? $prover->type->label() : ($prover->type ?: '---') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-xs">---</span>
                                    @endif
                                </x-table.td>

                                <x-table.td>
                                    @if($instrument->site)
                                        <span class="inline-flex items-center gap-1 text-xs text-gray-700 dark:text-gray-300 font-medium">
                                            <i class="fas fa-map-marker-alt text-brand-600 text-[10px]"></i>
                                            {{ $instrument->site->short_name ?? $instrument->site->full_name }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-xs">---</span>
                                    @endif
                                </x-table.td>

                                <x-table.td>
                                    @if($iStatus)
                                        <x-badge :variant="$iStatus->badgeVariant()" :dot="true" size="sm">
                                            {{ $iStatus->label() }}
                                        </x-badge>
                                    @else
                                        <span class="text-gray-400 text-xs">---</span>
                                    @endif
                                </x-table.td>

                                <x-table.td class="text-end">
                                    <x-table.actions class="justify-end">
                                        @can('view measuring instruments')
                                            <x-table.action-view :href="route('metrology.instruments.show', $instrument)" />
                                        @endcan

                                        @can('edit measuring instruments')
                                            {{-- href kept as a no-JS fallback; click opens the modal --}}
                                            <x-table.action-edit
                                                :href="route('metrology.instruments.edit', $instrument)"
                                                :title="__('Edit Measuring Instrument')"
                                                @click.prevent="openEdit({{ $instrument->id }})"
                                            />
                                        @endcan

                                        @can('delete measuring instruments')
                                            <x-table.action-delete
                                                type="button"
                                                @click="openDelete({{ $instrument->id }})"
                                                :title="__('Delete Measuring Instrument')"
                                            />
                                        @endcan
                                    </x-table.actions>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty :colspan="7" :message="__('No measuring instruments found.')" />
                        @endforelse

                        <x-slot:pagination>
                            {{ $instruments->withQueryString()->links() }}
                        </x-slot:pagination>
                    </x-table>
                </main>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════
             Create / Edit Instrument Modal (single shared form)
        ══════════════════════════════════════════════════ -->
        <template x-teleport="body">
            <div
                x-show="showFormModal"
                x-cloak
                class="fixed inset-0 z-[100] overflow-y-auto"
                aria-labelledby="instrument-modal-title"
                role="dialog"
                aria-modal="true"
            >
                <div
                    x-show="showFormModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="showFormModal = false"
                    class="fixed inset-0 bg-gray-900/70 backdrop-blur-sm transition-opacity"
                ></div>

                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-6">
                    <div
                        x-show="showFormModal"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="relative z-10 w-full max-w-4xl bg-white dark:bg-gray-800 rounded-2xl text-start shadow-2xl transform transition-all border border-gray-100 dark:border-gray-700 flex flex-col max-h-[90vh] overflow-hidden my-4 sm:my-8"
                        @click.stop
                    >
                        <form :action="actionUrl" method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
                        @csrf
                        <input type="hidden" name="_form" :value="mode">
                        <input type="hidden" name="_method" value="PUT" :disabled="mode !== 'edit'">
                        <input type="hidden" name="edit_id" :value="editId" :disabled="mode !== 'edit'">

                        <!-- Sticky Modal Header -->
                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 px-6 py-4 shrink-0 bg-white dark:bg-gray-800 z-10">
                            <div class="flex items-center gap-3">
                                <x-tool-icon name="instruments" class="w-9 h-9 shrink-0" />
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 id="instrument-modal-title" class="text-lg font-bold text-gray-900 dark:text-white"
                                            x-text="mode === 'edit' ? @js(__('Edit Measuring Instrument')) : @js(__('New Measuring Instrument'))"></h3>
                                        <x-badge variant="info" size="sm" class="uppercase font-mono" x-text="form.instrument_type.replace('_', ' ')"></x-badge>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ __('Manage industrial measurement instruments, technical specifications, and tracking status') }}
                                    </p>
                                </div>
                            </div>
                            <button type="button" @click="showFormModal = false" class="text-gray-400 hover:text-gray-500 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition" aria-label="{{ __('Close') }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- Scrollable Form Body -->
                        <div class="p-6 space-y-6 overflow-y-auto flex-1">
                            @if ($reopen)
                                <div x-show="showErrors">
                                    <x-alert variant="danger">
                                        <ul class="list-disc list-inside text-xs space-y-1">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </x-alert>
                                </div>
                            @endif

                            <!-- SECTION 1: Core Instrument Identity -->
                            <div class="p-5 rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50/40 dark:bg-gray-900/20 space-y-4">
                                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-2.5">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                        <i class="fas fa-id-card text-brand-600"></i>
                                        <span>{{ __('Basic Identification & Deployed Site') }}</span>
                                    </h4>
                                    <span class="text-[11px] text-gray-400 font-mono">{{ __('Identity') }}</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <x-input-label for="f_tag_number" :value="__('Tag Number')" :required="true" />
                                        <x-text-input id="f_tag_number" name="tag_number" type="text" class="mt-1 block w-full font-mono text-sm" x-model="form.tag_number" placeholder="e.g. 05-PT-595 A" required />
                                    </div>

                                    <div>
                                        <x-input-label for="f_serial_number" :value="__('Serial Number')" :required="true" />
                                        <x-text-input id="f_serial_number" name="serial_number" type="text" class="mt-1 block w-full font-mono text-sm" x-model="form.serial_number" placeholder="e.g. SN-984210" required />
                                    </div>

                                    <div>
                                        <x-input-label for="f_instrument_type" :value="__('Instrument Type')" :required="true" />
                                        <select id="f_instrument_type" name="instrument_type" x-model="form.instrument_type" @change="onTypeChange()" required class="{{ $inputCls }}">
                                            @foreach(\App\Enums\InstrumentType::cases() as $type)
                                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100 dark:border-gray-700/50">
                                    <div>
                                        <x-input-label for="f_site_id" :value="__('Site')" />
                                        <select id="f_site_id" name="site_id" x-model="form.site_id" class="{{ $inputCls }}">
                                            <option value="">-- {{ __('Select Site') }} --</option>
                                            @foreach($sites as $site)
                                                <option value="{{ $site->id }}">{{ $site->short_name ?? $site->full_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <x-input-label for="f_status" :value="__('Status')" :required="true" />
                                        <select id="f_status" name="status" x-model="form.status" required class="{{ $inputCls }}">
                                            @foreach(\App\Enums\InstrumentStatus::cases() as $st)
                                                <option value="{{ $st->value }}">{{ $st->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Photo Upload -->
                                <div class="pt-2 border-t border-gray-100 dark:border-gray-700/50 space-y-2">
                                    <x-input-label for="f_image" :value="__('Instrument Photo (WebP / JPEG / PNG)')" />

                                    <div x-show="mode === 'edit' && imageUrl"
                                         class="flex items-center gap-3 p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                        <img :src="imageUrl" :class="removeImage ? 'opacity-40 grayscale' : ''" class="w-12 h-12 object-contain rounded border border-gray-200 dark:border-gray-700 p-0.5 bg-white dark:bg-gray-900 transition" alt="{{ __('Current Photo') }}">
                                        <label class="flex items-center gap-1.5 text-xs text-rose-600 dark:text-rose-400 cursor-pointer">
                                            <input type="checkbox" name="remove_image" value="1" x-model="removeImage" :disabled="mode !== 'edit'" class="rounded border-rose-300 text-rose-600">
                                            <span>{{ __('Remove Current Photo') }}</span>
                                        </label>
                                    </div>

                                    <input id="f_image" name="image" type="file" accept="image/*" @change="previewImage($event)" class="{{ $fileCls }}">

                                    <div x-show="imagePreview" class="mt-2.5">
                                        <img :src="imagePreview" class="w-16 h-16 object-contain rounded-lg border border-gray-200 dark:border-gray-700 p-1 bg-white dark:bg-gray-900" alt="{{ __('Preview') }}">
                                    </div>
                                </div>
                            </div>

                            <!-- ══════════════════════════════════════════════════
                                 CATEGORY 1: TRANSMITTER (محول إشارة)
                            ══════════════════════════════════════════════════ -->
                            <template x-if="form.instrument_type === 'transmitter'">
                                <div class="space-y-5">
                                    <!-- Classification banner -->
                                    <div class="p-4 rounded-xl border border-indigo-200 dark:border-indigo-800/70 bg-indigo-50/60 dark:bg-indigo-950/30 flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2.5 text-indigo-900 dark:text-indigo-200 font-semibold">
                                            <i class="fas fa-satellite-dish text-indigo-600 dark:text-indigo-400 text-base"></i>
                                            <span>{{ __('Transmitter') }} — 4-20mA / HART Loop & Sensing</span>
                                        </div>
                                        <x-badge variant="info" size="sm">HART / 4-20mA</x-badge>
                                    </div>

                                    <!-- Process Variable & Technology -->
                                    <div class="p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 space-y-4">
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                            <i class="fas fa-sliders-h text-brand-600"></i>
                                            <span>{{ __('Process Variable & Technology Classification') }}</span>
                                        </h4>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                            <div>
                                                <x-input-label for="tx_process_variable" :value="__('Process Variable')" :required="true" />
                                                <select id="tx_process_variable" name="process_variable" x-model="form.process_variable" required class="{{ $inputCls }}">
                                                    @foreach(\App\Enums\ProcessVariable::cases() as $pv)
                                                        @if($pv !== \App\Enums\ProcessVariable::Quality && $pv !== \App\Enums\ProcessVariable::Volume)
                                                            <option value="{{ $pv->value }}">{{ $pv->label() }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <x-input-label for="tx_measurement_type" :value="__('Measurement Type / Principle')" />
                                                <select id="tx_measurement_type" name="measurement_type" x-model="form.measurement_type" class="{{ $inputCls }}">
                                                    <option value="">-- {{ __('Select Principle') }} --</option>
                                                    <template x-for="opt in currentMeasurementTypeList" :key="opt.value">
                                                        <option :value="opt.value" x-text="opt.label" :selected="opt.value === form.measurement_type"></option>
                                                    </template>
                                                </select>
                                            </div>

                                            <div>
                                                <x-input-label for="tx_fluid_type" :value="__('Fluid Type')" />
                                                <select id="tx_fluid_type" name="fluid_type" x-model="form.fluid_type" class="{{ $inputCls }}">
                                                    <option value="">-- {{ __('Select Fluid') }} --</option>
                                                    @foreach(\App\Enums\FluidType::cases() as $ft)
                                                        <option value="{{ $ft->value }}">{{ $ft->label() }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <x-input-label for="tx_technology" :value="__('Sensor Technology / Architecture')" />
                                                <select id="tx_technology" name="technology" x-model="form.technology" class="{{ $inputCls }}">
                                                    <option value="Conventional">{{ __('Conventional (Analog / 4-20mA)') }}</option>
                                                    <option value="SMART">{{ __('SMART (HART / Fieldbus / Digital)') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Physical Quantities & Ranges -->
                                    <div class="p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 space-y-4">
                                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-2.5">
                                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                                <i class="fas fa-wave-square text-brand-600"></i>
                                                <span>{{ __('Physical Quantities, Calibration Ranges & Precision') }}</span>
                                            </h4>
                                            <span class="text-[11px] text-gray-400 font-mono">{{ __('Active Loops') }}</span>
                                        </div>

                                        <!-- Measurement capabilities -->
                                        @if($measurementGrandeurs->count() > 0)
                                            <div class="space-y-2">
                                                <div class="flex items-center justify-between text-xs font-bold text-blue-600 dark:text-blue-400">
                                                    <span>{{ __('Measurement Capabilities') }} (Capteurs / Sensing)</span>
                                                    <x-badge variant="info" size="sm">Measurement</x-badge>
                                                </div>

                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                    @foreach($measurementGrandeurs as $g)
                                                        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 space-y-2">
                                                            <div class="flex items-center justify-between text-xs">
                                                                <label class="flex items-center gap-2 cursor-pointer font-bold text-gray-900 dark:text-white">
                                                                    <input type="checkbox" name="params[{{ $g->id }}][selected]" value="1"
                                                                           x-model="form.specs[{{ $g->id }}].selected"
                                                                           class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                                                    <span>{{ $g->name }}</span>
                                                                    <span class="font-mono text-gray-400">({{ $g->symbol }})</span>
                                                                </label>
                                                            </div>

                                                            <div class="grid grid-cols-4 gap-1.5 font-mono text-xs" x-show="form.specs[{{ $g->id }}].selected">
                                                                <div>
                                                                    <input type="number" step="any" name="params[{{ $g->id }}][min]" x-model="form.specs[{{ $g->id }}].min" placeholder="{{ __('Min') }}" class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-1.5">
                                                                </div>
                                                                <div>
                                                                    <input type="number" step="any" name="params[{{ $g->id }}][max]" x-model="form.specs[{{ $g->id }}].max" placeholder="{{ __('Max') }}" class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-1.5">
                                                                </div>
                                                                <div>
                                                                    <input type="number" step="any" name="params[{{ $g->id }}][acc]" x-model="form.specs[{{ $g->id }}].acc" placeholder="{{ __('Accuracy') }}" class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-1.5">
                                                                </div>
                                                                <div>
                                                                    <select name="params[{{ $g->id }}][acc_type]" x-model="form.specs[{{ $g->id }}].acc_type" class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-1">
                                                                        <option value="%">%</option>
                                                                        <option value="abs">{{ __('Abs') }}</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Source capabilities -->
                                        @if($sourceGrandeurs->count() > 0)
                                            <div class="space-y-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                                                <div class="flex items-center justify-between text-xs font-bold text-amber-600 dark:text-amber-400">
                                                    <span>{{ __('Source / Generation Capabilities') }} (Source / Boucle 4-20mA)</span>
                                                    <x-badge variant="warning" size="sm">Source</x-badge>
                                                </div>

                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                    @foreach($sourceGrandeurs as $g)
                                                        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 space-y-2">
                                                            <div class="flex items-center justify-between text-xs">
                                                                <label class="flex items-center gap-2 cursor-pointer font-bold text-gray-900 dark:text-white">
                                                                    <input type="checkbox" name="params[{{ $g->id }}][selected]" value="1"
                                                                           x-model="form.specs[{{ $g->id }}].selected"
                                                                           class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                                                    <span>{{ $g->name }}</span>
                                                                    <span class="font-mono text-gray-400">({{ $g->symbol }})</span>
                                                                </label>
                                                            </div>

                                                            <div class="grid grid-cols-4 gap-1.5 font-mono text-xs" x-show="form.specs[{{ $g->id }}].selected">
                                                                <div>
                                                                    <input type="number" step="any" name="params[{{ $g->id }}][min]" x-model="form.specs[{{ $g->id }}].min" placeholder="{{ __('Min') }}" class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-1.5">
                                                                </div>
                                                                <div>
                                                                    <input type="number" step="any" name="params[{{ $g->id }}][max]" x-model="form.specs[{{ $g->id }}].max" placeholder="{{ __('Max') }}" class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-1.5">
                                                                </div>
                                                                <div>
                                                                    <input type="number" step="any" name="params[{{ $g->id }}][acc]" x-model="form.specs[{{ $g->id }}].acc" placeholder="{{ __('Accuracy') }}" class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-1.5">
                                                                </div>
                                                                <div>
                                                                    <select name="params[{{ $g->id }}][acc_type]" x-model="form.specs[{{ $g->id }}].acc_type" class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-1">
                                                                        <option value="%">%</option>
                                                                        <option value="abs">{{ __('Abs') }}</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </template>

                            <!-- ══════════════════════════════════════════════════
                                 CATEGORY 2: TEMPERATURE PROBE (مسبار حرارة)
                            ══════════════════════════════════════════════════ -->
                            <template x-if="form.instrument_type === 'probe'">
                                <div class="space-y-5">
                                    <div class="p-4 rounded-xl border border-teal-200 dark:border-teal-800/70 bg-teal-50/60 dark:bg-teal-950/30 flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2.5 text-teal-900 dark:text-teal-200 font-semibold">
                                            <i class="fas fa-thermometer-half text-teal-600 dark:text-teal-400 text-base"></i>
                                            <span>{{ __('Temperature Probe') }} — IEC 60751 / ASTM E 1137</span>
                                        </div>
                                        <x-badge variant="neutral" size="sm">RTD / Pt100</x-badge>
                                    </div>

                                    <input type="hidden" name="process_variable" value="temperature">

                                    <div class="p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 space-y-4">
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                            <i class="fas fa-microchip text-brand-600"></i>
                                            <span>{{ __('Sensor Technology & Wiring Classification') }}</span>
                                        </h4>

                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <div>
                                                <x-input-label for="pb_technology" :value="__('Sensor Technology / Architecture')" :required="true" />
                                                <select id="pb_technology" name="technology" x-model="form.technology" required class="{{ $inputCls }}">
                                                    <option value="RTD_PT100_4W">RTD Pt100 (4-Wire Precision)</option>
                                                    <option value="RTD_PT100_3W">RTD Pt100 (3-Wire)</option>
                                                    <option value="RTD_PT100_2W">RTD Pt100 (2-Wire)</option>
                                                    <option value="Thermocouple_K">Thermocouple Type K (Chromel / Alumel)</option>
                                                    <option value="Thermocouple_J">Thermocouple Type J (Iron / Constantan)</option>
                                                    <option value="Thermocouple_T">Thermocouple Type T (Copper / Constantan)</option>
                                                </select>
                                            </div>

                                            <div>
                                                <x-input-label for="pb_fluid_type" :value="__('Fluid Type')" />
                                                <select id="pb_fluid_type" name="fluid_type" x-model="form.fluid_type" class="{{ $inputCls }}">
                                                    @foreach(\App\Enums\FluidType::cases() as $ft)
                                                        <option value="{{ $ft->value }}">{{ $ft->label() }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <x-input-label for="pb_tolerance_class" :value="__('Tolerance / Class')" />
                                                <select id="pb_tolerance_class" name="measurement_type" x-model="form.measurement_type" class="{{ $inputCls }}">
                                                    <option value="Class_A">Class A (±0.15 + 0.002·|t| °C)</option>
                                                    <option value="Class_B">Class B (±0.30 + 0.005·|t| °C)</option>
                                                    <option value="Class_1_3_DIN">1/3 DIN (±0.05 + 0.001·|t| °C)</option>
                                                    <option value="Class_1_10_DIN">1/10 DIN Ultra-Precision</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Temperature & Resistance Grandeurs -->
                                    <div class="p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 space-y-4">
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                            <i class="fas fa-sliders-h text-brand-600"></i>
                                            <span>{{ __('Physical Quantities, Calibration Ranges & Precision') }}</span>
                                        </h4>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            @foreach($measurementGrandeurs as $g)
                                                <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 space-y-2">
                                                    <div class="flex items-center justify-between text-xs">
                                                        <label class="flex items-center gap-2 cursor-pointer font-bold text-gray-900 dark:text-white">
                                                            <input type="checkbox" name="params[{{ $g->id }}][selected]" value="1"
                                                                   x-model="form.specs[{{ $g->id }}].selected"
                                                                   class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                                            <span>{{ $g->name }}</span>
                                                            <span class="font-mono text-gray-400">({{ $g->symbol }})</span>
                                                        </label>
                                                    </div>

                                                    <div class="grid grid-cols-4 gap-1.5 font-mono text-xs" x-show="form.specs[{{ $g->id }}].selected">
                                                        <div>
                                                            <input type="number" step="any" name="params[{{ $g->id }}][min]" x-model="form.specs[{{ $g->id }}].min" placeholder="{{ __('Min') }}" class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-1.5">
                                                        </div>
                                                        <div>
                                                            <input type="number" step="any" name="params[{{ $g->id }}][max]" x-model="form.specs[{{ $g->id }}].max" placeholder="{{ __('Max') }}" class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-1.5">
                                                        </div>
                                                        <div>
                                                            <input type="number" step="any" name="params[{{ $g->id }}][acc]" x-model="form.specs[{{ $g->id }}].acc" placeholder="{{ __('Accuracy') }}" class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-1.5">
                                                        </div>
                                                        <div>
                                                            <select name="params[{{ $g->id }}][acc_type]" x-model="form.specs[{{ $g->id }}].acc_type" class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-1">
                                                                <option value="%">%</option>
                                                                <option value="abs">{{ __('Abs') }}</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- ══════════════════════════════════════════════════
                                 CATEGORY 3: FLOW COMPUTER (حاسوب تدفق)
                            ══════════════════════════════════════════════════ -->
                            <template x-if="form.instrument_type === 'flow_computer'">
                                <div class="space-y-5">
                                    <div class="p-4 rounded-xl border border-purple-200 dark:border-purple-800/70 bg-purple-50/60 dark:bg-purple-950/30 flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2.5 text-purple-900 dark:text-purple-200 font-semibold">
                                            <i class="fas fa-server text-purple-600 dark:text-purple-400 text-base"></i>
                                            <span>{{ __('Flow Computer') }} — AGA 8 / ISO 6976 / API MPMS Supervisory Metrology</span>
                                        </div>
                                        <x-badge variant="primary" size="sm">AGA 8 / ISO 6976</x-badge>
                                    </div>

                                    <input type="hidden" name="technology" value="SMART">

                                    <!-- Multichannel Wiring Section -->
                                    <div class="p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 space-y-4">
                                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                                            <div>
                                                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                                    <i class="fas fa-network-wired text-brand-600"></i>
                                                    <span>{{ __('Connected Transmitters & Channels Wiring') }}</span>
                                                </h4>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                    {{ __('Link physical pressure, temperature, and differential transmitters to flow computer calculation channels') }}
                                                </p>
                                            </div>

                                            <x-secondary-button type="button" @click="addWiringRow()" class="flex items-center gap-1.5 text-xs">
                                                <i class="fas fa-plus text-brand-600"></i>
                                                <span>{{ __('Add Channel') }}</span>
                                            </x-secondary-button>
                                        </div>

                                        <!-- Site hint banner -->
                                        <div class="p-3 rounded-xl border text-xs flex items-center gap-2.5 transition"
                                             :class="availableTransmittersForSite.length > 0 ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300' : 'bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800/60 text-amber-800 dark:text-amber-300'">
                                            <i :class="availableTransmittersForSite.length > 0 ? 'fas fa-check-circle text-emerald-500' : 'fas fa-exclamation-triangle text-amber-500'"></i>
                                            <span x-show="!form.site_id">{{ __('Please select an industrial site above to filter available transmitters.') }}</span>
                                            <span x-show="form.site_id && availableTransmittersForSite.length > 0">
                                                <strong x-text="availableTransmittersForSite.length"></strong> {{ __('active transmitters deployed at this station ready for channel wiring.') }}
                                            </span>
                                            <span x-show="form.site_id && availableTransmittersForSite.length === 0">
                                                {{ __('No active transmitters found for the selected site.') }}
                                            </span>
                                        </div>

                                        <!-- Dynamic wiring rows -->
                                        <div class="space-y-2.5">
                                            <template x-for="(row, index) in form.transmitters" :key="index">
                                                <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 flex items-center gap-3">
                                                    <div class="flex-1">
                                                        <label class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">{{ __('Transmitter') }}</label>
                                                        <select :name="'transmitters[' + index + '][id]'" x-model="row.id"
                                                            class="mt-0.5 block w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-brand-600">
                                                            <option value="">-- {{ __('Select Transmitter') }} --</option>
                                                            <template x-for="t in availableTransmittersForSite" :key="t.id">
                                                                <option :value="t.id" x-text="t.tag + (t.serial_number ? ' (' + t.serial_number + ')' : '')"></option>
                                                            </template>
                                                        </select>
                                                    </div>

                                                    <div class="w-32">
                                                        <label class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">{{ __('Channel') }}</label>
                                                        <input type="text" :name="'transmitters[' + index + '][channel]'" x-model="row.channel"
                                                            placeholder="Ch1"
                                                            class="mt-0.5 block w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-2.5">
                                                    </div>

                                                    <div class="pt-4">
                                                        <button type="button" @click="removeWiringRow(index)" class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition" title="{{ __('Remove Channel') }}">
                                                            <i class="fas fa-trash-alt text-sm"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>

                                            <div x-show="form.transmitters.length === 0" class="p-6 text-center text-xs text-gray-400 dark:text-gray-500 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                                                {{ __('No transmitter channels wired yet. Click "Add Channel" to connect transmitters.') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- ══════════════════════════════════════════════════
                                 CATEGORY 4: CHROMATOGRAPH (كروماتوغرافيا الغاز)
                            ══════════════════════════════════════════════════ -->
                            <template x-if="form.instrument_type === 'chromatograph'">
                                <div class="space-y-5">
                                    <div class="p-4 rounded-xl border border-purple-200 dark:border-purple-800/70 bg-purple-50/60 dark:bg-purple-950/30 flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2.5 text-purple-900 dark:text-purple-200 font-semibold">
                                            <i class="fas fa-vial text-purple-600 dark:text-purple-400 text-base"></i>
                                            <span>{{ __('Gas Chromatograph') }} — ISO 6974 / ASTM D 1945 Online Analytical Quality</span>
                                        </div>
                                        <x-badge variant="neutral" size="sm">ISO 6974</x-badge>
                                    </div>

                                    <input type="hidden" name="process_variable" value="quality">
                                    <input type="hidden" name="fluid_type" value="gas">
                                    <input type="hidden" name="measurement_type" value="Gas_Chromatography">

                                    <div class="p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 space-y-4">
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                            <i class="fas fa-microchip text-brand-600"></i>
                                            <span>{{ __('Chromatography Detectors') }} & Hardware</span>
                                        </h4>

                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <div>
                                                <x-input-label for="gc_technology" :value="__('Chromatography Detectors')" :required="true" />
                                                <select id="gc_technology" name="technology" x-model="form.technology" required class="{{ $inputCls }}">
                                                    <option value="TCD">TCD (Thermal Conductivity Detector)</option>
                                                    <option value="FID">FID (Flame Ionization Detector)</option>
                                                    <option value="TCD_FID">Dual TCD / FID Detectors</option>
                                                </select>
                                            </div>

                                            <div>
                                                <x-input-label for="gc_carrier_gas" :value="__('Carrier Gas Type')" />
                                                <select id="gc_carrier_gas" name="carrier_gas" class="{{ $inputCls }}">
                                                    <option value="Helium">Helium (He - 99.999% Purity)</option>
                                                    <option value="Hydrogen">Hydrogen (H2 - Ultra Pure)</option>
                                                    <option value="Nitrogen">Nitrogen (N2 - Carrier)</option>
                                                </select>
                                            </div>

                                            <div>
                                                <x-input-label for="gc_sampling_streams" :value="__('Sampling Streams Count')" />
                                                <input type="number" id="gc_sampling_streams" name="sampling_streams" min="1" max="12" value="1" class="{{ $inputCls }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- ══════════════════════════════════════════════════
                                 CATEGORY 5: STANDARD GAUGE (الجاجة العيارية)
                            ══════════════════════════════════════════════════ -->
                            <template x-if="form.instrument_type === 'standard_gauge'">
                                <div class="space-y-5">
                                    <div class="p-4 rounded-xl border border-amber-200 dark:border-amber-800/70 bg-amber-50/60 dark:bg-amber-950/30 flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2.5 text-amber-900 dark:text-amber-200 font-semibold">
                                            <i class="fas fa-flask text-amber-600 dark:text-amber-400 text-base"></i>
                                            <span>{{ __('Standard Volumetric Test Measure (Jauge étalon) Specifications') }} — ISO 17025</span>
                                        </div>
                                        <x-badge variant="success" size="sm">ISO 17025</x-badge>
                                    </div>

                                    <input type="hidden" name="process_variable" value="volume">
                                    <input type="hidden" name="fluid_type" value="liquid">
                                    <input type="hidden" name="technology" value="Conventional">

                                    <div class="p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 space-y-4">
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                            <i class="fas fa-flask text-brand-600"></i>
                                            <span>{{ __('Standard Volumetric Test Measure (Jauge étalon) Specifications') }}</span>
                                        </h4>

                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <div>
                                                <x-input-label for="f_g_capacity" :value="__('Certified Nominal Capacity BMV (Liters)')" :required="true" />
                                                <x-text-input id="f_g_capacity" name="standard_gauge_spec[nominal_capacity_liters]" type="number" step="0.00001" class="mt-1 block w-full font-mono text-sm" x-model="form.gauge.nominal_capacity_liters" placeholder="e.g. 500.00000" required />
                                            </div>

                                            <div>
                                                <x-input-label for="f_g_neck" :value="__('Neck Scale Sensitivity (L/mm)')" />
                                                <x-text-input id="f_g_neck" name="standard_gauge_spec[neck_scale_sensitivity]" type="number" step="0.00001" class="mt-1 block w-full font-mono text-sm" x-model="form.gauge.neck_scale_sensitivity" placeholder="e.g. 0.05000" />
                                            </div>

                                            <div>
                                                <x-input-label for="f_g_gcm" :value="__('Metal Cubical Expansion Coef Gcm (1/°C)')" />
                                                <x-text-input id="f_g_gcm" name="standard_gauge_spec[cubical_expansion_coef_gcm]" type="number" step="0.00000001" class="mt-1 block w-full font-mono text-sm" x-model="form.gauge.cubical_expansion_coef_gcm" placeholder="0.00005100" />
                                            </div>

                                            <div>
                                                <x-input-label for="f_g_cert" :value="__('ISO 17025 Certificate Number')" />
                                                <x-text-input id="f_g_cert" name="standard_gauge_spec[calibration_certificate_number]" type="text" class="mt-1 block w-full text-sm" x-model="form.gauge.calibration_certificate_number" placeholder="CERT-ISO-2026" />
                                            </div>

                                            <div>
                                                <x-input-label for="f_g_date" :value="__('Calibration Date')" />
                                                <x-text-input id="f_g_date" name="standard_gauge_spec[calibration_date]" type="date" class="mt-1 block w-full text-sm" x-model="form.gauge.calibration_date" />
                                            </div>

                                            <div>
                                                <x-input-label for="f_g_expiry" :value="__('Calibration Expiry Date')" />
                                                <x-text-input id="f_g_expiry" name="standard_gauge_spec[calibration_expiry_date]" type="date" class="mt-1 block w-full text-sm" x-model="form.gauge.calibration_expiry_date" />
                                            </div>

                                            <div>
                                                <x-input-label for="f_g_mat" :value="__('Vessel Material')" />
                                                <x-text-input id="f_g_mat" name="standard_gauge_spec[vessel_material]" type="text" class="mt-1 block w-full text-sm" x-model="form.gauge.vessel_material" placeholder="Stainless Steel 316" />
                                            </div>

                                            <div>
                                                <x-input-label for="f_g_temp" :value="__('Base Reference Temperature (°C)')" />
                                                <x-text-input id="f_g_temp" name="standard_gauge_spec[base_reference_temperature]" type="number" step="0.1" class="mt-1 block w-full font-mono text-sm" x-model="form.gauge.base_reference_temperature" placeholder="20.0" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- ══════════════════════════════════════════════════
                                 CATEGORY 6: PROVER (أنبوب معايرة الحجم)
                            ══════════════════════════════════════════════════ -->
                            <template x-if="form.instrument_type === 'prover'">
                                <div class="space-y-5">
                                    <div class="p-4 rounded-xl border border-teal-200 dark:border-teal-800/70 bg-teal-50/60 dark:bg-teal-950/30 flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2.5 text-teal-900 dark:text-teal-200 font-semibold">
                                            <i class="fas fa-tachometer-alt text-teal-600 dark:text-teal-400 text-base"></i>
                                            <span>{{ __('Prover Metrological Specifications & Secondary Classification') }} — API MPMS Ch. 4 / OAM</span>
                                        </div>
                                        <x-badge variant="info" size="sm">API MPMS Ch. 4</x-badge>
                                    </div>

                                    <input type="hidden" name="process_variable" value="volume">
                                    <input type="hidden" name="fluid_type" value="liquid">
                                    <input type="hidden" name="technology" value="Conventional">

                                    <div class="p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 space-y-4">
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                            <i class="fas fa-tachometer-alt text-brand-600"></i>
                                            <span>{{ __('Prover Metrological Specifications & Secondary Classification') }}</span>
                                        </h4>

                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <div>
                                                <x-input-label for="f_p_type" :value="__('Prover Secondary Classification')" :required="true" />
                                                <select id="f_p_type" name="prover_spec[type]" x-model="form.prover.type" required class="{{ $inputCls }}">
                                                    @foreach(\App\Enums\ProverType::cases() as $pt)
                                                        <option value="{{ $pt->value }}">{{ $pt->label() }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <x-input-label for="f_p_id" :value="__('Internal Diameter (ID) mm')" />
                                                <x-text-input id="f_p_id" name="prover_spec[inner_diameter]" type="number" step="0.0001" class="mt-1 block w-full font-mono text-sm" x-model="form.prover.inner_diameter" placeholder="e.g. 406.4000" />
                                            </div>

                                            <div>
                                                <x-input-label for="f_p_wt" :value="__('Wall Thickness (WT) mm')" />
                                                <x-text-input id="f_p_wt" name="prover_spec[wall_thickness]" type="number" step="0.0001" class="mt-1 block w-full font-mono text-sm" x-model="form.prover.wall_thickness" placeholder="e.g. 12.7000" />
                                            </div>

                                            <div>
                                                <x-input-label for="f_p_vol" :value="__('Nominal Base Volume (Previous BPV) Liters')" />
                                                <x-text-input id="f_p_vol" name="prover_spec[nominal_base_volume]" type="number" step="0.00001" class="mt-1 block w-full font-mono text-sm" x-model="form.prover.nominal_base_volume" placeholder="e.g. 5000.00000" />
                                            </div>

                                            <div>
                                                <x-input-label for="f_p_gc" :value="__('Thermal Expansion Coef Gc (1/°C)')" />
                                                <x-text-input id="f_p_gc" name="prover_spec[cubical_expansion_coef]" type="number" step="0.00000001" class="mt-1 block w-full font-mono text-sm" x-model="form.prover.cubical_expansion_coef" placeholder="0.00003300" />
                                            </div>

                                            <div>
                                                <x-input-label for="f_p_e" :value="__('Elasticity Modulus E (bar)')" />
                                                <x-text-input id="f_p_e" name="prover_spec[elasticity_modulus]" type="number" step="0.0001" class="mt-1 block w-full font-mono text-sm" x-model="form.prover.elasticity_modulus" placeholder="2068427.0000" />
                                            </div>

                                            <div x-show="form.prover.type === 'compact_svp'">
                                                <x-input-label for="f_p_ga" :value="__('Area Expansion Coef Ga (1/°C) [SVP]')" />
                                                <x-text-input id="f_p_ga" name="prover_spec[area_expansion_coef]" type="number" step="0.00000001" class="mt-1 block w-full font-mono text-sm" x-model="form.prover.area_expansion_coef" placeholder="0.00003400" />
                                            </div>

                                            <div x-show="form.prover.type === 'compact_svp'">
                                                <x-input-label for="f_p_gl" :value="__('Linear Expansion Coef Gl (1/°C) [SVP]')" />
                                                <x-text-input id="f_p_gl" name="prover_spec[linear_expansion_coef]" type="number" step="0.00000001" class="mt-1 block w-full font-mono text-sm" x-model="form.prover.linear_expansion_coef" placeholder="0.00000120" />
                                            </div>

                                            <div>
                                                <x-input-label for="f_p_mat" :value="__('Prover Wall Material')" />
                                                <x-text-input id="f_p_mat" name="prover_spec[material]" type="text" class="mt-1 block w-full text-sm" x-model="form.prover.material" placeholder="Carbon Steel / Mild Steel" />
                                            </div>

                                            <div class="sm:col-span-3 pt-2">
                                                <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-gray-700 dark:text-gray-300">
                                                    <input type="checkbox" name="prover_spec[pulse_interpolation]" value="1" x-model="form.prover.pulse_interpolation" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                                    <span>{{ __('Pulse Interpolation Module (Double Chronometry API 4.6)') }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Sticky Modal Footer -->
                        <div class="flex items-center justify-end gap-3 border-t border-gray-100 dark:border-gray-700 px-6 py-4 shrink-0 bg-gray-50/50 dark:bg-gray-800/80">
                            <x-secondary-button type="button" @click="showFormModal = false">
                                {{ __('Cancel') }}
                            </x-secondary-button>
                            <x-primary-button type="submit">
                                <span x-text="mode === 'edit' ? @js(__('Save Changes')) : @js(__('Create Instrument'))"></span>
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </template>

        <!-- ══════════════════════════════════════════════════
             Delete Confirmation Modal
        ══════════════════════════════════════════════════ -->
        <x-crud-modal.delete
            show="showDeleteModal"
            action-url="deleteActionUrl"
            item-name="deleteName"
            :title="__('Delete Measuring Instrument')"
        />
    </div>
</x-app-layout>