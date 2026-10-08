<?php

declare(strict_types=1);

namespace App\Http\Controllers\Metrology;

use App\Enums\GrandeurType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Metrology\StoreCalibrationCertificateRequest;
use App\Http\Requests\Metrology\UnlockCalibrationCertificateRequest;
use App\Http\Requests\Metrology\UpdateCalibrationCertificateRequest;
use App\Models\CalibrationCertificate;
use App\Models\Equipment;
use App\Services\CalibrationCertificateService;
use App\Services\InterpolationService;
use App\Services\MetrologyCalculationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CalibrationCertificateController extends Controller
{
    public function __construct(
        protected CalibrationCertificateService $certificateService,
        protected MetrologyCalculationService $calculationService,
        protected InterpolationService $interpolationService
    ) {}

    /**
     * Display a listing of calibration certificates with metrics and filters.
     */
    public function index(Request $request): View
    {
        Gate::authorize('view calibration certificates');

        $certificates = $this->certificateService->getPaginatedCertificates($request, 15);
        $statistics = $this->certificateService->getStatistics();
        $equipments = Equipment::forCalibration()
            ->select(['id', 'full_name', 'short_name', 'internal_code'])
            ->orderByRaw('COALESCE(short_name, full_name) ASC')
            ->get();

        return view('metrology.certificates.index', compact('certificates', 'statistics', 'equipments'));
    }

    /**
     * Show the form for creating a new certificate.
     */
    public function create(): View
    {
        Gate::authorize('create calibration certificates');

        $equipments = Equipment::with(['specifications.grandeur'])
            ->forCalibration()
            ->orderByRaw('COALESCE(short_name, full_name) ASC')
            ->get();

        $equipmentsData = $this->formatEquipmentsData($equipments);

        return view('metrology.certificates.create', compact('equipments', 'equipmentsData'));
    }

    /**
     * Store a newly created certificate.
     */
    public function store(StoreCalibrationCertificateRequest $request): RedirectResponse
    {
        $certificate = $this->certificateService->storeCertificate(
            $request->validated(),
            $request->file('certificate_file'),
            $request->user()
        );

        return redirect()->route('metrology.calibration-certificates.show', $certificate)
            ->with('success', __('Calibration certificate successfully created.'));
    }

    /**
     * Display the specified calibration certificate details and metrology curves.
     */
    public function show(CalibrationCertificate $certificate, Request $request): View
    {
        Gate::authorize('view calibration certificates');

        $certificate->load([
            'equipment.specifications.grandeur',
            'calibrationPoints.equipmentSpecification.grandeur',
            'calibrationInterpolations',
            'creator',
            'approver',
            'locker',
            'previousCertificate',
        ]);

        $range = (float) $request->input('range', 0.0);
        $fluid = $request->input('fluid', 'gaz');

        // Multi-parameter specification detection
        $specifications = $this->interpolationService->getCertificateSpecifications($certificate);
        $activeSpecId = $request->filled('spec_id')
            ? (int) $request->input('spec_id')
            : ($specifications->first()?->id ?? null);

        $activeSpecification = $activeSpecId !== null
            ? ($specifications->firstWhere('id', $activeSpecId) ?? $specifications->first())
            : null;

        if ($activeSpecification) {
            $activeSpecId = $activeSpecification->id;
        }

        // Standard 5-point interpolation grid scoped to active specification
        $savedInterpolations = $certificate->calibrationInterpolations
            ->where('equipment_specification_id', $activeSpecId);

        $custom5Points = null;
        if ($savedInterpolations->count() === 5) {
            $custom5Points = $savedInterpolations->pluck('target_nominal')->map(fn ($v) => (float) $v)->values()->toArray();
        }

        $minBound = $request->filled('min_bound') ? (float) $request->input('min_bound') : null;
        $maxBound = $request->filled('max_bound') ? (float) $request->input('max_bound') : null;

        $fivePointGrid = $this->interpolationService->generateFivePointGrid(
            $certificate,
            $custom5Points,
            $minBound,
            $maxBound,
            $activeSpecId
        );

        // Historical comparison across all certificates of this equipment for the 5 points
        $target5Points = ! empty($fivePointGrid['points'])
            ? array_column($fivePointGrid['points'], 'target_x')
            : [];

        $comparisonData = ! empty($target5Points)
            ? $this->interpolationService->generateMultiCertificateComparison(
                $certificate,
                $target5Points,
                $activeSpecId,
                $fivePointGrid['unit_symbol'] ?? ''
            )
            : ['labels' => [], 'datasets' => [], 'has_data' => false];

        $mesureSpecs = $specifications->filter(
            fn ($s) => $s->grandeur?->type === GrandeurType::Measurement
        )->values();

        $sourceSpecs = $specifications->filter(
            fn ($s) => $s->grandeur?->type === GrandeurType::Source
        )->values();

        $measurementPoints = $certificate->calibrationPoints->filter(
            fn ($pt) => $pt->equipmentSpecification?->grandeur?->type === GrandeurType::Measurement
        )->values();

        $sourcePoints = $certificate->calibrationPoints->filter(
            fn ($pt) => $pt->equipmentSpecification?->grandeur?->type === GrandeurType::Source
        )->values();

        $otherPoints = $certificate->calibrationPoints->reject(
            fn ($pt) => in_array($pt->equipmentSpecification?->grandeur?->type, [GrandeurType::Measurement, GrandeurType::Source], true)
        )->values();

        return view('metrology.certificates.show', compact(
            'certificate',
            'range',
            'fluid',
            'specifications',
            'activeSpecification',
            'fivePointGrid',
            'comparisonData',
            'mesureSpecs',
            'sourceSpecs',
            'measurementPoints',
            'sourcePoints',
            'otherPoints'
        ));
    }

    /**
     * Update and save custom 5-point interpolation grid for the certificate.
     */
    public function updateInterpolationGrid(Request $request, CalibrationCertificate $certificate): RedirectResponse
    {
        Gate::authorize('view calibration certificates');

        $validated = $request->validate([
            'points' => ['required', 'array', 'size:5'],
            'points.*' => ['required', 'numeric'],
            'min_bound' => ['nullable', 'numeric'],
            'max_bound' => ['nullable', 'numeric'],
            'equipment_specification_id' => ['nullable', 'integer', 'exists:equipment_specifications,id'],
        ]);

        $points = array_map('floatval', $validated['points']);
        sort($points);

        for ($i = 0; $i < 4; $i++) {
            if ($points[$i] >= $points[$i + 1]) {
                return back()->with('error', __('Points must be strictly ascending with distinct values.'))
                    ->withInput();
            }
        }

        $specId = isset($validated['equipment_specification_id']) ? (int) $validated['equipment_specification_id'] : null;

        try {
            $this->interpolationService->saveFivePointGrid(
                $certificate,
                $points,
                isset($validated['min_bound']) ? (float) $validated['min_bound'] : null,
                isset($validated['max_bound']) ? (float) $validated['max_bound'] : null,
                $specId
            );

            return redirect()->route('metrology.calibration-certificates.show', [
                'certificate' => $certificate,
                'tab' => 'interpolation',
                'spec_id' => $specId,
            ])->with('success', __('Standard 5-point interpolation grid successfully calculated and saved.'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Show the form for editing the certificate.
     */
    public function edit(CalibrationCertificate $certificate): View|RedirectResponse
    {
        Gate::authorize('edit calibration certificates');

        if ($certificate->is_locked) {
            return redirect()->route('metrology.calibration-certificates.show', $certificate)
                ->with('error', __('Cannot modify locked certificate. Unlock with authorization first.'));
        }

        $certificate->load(['calibrationPoints.equipmentSpecification.grandeur']);
        $equipments = Equipment::with(['specifications.grandeur'])
            ->forCalibration()
            ->orderByRaw('COALESCE(short_name, full_name) ASC')
            ->get();

        if ($certificate->equipment && ! $equipments->contains('id', $certificate->equipment_id)) {
            $equipments->prepend($certificate->equipment);
        }

        $equipmentsData = $this->formatEquipmentsData($equipments);

        return view('metrology.certificates.edit', compact('certificate', 'equipments', 'equipmentsData'));
    }

    /**
     * Update the specified certificate.
     */
    public function update(
        UpdateCalibrationCertificateRequest $request,
        CalibrationCertificate $certificate
    ): RedirectResponse {
        $this->certificateService->updateCertificate(
            $certificate,
            $request->validated(),
            $request->file('certificate_file'),
            $request->user()
        );

        return redirect()->route('metrology.calibration-certificates.show', $certificate)
            ->with('success', __('Calibration certificate updated successfully.'));
    }

    /**
     * Remove the specified certificate.
     */
    public function destroy(Request $request, CalibrationCertificate $certificate): RedirectResponse
    {
        Gate::authorize('delete calibration certificates');

        $this->certificateService->deleteCertificate($certificate);

        return redirect()->route('metrology.calibration-certificates', $request->query())
            ->with('success', __('Calibration certificate deleted successfully.'));
    }

    /**
     * Approve and officially lock the certificate for operational usage.
     */
    public function approve(CalibrationCertificate $certificate, Request $request): RedirectResponse
    {
        Gate::authorize('edit calibration certificates');

        $this->certificateService->approveAndLock($certificate, $request->user());

        return redirect()->back()
            ->with('success', __('Certificate approved and locked for operational compliance.'));
    }

    /**
     * Unlock certificate for authorized review with mandatory reason.
     */
    public function unlock(
        UnlockCalibrationCertificateRequest $request,
        CalibrationCertificate $certificate
    ): RedirectResponse {
        $this->certificateService->unlock(
            $certificate,
            $request->user(),
            (string) $request->validated('reason')
        );

        return redirect()->back()
            ->with('success', __('Certificate successfully unlocked for revision.'));
    }

    /**
     * Download or stream the certificate PDF document.
     */
    public function download(CalibrationCertificate $certificate): StreamedResponse|BinaryFileResponse|RedirectResponse
    {
        Gate::authorize('view calibration certificates');

        if (blank($certificate->certificate_path) || ! Storage::disk('public')->exists($certificate->certificate_path)) {
            return redirect()->back()->with('error', __('Certificate PDF document not found on storage.'));
        }

        $rawReference = trim((string) $certificate->reference);
        $cleanReference = str_replace(['/', '\\'], '-', $rawReference);
        $cleanReference = preg_replace('/[^A-Za-z0-9_\-\. ]+/', '_', (string) $cleanReference);
        $cleanReference = trim((string) $cleanReference, ".-_ \t\n\r\0\x0B");

        $fileName = ($cleanReference !== '' ? $cleanReference : 'certificate-'.$certificate->id).'.pdf';

        return Storage::disk('public')->download($certificate->certificate_path, $fileName);
    }

    /**
     * Format equipments and their specifications for Alpine.js reactive components.
     *
     * @param  Collection<int, Equipment>  $equipments
     * @return array<int, array<string, mixed>>
     */
    protected function formatEquipmentsData($equipments): array
    {
        return $equipments->mapWithKeys(fn (Equipment $eq) => [
            $eq->id => [
                'id' => $eq->id,
                'name' => $eq->short_name ?: $eq->full_name,
                'short_name' => $eq->short_name,
                'full_name' => $eq->full_name,
                'code' => $eq->internal_code,
                'serial_number' => $eq->serial_number,
                'designation' => $eq->designation,
                'specifications' => $eq->specifications->map(function ($spec) {
                    $discipline = $spec->discipline();

                    return [
                        'id' => $spec->id,
                        'name' => $spec->grandeur?->name ?? __('Standard Parameter'),
                        'symbol' => $spec->grandeur?->symbol ?? '',
                        'type' => $spec->grandeur?->type?->value ?? 'measurement',
                        'type_label' => $spec->grandeur?->type?->label() ?? __('Measurement / In'),
                        'type_badge' => $spec->grandeur?->type?->badgeVariant() ?? 'success',
                        'discipline' => $discipline->value,
                        'discipline_label' => $discipline->label(),
                        'icon' => $discipline->icon(),
                        'color_key' => $discipline->colorKey(),
                        'color_class' => $discipline->textClass(),
                        'badge_class' => $discipline->badgeClass(),
                        'icon_container' => $discipline->iconContainerClass(),
                        'range_min' => $spec->range_min,
                        'range_max' => $spec->range_max,
                        'accuracy_value' => $spec->accuracy_value,
                        'accuracy_type' => $spec->accuracy_type?->value ?? '%',
                    ];
                })->values()->all(),
            ],
        ])->all();
    }
}
