<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CalibrationCertificateStatus;
use App\Enums\CalibrationPointStatus;
use App\Models\CalibrationCertificate;
use App\Models\CalibrationPoint;
use App\Models\Equipment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CalibrationCertificateService
{
    public function __construct(
        protected MediaOptimizationService $mediaService,
        protected MetrologyCalculationService $calculationService
    ) {}

    /**
     * Retrieve paginated calibration certificates with dynamic filtering and sorting.
     */
    public function getPaginatedCertificates(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = CalibrationCertificate::with([
            'equipment',
            'creator',
            'approver',
            'calibrationPoints.equipmentSpecification.grandeur',
        ]);

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('laboratory_name', 'like', "%{$search}%")
                    ->orWhere('remarks', 'like', "%{$search}%")
                    ->orWhereHas('equipment', function ($eq) use ($search): void {
                        $eq->where('short_name', 'like', "%{$search}%")
                            ->orWhere('internal_code', 'like', "%{$search}%")
                            ->orWhere('full_name', 'like', "%{$search}%")
                            ->orWhere('serial_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('equipment_id')) {
            $query->where('equipment_id', $request->input('equipment_id'));
        }

        if ($request->filled('laboratory_name')) {
            $query->where('laboratory_name', 'like', '%'.$request->input('laboratory_name').'%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('is_locked')) {
            $query->where('is_locked', (bool) $request->input('is_locked'));
        }

        if ($request->filled('calibration_date_from')) {
            $query->whereDate('calibration_date', '>=', $request->input('calibration_date_from'));
        }

        if ($request->filled('calibration_date_to')) {
            $query->whereDate('calibration_date', '<=', $request->input('calibration_date_to'));
        }

        if ($request->filled('expiry_date_from')) {
            $query->whereDate('expiry_date', '>=', $request->input('expiry_date_from'));
        }

        if ($request->filled('expiry_date_to')) {
            $query->whereDate('expiry_date', '<=', $request->input('expiry_date_to'));
        }

        $sortField = $request->input('sort_by', 'calibration_date');
        $sortDirection = $request->input('sort_order', 'desc');

        $allowedSorts = ['id', 'reference', 'calibration_date', 'expiry_date', 'price', 'status', 'created_at'];
        if (! in_array($sortField, $allowedSorts, true)) {
            $sortField = 'calibration_date';
        }

        $query->orderBy($sortField, strtolower((string) $sortDirection) === 'asc' ? 'asc' : 'desc');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Compute statistics dashboard metrics for calibration certificates.
     *
     * @return array<string, int|float>
     */
    public function getStatistics(): array
    {
        $today = Carbon::today();
        $in30Days = Carbon::today()->addDays(30);

        return [
            'total' => CalibrationCertificate::count(),
            'valid' => CalibrationCertificate::valid()->count(),
            'expiring_soon' => CalibrationCertificate::whereDate('expiry_date', '>=', $today)
                ->whereDate('expiry_date', '<=', $in30Days)
                ->count(),
            'expired' => CalibrationCertificate::whereDate('expiry_date', '<', $today)->count(),
            'draft' => CalibrationCertificate::where('status', CalibrationCertificateStatus::Draft)->count(),
            'total_cost' => (float) CalibrationCertificate::sum('price'),
        ];
    }

    /**
     * Store a newly created calibration certificate with points and optional document attachment.
     */
    public function storeCertificate(array $data, ?UploadedFile $file = null, ?User $user = null): CalibrationCertificate
    {
        return DB::transaction(function () use ($data, $file, $user): CalibrationCertificate {
            $calibrationDate = ! empty($data['calibration_date'])
                ? Carbon::parse($data['calibration_date'])
                : Carbon::today();

            $validityMonths = (int) ($data['validity_period_months'] ?? 12);

            $expiryDate = ! empty($data['expiry_date'])
                ? Carbon::parse($data['expiry_date'])
                : $calibrationDate->copy()->addMonths($validityMonths);

            $certPath = null;
            $certHash = null;
            $fileName = null;
            $fileSize = null;
            $mimeType = null;

            if ($file instanceof UploadedFile) {
                $fileName = $file->getClientOriginalName();
                $fileSize = $file->getSize();
                $mimeType = $file->getMimeType();
                $rawContent = (string) file_get_contents($file->getRealPath());
                $certHash = hash('sha256', $rawContent);

                $certPath = $this->mediaService->optimizePdf($file, 'equipment/certificates');
            }

            $equipment = Equipment::findOrFail((int) $data['equipment_id']);
            if (! $equipment->isCalibrationEligible()) {
                throw new RuntimeException(__('Selected equipment is not eligible for metrological calibration.'));
            }

            $certificate = CalibrationCertificate::create([
                'reference' => $data['reference'] ?? null,
                'certificate_type' => $data['certificate_type'] ?? 'periodic',
                'equipment_id' => $equipment->id,
                'laboratory_name' => $data['laboratory_name'] ?? null,
                'calibration_date' => $calibrationDate,
                'expiry_date' => $expiryDate,
                'validity_period_months' => $validityMonths,
                'price' => (float) ($data['price'] ?? 0.0),
                'is_locked' => false,
                'status' => CalibrationCertificateStatus::Draft,
                'certificate_path' => $certPath,
                'certificate_hash' => $certHash,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'environmental_conditions' => $data['environmental_conditions'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'previous_certificate_id' => ! empty($data['previous_certificate_id']) ? (int) $data['previous_certificate_id'] : null,
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]);

            // Sync calibration points
            if (! empty($data['points']) && is_array($data['points'])) {
                $this->syncPoints($certificate, $data['points']);
            }

            // Sync equipment certificate_path shortcut if file was uploaded
            if ($certPath) {
                Equipment::where('id', $certificate->equipment_id)->update([
                    'certificate_path' => $certPath,
                ]);
            }

            return $certificate;
        });
    }

    /**
     * Update an existing calibration certificate.
     */
    public function updateCertificate(
        CalibrationCertificate $certificate,
        array $data,
        ?UploadedFile $file = null,
        ?User $user = null
    ): CalibrationCertificate {
        if ($certificate->is_locked) {
            throw new RuntimeException(__('Cannot modify locked certificate. Unlock with authorization first.'));
        }

        return DB::transaction(function () use ($certificate, $data, $file, $user): CalibrationCertificate {
            $calibrationDate = ! empty($data['calibration_date'])
                ? Carbon::parse($data['calibration_date'])
                : $certificate->calibration_date;

            $validityMonths = (int) ($data['validity_period_months'] ?? $certificate->validity_period_months ?? 12);

            $expiryDate = ! empty($data['expiry_date'])
                ? Carbon::parse($data['expiry_date'])
                : ($calibrationDate ? $calibrationDate->copy()->addMonths($validityMonths) : null);

            $targetEquipmentId = (int) ($data['equipment_id'] ?? $certificate->equipment_id);
            if ($targetEquipmentId !== $certificate->equipment_id) {
                $equipment = Equipment::findOrFail($targetEquipmentId);
                if (! $equipment->isCalibrationEligible()) {
                    throw new RuntimeException(__('Selected equipment is not eligible for metrological calibration.'));
                }
            }

            $updateData = [
                'reference' => $data['reference'] ?? $certificate->reference,
                'certificate_type' => $data['certificate_type'] ?? $certificate->certificate_type,
                'equipment_id' => $targetEquipmentId,
                'laboratory_name' => $data['laboratory_name'] ?? $certificate->laboratory_name,
                'calibration_date' => $calibrationDate,
                'expiry_date' => $expiryDate,
                'validity_period_months' => $validityMonths,
                'price' => isset($data['price']) ? (float) $data['price'] : $certificate->price,
                'environmental_conditions' => $data['environmental_conditions'] ?? $certificate->environmental_conditions,
                'remarks' => $data['remarks'] ?? $certificate->remarks,
                'updated_by' => $user?->id,
            ];

            if ($file instanceof UploadedFile) {
                $rawContent = (string) file_get_contents($file->getRealPath());
                $certPath = $this->mediaService->optimizePdf($file, 'equipment/certificates');

                $updateData['certificate_path'] = $certPath;
                $updateData['certificate_hash'] = hash('sha256', $rawContent);
                $updateData['file_name'] = $file->getClientOriginalName();
                $updateData['file_size'] = $file->getSize();
                $updateData['mime_type'] = $file->getMimeType();

                // Update equipment certificate link
                Equipment::where('id', $certificate->equipment_id)->update([
                    'certificate_path' => $certPath,
                ]);
            }

            $certificate->update($updateData);

            // Sync points if supplied
            if (isset($data['points']) && is_array($data['points'])) {
                $certificate->calibrationPoints()->delete();
                $this->syncPoints($certificate, $data['points']);
            }

            return $certificate->fresh(['equipment', 'calibrationPoints']);
        });
    }

    /**
     * Approve and lock the certificate.
     */
    public function approveAndLock(CalibrationCertificate $certificate, User $user): CalibrationCertificate
    {
        return DB::transaction(function () use ($certificate, $user): CalibrationCertificate {
            $now = Carbon::now();
            $status = ($certificate->expiry_date && $certificate->expiry_date->isPast())
                ? CalibrationCertificateStatus::Expired
                : CalibrationCertificateStatus::Approved;

            $certificate->update([
                'status' => $status,
                'is_locked' => true,
                'approved_by' => $user->id,
                'approved_at' => $now,
                'locked_by' => $user->id,
                'locked_at' => $now,
            ]);

            // Update equipment certificate shortcut and active state
            if ($certificate->certificate_path) {
                Equipment::where('id', $certificate->equipment_id)->update([
                    'certificate_path' => $certificate->certificate_path,
                ]);
            }

            activity('calibration_certificate')
                ->performedOn($certificate)
                ->causedBy($user)
                ->log('Certificate approved and officially locked for operational use.');

            return $certificate;
        });
    }

    /**
     * Unlock certificate for authorized revision.
     */
    public function unlock(CalibrationCertificate $certificate, User $user, string $reason): CalibrationCertificate
    {
        $certificate->update([
            'is_locked' => false,
            'status' => CalibrationCertificateStatus::UnderReview,
        ]);

        activity('calibration_certificate')
            ->performedOn($certificate)
            ->causedBy($user)
            ->withProperties(['unlock_reason' => $reason])
            ->log("Certificate unlocked. Reason: {$reason}");

        return $certificate;
    }

    /**
     * Delete certificate (if not locked).
     */
    public function deleteCertificate(CalibrationCertificate $certificate): bool
    {
        if ($certificate->is_locked) {
            throw new RuntimeException(__('Cannot delete locked certificate.'));
        }

        return DB::transaction(function () use ($certificate): bool {
            $certificate->calibrationPoints()->delete();

            return (bool) $certificate->delete();
        });
    }

    /**
     * Synchronize points array to calibration_points table.
     */
    protected function syncPoints(CalibrationCertificate $certificate, array $points): void
    {
        $specs = $certificate->equipment?->specifications;

        foreach ($points as $point) {
            $nominal = (float) ($point['nominal_value'] ?? 0.0);
            $correction = (float) ($point['correction'] ?? 0.0);
            $uncertainty = (float) ($point['uncertainty'] ?? 0.0);
            $specId = ! empty($point['equipment_specification_id']) ? (int) $point['equipment_specification_id'] : null;

            if ($specId === null && $specs && $specs->isNotEmpty()) {
                if ($specs->count() === 1) {
                    $specId = $specs->first()->id;
                } else {
                    $exactCandidates = [];
                    $tolCandidates = [];

                    foreach ($specs as $s) {
                        $min = (float) $s->range_min;
                        $max = (float) $s->range_max;
                        $span = abs($max - $min);
                        $tol = max(1.0, $span * 0.15);

                        if ($nominal >= $min && $nominal <= $max) {
                            $exactCandidates[] = ['id' => $s->id, 'span' => $span];
                        } elseif ($nominal >= ($min - $tol) && $nominal <= ($max + $tol)) {
                            $tolCandidates[] = ['id' => $s->id, 'span' => $span];
                        }
                    }

                    $pool = ! empty($exactCandidates) ? $exactCandidates : $tolCandidates;
                    if (! empty($pool)) {
                        usort($pool, fn (array $a, array $b): int => $a['span'] <=> $b['span']);
                        $specId = $pool[0]['id'];
                    }
                }
            }

            // Check tolerance status
            $status = CalibrationPointStatus::InTolerance;
            if (isset($point['status'])) {
                $status = $point['status'] instanceof CalibrationPointStatus
                    ? $point['status']
                    : CalibrationPointStatus::tryFrom((string) $point['status']) ?? CalibrationPointStatus::InTolerance;
            }

            CalibrationPoint::create([
                'calibration_certificate_id' => $certificate->id,
                'equipment_specification_id' => $specId,
                'nominal_value' => $nominal,
                'correction' => $correction,
                'uncertainty' => $uncertainty,
                'status' => $status,
            ]);
        }
    }
}
