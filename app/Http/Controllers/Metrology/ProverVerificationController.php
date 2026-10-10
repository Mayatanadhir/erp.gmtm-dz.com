<?php

declare(strict_types=1);

namespace App\Http\Controllers\Metrology;

use App\Constants\MetrologyConstants;
use App\DTOs\Metrology\ProverTubeDTO;
use App\DTOs\Metrology\ProverVerificationDTO;
use App\DTOs\Metrology\StandardGaugeDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Calibration\StoreProverVerificationRequest;
use App\Models\Instrument;
use App\Models\ProverVerification;
use App\Models\ProverVerificationRun;
use App\Models\Site;
use App\Services\ProverMetrologyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class ProverVerificationController extends Controller
{
    public function __construct(
        protected ProverMetrologyService $proverService
    ) {}

    /**
     * Prover & Standard Gauges Hub Index (Sessions & Inventory).
     */
    public function index(Request $request): View
    {
        if (Gate::has('view reports')) {
            Gate::authorize('view reports');
        }

        $search = $request->input('search');
        $typeFilter = $request->input('type');
        $siteFilter = $request->input('site_id');
        $statusFilter = $request->input('status');
        $tab = $request->input('tab', 'sessions');

        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 15;
        }

        // 1. Verification Sessions Query
        $verifQuery = ProverVerification::with([
            'prover.site.customer',
            'prover.proverSpecification',
            'jauge.site.customer',
            'jauge.standardGaugeSpecification',
            'runs',
        ])->latest('calibration_date');

        if ($search) {
            $verifQuery->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('prover', function ($pq) use ($search) {
                        $pq->where('serial_number', 'like', "%{$search}%")
                            ->orWhere('tag_number', 'like', "%{$search}%")
                            ->orWhereHas('site', function ($sq) use ($search) {
                                $sq->where('full_name', 'like', "%{$search}%")
                                    ->orWhere('short_name', 'like', "%{$search}%")
                                    ->orWhere('site_code', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('jauge', function ($jq) use ($search) {
                        $jq->where('serial_number', 'like', "%{$search}%")
                            ->orWhere('tag_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($siteFilter) {
            $verifQuery->where(function ($q) use ($siteFilter) {
                $q->whereHas('prover', fn ($pq) => $pq->where('site_id', $siteFilter))
                    ->orWhereHas('jauge', fn ($jq) => $jq->where('site_id', $siteFilter));
            });
        }

        if ($statusFilter) {
            if ($statusFilter === 'conforme' || $statusFilter === 'compliant') {
                $verifQuery->where('is_conforme', true);
            } elseif ($statusFilter === 'non_conforme' || $statusFilter === 'non_compliant') {
                $verifQuery->where('is_conforme', false);
            }
        }

        $verifications = $verifQuery->paginate($perPage, ['*'], 'verif_page')->withQueryString();

        // 2. Physical Instruments Inventory Query (Strictly backward-compatible with ReportProverIndexTest)
        $instQuery = Instrument::with([
            'site',
            'proverSpecification',
            'standardGaugeSpecification',
            'latestProverVerification',
        ])->whereIn('instrument_type', ['prover', 'standard_gauge', 'Prover', 'StandardGauge']);

        if ($search) {
            $instQuery->where(function ($q) use ($search) {
                $q->where('tag_number', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhereHas('site', function ($sq) use ($search) {
                        $sq->where('short_name', 'like', "%{$search}%")
                            ->orWhere('site_code', 'like', "%{$search}%")
                            ->orWhere('full_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($typeFilter) {
            $instQuery->where('instrument_type', $typeFilter);
        }

        if ($siteFilter) {
            $instQuery->where('site_id', $siteFilter);
        }

        if ($statusFilter && in_array($statusFilter, ['active', 'inactive'], true)) {
            $instQuery->where('status', $statusFilter);
        }

        $allInstruments = (clone $instQuery)->get();
        $totalProvers = $allInstruments->filter(fn ($i) => strtolower($i->instrument_type instanceof \BackedEnum ? $i->instrument_type->value : (string) $i->instrument_type) === 'prover')->count();
        $totalGauges = $allInstruments->filter(fn ($i) => strtolower($i->instrument_type instanceof \BackedEnum ? $i->instrument_type->value : (string) $i->instrument_type) === 'standard_gauge')->count();
        $nominalBaseVolume = $allInstruments->sum(fn ($i) => (float) ($i->proverSpecification?->nominal_base_volume ?? 0));

        $provers = $instQuery->orderBy('tag_number')->paginate($perPage, ['*'], 'inst_page')->withQueryString();
        $sites = Site::orderBy('short_name')->get(['id', 'full_name', 'short_name', 'site_code']);

        $stats = [
            'total_provers' => $totalProvers,
            'total_gauges' => $totalGauges,
            'total_all' => $totalProvers + $totalGauges,
            'nominal_base_volume' => $nominalBaseVolume,
            'repeatability_target' => '≤ 0.020%',
            'total_verifications' => ProverVerification::count(),
            'compliant_verifications' => ProverVerification::where('is_conforme', true)->count(),
        ];

        $filters = [
            'search' => $search,
            'type' => $typeFilter,
            'site_id' => $siteFilter,
            'status' => $statusFilter,
            'tab' => $tab,
        ];

        return view('metrology.reports.report-prover.index', compact('verifications', 'provers', 'sites', 'stats', 'filters'));
    }

    /**
     * Create view for new prover verification session.
     */
    public function create(): View
    {
        if (Gate::has('create reports')) {
            Gate::authorize('create reports');
        }

        $sites = Site::orderBy('short_name')->get();

        $provers = Instrument::whereIn('instrument_type', ['prover', 'Prover'])
            ->where('status', 'active')
            ->with('proverSpecification')
            ->get()
            ->map(fn (Instrument $i) => [
                'id' => $i->id,
                'site_id' => $i->site_id,
                'name' => $i->tag_number ? "{$i->tag_number} ({$i->serial_number})" : $i->serial_number,
                'serial_number' => $i->serial_number,
                'tag_number' => $i->tag_number ?? '',
                'brand' => $i->technology ?? '',
                'type' => $i->proverSpecification?->type instanceof \BackedEnum
                    ? $i->proverSpecification->type->value
                    : ($i->proverSpecification?->type ?? 'bidirectional_pipe'),
                'type_label' => match ($i->proverSpecification?->type instanceof \BackedEnum ? $i->proverSpecification->type->value : ($i->proverSpecification?->type ?? 'bidirectional_pipe')) {
                    'bidirectional_pipe' => __('Bidirectional Pipe'),
                    'unidirectional_pipe' => __('Unidirectional Pipe'),
                    'compact_svp' => __('Compact SVP'),
                    default => __('Bidirectional Pipe'),
                },
                'inner_diameter' => (string) ($i->proverSpecification?->inner_diameter ?? ''),
                'wall_thickness' => (string) ($i->proverSpecification?->wall_thickness ?? ''),
                'cubical_expansion_coef' => (string) ($i->proverSpecification?->cubical_expansion_coef ?? MetrologyConstants::G_C_MILD_STEEL),
                'area_expansion' => (string) ($i->proverSpecification?->area_expansion_coef ?? MetrologyConstants::G_A_SVP_CYLINDER),
                'linear_expansion' => (string) ($i->proverSpecification?->linear_expansion_coef ?? MetrologyConstants::G_L_SVP_SHAFT),
                'elasticity_modulus' => (string) ($i->proverSpecification?->elasticity_modulus ?? MetrologyConstants::E_MODULUS_STEEL_BAR),
                'nominal_base_volume' => (string) ($i->proverSpecification?->nominal_base_volume ?? ''),
            ]);

        $gauges = Instrument::whereIn('instrument_type', ['standard_gauge', 'StandardGauge'])
            ->where('status', 'active')
            ->with('standardGaugeSpecification')
            ->get()
            ->map(fn (Instrument $i) => [
                'id' => $i->id,
                'site_id' => $i->site_id,
                'name' => $i->tag_number ? "{$i->tag_number} ({$i->serial_number})" : $i->serial_number,
                'serial_number' => $i->serial_number,
                'tag_number' => $i->tag_number ?? '',
                'nominal_volume' => (string) ($i->standardGaugeSpecification?->nominal_capacity_liters ?? '400'),
                'neck_scale_factor' => (string) ($i->standardGaugeSpecification?->neck_scale_sensitivity ?? '0.01000'),
                'thermal_expansion' => (string) ($i->standardGaugeSpecification?->cubical_expansion_coef_gcm ?? MetrologyConstants::G_CM_STAINLESS_STEEL),
                'reference_temperature' => (string) MetrologyConstants::REF_TEMPERATURE_C,
            ]);

        return view('metrology.reports.report-prover.create', compact('sites', 'provers', 'gauges'));
    }

    /**
     * Real-time calculation preview (Ajax Live Preview).
     */
    public function calculatePreview(StoreProverVerificationRequest $request): JsonResponse
    {
        try {
            $proverInstrument = $request->filled('prover_id')
                ? Instrument::with('proverSpecification')->find($request->input('prover_id'))
                : null;

            $jaugeInstrument = $request->filled('jauge_id')
                ? Instrument::with('standardGaugeSpecification')->find($request->input('jauge_id'))
                : null;

            $proverDto = $proverInstrument
                ? ProverTubeDTO::fromInstrument($proverInstrument)
                : ProverTubeDTO::fromArray($request->validated());

            $gaugeDto = $jaugeInstrument
                ? StandardGaugeDTO::fromInstrument($jaugeInstrument)
                : StandardGaugeDTO::fromArray($request->validated());

            $dto = ProverVerificationDTO::fromArrayWithDTOs(
                data: $request->validated(),
                proverTube: $proverDto,
                standardGauge: $gaugeDto,
            );

            $result = $this->proverService->calculateVerification($dto);

            return response()->json([
                'success' => true,
                'data' => [
                    'base_prover_volume' => (string) $result->baseProverVolume,
                    'max_run_volume' => (string) $result->maxRunVolume,
                    'min_run_volume' => (string) $result->minRunVolume,
                    'repeatability_percent' => (string) $result->repeatabilityPercent,
                    'is_conforme' => $result->isConforme,
                    'runs' => array_map(fn ($r) => [
                        'run_number' => $r->runNumber,
                        'fill_number' => $r->fillNumber,
                        'scale_reading_mm' => $r->scaleReadingMm ? (string) $r->scaleReadingMm : null,
                        'indicated_volume' => (string) $r->indicatedVolume,
                        'shaft_temperature' => $r->shaftTemperature ? (string) $r->shaftTemperature : null,
                        'c_tdw' => (string) $r->cTdw,
                        'c_tsm' => (string) $r->cTsm,
                        'c_tsp' => (string) $r->cTsp,
                        'c_psp' => (string) $r->cPsp,
                        'c_plp' => (string) $r->cPlp,
                        'corrected_volume' => (string) $r->correctedVolume,
                    ], $result->calculatedRuns),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Store prover verification session and runs atomically.
     */
    public function store(StoreProverVerificationRequest $request): RedirectResponse
    {
        try {
            $proverInstrument = $request->filled('prover_id')
                ? Instrument::with('proverSpecification')->find($request->input('prover_id'))
                : null;

            $jaugeInstrument = $request->filled('jauge_id')
                ? Instrument::with('standardGaugeSpecification')->find($request->input('jauge_id'))
                : null;

            $proverDto = $proverInstrument
                ? ProverTubeDTO::fromInstrument($proverInstrument)
                : ProverTubeDTO::fromArray($request->validated());

            $gaugeDto = $jaugeInstrument
                ? StandardGaugeDTO::fromInstrument($jaugeInstrument)
                : StandardGaugeDTO::fromArray($request->validated());

            $validated = $request->validated();

            $dto = ProverVerificationDTO::fromArrayWithDTOs(
                data: $validated,
                proverTube: $proverDto,
                standardGauge: $gaugeDto,
            );

            $result = $this->proverService->calculateVerification($dto);

            $verification = DB::transaction(function () use ($dto, $result, $request) {
                $verification = ProverVerification::create([
                    'prover_id' => $request->input('prover_id'),
                    'jauge_id' => $request->input('jauge_id'),
                    'reference_number' => $request->input('reference_number') ?: 'FE/WDP/'.($dto->proverTube->serialNumber),
                    'calibration_date' => $dto->calibrationDate,
                    'reference_temperature' => (float) (string) $dto->referenceTemperature,
                    'pressure_unit' => $dto->pressureUnit,
                    'remarks' => $dto->remarks,
                    'base_prover_volume' => (float) (string) $result->baseProverVolume,
                    'max_run_volume' => (float) (string) $result->maxRunVolume,
                    'min_run_volume' => (float) (string) $result->minRunVolume,
                    'repeatability_percent' => (float) (string) $result->repeatabilityPercent,
                    'is_conforme' => $result->isConforme,
                ]);

                foreach ($result->calculatedRuns as $run) {
                    ProverVerificationRun::create([
                        'prover_verification_id' => $verification->id,
                        'run_number' => $run->runNumber,
                        'fill_number' => $run->fillNumber,
                        'scale_reading_mm' => $run->scaleReadingMm !== null ? (float) (string) $run->scaleReadingMm : null,
                        'indicated_volume' => (float) (string) $run->indicatedVolume,
                        'gauge_temperature' => (float) (string) $run->gaugeTemperature,
                        'prover_temperature' => (float) (string) $run->proverTemperature,
                        'shaft_temperature' => $run->shaftTemperature ? (float) (string) $run->shaftTemperature : null,
                        'prover_pressure' => (float) (string) $run->proverPressure,
                        'c_tdw' => (float) (string) $run->cTdw,
                        'c_tsm' => (float) (string) $run->cTsm,
                        'c_tsp' => (float) (string) $run->cTsp,
                        'c_psp' => (float) (string) $run->cPsp,
                        'c_plp' => (float) (string) $run->cPlp,
                        'corrected_volume' => (float) (string) $run->correctedVolume,
                    ]);
                }

                return $verification;
            });

            return redirect()
                ->route('metrology.reports.report-prover.show', $verification->id)
                ->with('success', __('Prover verification session recorded successfully.'));
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', __('Metrological calculation or save failed: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Show verification session and detailed measurement runs.
     */
    public function show(ProverVerification $proverVerification): View
    {
        if (Gate::has('view reports')) {
            Gate::authorize('view reports');
        }

        $proverVerification->load([
            'prover.site.customer',
            'prover.proverSpecification',
            'jauge.site.customer',
            'jauge.standardGaugeSpecification',
            'runs',
        ]);

        return view('metrology.reports.report-prover.show', compact('proverVerification'));
    }

    /**
     * Show the edit form for a prover verification session.
     */
    public function edit(ProverVerification $proverVerification): View
    {
        if (Gate::has('create reports')) {
            Gate::authorize('create reports');
        }

        $proverVerification->load([
            'prover.proverSpecification',
            'jauge.standardGaugeSpecification',
            'runs' => fn ($q) => $q->orderBy('run_number')->orderBy('fill_number'),
        ]);

        $sites = Site::orderBy('short_name')->get(['id', 'full_name', 'short_name', 'site_code']);

        $provers = Instrument::whereIn('instrument_type', ['prover', 'Prover'])
            ->where('status', 'active')
            ->with('proverSpecification')
            ->get()
            ->map(fn (Instrument $i) => [
                'id' => $i->id,
                'site_id' => $i->site_id,
                'name' => $i->tag_number ? "{$i->tag_number} ({$i->serial_number})" : $i->serial_number,
                'serial_number' => $i->serial_number,
                'tag_number' => $i->tag_number ?? '',
                'brand' => $i->technology ?? '',
                'type' => $i->proverSpecification?->type instanceof \BackedEnum
                    ? $i->proverSpecification->type->value
                    : ($i->proverSpecification?->type ?? 'bidirectional_pipe'),
                'type_label' => match ($i->proverSpecification?->type instanceof \BackedEnum ? $i->proverSpecification->type->value : ($i->proverSpecification?->type ?? 'bidirectional_pipe')) {
                    'bidirectional_pipe' => __('Bidirectional Pipe'),
                    'unidirectional_pipe' => __('Unidirectional Pipe'),
                    'compact_svp' => __('Compact SVP'),
                    default => __('Bidirectional Pipe'),
                },
                'inner_diameter' => (string) ($i->proverSpecification?->inner_diameter ?? ''),
                'wall_thickness' => (string) ($i->proverSpecification?->wall_thickness ?? ''),
                'cubical_expansion_coef' => (string) ($i->proverSpecification?->cubical_expansion_coef ?? MetrologyConstants::G_C_MILD_STEEL),
                'area_expansion' => (string) ($i->proverSpecification?->area_expansion_coef ?? MetrologyConstants::G_A_SVP_CYLINDER),
                'linear_expansion' => (string) ($i->proverSpecification?->linear_expansion_coef ?? MetrologyConstants::G_L_SVP_SHAFT),
                'elasticity_modulus' => (string) ($i->proverSpecification?->elasticity_modulus ?? MetrologyConstants::E_MODULUS_STEEL_BAR),
                'nominal_base_volume' => (string) ($i->proverSpecification?->nominal_base_volume ?? ''),
            ]);

        $gauges = Instrument::whereIn('instrument_type', ['standard_gauge', 'StandardGauge'])
            ->where('status', 'active')
            ->with('standardGaugeSpecification')
            ->get()
            ->map(fn (Instrument $i) => [
                'id' => $i->id,
                'site_id' => $i->site_id,
                'name' => $i->tag_number ? "{$i->tag_number} ({$i->serial_number})" : $i->serial_number,
                'serial_number' => $i->serial_number,
                'tag_number' => $i->tag_number ?? '',
                'nominal_volume' => (string) ($i->standardGaugeSpecification?->nominal_capacity_liters ?? '400'),
                'neck_scale_factor' => (string) ($i->standardGaugeSpecification?->neck_scale_sensitivity ?? '0.01000'),
                'thermal_expansion' => (string) ($i->standardGaugeSpecification?->cubical_expansion_coef_gcm ?? MetrologyConstants::G_CM_STAINLESS_STEEL),
                'reference_temperature' => (string) ($i->standardGaugeSpecification?->base_reference_temperature ?? MetrologyConstants::REF_TEMPERATURE_C),
            ]);

        // Group runs by run_number
        $groupedRuns = [];
        foreach ($proverVerification->runs as $run) {
            $rNum = $run->run_number;
            if (! isset($groupedRuns[$rNum])) {
                $groupedRuns[$rNum] = [
                    'run_number' => $rNum,
                    'fills' => [],
                ];
            }
            $groupedRuns[$rNum]['fills'][] = [
                'fill_number' => $run->fill_number,
                'indicated_volume' => number_format((float) $run->indicated_volume, 4, '.', ''),
                'gauge_temperature' => (string) $run->gauge_temperature,
                'prover_temperature' => (string) $run->prover_temperature,
                'shaft_temperature' => $run->shaft_temperature !== null ? (string) $run->shaft_temperature : '',
                'prover_pressure' => (string) $run->prover_pressure,
                'corrected_volume' => (string) $run->corrected_volume,
            ];
        }
        $initialRuns = array_values($groupedRuns);

        return view('metrology.reports.report-prover.edit', compact('proverVerification', 'sites', 'provers', 'gauges', 'initialRuns'));
    }

    /**
     * Update prover verification session and runs atomically.
     */
    public function update(StoreProverVerificationRequest $request, ProverVerification $proverVerification): RedirectResponse
    {
        try {
            $proverInstrument = $request->filled('prover_id')
                ? Instrument::with('proverSpecification')->find($request->input('prover_id'))
                : null;

            $jaugeInstrument = $request->filled('jauge_id')
                ? Instrument::with('standardGaugeSpecification')->find($request->input('jauge_id'))
                : null;

            $proverDto = $proverInstrument
                ? ProverTubeDTO::fromInstrument($proverInstrument)
                : ProverTubeDTO::fromArray($request->validated());

            $gaugeDto = $jaugeInstrument
                ? StandardGaugeDTO::fromInstrument($jaugeInstrument)
                : StandardGaugeDTO::fromArray($request->validated());

            $dto = ProverVerificationDTO::fromArrayWithDTOs(
                data: $request->validated(),
                proverTube: $proverDto,
                standardGauge: $gaugeDto,
            );

            $result = $this->proverService->calculateVerification($dto);

            DB::transaction(function () use ($proverVerification, $dto, $result, $request) {
                $proverVerification->update([
                    'prover_id' => $request->input('prover_id'),
                    'jauge_id' => $request->input('jauge_id'),
                    'reference_number' => $request->input('reference_number') ?: 'FE/WDP/'.($dto->proverTube->serialNumber),
                    'calibration_date' => $dto->calibrationDate,
                    'reference_temperature' => (float) (string) $dto->referenceTemperature,
                    'pressure_unit' => $dto->pressureUnit,
                    'remarks' => $dto->remarks,
                    'base_prover_volume' => (float) (string) $result->baseProverVolume,
                    'max_run_volume' => (float) (string) $result->maxRunVolume,
                    'min_run_volume' => (float) (string) $result->minRunVolume,
                    'repeatability_percent' => (float) (string) $result->repeatabilityPercent,
                    'is_conforme' => $result->isConforme,
                ]);

                $proverVerification->runs()->delete();

                foreach ($result->calculatedRuns as $run) {
                    ProverVerificationRun::create([
                        'prover_verification_id' => $proverVerification->id,
                        'run_number' => $run->runNumber,
                        'fill_number' => $run->fillNumber,
                        'scale_reading_mm' => $run->scaleReadingMm !== null ? (float) (string) $run->scaleReadingMm : null,
                        'indicated_volume' => (float) (string) $run->indicatedVolume,
                        'gauge_temperature' => (float) (string) $run->gaugeTemperature,
                        'prover_temperature' => (float) (string) $run->proverTemperature,
                        'shaft_temperature' => $run->shaftTemperature ? (float) (string) $run->shaftTemperature : null,
                        'prover_pressure' => (float) (string) $run->proverPressure,
                        'c_tdw' => (float) (string) $run->cTdw,
                        'c_tsm' => (float) (string) $run->cTsm,
                        'c_tsp' => (float) (string) $run->cTsp,
                        'c_psp' => (float) (string) $run->cPsp,
                        'c_plp' => (float) (string) $run->cPlp,
                        'corrected_volume' => (float) (string) $run->correctedVolume,
                    ]);
                }
            });

            return redirect()
                ->route('metrology.reports.report-prover.show', $proverVerification->id)
                ->with('success', __('Prover verification session updated successfully.'));
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', __('Metrological calculation or save failed: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Delete verification session.
     */
    public function destroy(ProverVerification $proverVerification): RedirectResponse
    {
        if (Gate::has('delete reports')) {
            Gate::authorize('delete reports');
        }

        $proverVerification->delete();

        return redirect()
            ->route('metrology.reports.report-prover.index')
            ->with('success', __('Prover verification session deleted successfully.'));
    }

    /**
     * Generate official technical PDF report (A4 Landscape with EMT evaluation).
     */
    public function generatePdf(ProverVerification $proverVerification): Response
    {
        if (Gate::has('view reports')) {
            Gate::authorize('view reports');
        }

        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $proverVerification->load([
            'prover.site.customer',
            'prover.proverSpecification',
            'jauge.site.customer',
            'jauge.standardGaugeSpecification',
            'runs',
        ]);

        $pdf = Pdf::loadView('metrology.reports.pdf.prover_verification_report_detailed', compact('proverVerification'));
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOption([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'defaultFont' => 'sans-serif',
        ]);

        $proverSerial = $proverVerification->prover?->serial_number ?? 'UNKNOWN';
        $calibDate = $proverVerification->calibration_date ? $proverVerification->calibration_date->format('Ymd') : now()->format('Ymd');
        $fileName = 'Prover_Verification_Report_'.$proverSerial.'_'.$calibDate.'.pdf';

        return $pdf->stream($fileName);
    }

    /**
     * Generate technical PDF report without EMT compliance evaluation.
     */
    public function generatePdfNotEmt(ProverVerification $proverVerification): Response
    {
        if (Gate::has('view reports')) {
            Gate::authorize('view reports');
        }

        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $proverVerification->load([
            'prover.site.customer',
            'prover.proverSpecification',
            'jauge.site.customer',
            'jauge.standardGaugeSpecification',
            'runs',
        ]);

        $pdf = Pdf::loadView('metrology.reports.pdf.prover_verification_report_summary', compact('proverVerification'));
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOption([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'defaultFont' => 'sans-serif',
        ]);

        $proverSerial = $proverVerification->prover?->serial_number ?? 'UNKNOWN';
        $calibDate = $proverVerification->calibration_date ? $proverVerification->calibration_date->format('Ymd') : now()->format('Ymd');
        $fileName = 'Prover_Verification_NotEMT_'.$proverSerial.'_'.$calibDate.'.pdf';

        return $pdf->stream($fileName);
    }
}
