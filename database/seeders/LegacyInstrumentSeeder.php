<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AccuracyType;
use App\Enums\FluidType;
use App\Enums\InstrumentStatus;
use App\Enums\InstrumentType;
use App\Enums\ProcessVariable;
use App\Enums\ProverType;
use App\Models\Instrument;
use App\Models\InstrumentSpecification;
use App\Models\ProverSpecification;
use App\Models\StandardGaugeSpecification;
use App\Services\MediaOptimizationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class LegacyInstrumentSeeder extends Seeder
{
    public function run(): void
    {
        $legacyAppPath = 'd:/HARD Project/app.gmtm-dz.com';
        $imagesSourceDir = $legacyAppPath.'/public/assets/img_instruments';

        $legacyInstruments = $this->fetchFromLegacyDatabase();
        if (empty($legacyInstruments)) {
            return;
        }

        $mediaService = app(MediaOptimizationService::class);

        foreach ($legacyInstruments as $item) {
            $instrumentType = InstrumentType::tryFromLegacy($item['instrument_type'] ?? '') ?? InstrumentType::Transmitter;
            $status = ($item['status'] ?? '') === 'inactive' ? InstrumentStatus::Inactive : InstrumentStatus::Active;
            $processVariable = ProcessVariable::tryFromLegacy($item['process_variable'] ?? null);
            $fluidType = FluidType::tryFromLegacy($item['fluid_type'] ?? null);

            // Process image to WebP
            $optimizedImagePath = null;
            $imageHash = null;

            if (! empty($item['image_path'])) {
                $sourceImageFile = $imagesSourceDir.'/'.$item['image_path'];
                if (File::exists($sourceImageFile)) {
                    try {
                        $optimizedImagePath = $mediaService->optimizeImage($sourceImageFile, 'instruments/images');
                        $disk = Storage::disk('public');
                        if ($disk->exists($optimizedImagePath)) {
                            $imageHash = hash('sha256', (string) $disk->get($optimizedImagePath));
                        }
                    } catch (\Throwable) {
                        $optimizedImagePath = null;
                        $imageHash = null;
                    }
                }
            }

            /** @var Instrument $instrument */
            $instrument = Instrument::updateOrCreate(
                ['serial_number' => (string) $item['serial_number']],
                [
                    'site_id' => ! empty($item['site_id']) ? (int) $item['site_id'] : null,
                    'tag_number' => (string) ($item['tag_number'] ?? 'TAG-'.($item['id'] ?? '0')),
                    'instrument_type' => $instrumentType,
                    'process_variable' => $processVariable,
                    'measurement_type' => $item['measurement_type'] ?? null,
                    'fluid_type' => $fluidType,
                    'technology' => $item['technology'] ?? null,
                    'status' => $status,
                    'image_path' => $optimizedImagePath,
                    'image_hash' => $imageHash,
                ]
            );

            // 1. Sync physical specifications
            if (! empty($item['specifications'])) {
                foreach ($item['specifications'] as $spec) {
                    InstrumentSpecification::updateOrCreate(
                        [
                            'instrument_id' => $instrument->id,
                            'grandeur_id' => (int) $spec['grandeur_id'],
                        ],
                        [
                            'range_min' => isset($spec['range_min']) ? (float) $spec['range_min'] : null,
                            'range_max' => isset($spec['range_max']) ? (float) $spec['range_max'] : null,
                            'accuracy_value' => isset($spec['accuracy_value']) ? (float) $spec['accuracy_value'] : null,
                            'accuracy_type' => ($spec['accuracy_type'] ?? '%') === 'abs' ? AccuracyType::Absolute : AccuracyType::Percentage,
                        ]
                    );
                }
            }

            // 2. Sync standard gauge specifications
            if (! empty($item['standard_gauge_spec'])) {
                $gSpec = $item['standard_gauge_spec'];
                StandardGaugeSpecification::updateOrCreate(
                    ['instrument_id' => $instrument->id],
                    [
                        'nominal_capacity_liters' => (float) ($gSpec['nominal_capacity_liters'] ?? 0),
                        'neck_scale_sensitivity' => isset($gSpec['neck_scale_sensitivity']) ? (float) $gSpec['neck_scale_sensitivity'] : null,
                        'cubical_expansion_coef_gcm' => isset($gSpec['cubical_expansion_coef_gcm']) ? (float) $gSpec['cubical_expansion_coef_gcm'] : null,
                        'vessel_material' => $gSpec['vessel_material'] ?? 'Stainless Steel',
                        'base_reference_temperature' => isset($gSpec['base_reference_temperature']) ? (float) $gSpec['base_reference_temperature'] : 20.00,
                        'calibration_certificate_number' => $gSpec['calibration_certificate_number'] ?? null,
                        'calibration_date' => $gSpec['calibration_date'] ?? null,
                        'calibration_expiry_date' => $gSpec['calibration_expiry_date'] ?? null,
                    ]
                );
            }

            // 3. Sync prover specifications
            if (! empty($item['prover_spec'])) {
                $pSpec = $item['prover_spec'];
                ProverSpecification::updateOrCreate(
                    ['instrument_id' => $instrument->id],
                    [
                        'type' => ProverType::tryFrom($pSpec['type'] ?? '') ?? ProverType::BidirectionalPipe,
                        'inner_diameter' => isset($pSpec['inner_diameter']) ? (float) $pSpec['inner_diameter'] : null,
                        'wall_thickness' => isset($pSpec['wall_thickness']) ? (float) $pSpec['wall_thickness'] : null,
                        'nominal_base_volume' => isset($pSpec['nominal_base_volume']) ? (float) $pSpec['nominal_base_volume'] : null,
                        'cubical_expansion_coef' => isset($pSpec['cubical_expansion_coef']) ? (float) $pSpec['cubical_expansion_coef'] : null,
                        'elasticity_modulus' => isset($pSpec['elasticity_modulus']) ? (float) $pSpec['elasticity_modulus'] : null,
                        'material' => $pSpec['material'] ?? 'Mild Steel',
                        'pulse_interpolation' => (bool) ($pSpec['pulse_interpolation'] ?? false),
                    ]
                );
            }
        }

        // 4. Second pass: sync flow computer linked transmitters
        foreach ($legacyInstruments as $item) {
            if (! empty($item['linked_transmitters'])) {
                $flowComputer = Instrument::where('serial_number', (string) $item['serial_number'])->first();
                if ($flowComputer && $flowComputer->instrument_type === InstrumentType::FlowComputer) {
                    $pivotData = [];
                    foreach ($item['linked_transmitters'] as $link) {
                        $targetTransmitter = Instrument::where('id', $link['transmitter_id'])->first();
                        if ($targetTransmitter) {
                            $pivotData[$targetTransmitter->id] = ['channel_number' => (int) ($link['channel_number'] ?? 1)];
                        }
                    }
                    $flowComputer->linkedTransmitters()->sync($pivotData);
                }
            }
        }
    }

    /**
     * Fetch instruments and all attached relations from legacy gmtm_app MySQL database.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function fetchFromLegacyDatabase(): array
    {
        try {
            $pdo = new \PDO('mysql:host=127.0.0.1;dbname=gmtm_app', 'root', 'root');
            $stmt = $pdo->query('SELECT * FROM instruments ORDER BY id ASC');
            $instruments = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($instruments as &$inst) {
                $id = $inst['id'];

                // Physical specifications
                try {
                    $specStmt = $pdo->prepare('SELECT * FROM instrument_specifications WHERE instrument_id = ?');
                    $specStmt->execute([$id]);
                    $inst['specifications'] = $specStmt->fetchAll(\PDO::FETCH_ASSOC);
                } catch (\Throwable) {
                    $inst['specifications'] = [];
                }

                // Standard gauge specifications
                try {
                    $gaugeStmt = $pdo->prepare('SELECT * FROM standard_gauge_specifications WHERE instrument_id = ?');
                    $gaugeStmt->execute([$id]);
                    $inst['standard_gauge_spec'] = $gaugeStmt->fetch(\PDO::FETCH_ASSOC) ?: null;

                    if (! $inst['standard_gauge_spec']) {
                        $tmStmt = $pdo->prepare('SELECT * FROM test_measure_specifications WHERE instrument_id = ?');
                        $tmStmt->execute([$id]);
                        $inst['standard_gauge_spec'] = $tmStmt->fetch(\PDO::FETCH_ASSOC) ?: null;
                    }
                } catch (\Throwable) {
                    $inst['standard_gauge_spec'] = null;
                }

                // Prover specifications
                try {
                    $proverStmt = $pdo->prepare('SELECT * FROM prover_specifications WHERE instrument_id = ?');
                    $proverStmt->execute([$id]);
                    $inst['prover_spec'] = $proverStmt->fetch(\PDO::FETCH_ASSOC) ?: null;
                } catch (\Throwable) {
                    $inst['prover_spec'] = null;
                }

                // Linked transmitters
                try {
                    $fcStmt = $pdo->prepare('SELECT * FROM flow_computer_transmitters WHERE flow_computer_id = ?');
                    $fcStmt->execute([$id]);
                    $inst['linked_transmitters'] = $fcStmt->fetchAll(\PDO::FETCH_ASSOC);
                } catch (\Throwable) {
                    $inst['linked_transmitters'] = [];
                }
            }

            return $instruments;
        } catch (\Throwable $e) {
            // Try empty password fallback if password 'root' fails
            try {
                $pdo = new \PDO('mysql:host=127.0.0.1;dbname=gmtm_app', 'root', '');
                $stmt = $pdo->query('SELECT * FROM instruments ORDER BY id ASC');
                $instruments = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($instruments as &$inst) {
                    $id = $inst['id'];
                    $specStmt = $pdo->prepare('SELECT * FROM instrument_specifications WHERE instrument_id = ?');
                    $specStmt->execute([$id]);
                    $inst['specifications'] = $specStmt->fetchAll(\PDO::FETCH_ASSOC);
                }

                return $instruments;
            } catch (\Throwable) {
                return [];
            }
        }
    }
}
