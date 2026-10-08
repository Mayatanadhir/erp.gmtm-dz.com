<?php

declare(strict_types=1);

namespace App\Http\Controllers\Metrology;

use App\Http\Controllers\Controller;
use App\Http\Requests\Calibration\StoreChromatographVerificationRequest;
use App\Models\ChromatographCompositionPoint;
use App\Models\ChromatographPhysicalProperty;
use App\Models\ChromatographVerification;
use App\Models\Equipment;
use App\Models\Instrument;
use App\Models\Mission;
use App\Models\Report;
use App\Models\Site;
use App\Services\CalibratorResolutionService;
use App\Services\ChromatographMetrologyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChromatographVerificationController extends Controller
{
    public function __construct(
        protected ChromatographMetrologyService $chromatoService,
        protected CalibratorResolutionService $calibratorService
    ) {}

    /**
     * قائمة جلسات فحص أجهزة الكروماتوغرافيا الغازية (CPG).
     */
    public function index(Request $request): View
    {
        if (Gate::has('view reports')) {
            Gate::authorize('view reports');
        }

        $query = ChromatographVerification::with([
            'instrument.site.customer',
            'report.mission.site.customer',
        ])->latest('verification_date');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('standard_gas_bottle_number', 'like', "%{$search}%")
                    ->orWhere('gas_bottle_number', 'like', "%{$search}%")
                    ->orWhere('certificate_number', 'like', "%{$search}%")
                    ->orWhereHas('instrument', function ($iq) use ($search) {
                        $iq->where('tag_number', 'like', "%{$search}%")
                            ->orWhere('serial_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('instrument.site', function ($sq) use ($search) {
                        $sq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('short_name', 'like', "%{$search}%")
                            ->orWhere('site_code', 'like', "%{$search}%")
                            ->orWhere('location', 'like', "%{$search}%");
                    })
                    ->orWhereHas('report', function ($rq) use ($search) {
                        $rq->where('report_number', 'like', "%{$search}%")
                            ->orWhereHas('mission', function ($mq) use ($search) {
                                $mq->where('reference', 'like', "%{$search}%");
                            });
                    });

                if (preg_match('/VERIF-GC-0*(\d+)/i', $search, $matches)) {
                    $q->orWhere('id', (int) $matches[1]);
                } elseif (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        if ($request->filled('mission_id')) {
            $missionId = $request->input('mission_id');
            if ($missionId === 'standalone') {
                $query->whereNull('report_mission_id');
            } else {
                $query->whereHas('report', function ($rq) use ($missionId) {
                    $rq->where('mission_id', $missionId);
                });
            }
        }

        if ($request->filled('status')) {
            if ($request->status === 'conforme') {
                $query->where('overall_status', true);
            } elseif ($request->status === 'non_conforme') {
                $query->where('overall_status', false);
            }
        }

        if ($request->filled('site_id')) {
            $siteId = $request->input('site_id');
            $query->where(function ($q) use ($siteId) {
                $q->whereHas('instrument', fn ($iq) => $iq->where('site_id', $siteId))
                    ->orWhereHas('report.mission', fn ($mq) => $mq->where('site_id', $siteId));
            });
        }

        $totalCount = ChromatographVerification::count();
        $conformeCount = ChromatographVerification::where('overall_status', true)->count();
        $nonConformeCount = ChromatographVerification::where('overall_status', false)->count();

        $sites = Site::orderBy('short_name')->get();
        $missions = Mission::with('site')->orderBy('created_at', 'desc')->get();
        $verifications = $query->paginate(15)->withQueryString();

        return view('metrology.reports.report-chromatograph.index', compact(
            'verifications',
            'totalCount',
            'conformeCount',
            'nonConformeCount',
            'sites',
            'missions'
        ));
    }

    /**
     * واجهة بدء جلسة فحص كروماتوغراف جديدة.
     */
    public function create(Request $request): View
    {
        if (Gate::has('create reports')) {
            Gate::authorize('create reports');
        }

        $sites = Site::orderBy('short_name')->get();
        $instruments = Instrument::whereIn('instrument_type', ['chromatograph', 'Chromatograph'])
            ->where('status', 'active')
            ->with('site')
            ->orderBy('tag_number')
            ->get();

        $selectedInstrumentId = $request->input('instrument_id');
        $selectedInstrument = $selectedInstrumentId
            ? Instrument::with('site')->find($selectedInstrumentId)
            : null;

        $reportId = $request->input('report_id');
        $report = $reportId ? Report::find($reportId) : null;

        $availableCalibrators = $report
            ? $this->calibratorService->getCertifiedMissionEquipments($report)
            : Equipment::all();

        $defaultComponents = $this->chromatoService->getDefaultComponents();
        $defaultPhysicalProperties = $this->chromatoService->getDefaultPhysicalProperties();

        $verification = null;
        $missions = Mission::with('site')->orderBy('created_at', 'desc')->get();
        $suggestedReference = ChromatographVerification::generateReferenceNumber();
        $suggestedNumber = Report::generateReportNumber('chromatograph');

        return view('metrology.reports.report-chromatograph.create', compact(
            'sites',
            'instruments',
            'selectedInstrument',
            'report',
            'verification',
            'availableCalibrators',
            'defaultComponents',
            'defaultPhysicalProperties',
            'missions',
            'suggestedReference',
            'suggestedNumber'
        ));
    }

    /**
     * واجهة إدخال البيانات والتحليل المترولوجي (Saisie).
     */
    public function saisie(ChromatographVerification $chromatographVerification): View
    {
        if (Gate::has('edit reports')) {
            Gate::authorize('edit reports');
        }

        $chromatographVerification->load([
            'instrument.site',
            'report.mission.site',
            'compositionPoints',
            'physicalProperties',
            'calibrators',
        ]);

        $instrument = $chromatographVerification->instrument;
        $report = $chromatographVerification->report;
        $verification = $chromatographVerification;
        $suggestedReference = $chromatographVerification->reference_number;

        $availableCalibrators = $report
            ? $this->calibratorService->getCertifiedMissionEquipments($report)
            : Equipment::all();

        $defaultComponents = $this->chromatoService->getDefaultComponents();
        $defaultPhysicalProperties = $this->chromatoService->getDefaultPhysicalProperties();
        $missions = Mission::with('site')->orderBy('created_at', 'desc')->get();

        $chromatographVerification = $verification;

        return view('metrology.reports.report-chromatograph.saisie', compact(
            'instrument',
            'report',
            'verification',
            'chromatographVerification',
            'availableCalibrators',
            'defaultComponents',
            'defaultPhysicalProperties',
            'missions',
            'suggestedReference'
        ));
    }

    /**
     * حفظ بيانات الفحص والتحقق المترولوجي.
     */
    public function store(StoreChromatographVerificationRequest $request): RedirectResponse
    {
        if (Gate::has('create reports')) {
            Gate::authorize('create reports');
        }

        $validated = $request->validated();

        $instrumentId = $request->input('instrument_id');
        $reportMissionId = $request->input('report_mission_id') ?: null;
        $verificationId = $request->input('verification_id');

        if (! $instrumentId) {
            return back()->withInput()->with('error', __('Please select a chromatograph instrument.'));
        }

        $instrument = Instrument::findOrFail($instrumentId);

        // ربط الفحص بتقرير المهمة إن تم تحديد مهمة (اختياري)
        $report = null;
        if ($request->has('mission_id')) {
            if ($request->filled('mission_id')) {
                $mission = Mission::find($request->input('mission_id'));
                if ($mission) {
                    $report = Report::firstOrCreate(
                        ['mission_id' => $mission->id],
                        [
                            'report_number' => Report::generateReportNumber('chromatograph'),
                            'status' => 'progress',
                        ]
                    );
                }
            } else {
                $report = null;
            }
        } elseif ($reportMissionId) {
            $report = Report::find($reportMissionId);
        }

        try {
            $verification = DB::transaction(function () use ($validated, $instrument, $report, $verificationId) {
                $referenceNumber = ! empty($validated['reference_number'])
                    ? trim($validated['reference_number'])
                    : null;

                if (empty($referenceNumber)) {
                    if ($verificationId) {
                        $existing = ChromatographVerification::find($verificationId);
                        $referenceNumber = $existing?->reference_number ?? ChromatographVerification::generateReferenceNumber();
                    } else {
                        $referenceNumber = ChromatographVerification::generateReferenceNumber();
                    }
                }

                $attributes = [
                    'reference_number' => $referenceNumber,
                    'report_mission_id' => $report?->id,
                    'verification_date' => $validated['verification_date'],
                    'ambient_temperature' => $validated['ambient_temperature'] ?? null,
                    'ambient_pressure' => $validated['ambient_pressure'] ?? null,
                    'standard_gas_bottle_number' => $validated['standard_gas_bottle_number'],
                    'gas_bottle_number' => $validated['standard_gas_bottle_number'],
                    'certificate_number' => $validated['certificate_number'],
                    'reference_conditions' => $validated['reference_conditions'],
                    'cylinder_validity_date' => $validated['cylinder_validity_date'] ?? null,
                    'gas_bottle_expiry' => $validated['cylinder_validity_date'] ?? null,
                    'cylinder_pressure_bar' => $validated['cylinder_pressure_bar'] ?? null,
                    'gas_bottle_pressure' => $validated['cylinder_pressure_bar'] ?? null,
                    'remarks' => $validated['remarks'] ?? null,
                ];

                if ($verificationId) {
                    $verification = ChromatographVerification::findOrFail($verificationId);
                    $verification->update($attributes);
                } else {
                    $verification = ChromatographVerification::create(
                        array_merge(['instrument_id' => $instrument->id], $attributes)
                    );
                }

                // حفظ المعايير المعتمدة
                if (! empty($validated['calibrator_ids'])) {
                    $verification->calibrators()->sync($validated['calibrator_ids']);
                } else {
                    $verification->calibrators()->detach();
                }

                // 1. حساب وتحقق الكسور المولية للغاز (11-12 مركباً قياسياً وفق ASTM D 1945 & ISO 6974-2)
                ChromatographCompositionPoint::where('verification_id', $verification->id)->delete();
                $allRepeatabilityConforme = true;
                $allCompositionErrorConforme = true;
                $compositionPoints = [];

                foreach ($validated['components'] as $comp) {
                    $runs = [
                        (float) $comp['run_1'],
                        (float) $comp['run_2'],
                        (float) $comp['run_3'],
                        (float) $comp['run_4'],
                        (float) $comp['run_5'],
                    ];

                    $eval = $this->chromatoService->evaluateComponent($runs, (float) $comp['reference_value']);

                    if (! $eval['repeatability_is_conforme']) {
                        $allRepeatabilityConforme = false;
                    }
                    if (! $eval['error_is_conforme']) {
                        $allCompositionErrorConforme = false;
                    }

                    $compositionPoints[] = [
                        'verification_id' => $verification->id,
                        'step_order' => (int) $comp['step_order'],
                        'component_name' => $comp['component_name'],
                        'component_symbol' => $comp['component_symbol'],
                        'reference_value' => (float) $comp['reference_value'],
                        'run_1' => $runs[0],
                        'run_2' => $runs[1],
                        'run_3' => $runs[2],
                        'run_4' => $runs[3],
                        'run_5' => $runs[4],
                        'mean_value' => $eval['mean_value'],
                        'repeatability' => $eval['repeatability'],
                        'repeatability_limit_astm' => $eval['repeatability_limit_astm'],
                        'repeatability_is_conforme' => $eval['repeatability_is_conforme'] ? 1 : 0,
                        'relative_error_percent' => $eval['relative_error_percent'],
                        'emt_limit_percent' => $eval['emt_limit_percent'],
                        'error_is_conforme' => $eval['error_is_conforme'] ? 1 : 0,
                        'is_conforme' => $eval['is_conforme'] ? 1 : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                ChromatographCompositionPoint::insert($compositionPoints);

                // 2. الخواص الفيزيائية والطاقوية (PCS, PCI, Rho, Z وفق OIML R 140 Classe A & ISO 6976)
                ChromatographPhysicalProperty::where('verification_id', $verification->id)->delete();
                $allPhysicalConforme = true;

                if (! empty($validated['physical_properties'])) {
                    $defaultProps = collect($this->chromatoService->getDefaultPhysicalProperties())->keyBy('symbol');
                    $physicalProps = [];

                    $physPropOrderMap = [
                        'PCS' => 1,
                        'PCI' => 2,
                        'Pb' => 3,
                        'rho' => 3,
                        'Zb' => 4,
                        'Z' => 4,
                    ];
                    $sortedInputProps = collect($validated['physical_properties'])->sortBy(function ($prop) use ($physPropOrderMap) {
                        return $physPropOrderMap[$prop['property_symbol'] ?? ''] ?? 99;
                    });

                    foreach ($sortedInputProps as $prop) {
                        $runs = [
                            (float) $prop['run_1'],
                            (float) $prop['run_2'],
                            (float) $prop['run_3'],
                            (float) $prop['run_4'],
                            (float) $prop['run_5'],
                        ];

                        $rawSymbol = $prop['property_symbol'];
                        $symbol = match ($rawSymbol) {
                            'rho' => 'Pb',
                            'Z' => 'Zb',
                            default => $rawSymbol,
                        };
                        $isPcs = strtoupper($symbol) === 'PCS';
                        $defaultMeta = $defaultProps->get($symbol) ?? $defaultProps->get($rawSymbol);
                        $emtLimit = $defaultMeta['emt_limit'] ?? 0.50;
                        $repLimit = $isPcs ? 0.10 : ($defaultMeta['repeatability_limit'] ?? null);

                        $evalProp = $this->chromatoService->evaluatePhysicalProperty(
                            $runs,
                            (float) $prop['reference_value'],
                            (float) $emtLimit,
                            $repLimit !== null ? (float) $repLimit : null
                        );

                        if (! $evalProp['is_conforme']) {
                            $allPhysicalConforme = false;
                        }

                        $physicalProps[] = [
                            'verification_id' => $verification->id,
                            'property_name' => $prop['property_name'],
                            'property_symbol' => $symbol,
                            'unit' => $prop['unit'],
                            'reference_value' => (float) $prop['reference_value'],
                            'run_1' => $runs[0],
                            'run_2' => $runs[1],
                            'run_3' => $runs[2],
                            'run_4' => $runs[3],
                            'run_5' => $runs[4],
                            'mean_value' => $evalProp['mean_value'],
                            'repeatability' => $evalProp['repeatability'],
                            'repeatability_limit' => $evalProp['repeatability_limit'],
                            'relative_error_percent' => $evalProp['relative_error_percent'],
                            'emt_limit_percent' => $evalProp['emt_limit_percent'],
                            'is_conforme' => $evalProp['is_conforme'] ? 1 : 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    ChromatographPhysicalProperty::insert($physicalProps);
                }

                $overallStatus = $allRepeatabilityConforme && $allCompositionErrorConforme && $allPhysicalConforme;

                $verification->update([
                    'repeatability_status' => $allRepeatabilityConforme,
                    'composition_accuracy_status' => $allCompositionErrorConforme,
                    'physical_properties_status' => $allPhysicalConforme,
                    'overall_status' => $overallStatus,
                ]);

                return $verification;
            });

            return redirect()
                ->route('metrology.reports.report-chromatograph.show', $verification->id)
                ->with('success', __('Chromatograph verification session recorded successfully.'));
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', __('Metrological calculation or save failed: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * عرض شهادة وتفاصيل فحص الكروماتوغراف (Inspection Details).
     */
    public function show(ChromatographVerification $chromatographVerification): View
    {
        if (Gate::has('view reports')) {
            Gate::authorize('view reports');
        }

        $chromatographVerification->load([
            'instrument.site',
            'report.mission.site',
            'compositionPoints',
            'physicalProperties',
            'calibrators.latestCertificate',
        ]);

        $verification = $chromatographVerification;

        return view('metrology.reports.report-chromatograph.show', compact('chromatographVerification', 'verification'));
    }

    /**
     * توليد تقرير التفتيش المترولوجي الرسمي بصيغة PDF (A4 Landscape).
     */
    public function generatePdf(ChromatographVerification $chromatographVerification): Response
    {
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $chromatographVerification->load([
            'instrument.site.customer',
            'report.mission.site.customer',
            'compositionPoints',
            'physicalProperties',
            'calibrators.latestCertificate',
        ]);

        $pdfView = view()->exists('metrology.reports.pdf.chromatograph_verification_report_detailed')
            ? 'metrology.reports.pdf.chromatograph_verification_report_detailed'
            : 'pdf.chromatograph_verification_report';

        $pdf = Pdf::loadView($pdfView, [
            'chromatographVerification' => $chromatographVerification,
            'verification' => $chromatographVerification,
        ]);
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOption([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'defaultFont' => 'sans-serif',
        ]);

        $tag = $chromatographVerification->instrument?->tag_number ?? 'CPG';
        $date = $chromatographVerification->verification_date?->format('Ymd') ?? now()->format('Ymd');
        $fileName = 'Chromatograph_Verification_'.$tag.'_'.$date.'.pdf';

        return $pdf->stream($fileName);
    }

    /**
     * توليد تقرير التفتيش المترولوجي بدون أعمدة EMT وقرار المطابقة (NotEMT) بصيغة PDF (A4 Landscape).
     */
    public function generatePdfNotEmt(ChromatographVerification $chromatographVerification): Response
    {
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $chromatographVerification->load([
            'instrument.site.customer',
            'report.mission.site.customer',
            'compositionPoints',
            'physicalProperties',
            'calibrators.latestCertificate',
        ]);

        $pdfView = view()->exists('metrology.reports.pdf.chromatograph_verification_report_summary')
            ? 'metrology.reports.pdf.chromatograph_verification_report_summary'
            : 'pdf.chromatograph_verification_report_NotEMT';

        $pdf = Pdf::loadView($pdfView, [
            'chromatographVerification' => $chromatographVerification,
            'verification' => $chromatographVerification,
        ]);
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOption([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'defaultFont' => 'sans-serif',
        ]);

        $tag = $chromatographVerification->instrument?->tag_number ?? 'CPG';
        $date = $chromatographVerification->verification_date?->format('Ymd') ?? now()->format('Ymd');
        $fileName = 'Chromatograph_Verification_NotEMT_'.$tag.'_'.$date.'.pdf';

        return $pdf->stream($fileName);
    }

    /**
     * تصدير وثيقة فحص الكروماتوغراف بصيغة Excel / CSV.
     */
    public function exportExcel(ChromatographVerification $chromatographVerification): StreamedResponse|BinaryFileResponse
    {
        $chromatographVerification->load([
            'instrument.site.customer',
            'report.mission.site.customer',
            'compositionPoints',
            'physicalProperties',
            'calibrators.latestCertificate',
        ]);

        $tag = $chromatographVerification->instrument?->tag_number ?? 'CPG';
        $date = $chromatographVerification->verification_date?->format('Ymd') ?? now()->format('Ymd');
        $cleanTag = str_replace(['\\', '/', '?', '*', ':', '[', ']'], '-', $tag);
        $fileName = 'Chromatograph_Verification_'.$cleanTag.'_'.$date.'.csv';

        return response()->streamDownload(function () use ($chromatographVerification) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['RAPPORT DE VERIFICATION METROLOGIQUE - CHROMATOGRAPHE EN PHASE GAZEUSE']);
            fputcsv($handle, ['Reference', $chromatographVerification->reference_number]);
            fputcsv($handle, ['Tag Instrument', $chromatographVerification->instrument?->tag_number ?? '-']);
            fputcsv($handle, ['N° Serie', $chromatographVerification->instrument?->serial_number ?? '-']);
            fputcsv($handle, ['Date', $chromatographVerification->verification_date?->format('d/m/Y') ?? '-']);
            fputcsv($handle, ['Bouteille Etalon', $chromatographVerification->standard_gas_bottle_number ?? '-']);
            fputcsv($handle, ['Certificat', $chromatographVerification->certificate_number ?? '-']);
            fputcsv($handle, ['Statut Global', $chromatographVerification->overall_status ? 'CONFORME' : 'NON CONFORME']);
            fputcsv($handle, []);

            // Composition
            fputcsv($handle, ['FRACTIONS MOLAIRES (% mol)']);
            fputcsv($handle, ['#', 'Composant', 'Valeur Ref', 'Run 1', 'Run 2', 'Run 3', 'Run 4', 'Run 5', 'Moyenne', 'Repetabilite', 'Limite ASTM', 'Erreur %', 'Limite EMT', 'Verdict']);
            foreach ($chromatographVerification->compositionPoints as $pt) {
                fputcsv($handle, [
                    $pt->step_order,
                    $pt->component_name,
                    $pt->reference_value,
                    $pt->run_1,
                    $pt->run_2,
                    $pt->run_3,
                    $pt->run_4,
                    $pt->run_5,
                    $pt->mean_value,
                    $pt->repeatability,
                    $pt->repeatability_limit_astm,
                    $pt->relative_error_percent,
                    $pt->emt_limit_percent,
                    $pt->is_conforme ? 'Conforme' : 'Non Conforme',
                ]);
            }

            fputcsv($handle, []);
            // Physical Properties
            fputcsv($handle, ['PROPRIETES PHYSIQUES ET ENERGETIQUES']);
            fputcsv($handle, ['Propriete', 'Symbole', 'Unite', 'Valeur Ref', 'Run 1', 'Run 2', 'Run 3', 'Run 4', 'Run 5', 'Moyenne', 'Erreur %', 'Limite EMT', 'Verdict']);
            foreach ($chromatographVerification->physicalProperties as $pr) {
                fputcsv($handle, [
                    $pr->property_name,
                    $pr->property_symbol,
                    $pr->unit,
                    $pr->reference_value,
                    $pr->run_1,
                    $pr->run_2,
                    $pr->run_3,
                    $pr->run_4,
                    $pr->run_5,
                    $pr->mean_value,
                    $pr->relative_error_percent,
                    $pr->emt_limit_percent,
                    $pr->is_conforme ? 'Conforme' : 'Non Conforme',
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * تنزيل نموذج فارغ لفحص الكروماتوغراف بصيغة CSV / Excel.
     */
    public function exportTemplate(Instrument $instrument): StreamedResponse
    {
        $instrument->load(['site.customer']);
        $tag = $instrument->tag_number ?? 'CPG';
        $cleanTag = str_replace(['\\', '/', '?', '*', ':', '[', ']'], '-', $tag);
        $fileName = 'Template_Chromatographe_'.$cleanTag.'.csv';

        return response()->streamDownload(function () use ($instrument) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['TEMPLATE DE VERIFICATION METROLOGIQUE - CHROMATOGRAPHE EN PHASE GAZEUSE']);
            fputcsv($handle, ['Tag Instrument', $instrument->tag_number ?? '-']);
            fputcsv($handle, ['N° Serie', $instrument->serial_number ?? '-']);
            fputcsv($handle, ['Site', $instrument->site?->full_name ?? ($instrument->site?->short_name ?? '-')]);
            fputcsv($handle, []);

            fputcsv($handle, ['FRACTIONS MOLAIRES (% mol)']);
            fputcsv($handle, ['#', 'Composant', 'Valeur Ref', 'Run 1', 'Run 2', 'Run 3', 'Run 4', 'Run 5']);
            $defaultComponents = $this->chromatoService->getDefaultComponents();
            $step = 1;
            foreach ($defaultComponents as $comp) {
                fputcsv($handle, [$step++, $comp['name'], $comp['reference_value'] ?? '', '', '', '', '', '']);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['PROPRIETES PHYSIQUES ET ENERGETIQUES']);
            fputcsv($handle, ['Propriete', 'Symbole', 'Unite', 'Valeur Ref', 'Run 1', 'Run 2', 'Run 3', 'Run 4', 'Run 5']);
            $defaultProps = $this->chromatoService->getDefaultPhysicalProperties();
            foreach ($defaultProps as $pr) {
                fputcsv($handle, [$pr['name'], $pr['symbol'], $pr['unit'], $pr['reference_value'] ?? '', '', '', '', '', '']);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * حذف جلسة فحص الكروماتوغراف.
     */
    public function destroy(ChromatographVerification $chromatographVerification): RedirectResponse
    {
        if (Gate::has('delete reports')) {
            Gate::authorize('delete reports');
        }

        $chromatographVerification->delete();

        return redirect()
            ->route('metrology.reports.report-chromatograph.index')
            ->with('success', __('Chromatograph verification session deleted successfully.'));
    }
}
