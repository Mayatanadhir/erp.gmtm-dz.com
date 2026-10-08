<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Instrument;
use Database\Seeders\LegacyInstrumentSeeder;
use Illuminate\Console\Command;

class ImportLegacyInstrumentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instruments:import-legacy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import and synchronize legacy measuring instruments and specifications from app.gmtm-dz.com';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting legacy measuring instruments modernization & import...');

        $seeder = new LegacyInstrumentSeeder;
        $seeder->run();

        $instruments = Instrument::with(['site', 'specifications.grandeur'])->orderBy('id')->get();

        $rows = $instruments->map(fn (Instrument $inst) => [
            'ID' => $inst->id,
            'Tag' => $inst->tag_number,
            'S/N' => $inst->serial_number,
            'Type' => $inst->instrument_type->label(),
            'Site' => $inst->site->short_name ?? $inst->site->full_name ?? '—',
            'Status' => $inst->status->label(),
            'Specs' => $inst->specifications->count(),
            'Image' => $inst->image_path ? '✓ WebP' : '—',
        ])->toArray();

        $this->table(
            ['ID', 'Tag', 'S/N', 'Type', 'Site', 'Status', 'Specs', 'Image'],
            $rows
        );

        $this->info("Successfully imported and modernized {$instruments->count()} legacy measuring instruments.");

        return self::SUCCESS;
    }
}
