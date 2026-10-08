<?php

declare(strict_types=1);

namespace App\Http\Controllers\Metrology;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Instrument;
use App\Models\Mission;
use App\Models\Report;
use App\Models\Site;
use App\Services\CalibratorResolutionService;
use App\Services\OamMetrologyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class ReportController extends Controller
{
    public function __construct(
        protected CalibratorResolutionService $calibratorService,
        protected OamMetrologyService $oamService
    ) {}

    /**
     * Display a listing of reports with filters.
     */
    public function index(Request $request): View
    {
        Gate::authorize('view reports');

        $filters = $request->only(['search', 'mission_id', 'date_from', 'date_to', 'status', 'category']);
        $perPageInput = $request->input('per_page', 10);
        $perPage = in_array((int) $perPageInput, [10, 25, 50], true) ? (int) $perPageInput : 10;

        $reports = Report::with('mission.site.instruments')
            ->filter($filters)
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $missions = Mission::with('site')->orderByDesc('id')->get();
        $totalCount = Report::count();

        // Pass counts for the Metrology Reports Dashboard
        $instrumentsCount = Instrument::count();
        $chromatographsCount = Instrument::where('instrument_type', 'like', '%chromatograph%')
            ->orWhere('technology', 'like', '%chromatograph%')
            ->orWhere('tag_number', 'like', '%GC%')
            ->count();
        $proversCount = Equipment::where('category', 'like', '%prover%')
            ->orWhere('designation', 'like', '%prover%')
            ->orWhere('designation', 'like', '%jauge%')
            ->count();
        $equipmentCount = Equipment::count();
        $certificatesCount = DB::table('calibration_certificates')->count();

        // Categorized reports counts
        $chromatographsReportsCount = Report::where(function ($q) {
            $q->where('report_number', 'like', 'RPT-CPG%')
                ->orWhere('report_number', 'like', 'RPT-GC%');
        })->count();

        $proversReportsCount = Report::where(function ($q) {
            $q->where('report_number', 'like', 'RPT-PRV%')
                ->orWhere('report_number', 'like', 'RPT-PROV%');
        })->count();

        $instrumentsReportsCount = max(0, $totalCount - $chromatographsReportsCount - $proversReportsCount);
        $selectedCategory = $filters['category'] ?? 'all';

        return view('metrology.reports.index', compact(
            'reports',
            'missions',
            'totalCount',
            'filters',
            'perPage',
            'instrumentsCount',
            'chromatographsCount',
            'proversCount',
            'equipmentCount',
            'certificatesCount',
            'instrumentsReportsCount',
            'chromatographsReportsCount',
            'proversReportsCount',
            'selectedCategory'
        ));
    }

    /**
     * Show the form for creating a new report.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create reports');

        $category = $request->query('category', 'instruments');
        if (! in_array($category, ['instruments', 'prover', 'chromatograph', 'transmitter'], true)) {
            $category = 'instruments';
        }
        if ($category === 'transmitter') {
            $category = 'instruments';
        }

        $missions = Mission::with(['site.instruments' => function ($q) use ($category) {
            $q->where('status', 'active');
            if ($category === 'prover') {
                $q->whereIn('instrument_type', ['prover', 'standard_gauge', 'Prover', 'StandardGauge']);
            } elseif ($category === 'chromatograph') {
                $q->whereIn('instrument_type', ['chromatograph', 'Chromatograph']);
            } else {
                $q->whereIn('instrument_type', ['transmitter', 'probe', 'flow_computer', 'Transmitter', 'Probe', 'FlowComputer']);
            }
        }])->orderByDesc('id')->get();

        $suggestedNumber = Report::generateReportNumber($category);
        $selectedMissionId = old('mission_id');
        $selectedMission = $selectedMissionId ? $missions->firstWhere('id', (int) $selectedMissionId) : null;
        $calibratorsData = $this->getReportAvailableCalibrators(null, $selectedMission);

        $viewMap = [
            'prover' => 'metrology.reports.report-Prover.create',
            'chromatograph' => 'metrology.reports.report-chromatograph.create',
            'instruments' => 'metrology.reports.report-instruments.create',
        ];

        $viewName = $viewMap[$category] ?? 'metrology.reports.report-instruments.create';

        return view($viewName, compact('missions', 'suggestedNumber', 'calibratorsData', 'category'));
    }

    /**
     * Store a newly created report.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create reports');

        $validated = $request->validate([
            'mission_id' => ['nullable', 'integer', 'exists:missions,id'],
            'report_number' => ['required', 'string', 'max:255', 'unique:reports,report_number'],
            'status' => ['required', 'in:progress,completed'],
            'instrument_ids' => ['nullable', 'array'],
            'instrument_ids.*' => ['integer'],
            'default_calibrators' => ['nullable', 'array'],
            'default_calibrators.pressure_calibrator_1' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.pressure_calibrator_2' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.dp_calibrator_1' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.dp_calibrator_2' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.temperature_calibrator_1' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.temperature_calibrator_2' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.probe_calibrator_1' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.probe_calibrator_2' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.flow_computer_calibrator_1' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.flow_computer_pressure_calibrator' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.flow_computer_dp_calibrator' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.flow_computer_temp_calibrator' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.flow_computer_calibrator_2' => ['nullable', 'integer', 'exists:equipment,id'],
        ]);

        if (! empty($validated['mission_id'])) {
            $mission = Mission::with(['site.instruments' => function ($q) {
                $q->where('status', 'active');
            }])->find($validated['mission_id']);

            $siteInstIds = $mission?->site?->instruments->reject(function ($inst) {
                $t = $inst->instrument_type instanceof \BackedEnum ? $inst->instrument_type->value : (string) $inst->instrument_type;

                return in_array($t, Report::NON_REPORTABLE_TYPES, true);
            })->pluck('id')->toArray() ?? [];

            $includedIds = array_map('intval', $request->input('instrument_ids', []));
            $excludedIds = array_values(array_diff($siteInstIds, $includedIds));
            $validated['excluded_instrument_ids'] = $excludedIds;
        }

        $report = Report::create($validated);
        if (! empty($validated['default_calibrators'])) {
            $report->syncDefaultCalibratorsToAllInstruments();
        }

        return redirect()->route('metrology.reports.show', $report->id)
            ->with('success', __('Report created successfully.'));
    }

    /**
     * Display the specified report with instrument calibration dashboard.
     */
    public function show(Report $report): View
    {
        Gate::authorize('view reports');

        $report->load([
            'mission.site.instruments.specifications.grandeur',
            'mission.site.instruments.standardGaugeSpecification',
        ]);

        $appareilsData = [];
        $stats = [
            'total' => 0,
            'termines' => 0,
            'en_cours' => 0,
            'non_conformes' => 0,
        ];

        $allInstruments = $report->mission?->site?->instruments ?? collect();
        $excludedIds = $report->excluded_instrument_ids ?? [];

        $instruments = $allInstruments->reject(function ($inst) use ($excludedIds, $report) {
            $typeVal = $inst->instrument_type instanceof \BackedEnum ? $inst->instrument_type->value : (string) $inst->instrument_type;
            if (in_array($inst->id, $excludedIds, true)) {
                return true;
            }
            if (in_array($typeVal, Report::NON_REPORTABLE_TYPES, true) && ! $this->hasVerificationData((int) $report->id, $inst)) {
                return true;
            }

            return false;
        })->values();

        foreach ($instruments as $index => $instrument) {
            $spec = $instrument->specifications->first();

            $plage = '-';
            if ($spec && $spec->range_min !== null && $spec->range_max !== null) {
                $unit = $spec->grandeur ? $spec->grandeur->symbol : '';
                $plage = (float) $spec->range_min.' - '.(float) $spec->range_max.' '.$unit;
            }

            $statusData = $this->calculateInstrumentStatus((int) $report->id, $instrument);

            $typeLabel = $instrument->instrument_type instanceof \BackedEnum
                ? $instrument->instrument_type->label()
                : (string) $instrument->instrument_type;

            $appareilsData[] = [
                'id' => $instrument->id,
                'sequence' => $index + 1,
                'tag' => $instrument->tag_number,
                'serial' => $instrument->serial_number,
                'type' => $typeLabel,
                'image_path' => $instrument->image_path,
                'image_url' => $instrument->image_url,
                'plage' => $plage,
                'status_label' => $statusData['label'],
                'status_color' => $statusData['color'],
            ];

            $stats['total']++;
            if ($statusData['label'] === 'Conforme') {
                $stats['termines']++;
            } elseif ($statusData['label'] === 'Non conforme') {
                $stats['termines']++;
                $stats['non_conformes']++;
            } elseif ($statusData['label'] === 'En cours' || $statusData['label'] === 'À faire') {
                $stats['en_cours']++;
            }
        }

        $progressPercentage = $stats['total'] > 0
            ? round(($stats['termines'] / $stats['total']) * 100)
            : 0;

        $calibratorsData = $this->getReportAvailableCalibrators($report);

        $category = $report->category ?? 'instruments';
        $viewMap = [
            'prover' => 'metrology.reports.report-Prover.show',
            'chromatograph' => 'metrology.reports.report-chromatograph.show',
            'instruments' => 'metrology.reports.report-instruments.show',
        ];

        $viewName = $viewMap[$category] ?? null;
        if (! $viewName || ! view()->exists($viewName)) {
            $viewName = view()->exists('metrology.reports.report-instruments.show')
                ? 'metrology.reports.report-instruments.show'
                : (view()->exists('metrology.reports.show') ? 'metrology.reports.show' : 'metrology.reports.report-instruments.show');
        }

        return view($viewName, compact('report', 'appareilsData', 'stats', 'progressPercentage', 'calibratorsData'));
    }

    /**
     * Show the form for editing the specified report.
     */
    public function edit(Report $report): View
    {
        Gate::authorize('edit reports');

        $missions = Mission::with('site')->orderByDesc('id')->get();
        $report->load(['mission.site.instruments']);
        $calibratorsData = $this->getReportAvailableCalibrators($report);

        $category = $report->category ?? 'instruments';
        $viewMap = [
            'prover' => 'metrology.reports.report-Prover.edit',
            'chromatograph' => 'metrology.reports.report-chromatograph.edit',
            'instruments' => 'metrology.reports.report-instruments.edit',
        ];

        $viewName = $viewMap[$category] ?? null;
        if (! $viewName || ! view()->exists($viewName)) {
            $viewName = view()->exists('metrology.reports.report-instruments.edit')
                ? 'metrology.reports.report-instruments.edit'
                : (view()->exists('metrology.reports.edit') ? 'metrology.reports.edit' : 'metrology.reports.report-instruments.edit');
        }

        return view($viewName, compact('report', 'missions', 'calibratorsData'));
    }

    /**
     * Update the specified report.
     */
    public function update(Request $request, Report $report): RedirectResponse
    {
        Gate::authorize('edit reports');

        $validated = $request->validate([
            'mission_id' => ['nullable', 'integer', 'exists:missions,id'],
            'report_number' => ['required', 'string', 'max:255', 'unique:reports,report_number,'.$report->id],
            'status' => ['required', 'in:progress,completed'],
            'instrument_ids' => ['nullable', 'array'],
            'instrument_ids.*' => ['integer'],
            'default_calibrators' => ['nullable', 'array'],
            'default_calibrators.pressure_calibrator_1' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.pressure_calibrator_2' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.dp_calibrator_1' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.dp_calibrator_2' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.temperature_calibrator_1' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.temperature_calibrator_2' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.probe_calibrator_1' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.probe_calibrator_2' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.flow_computer_calibrator_1' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.flow_computer_pressure_calibrator' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.flow_computer_dp_calibrator' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.flow_computer_temp_calibrator' => ['nullable', 'integer', 'exists:equipment,id'],
            'default_calibrators.flow_computer_calibrator_2' => ['nullable', 'integer', 'exists:equipment,id'],
            'sync_calibrators_to_all' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['mission_id'])) {
            $mission = Mission::with('site.instruments')->find($validated['mission_id']);
            $siteInstruments = $mission?->site?->instruments->reject(function ($inst) {
                $t = $inst->instrument_type instanceof \BackedEnum ? $inst->instrument_type->value : (string) $inst->instrument_type;

                return in_array($t, Report::NON_REPORTABLE_TYPES, true);
            }) ?? collect();

            $siteInstIds = $siteInstruments->pluck('id')->toArray();
            $includedIds = array_map('intval', $request->input('instrument_ids', []));
            $validated['excluded_instrument_ids'] = array_values(array_diff($siteInstIds, $includedIds));
        }

        $report->update($validated);

        if (! empty($validated['sync_calibrators_to_all'])) {
            $report->syncDefaultCalibratorsToAllInstruments();
        }

        return redirect()->route('metrology.reports.show', $report->id)
            ->with('success', __('Report updated successfully.'));
    }

    /**
     * Remove the specified report from storage.
     */
    public function destroy(Report $report): RedirectResponse
    {
        Gate::authorize('delete reports');

        $report->delete();

        return redirect()->route('metrology.reports.index')
            ->with('success', __('Report deleted successfully.'));
    }

    /**
     * Get JSON details of a mission for report form auto-completion.
     */
    public function getMissionDetails(Request $request, Mission $mission): JsonResponse
    {
        Gate::authorize('view reports');

        $category = $request->query('category');

        $mission->load([
            'site.instruments.specifications.grandeur',
            'site.instruments.standardGaugeSpecification',
        ]);

        $instruments = ($mission->site?->instruments ?? collect())
            ->filter(function ($inst) use ($category) {
                $t = strtolower($inst->instrument_type instanceof \BackedEnum ? $inst->instrument_type->value : (string) $inst->instrument_type);

                if (in_array($t, Report::NON_REPORTABLE_TYPES, true)) {
                    return false;
                }

                if ($category === 'prover') {
                    return in_array($t, ['prover', 'standard_gauge', 'standardgauge'], true);
                }

                if ($category === 'chromatograph') {
                    return $t === 'chromatograph';
                }

                if ($category === 'instruments' || $category === 'transmitter') {
                    return in_array($t, ['transmitter', 'probe', 'flow_computer', 'flowcomputer'], true);
                }

                return true;
            })
            ->values()
            ->map(function ($inst) {
                $specs = $inst->specifications->first();

                return [
                    'id' => $inst->id,
                    'tag_number' => $inst->tag_number,
                    'serial_number' => $inst->serial_number,
                    'instrument_type' => $inst->instrument_type instanceof \BackedEnum ? $inst->instrument_type->value : (string) $inst->instrument_type,
                    'range_min' => $specs?->range_min,
                    'range_max' => $specs?->range_max,
                    'unit' => $specs?->grandeur?->symbol ?? '',
                    'image_url' => $inst->image_url,
                ];
            });

        $calibratorsData = $this->getReportAvailableCalibrators(null, $mission);

        $calibratorOptions = collect($calibratorsData)
            ->except('all')
            ->map(fn (Collection $equipments): Collection => $this->formatCalibratorOptions($equipments));

        return response()->json([
            'site_name' => $mission->site?->name ?? '-',
            'instruments' => $instruments,
            'calibrators' => $calibratorOptions,
        ]);
    }

    /**
     * Generate official detailed PDF report.
     */
    public function generatePdf(Report $report): Response
    {
        Gate::authorize('view reports');

        $report->load([
            'mission.site',
            'transmitterVerifications.instrument.specifications.grandeur',
            'transmitterVerifications.calibrators.calibrationCertificates',
            'transmitterVerifications.points',
            'probeVerifications.instrument.specifications.grandeur',
            'probeVerifications.calibrators.calibrationCertificates',
            'probeVerifications.points',
            'flowComputerVerifications.instrument.specifications.grandeur',
            'flowComputerVerifications.simulatedTransmitter.specifications.grandeur',
            'flowComputerVerifications.calibrators.calibrationCertificates',
            'flowComputerVerifications.points',
        ]);

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('metrology.reports.pdf.instrument_verification_report_detailed', compact('report'))
                ->setPaper('a4', 'landscape')
                ->setOption(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);

            $filename = 'RAPPORT_ETALONNAGE_'.str_replace(['/', '\\', ' '], '_', $report->report_number).'.pdf';

            return $pdf->stream($filename);
        }

        return response()->view('metrology.reports.pdf.instrument_verification_report_detailed', compact('report'));
    }

    /**
     * Generate official summary PDF report.
     */
    public function generatePdfSummary(Report $report): Response
    {
        Gate::authorize('view reports');

        $report->load([
            'mission.site',
            'transmitterVerifications.instrument.specifications.grandeur',
            'transmitterVerifications.calibrators.calibrationCertificates',
            'transmitterVerifications.points',
            'probeVerifications.instrument.specifications.grandeur',
            'probeVerifications.calibrators.calibrationCertificates',
            'probeVerifications.points',
            'flowComputerVerifications.instrument.specifications.grandeur',
            'flowComputerVerifications.simulatedTransmitter.specifications.grandeur',
            'flowComputerVerifications.calibrators.calibrationCertificates',
            'flowComputerVerifications.points',
        ]);

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('metrology.reports.pdf.instrument_verification_report_summary', compact('report'))
                ->setPaper('a4', 'landscape')
                ->setOption(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);

            $filename = 'RAPPORT_SOMMAIRE_'.str_replace(['/', '\\', ' '], '_', $report->report_number).'.pdf';

            return $pdf->stream($filename);
        }

        return response()->view('metrology.reports.pdf.instrument_verification_report_summary', compact('report'));
    }

    /**
     * لوحة تحكم عيارات الحجم والأنابيب العيارية (Standard Provers Hub)
     */
    public function proverIndex(Request $request): View
    {
        Gate::authorize('view reports');

        $search = $request->input('search');
        $typeFilter = $request->input('type');
        $siteFilter = $request->input('site_id');
        $statusFilter = $request->input('status');

        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 15;
        }

        $query = Instrument::with([
            'site',
            'proverSpecification',
            'standardGaugeSpecification',
            'latestProverVerification',
        ])->whereIn('instrument_type', ['prover', 'standard_gauge', 'Prover', 'StandardGauge']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('tag_number', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhereHas('site', function ($sq) use ($search) {
                        $sq->where('short_name', 'like', "%{$search}%")
                            ->orWhere('site_code', 'like', "%{$search}%");
                    });
            });
        }

        if ($typeFilter) {
            $query->where('instrument_type', $typeFilter);
        }

        if ($siteFilter) {
            $query->where('site_id', $siteFilter);
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $allInstruments = (clone $query)->get();

        $totalProvers = $allInstruments->filter(fn ($i) => strtolower($i->instrument_type instanceof \BackedEnum ? $i->instrument_type->value : (string) $i->instrument_type) === 'prover')->count();
        $totalGauges = $allInstruments->filter(fn ($i) => strtolower($i->instrument_type instanceof \BackedEnum ? $i->instrument_type->value : (string) $i->instrument_type) === 'standard_gauge')->count();
        $nominalBaseVolume = $allInstruments->sum(fn ($i) => (float) ($i->proverSpecification?->nominal_base_volume ?? 0));

        $paginatedProvers = $query->orderBy('tag_number')->paginate($perPage)->withQueryString();

        $sites = Site::orderBy('full_name')->get(['id', 'full_name', 'short_name', 'site_code']);

        $viewName = view()->exists('metrology.reports.report-Prover.index')
            ? 'metrology.reports.report-Prover.index'
            : 'admin.reports.report-Prover.index';

        return view($viewName, [
            'provers' => $paginatedProvers,
            'sites' => $sites,
            'stats' => [
                'total_all' => $allInstruments->count(),
                'total_provers' => $totalProvers,
                'total_gauges' => $totalGauges,
                'nominal_base_volume' => $nominalBaseVolume,
                'repeatability_target' => '≤ 0.05%',
                'compliance_rate' => 100.0,
            ],
            'filters' => [
                'search' => $search,
                'type' => $typeFilter,
                'site_id' => $siteFilter,
                'status' => $statusFilter,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * لوحة تحكم أجهزة الكروماتوغرافيا الغازية (Gas Chromatographs Hub)
     */
    public function chromatographIndex(Request $request): View
    {
        Gate::authorize('view reports');

        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 15;
        }

        $query = Instrument::with(['site', 'specifications.grandeur'])
            ->whereIn('instrument_type', ['chromatograph', 'Chromatograph']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('tag_number', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhereHas('site', function ($sq) use ($search) {
                        $sq->where('short_name', 'like', "%{$search}%")
                            ->orWhere('site_code', 'like', "%{$search}%");
                    });
            });
        }

        $chromatographs = $query->orderBy('tag_number')->get();

        $viewName = view()->exists('metrology.reports.report-chromatograph.index')
            ? 'metrology.reports.report-chromatograph.index'
            : 'admin.reports.report-chromatograph.index';

        return view($viewName, [
            'chromatographs' => $chromatographs,
            'stats' => [
                'total_gc' => $chromatographs->count(),
                'norm' => 'OIML R 140 / ISO 6974',
                'repeatability_target' => '≤ 0.10%',
                'average_pcs' => '40.28 MJ/m³',
            ],
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }

    // ==========================================
    // Internal Helper Methods
    // ==========================================

    private function getReportAvailableCalibrators(?Report $report = null, ?Mission $selectedMission = null): array
    {
        $allEquips = collect();

        if ($report) {
            $allEquips = $this->calibratorService->getCertifiedMissionEquipments($report);
        } elseif ($selectedMission) {
            $dummyReport = new Report(['mission_id' => $selectedMission->id]);
            $dummyReport->setRelation('mission', $selectedMission);
            $allEquips = $this->calibratorService->getCertifiedMissionEquipments($dummyReport);
        }

        $transmitterTemperature = $this->calibratorService->filterByFamily($allEquips, 'transmitter_temperature');

        return [
            'pressure' => $this->calibratorService->filterByFamily($allEquips, 'pressure'),
            'dp_pressure' => $this->calibratorService->filterByFamily($allEquips, 'dp_pressure'),
            'electrical' => $this->calibratorService->filterByFamily($allEquips, 'electrical'),
            'temperature' => $transmitterTemperature,
            'transmitter_temperature' => $transmitterTemperature,
            'probe_thermal' => $this->calibratorService->filterByFamily($allEquips, 'probe_thermal'),
            'probe_resistance' => $this->calibratorService->filterByFamily($allEquips, 'probe_resistance'),
            'all' => $allEquips,
        ];
    }

    /**
     * Map calibrator equipment to lightweight dropdown options.
     *
     * @param  Collection<int, Equipment>  $equipments
     * @return Collection<int, array{id: int, label: string}>
     */
    private function formatCalibratorOptions(Collection $equipments): Collection
    {
        return $equipments->values()->map(fn (Equipment $equipment): array => [
            'id' => $equipment->id,
            'label' => ($equipment->full_name ?? $equipment->designation ?? $equipment->internal_code)
                .' ('.($equipment->serial_number ?? $equipment->internal_code).')',
        ]);
    }

    private function calculateInstrumentStatus(int $reportId, Instrument $instrument): array
    {
        $typeVal = strtolower($instrument->instrument_type instanceof \BackedEnum ? $instrument->instrument_type->value : (string) $instrument->instrument_type);

        if ($typeVal === 'flowcomputer' || $typeVal === 'flow_computer') {
            $linkedTransmitterIds = $instrument->linkedTransmitters()->pluck('instruments.id');

            $verifications = DB::table('flow_computer_verifications')
                ->where('report_mission_id', $reportId)
                ->where('instrument_id', $instrument->id)
                ->get();

            if ($verifications->isEmpty()) {
                return ['label' => 'À faire', 'color' => 'gray'];
            }

            if ($linkedTransmitterIds->isEmpty()) {
                $verification = $verifications->first();
                $points = DB::table('flow_computer_verification_points')
                    ->where('verification_id', $verification->id)
                    ->get();

                if ($points->isEmpty()) {
                    return ['label' => 'À faire', 'color' => 'gray'];
                }

                $hasEmpty = $points->contains(function ($p) {
                    return is_null($p->indicated_value) || trim((string) $p->indicated_value) === '' || is_null($p->measured_signal);
                });

                if ($points->count() < 10 || $hasEmpty) {
                    return ['label' => 'En cours', 'color' => 'orange'];
                }

                $allConforme = $points->every(fn ($point) => (int) $point->is_conforme === 1);

                return $allConforme ? ['label' => 'Conforme', 'color' => 'green'] : ['label' => 'Non conforme', 'color' => 'red'];
            }

            $completedCount = 0;
            $hasFailure = false;
            $hasAnyData = false;

            foreach ($linkedTransmitterIds as $tId) {
                $v = $verifications->firstWhere('simulated_transmitter_id', $tId);
                if (! $v && $linkedTransmitterIds->count() === 1) {
                    $v = $verifications->first();
                }
                if (! $v) {
                    continue;
                }

                $pts = DB::table('flow_computer_verification_points')
                    ->where('verification_id', $v->id)
                    ->get();

                $hasFilledPoint = $pts->contains(function ($p) {
                    return ! is_null($p->indicated_value) && trim((string) $p->indicated_value) !== '' && ! is_null($p->measured_signal);
                });

                if ($hasFilledPoint) {
                    $hasAnyData = true;
                }

                $hasEmptyPoint = $pts->contains(function ($p) {
                    return is_null($p->indicated_value) || trim((string) $p->indicated_value) === '' || is_null($p->measured_signal);
                });

                if ($pts->count() >= 10 && ! $hasEmptyPoint) {
                    $completedCount++;
                    if ($pts->contains(fn ($p) => (int) $p->is_conforme === 0)) {
                        $hasFailure = true;
                    }
                }
            }

            if ($completedCount === $linkedTransmitterIds->count() && $completedCount > 0) {
                return $hasFailure
                    ? ['label' => 'Non conforme', 'color' => 'red']
                    : ['label' => 'Conforme', 'color' => 'green'];
            }

            if ($completedCount > 0 || $hasAnyData) {
                return ['label' => 'En cours', 'color' => 'orange'];
            }

            return ['label' => 'À faire', 'color' => 'gray'];
        }

        $verificationTable = match ($typeVal) {
            'transmitter' => 'transmitter_verifications',
            'probe' => 'probe_verifications',
            default => null,
        };

        $pointsTable = match ($typeVal) {
            'transmitter' => 'transmitter_verification_points',
            'probe' => 'probe_verification_points',
            default => null,
        };

        if (! $verificationTable || ! $pointsTable) {
            return ['label' => 'À faire', 'color' => 'gray'];
        }

        $verification = DB::table($verificationTable)
            ->where('report_mission_id', $reportId)
            ->where('instrument_id', $instrument->id)
            ->first();

        if (! $verification) {
            return ['label' => 'À faire', 'color' => 'gray'];
        }

        $points = DB::table($pointsTable)
            ->where('verification_id', $verification->id)
            ->get();

        if ($points->isEmpty()) {
            return ['label' => 'À faire', 'color' => 'gray'];
        }

        $allEmpty = $points->every(function ($point) use ($typeVal) {
            if ($typeVal === 'probe') {
                return $point->measured_resistance === null && $point->indicated_temperature === null;
            }

            return $point->measured_signal === null && $point->indicated_value === null;
        });

        if ($allEmpty) {
            return ['label' => 'À faire', 'color' => 'gray'];
        }

        $expectedPoints = ($typeVal === 'probe') ? 5 : 10;
        if ($points->count() < $expectedPoints) {
            return ['label' => 'En cours', 'color' => 'orange'];
        }

        $hasEmptyPoint = $points->contains(function ($point) use ($typeVal) {
            if ($typeVal === 'probe') {
                return $point->reference_temperature === null
                    || $point->measured_resistance === null
                    || $point->indicated_temperature === null;
            }

            return $point->reference_value === null
                || ($point->measured_signal === null && $point->indicated_value === null);
        });

        if ($hasEmptyPoint) {
            return ['label' => 'En cours', 'color' => 'orange'];
        }

        $allConforme = $points->every(fn ($point) => (int) $point->is_conforme === 1);

        return $allConforme
            ? ['label' => 'Conforme', 'color' => 'green']
            : ['label' => 'Non conforme', 'color' => 'red'];
    }

    private function hasVerificationData(int $reportId, Instrument $instrument): bool
    {
        $typeVal = strtolower($instrument->instrument_type instanceof \BackedEnum ? $instrument->instrument_type->value : (string) $instrument->instrument_type);

        return match ($typeVal) {
            'transmitter' => (function () use ($reportId, $instrument) {
                $v = DB::table('transmitter_verifications')
                    ->where('report_mission_id', $reportId)
                    ->where('instrument_id', $instrument->id)
                    ->first();

                return $v && DB::table('transmitter_verification_points')
                    ->where('verification_id', $v->id)
                    ->where(function ($q) {
                        $q->whereNotNull('measured_signal')->orWhereNotNull('indicated_value');
                    })
                    ->exists();
            })(),
            'probe' => (function () use ($reportId, $instrument) {
                $v = DB::table('probe_verifications')
                    ->where('report_mission_id', $reportId)
                    ->where('instrument_id', $instrument->id)
                    ->first();

                return $v && DB::table('probe_verification_points')
                    ->where('verification_id', $v->id)
                    ->where(function ($q) {
                        $q->whereNotNull('measured_resistance')->orWhereNotNull('indicated_temperature');
                    })
                    ->exists();
            })(),
            'flowcomputer', 'flow_computer' => (function () use ($reportId, $instrument) {
                $verifIds = DB::table('flow_computer_verifications')
                    ->where('report_mission_id', $reportId)
                    ->where('instrument_id', $instrument->id)
                    ->pluck('id');

                return $verifIds->isNotEmpty() && DB::table('flow_computer_verification_points')
                    ->whereIn('verification_id', $verifIds)
                    ->whereNotNull('indicated_value')
                    ->where('indicated_value', '!=', '')
                    ->exists();
            })(),
            default => false,
        };
    }
}
