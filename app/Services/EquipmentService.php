<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\EquipmentRepositoryInterface;
use App\Models\Equipment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

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
                $data['image_path'] = $this->mediaService->optimizeImage($image, 'equipment/images');
            }

            if ($certificate !== null) {
                $data['certificate_path'] = $this->mediaService->optimizePdf($certificate, 'equipment/certificates');
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
                $data['image_path'] = null;
                $data['image_hash'] = null;
            } elseif ($image !== null) {
                $data['image_path'] = $this->mediaService->optimizeImage($image, 'equipment/images');
            }

            if ($removeCertificate) {
                $data['certificate_path'] = null;
            } elseif ($certificate !== null) {
                $data['certificate_path'] = $this->mediaService->optimizePdf($certificate, 'equipment/certificates');
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
            foreach ($equipment->specifications as $spec) {
                $spec->delete();
            }

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

        $specsToDelete = $equipment->specifications()->whereNotIn('id', $keptIds)->get();
        foreach ($specsToDelete as $spec) {
            $spec->delete();
        }
    }

    /**
     * Get aggregate statistics for the equipment overview KPI bar.
     * Consolidated into a single database query for optimal high-throughput performance.
     *
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        $raw = DB::table('equipment')
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
                COUNT(CASE WHEN status = 'active' AND (requires_calibration = 1 OR category = 'measuring_instrument') THEN 1 END) as has_certificate,
                COUNT(CASE WHEN status = 'active' AND category = 'work_tool' THEN 1 END) as work_tools,
                COUNT(CASE WHEN status = 'active' AND category = 'vehicle' THEN 1 END) as vehicles,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive,
                COUNT(CASE WHEN category = 'measuring_instrument' THEN 1 END) as measuring_instruments,
                COUNT(CASE WHEN requires_calibration = 1 THEN 1 END) as requires_calibration
            ")
            ->first();

        return [
            'total' => (int) ($raw->total ?? 0),
            'active' => (int) ($raw->active ?? 0),
            'has_certificate' => (int) ($raw->has_certificate ?? 0),
            'work_tools' => (int) ($raw->work_tools ?? 0),
            'vehicles' => (int) ($raw->vehicles ?? 0),
            'inactive' => (int) ($raw->inactive ?? 0),
            'measuring_instruments' => (int) ($raw->measuring_instruments ?? 0),
            'requires_calibration' => (int) ($raw->requires_calibration ?? 0),
        ];
    }
}
