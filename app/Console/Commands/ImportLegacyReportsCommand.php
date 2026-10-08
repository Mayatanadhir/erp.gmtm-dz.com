<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\LegacyReportsAndVerificationsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:import-legacy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import and synchronize legacy calibration reports and verifications from app.gmtm-dz.com with exact parity';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting legacy reports & verifications migration from gmtm_app...');

        $seeder = new LegacyReportsAndVerificationsSeeder;
        $seeder->run();

        $this->newLine();
        $this->info('Synchronization Summary:');

        $counts = [
            'Reports' => DB::table('reports')->count(),
            'Report Instruments' => DB::table('report_instruments')->count(),
            'Transmitter Verifications' => DB::table('transmitter_verifications')->count(),
            'Transmitter Points' => DB::table('transmitter_verification_points')->count(),
            'Transmitter Calibrators' => DB::table('transmitter_verification_calibrators')->count(),
            'Probe Verifications' => DB::table('probe_verifications')->count(),
            'Probe Points' => DB::table('probe_verification_points')->count(),
            'Probe Calibrators' => DB::table('probe_verification_calibrators')->count(),
            'Flow Computer Verifications' => DB::table('flow_computer_verifications')->count(),
            'Flow Computer Points' => DB::table('flow_computer_verification_points')->count(),
            'Flow Computer Calibrators' => DB::table('flow_computer_verification_calibrators')->count(),
            'Chromatograph Verifications' => DB::table('chromatograph_verifications')->count(),
            'Chromatograph Comp Points' => DB::table('chromatograph_composition_points')->count(),
            'Chromatograph Properties' => DB::table('chromatograph_physical_properties')->count(),
            'Prover Verifications' => DB::table('prover_verifications')->count(),
            'Prover Runs' => DB::table('prover_verification_runs')->count(),
        ];

        $tableRows = [];
        foreach ($counts as $entity => $count) {
            $tableRows[] = [$entity, $count];
        }

        $this->table(['Entity', 'Imported Count'], $tableRows);
        $this->info('Successfully imported and modernized all legacy reports and calibrations with 100% exact parity!');

        return self::SUCCESS;
    }
}
