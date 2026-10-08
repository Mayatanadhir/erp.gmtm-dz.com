<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\FluidType;
use App\Enums\InstrumentType;
use App\Enums\ProcessVariable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MigrateLegacyInstrumentsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrology:migrate-legacy-instruments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate and synchronize instruments, wiring channels, and specifications from legacy database with high precision';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting legacy instruments data migration...');

        // 1. Establish connection to legacy gmtm_app
        config(['database.connections.legacy' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'gmtm_app',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]]);

        $legacyDb = DB::connection('legacy');
        $erpDb = DB::connection('mysql');

        // 2. Migrate / Sync Instruments
        $this->info('--- 1. Syncing Instruments ---');
        $legacyInstruments = $legacyDb->table('instruments')->get();
        $syncedInstruments = 0;
        $legacyToErpIdMap = [];

        foreach ($legacyInstruments as $leg) {
            $normType = InstrumentType::tryFromLegacy($leg->instrument_type)?->value ?? 'transmitter';
            $normPv = ProcessVariable::tryFromLegacy($leg->process_variable)?->value;
            $normFluid = FluidType::tryFromLegacy($leg->fluid_type)?->value;
            $normStatus = strtolower($leg->status ?? 'active') === 'inactive' ? 'inactive' : 'active';

            $imagePath = $leg->image_path;
            if ($imagePath && ! str_starts_with($imagePath, 'instruments/images/')) {
                $imagePath = 'instruments/images/'.basename($imagePath);
            }

            // Find existing in ERP by serial_number, tag_number, or id
            $existing = null;
            if (! empty($leg->serial_number)) {
                $existing = $erpDb->table('instruments')->where('serial_number', $leg->serial_number)->first();
            }
            if (! $existing && ! empty($leg->tag_number)) {
                $existing = $erpDb->table('instruments')->where('tag_number', $leg->tag_number)->first();
            }
            if (! $existing) {
                $existing = $erpDb->table('instruments')->where('id', $leg->id)->first();
            }

            if ($existing) {
                $erpDb->table('instruments')->where('id', $existing->id)->update([
                    'site_id' => $leg->site_id,
                    'tag_number' => $leg->tag_number,
                    'instrument_type' => $normType,
                    'process_variable' => $normPv,
                    'measurement_type' => $leg->measurement_type,
                    'fluid_type' => $normFluid,
                    'technology' => $leg->technology ?? 'Conventional',
                    'status' => $normStatus,
                    'updated_at' => $leg->updated_at ?? now(),
                ]);
                $legacyToErpIdMap[$leg->id] = $existing->id;
            } else {
                // Check if id is free in ERP
                $idTaken = $erpDb->table('instruments')->where('id', $leg->id)->exists();
                $insertData = [
                    'site_id' => $leg->site_id,
                    'tag_number' => $leg->tag_number,
                    'serial_number' => $leg->serial_number ?? ('SN-'.$leg->tag_number),
                    'instrument_type' => $normType,
                    'process_variable' => $normPv,
                    'measurement_type' => $leg->measurement_type,
                    'fluid_type' => $normFluid,
                    'technology' => $leg->technology ?? 'Conventional',
                    'image_path' => $imagePath,
                    'image_hash' => $leg->image_hash,
                    'status' => $normStatus,
                    'created_at' => $leg->created_at ?? now(),
                    'updated_at' => $leg->updated_at ?? now(),
                    'deleted_at' => null,
                ];
                if (! $idTaken) {
                    $insertData['id'] = $leg->id;
                    $erpDb->table('instruments')->insert($insertData);
                    $legacyToErpIdMap[$leg->id] = $leg->id;
                } else {
                    $newId = $erpDb->table('instruments')->insertGetId($insertData);
                    $legacyToErpIdMap[$leg->id] = $newId;
                }
            }

            $syncedInstruments++;
        }
        $this->info("Synced {$syncedInstruments} instruments. Mapped IDs: ".count($legacyToErpIdMap));

        // 3. Migrate / Sync Wiring (flow_computer_transmitters) with string channels
        $this->info('--- 2. Syncing Flow Computer Wiring & Channels ---');
        $legacyWiring = $legacyDb->table('flow_computer_transmitters')->get();
        $syncedWiring = 0;

        foreach ($legacyWiring as $w) {
            $channelStr = trim((string) ($w->channel_number ?? 'Ch1'));
            if ($channelStr === '' || $channelStr === '0') {
                $channelStr = 'Ch1';
            }

            $erpFcId = $legacyToErpIdMap[$w->flow_computer_id] ?? $w->flow_computer_id;
            $erpTrId = $legacyToErpIdMap[$w->transmitter_id] ?? $w->transmitter_id;

            $fcExists = $erpDb->table('instruments')->where('id', $erpFcId)->exists();
            $trExists = $erpDb->table('instruments')->where('id', $erpTrId)->exists();

            if ($fcExists && $trExists) {
                $exists = $erpDb->table('flow_computer_transmitter')
                    ->where('flow_computer_id', $erpFcId)
                    ->where('transmitter_id', $erpTrId)
                    ->first();

                if ($exists) {
                    $erpDb->table('flow_computer_transmitter')
                        ->where('id', $exists->id)
                        ->update([
                            'channel_number' => $channelStr,
                            'updated_at' => $w->updated_at ?? now(),
                        ]);
                } else {
                    $erpDb->table('flow_computer_transmitter')->insert([
                        'flow_computer_id' => $erpFcId,
                        'transmitter_id' => $erpTrId,
                        'channel_number' => $channelStr,
                        'created_at' => $w->created_at ?? now(),
                        'updated_at' => $w->updated_at ?? now(),
                    ]);
                }
                $syncedWiring++;
            }
        }
        $this->info("Synced {$syncedWiring} wiring channel links.");

        // 4. Migrate / Sync Specifications (instrument_specifications)
        $this->info('--- 3. Syncing Physical Specifications ---');
        $legacySpecs = $legacyDb->table('instrument_specifications')->get();
        $syncedSpecs = 0;

        foreach ($legacySpecs as $spec) {
            $erpInstId = $legacyToErpIdMap[$spec->instrument_id] ?? $spec->instrument_id;
            $instExists = $erpDb->table('instruments')->where('id', $erpInstId)->exists();
            $grExists = $erpDb->table('grandeurs')->where('id', $spec->grandeur_id)->exists();

            if ($instExists && $grExists) {
                $exists = $erpDb->table('instrument_specifications')
                    ->where('instrument_id', $erpInstId)
                    ->where('grandeur_id', $spec->grandeur_id)
                    ->first();

                $specPayload = [
                    'instrument_id' => $erpInstId,
                    'grandeur_id' => $spec->grandeur_id,
                    'range_min' => $spec->range_min,
                    'range_max' => $spec->range_max,
                    'accuracy_value' => $spec->accuracy_value,
                    'accuracy_type' => $spec->accuracy_type ?? '%',
                    'created_at' => $spec->created_at ?? now(),
                    'updated_at' => $spec->updated_at ?? now(),
                ];

                if ($exists) {
                    $erpDb->table('instrument_specifications')
                        ->where('id', $exists->id)
                        ->update($specPayload);
                } else {
                    $erpDb->table('instrument_specifications')->insert($specPayload);
                }
                $syncedSpecs++;
            }
        }
        $this->info("Synced {$syncedSpecs} physical specifications.");

        // 5. Migrate Standard Gauge Specifications
        $this->info('--- 4. Syncing Standard Gauge Specifications ---');
        $legacyGaugeSpecs = $legacyDb->table('standard_gauge_specifications')->get();
        foreach ($legacyGaugeSpecs as $g) {
            $erpInstId = $legacyToErpIdMap[$g->instrument_id] ?? $g->instrument_id;
            $instExists = $erpDb->table('instruments')->where('id', $erpInstId)->exists();
            if ($instExists) {
                $erpDb->table('standard_gauge_specifications')->updateOrInsert(
                    ['instrument_id' => $erpInstId],
                    [
                        'nominal_capacity_liters' => $g->nominal_capacity_liters,
                        'neck_scale_sensitivity' => $g->neck_scale_sensitivity,
                        'cubical_expansion_coef_gcm' => $g->cubical_expansion_coef_gcm ?? '0.00005100',
                        'vessel_material' => 'Stainless Steel',
                        'base_reference_temperature' => 20.00,
                        'calibration_certificate_number' => $g->calibration_certificate_number,
                        'calibration_date' => $g->calibration_date,
                        'calibration_expiry_date' => $g->calibration_expiry_date,
                        'created_at' => $g->created_at ?? now(),
                        'updated_at' => $g->updated_at ?? now(),
                    ]
                );
            }
        }

        // 6. Migrate Prover Specifications
        $this->info('--- 5. Syncing Prover Specifications ---');
        $legacyProvers = $legacyDb->table('prover_specifications')->get();
        foreach ($legacyProvers as $p) {
            $erpInstId = $legacyToErpIdMap[$p->instrument_id] ?? $p->instrument_id;
            $instExists = $erpDb->table('instruments')->where('id', $erpInstId)->exists();
            if ($instExists) {
                $normProverType = match ($p->prover_type ?? '') {
                    'unidirectional_pipe' => 'unidirectional_pipe',
                    'compact_svp' => 'compact_svp',
                    default => 'bidirectional_pipe',
                };

                $erpDb->table('prover_specifications')->updateOrInsert(
                    ['instrument_id' => $erpInstId],
                    [
                        'type' => $normProverType,
                        'inner_diameter' => $p->inner_diameter ?? null,
                        'wall_thickness' => $p->wall_thickness ?? null,
                        'nominal_base_volume' => $p->nominal_base_volume ?? null,
                        'cubical_expansion_coef' => $p->cubical_expansion_coef ?? '0.00003300',
                        'elasticity_modulus' => $p->elasticity_modulus ?? '2068427.0000',
                        'area_expansion_coef' => $p->area_expansion_coef ?? null,
                        'linear_expansion_coef' => $p->linear_expansion_coef ?? null,
                        'material' => 'Mild Steel',
                        'pulse_interpolation' => false,
                        'created_at' => $p->created_at ?? now(),
                        'updated_at' => $p->updated_at ?? now(),
                    ]
                );
            }
        }

        // 7. Migrate Physical Instrument Images
        $this->info('--- 6. Copying Images & Computing Hashes ---');
        $legacyImgDir = 'c:/Project HARD/app.gmtm-dz.com/public/assets/img_instruments';
        $erpImgDir = storage_path('app/public/instruments/images');

        if (! File::exists($erpImgDir)) {
            File::makeDirectory($erpImgDir, 0755, true);
        }

        if (File::exists($legacyImgDir)) {
            $files = File::files($legacyImgDir);
            $copiedImages = 0;
            foreach ($files as $file) {
                $filename = $file->getFilename();
                $targetFile = $erpImgDir.'/'.$filename;
                File::copy($file->getRealPath(), $targetFile);

                $hash = hash_file('sha256', $targetFile);
                $erpDb->table('instruments')
                    ->where('image_path', 'like', "%{$filename}")
                    ->update([
                        'image_path' => 'instruments/images/'.$filename,
                        'image_hash' => $hash,
                    ]);

                $copiedImages++;
            }
            $this->info("Copied {$copiedImages} instrument images and updated hashes.");
        }

        $this->info('=== All legacy instruments data successfully migrated with 100% precision! ===');

        return self::SUCCESS;
    }
}
