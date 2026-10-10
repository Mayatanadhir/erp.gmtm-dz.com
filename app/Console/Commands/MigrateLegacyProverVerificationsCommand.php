<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Instrument;
use App\Models\ProverVerification;
use App\Models\ProverVerificationRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class MigrateLegacyProverVerificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrology:migrate-legacy-provers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate prover verification sessions and calibration runs from legacy gmtm_app to erp.gmtm-dz.com';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting prover verification data migration from gmtm_app...');

        $legacyDb = env('LEGACY_DB_DATABASE', 'gmtm_app');
        $legacyHost = env('DB_HOST', '127.0.0.1');
        $legacyUser = env('DB_USERNAME', 'root');
        $legacyPass = env('DB_PASSWORD', 'root');

        try {
            $legacyPdo = new PDO("mysql:host={$legacyHost};dbname={$legacyDb};charset=utf8mb4", $legacyUser, $legacyPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\Throwable $e) {
            $this->error('Failed to connect to legacy database: '.$e->getMessage());

            return self::FAILURE;
        }

        // Build instrument tag map
        $erpInstruments = Instrument::whereIn('instrument_type', ['prover', 'standard_gauge', 'Prover', 'StandardGauge'])->get();
        $erpByTag = [];
        foreach ($erpInstruments as $inst) {
            $erpByTag[trim($inst->tag_number)] = $inst->id;
        }

        $legacyInstruments = $legacyPdo->query('SELECT id, tag_number, serial_number FROM instruments')->fetchAll(PDO::FETCH_ASSOC);
        $instMap = [];
        foreach ($legacyInstruments as $li) {
            $tag = trim($li['tag_number']);
            if (isset($erpByTag[$tag])) {
                $instMap[(int) $li['id']] = $erpByTag[$tag];
            }
        }

        // Clean up orphan verifications if any
        $orphans = ProverVerification::whereDoesntHave('prover')->orWhereDoesntHave('jauge')->pluck('id');
        if ($orphans->isNotEmpty()) {
            $this->warn("Found {$orphans->count()} orphan verifications without valid instruments. Cleaning up...");
            ProverVerificationRun::whereIn('prover_verification_id', $orphans)->delete();
            ProverVerification::whereIn('id', $orphans)->delete();
        }

        // Fetch legacy prover verifications
        $verifications = $legacyPdo->query('SELECT * FROM prover_verifications')->fetchAll(PDO::FETCH_ASSOC);
        $this->info('Found '.count($verifications).' legacy prover verifications to process.');

        DB::beginTransaction();
        try {
            foreach ($verifications as $v) {
                $legacyProverId = (int) $v['prover_id'];
                $legacyJaugeId = (int) $v['jauge_id'];

                $mappedProverId = $instMap[$legacyProverId] ?? null;
                $mappedJaugeId = $instMap[$legacyJaugeId] ?? null;

                if (! $mappedProverId || ! $mappedJaugeId) {
                    $this->warn("Skipping verification ID {$v['id']} ({$v['reference_number']}): Missing instrument mapping.");

                    continue;
                }

                $verifRecord = ProverVerification::updateOrCreate(
                    ['id' => $v['id']],
                    [
                        'calibration_date' => $v['calibration_date'],
                        'reference_number' => $v['reference_number'],
                        'reference_temperature' => (float) ($v['reference_temperature'] ?? 20.0),
                        'pressure_unit' => $v['pressure_unit'] ?? 'bar',
                        'remarks' => $v['remarks'] ?? null,
                        'prover_id' => $mappedProverId,
                        'jauge_id' => $mappedJaugeId,
                        'base_prover_volume' => (float) $v['base_prover_volume'],
                        'max_run_volume' => (float) $v['max_run_volume'],
                        'min_run_volume' => (float) $v['min_run_volume'],
                        'repeatability_percent' => (float) $v['repeatability_percent'],
                        'is_conforme' => (bool) $v['is_conforme'],
                        'created_at' => $v['created_at'],
                        'updated_at' => $v['updated_at'],
                    ]
                );

                // Fetch and migrate runs
                $runStmt = $legacyPdo->prepare('SELECT * FROM prover_verification_runs WHERE prover_verification_id = ?');
                $runStmt->execute([$v['id']]);
                $runs = $runStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($runs as $r) {
                    ProverVerificationRun::updateOrCreate(
                        ['id' => $r['id']],
                        [
                            'prover_verification_id' => $verifRecord->id,
                            'run_number' => (int) $r['run_number'],
                            'fill_number' => (int) ($r['fill_number'] ?? 1),
                            'scale_reading_mm' => $r['scale_reading_mm'] !== null ? (float) $r['scale_reading_mm'] : null,
                            'indicated_volume' => (float) $r['indicated_volume'],
                            'gauge_temperature' => (float) $r['gauge_temperature'],
                            'prover_temperature' => (float) $r['prover_temperature'],
                            'shaft_temperature' => $r['shaft_temperature'] !== null ? (float) $r['shaft_temperature'] : null,
                            'prover_pressure' => (float) ($r['prover_pressure'] ?? 0.0),
                            'c_tdw' => (float) $r['c_tdw'],
                            'c_tsm' => (float) $r['c_tsm'],
                            'c_tsp' => (float) $r['c_tsp'],
                            'c_psp' => (float) $r['c_psp'],
                            'c_plp' => (float) $r['c_plp'],
                            'corrected_volume' => (float) $r['corrected_volume'],
                            'created_at' => $r['created_at'],
                            'updated_at' => $r['updated_at'],
                        ]
                    );
                }

                $this->info("Successfully migrated Prover Verification {$verifRecord->reference_number} with ".count($runs).' runs.');
            }

            DB::commit();
            $this->info('Migration completed successfully!');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Migration error: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
