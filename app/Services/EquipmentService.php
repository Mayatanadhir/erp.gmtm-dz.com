<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EquipmentCategory;
use App\Enums\EquipmentStatus;
use App\Interfaces\EquipmentRepositoryInterface;
use App\Models\Equipment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class EquipmentService extends BaseService
{
    public function __construct(
        protected EquipmentRepositoryInterface $equipmentRepository,
        protected MediaOptimizationService $mediaService
    ) {}

    /**
     * Create an equipment record, process image and certificate, and sync specifications.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, mixed>|null  $params
     */
    public function createEquipment(
        array $data,
        ?UploadedFile $image = null,
        ?UploadedFile $certificate = null,
        ?array $params = null
    ): Equipment {
        return $this->executeInTransaction(function () use ($data, $image, $certificate, $params): Equipment {
            if ($image !== null) {
                $storedPath = $this->mediaService->optimizeImage($image, 'equipment/images');
                $data['image_path'] = $storedPath;

                $disk = Storage::disk('public');
                if ($disk->exists($storedPath)) {
                    $data['image_hash'] = hash('sha256', (string) $disk->get($storedPath));
                }
            }

            if ($certificate !== null) {
                $storedCert = $this->mediaService->optimizePdf($certificate, 'equipment/certificates');
                $data['certificate_path'] = $storedCert;
            }

            $cleanData = Arr::except($data, ['image', 'certificate', 'params']);

            /** @var Equipment $equipment */
            $equipment = $this->equipmentRepository->create($cleanData);

            $this->syncSpecifications($equipment, $params, (bool) ($data['requires_calibration'] ?? false));

            return $equipment;
        });
    }

    /**
     * Update an equipment record, handle file updates/removals, and sync specifications.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, mixed>|null  $params
     */
    public function updateEquipment(
        Equipment $equipment,
        array $data,
        ?UploadedFile $image = null,
        ?UploadedFile $certificate = null,
        ?array $params = null,
        bool $removeImage = false,
        bool $removeCertificate = false
    ): bool {
        return $this->executeInTransaction(function () use (
            $equipment,
            $data,
            $image,
            $certificate,
            $params,
            $removeImage,
            $removeCertificate
        ): bool {
            if ($removeImage) {
                if (! blank($equipment->image_path)) {
                    $this->mediaService->safeDelete($equipment->image_path, 'public', $equipment->id);
                    $data['image_path'] = null;
                    $data['image_hash'] = null;
                }
            } elseif ($image !== null) {
                if (! blank($equipment->image_path)) {
                    $this->mediaService->safeDelete($equipment->image_path, 'public', $equipment->id);
                }
                $storedPath = $this->mediaService->optimizeImage($image, 'equipment/images');
                $data['image_path'] = $storedPath;

                $disk = Storage::disk('public');
                if ($disk->exists($storedPath)) {
                    $data['image_hash'] = hash('sha256', (string) $disk->get($storedPath));
                }
            }

            if ($removeCertificate) {
                if (! blank($equipment->certificate_path)) {
                    $this->mediaService->safeDelete($equipment->certificate_path, 'public', $equipment->id);
                    $data['certificate_path'] = null;
                }
            } elseif ($certificate !== null) {
                if (! blank($equipment->certificate_path)) {
                    $this->mediaService->safeDelete($equipment->certificate_path, 'public', $equipment->id);
                }
                $storedCert = $this->mediaService->optimizePdf($certificate, 'equipment/certificates');
                $data['certificate_path'] = $storedCert;
            }

            $cleanData = Arr::except($data, ['image', 'certificate', 'params']);
            $updated = $this->equipmentRepository->update($equipment->id, $cleanData);

            $this->syncSpecifications($equipment, $params, (bool) ($data['requires_calibration'] ?? $equipment->requires_calibration));

            return $updated;
        });
    }

    /**
     * Soft delete an equipment record.
     */
    public function deleteEquipment(Equipment $equipment): bool
    {
        return $this->executeInTransaction(function () use ($equipment): bool {
            return (bool) $this->equipmentRepository->delete($equipment->id);
        });
    }

    /**
     * Synchronize physical measurement and generation specifications.
     *
     * @param  array<int, mixed>|null  $params
     */
    public function syncSpecifications(Equipment $equipment, ?array $params, bool $requiresCalibration): void
    {
        if (! $requiresCalibration) {
            $equipment->specifications()->delete();

            return;
        }

        $keptIds = [];

        if (is_array($params)) {
            foreach ($params as $grandeurId => $details) {
                if (! empty($details['selected'])) {
                    $spec = $equipment->specifications()->updateOrCreate(
                        ['grandeur_id' => (int) $grandeurId],
                        [
                            'range_min' => (isset($details['min']) && $details['min'] !== '') ? (float) $details['min'] : 0.0,
                            'range_max' => (isset($details['max']) && $details['max'] !== '') ? (float) $details['max'] : 0.0,
                            'accuracy_value' => (isset($details['acc']) && $details['acc'] !== '') ? (float) $details['acc'] : 0.0,
                            'accuracy_type' => ! empty($details['acc_type']) ? $details['acc_type'] : '%',
                        ]
                    );

                    if ($spec) {
                        $keptIds[] = $spec->id;
                    }
                }
            }
        }

        $equipment->specifications()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Get aggregate statistics for the equipment overview KPI bar.
     *
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        return [
            'total' => Equipment::count(),
            'active' => Equipment::where('status', EquipmentStatus::Active->value)->count(),
            'has_certificate' => Equipment::where('status', EquipmentStatus::Active->value)
                ->where(function ($q): void {
                    $q->where('requires_calibration', true)
                        ->orWhere('category', EquipmentCategory::MeasuringInstrument->value);
                })->count(),
            'work_tools' => Equipment::where('status', EquipmentStatus::Active->value)
                ->where('category', EquipmentCategory::WorkTool->value)->count(),
            'vehicles' => Equipment::where('status', EquipmentStatus::Active->value)
                ->where('category', EquipmentCategory::Vehicle->value)->count(),
            'inactive' => Equipment::where('status', EquipmentStatus::Inactive->value)->count(),
            'measuring_instruments' => Equipment::where('category', EquipmentCategory::MeasuringInstrument->value)->count(),
            'requires_calibration' => Equipment::where('requires_calibration', true)->count(),
        ];
    }
}
