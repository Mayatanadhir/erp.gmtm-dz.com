<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\EquipmentSpecification;
use App\Services\MediaOptimizationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class LegacyEquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $legacyDbPath = 'd:/HARD Project/app.gmtm-dz.com';
        $imagesSourceDir = $legacyDbPath.'/public/assets/img_equipment';
        $certsSourceDir = $legacyDbPath.'/public/assets/certificates';

        // Connect directly to legacy database via PDO or read exported JSON
        $legacyEquipmentJson = file_exists(storage_path('legacy_equipment.json'))
            ? json_decode(file_get_contents(storage_path('legacy_equipment.json')), true)
            : $this->fetchFromLegacyDatabase();

        $mediaService = app(MediaOptimizationService::class);

        foreach ($legacyEquipmentJson as $item) {
            $category = match ($item['category'] ?? '') {
                'Measuring_Instrument' => 'measuring_instrument',
                'Work_Tool' => 'work_tool',
                'Vehicle' => 'vehicle',
                default => 'other',
            };

            $package = match ($item['package'] ?? '') {
                'Lot 01' => 'lot_01',
                'Lot 02' => 'lot_02',
                'Vehicle Lot' => 'vehicle_lot',
                default => 'none',
            };

            $status = ($item['status'] ?? '') === 'inactive' || ($item['status'] ?? '') === 'retired'
                ? 'inactive'
                : 'active';

            // Process image to WebP if present
            $optimizedImagePath = null;
            $imageHash = null;

            if (! empty($item['image_path'])) {
                $sourceImageFile = $imagesSourceDir.'/'.$item['image_path'];
                if (File::exists($sourceImageFile)) {
                    try {
                        $optimizedImagePath = $mediaService->optimizeImage($sourceImageFile, 'equipment/images');
                        $disk = Storage::disk('public');
                        if ($disk->exists($optimizedImagePath)) {
                            $imageHash = hash('sha256', (string) $disk->get($optimizedImagePath));
                        }
                    } catch (\Throwable $e) {
                        // Fallback copy if gd/imagick cannot decode
                        $ext = pathinfo($sourceImageFile, PATHINFO_EXTENSION) ?: 'png';
                        $hash = hash_file('sha256', $sourceImageFile);
                        $destRel = 'equipment/images/'.$hash.'.'.$ext;
                        Storage::disk('public')->put($destRel, File::get($sourceImageFile));
                        $optimizedImagePath = $destRel;
                        $imageHash = $hash;
                    }
                }
            }

            // Process certificate PDF if present
            $optimizedCertPath = null;
            if (! empty($item['certificate_path'])) {
                $sourceCertFile = $certsSourceDir.'/'.$item['certificate_path'];
                if (File::exists($sourceCertFile)) {
                    try {
                        $optimizedCertPath = $mediaService->optimizePdf($sourceCertFile, 'equipment/certificates');
                    } catch (\Throwable $e) {
                        $hash = hash_file('sha256', $sourceCertFile);
                        $destRel = 'equipment/certificates/'.$hash.'.pdf';
                        Storage::disk('public')->put($destRel, File::get($sourceCertFile));
                        $optimizedCertPath = $destRel;
                    }
                }
            }

            $equipment = Equipment::withTrashed()->updateOrCreate(
                ['id' => (int) $item['id']],
                [
                    'internal_code' => $item['internal_code'] ?? null,
                    'full_name' => $item['full_name'],
                    'short_name' => $item['short_name'] ?? null,
                    'serial_number' => $item['serial_number'] ?? null,
                    'category' => $category,
                    'package' => $package,
                    'requires_calibration' => (bool) ((string) ($item['requires_calibration'] ?? '0') === '1'),
                    'designation' => $item['designation'] ?? null,
                    'status' => $status,
                    'image_path' => $optimizedImagePath,
                    'image_hash' => $imageHash,
                    'certificate_path' => $optimizedCertPath,
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );

            // Sync specifications
            if (! empty($item['calibrator_specifications'])) {
                foreach ($item['calibrator_specifications'] as $spec) {
                    EquipmentSpecification::updateOrCreate(
                        [
                            'equipment_id' => $equipment->id,
                            'grandeur_id' => (int) $spec['grandeur_id'],
                        ],
                        [
                            'range_min' => (float) ($spec['range_min'] ?? 0),
                            'range_max' => (float) ($spec['range_max'] ?? 0),
                            'accuracy_value' => (float) ($spec['accuracy_value'] ?? 0),
                            'accuracy_type' => ! empty($spec['accuracy_type']) ? $spec['accuracy_type'] : '%',
                        ]
                    );
                }
            }
        }
    }

    /**
     * Fallback PDO fetch if JSON dump does not exist.
     */
    protected function fetchFromLegacyDatabase(): array
    {
        try {
            $pdo = new \PDO('mysql:host=127.0.0.1;dbname=gmtm_app', 'root', '');
            $stmt = $pdo->query('SELECT * FROM equipment');
            $equipment = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($equipment as &$item) {
                $specStmt = $pdo->prepare('SELECT * FROM calibrator_specifications WHERE equipment_id = ?');
                $specStmt->execute([$item['id']]);
                $item['calibrator_specifications'] = $specStmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            return $equipment;
        } catch (\Throwable) {
            return [];
        }
    }
}
