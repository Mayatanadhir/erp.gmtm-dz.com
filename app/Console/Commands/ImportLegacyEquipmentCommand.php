<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Equipment;
use Database\Seeders\GrandeurSeeder;
use Database\Seeders\LegacyEquipmentSeeder;
use Illuminate\Console\Command;

class ImportLegacyEquipmentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'equipment:import-legacy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import and synchronize legacy equipment records and specifications from app.gmtm-dz.com';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Step 1/2: Seeding standard physical quantities (grandeurs)...');
        $this->call('db:seed', ['--class' => GrandeurSeeder::class]);

        $this->info('Step 2/2: Migrating legacy equipment records, optimizing images to WebP, and syncing specifications...');
        $this->call('db:seed', ['--class' => LegacyEquipmentSeeder::class]);

        $equipment = Equipment::with('specifications.grandeur')->orderBy('id')->get();

        $rows = $equipment->map(fn (Equipment $e) => [
            'ID' => $e->id,
            'Code' => $e->internal_code ?? '—',
            'Full Name' => mb_strimwidth($e->full_name, 0, 30, '...'),
            'Category' => $e->category->label(),
            'Package' => $e->package->label(),
            'Status' => $e->status->label(),
            'Calib?' => $e->requires_calibration ? 'Yes' : 'No',
            'Specs' => $e->specifications->count(),
            'Image' => $e->image_path ? '✓ WebP' : '—',
            'Cert' => $e->certificate_path ? '✓ PDF' : '—',
        ])->toArray();

        $this->table(
            ['ID', 'Code', 'Full Name', 'Category', 'Package', 'Status', 'Calib?', 'Specs', 'Image', 'Cert'],
            $rows
        );

        $this->info("Successfully imported and modernized {$equipment->count()} legacy equipment records.");

        return self::SUCCESS;
    }
}
